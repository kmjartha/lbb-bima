<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/elective_helpers.php';

$user = require_view('electives');
$pdo = db();
$sc = active_scope();
$yearId = (int)$sc['year_id'];
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);
$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$subjectCategories = $pdo->prepare("SELECT id, nama FROM subject_categories WHERE academic_year_id = :y ORDER BY nama");
$subjectCategories->execute(['y' => $yearId]);
$subjectCategories = $subjectCategories->fetchAll();
$categoryMap = [];
foreach ($subjectCategories as $cat) {
    $categoryMap[(int)$cat['id']] = $cat['nama'];
}

// --- FITUR EXPORT MAPEL PILIHAN KE XLSX ---
if (($_GET['action'] ?? '') === 'export_xlsx') {
    $allElectives = electives_for_year($yearId);
    
    if (empty($allElectives)) {
        flash('warning', 'Tidak ada data untuk diexport.');
        redirect('admin/electives.php');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Mapel_Pilihan_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    
    fputcsv($output, ['Kode', 'Nama', 'Kategori', 'Jenjang', 'Rombel', 'Jumlah Opsi'], ',');
    
    foreach ($allElectives as $row) {
        $rowRombelsData = elective_rombels_for((int)$row['id']);
        $rombelText = implode('; ', array_map(function($rb) {
            return $rb['jenjang'] . ' ' . $rb['tingkat'] . ' - ' . $rb['nama'];
        }, $rowRombelsData));
        
        $classCount = count(elective_classes((int)$row['id']));
        
        fputcsv($output, [
            $row['kode'],
            $row['nama'],
            $categoryMap[(int)$row['category_id']] ?? '-',
            $row['jenjang'],
            $rombelText,
            $classCount
        ], ',');
    }
    
    fclose($output);
    exit;
}

// --- FITUR DOWNLOAD TEMPLATE IMPORT ---
if (($_GET['action'] ?? '') === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Template_Import_Mapel_Pilihan.csv');
    
    $output = fopen('php://output', 'w');
    // Tambahkan BOM agar bisa dibaca UTF-8 dengan baik di Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    // Header format template
    fputcsv($output, [
        'Kode Mapel', 
        'Nama Mapel', 
        'Jenjang (TK/SD/SMP/SMA)', 
        'ID Kategori (Lihat di Master Kategori)', 
        'ID Rombel (Pisahkan dengan koma)', 
        'Kode Opsi (Pisahkan dengan koma)', 
        'Nama Opsi (Pisahkan dengan koma)', 
        'Kapasitas Opsi (Pisahkan dengan koma)'
    ], ',');
    
    // Contoh Data
    fputcsv($output, [
        'MPL-IT', 
        'Pilihan IT Terpadu', 
        'SMA', 
        '1', 
        '12,13,14', 
        'IT-RPL,IT-TKJ', 
        'Rekayasa Perangkat Lunak,Teknik Komputer Jaringan', 
        '30,30'
    ], ',');
    
    fclose($output);
    exit;
}

// Load current year rombels for assignment
$allRombels = $pdo->prepare(
    "SELECT id, jenjang, tingkat, nama
     FROM rombel
     WHERE academic_year_id = :y AND deleted_at IS NULL
     ORDER BY FIELD(jenjang,'TK','SD','SMP','SMA'), tingkat, nama"
);
$allRombels->execute(['y' => $yearId]);
$allRombels = $allRombels->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        require_edit('electives');
        $op = (string)($_POST['op'] ?? '');

        // --- FITUR IMPORT CSV ---
        if ($op === 'import') {
            if (!isset($_FILES['file_import']) || $_FILES['file_import']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Gagal mengupload file.');
            }
            $ext = strtolower(pathinfo($_FILES['file_import']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                throw new RuntimeException('Hanya mendukung file format CSV.');
            }

            $file = fopen($_FILES['file_import']['tmp_name'], 'r');
            $bom = fread($file, 3); // Skip BOM
            if ($bom !== "\xEF\xBB\xBF") rewind($file);

            $header = fgetcsv($file, 1000, ',');
            if (!$header) throw new RuntimeException('Format CSV tidak valid.');

            $pdo->beginTransaction();
            $rowNum = 1;
            $imported = 0;

            while (($data = fgetcsv($file, 1000, ',')) !== false) {
                $rowNum++;
                if (count($data) < 8) continue; // Skip baris tidak lengkap

                $kode = trim((string)$data[0]);
                $nama = trim((string)$data[1]);
                $jenjang = trim((string)$data[2]);
                $categoryId = (int)trim((string)$data[3]);
                
                $rombelIdsRaw = array_filter(array_map('trim', explode(',', $data[4])), 'strlen');
                $opsiKodes = array_filter(array_map('trim', explode(',', $data[5])), 'strlen');
                $opsiNamas = array_map('trim', explode(',', $data[6]));
                $opsiKaps = array_map('trim', explode(',', $data[7]));

                // Lewati baris kosong / contoh
                if ($kode === '' || $nama === '' || stripos($kode, 'Kode Mapel') !== false) continue; 

                if (!in_array($jenjang, ['TK','SD','SMP','SMA'], true)) {
                    throw new RuntimeException("Jenjang invalid pada baris $rowNum.");
                }

                // Cek kategori
                $stmt = $pdo->prepare("SELECT 1 FROM subject_categories WHERE id = :id AND academic_year_id = :y");
                $stmt->execute(['id' => $categoryId, 'y' => $yearId]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException("Kategori ID '$categoryId' tidak ditemukan pada baris $rowNum.");
                }

                // Validasi Rombel
                $rombelIds = [];
                foreach ($rombelIdsRaw as $rId) {
                    if ((int)$rId > 0) $rombelIds[] = (int)$rId;
                }
                if (!$rombelIds) {
                    throw new RuntimeException("Minimal 1 ID rombel valid diperlukan pada baris $rowNum.");
                }

                $in = implode(',', $rombelIds);
                $count = (int)$pdo->query(
                    "SELECT COUNT(*) FROM rombel WHERE id IN ($in) AND academic_year_id = $yearId AND jenjang = " . $pdo->quote($jenjang) . " AND deleted_at IS NULL"
                )->fetchColumn();
                
                if ($count !== count($rombelIds)) {
                    throw new RuntimeException("ID Rombel ada yang tidak valid atau beda jenjang pada baris $rowNum.");
                }

                if (!$opsiKodes) {
                    throw new RuntimeException("Minimal 1 kode opsi mapel diperlukan pada baris $rowNum.");
                }

                // Cek Mapel eksisting by Kode
                $stmt = $pdo->prepare("SELECT id FROM electives WHERE kode = :k AND academic_year_id = :y AND deleted_at IS NULL LIMIT 1");
                $stmt->execute(['k' => $kode, 'y' => $yearId]);
                $existingId = $stmt->fetchColumn();

                if ($existingId) {
                    $id = (int)$existingId;
                    $stmt = $pdo->prepare("UPDATE electives SET nama = :n, jenjang = :j, category_id = :c WHERE id = :id");
                    $stmt->execute(['n' => $nama, 'j' => $jenjang, 'c' => $categoryId, 'id' => $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO electives (kode, nama, jenjang, category_id, academic_year_id) VALUES (:k, :n, :j, :c, :y)");
                    $stmt->execute(['k' => $kode, 'n' => $nama, 'j' => $jenjang, 'c' => $categoryId, 'y' => $yearId]);
                    $id = (int)$pdo->lastInsertId();
                }

                // Sync Rombel
                $pdo->prepare("DELETE FROM elective_rombels WHERE elective_id = :e")->execute(['e' => $id]);
                $insertRombel = $pdo->prepare("INSERT INTO elective_rombels (elective_id, rombel_id) VALUES (:e, :r)");
                foreach ($rombelIds as $rid) {
                    $insertRombel->execute(['e' => $id, 'r' => $rid]);
                }

                // Sync Options (Sub-Classes)
                $existingClassIds = [];
                $stmt = $pdo->prepare(
                    "SELECT ec.id, s.kode AS subject_kode
                     FROM elective_classes ec
                     LEFT JOIN subjects s ON s.id = ec.subject_id
                     WHERE ec.elective_id = :e AND ec.deleted_at IS NULL"
                );
                $stmt->execute(['e' => $id]);
                $dbClasses = $stmt->fetchAll();
                
                $mapDbClasses = [];
                foreach ($dbClasses as $dbc) {
                    $mapDbClasses[(string)($dbc['subject_kode'] ?? '')] = (int)$dbc['id'];
                    $existingClassIds[] = (int)$dbc['id'];
                }

                $usedIds = [];
                foreach ($opsiKodes as $idx => $optKode) {
                    $optNama = !empty($opsiNamas[$idx]) ? $opsiNamas[$idx] : $optKode;
                    $optKap = isset($opsiKaps[$idx]) ? max(0, (int)$opsiKaps[$idx]) : 0;

                    if (isset($mapDbClasses[$optKode])) {
                        $classId = $mapDbClasses[$optKode];
                        $update = $pdo->prepare("UPDATE elective_classes SET nama = :n, kapasitas = :k WHERE id = :id");
                        $update->execute(['n' => $optNama, 'k' => $optKap, 'id' => $classId]);
                    } else {
                        $insert = $pdo->prepare("INSERT INTO elective_classes (elective_id, nama, kapasitas) VALUES (:e, :n, :k)");
                        $insert->execute(['e' => $id, 'n' => $optNama, 'k' => $optKap]);
                        $classId = (int)$pdo->lastInsertId();
                    }
                    $usedIds[] = $classId;
                    
                    // Sync subject into the system (menggunakan KKM default)
                    elective_class_sync_subject($classId, $optNama, $yearId, $jenjang, $categoryId, [], [], $optKode);
                }

                // Cleanup unused options
                if ($existingClassIds) {
                    $toDelete = array_diff($existingClassIds, $usedIds);
                    if ($toDelete) {
                        $pdo->prepare("UPDATE elective_classes SET deleted_at = NOW() WHERE id IN (" . implode(',', $toDelete) . ")")->execute();
                        foreach ($toDelete as $removedClassId) {
                            elective_class_archive_subject((int)$removedClassId);
                        }
                    }
                }
                
                $imported++;
            }
            
            fclose($file);
            $pdo->commit();
            audit('import', "Imported $imported mapel pilihan");
            flash('success', "Berhasil memproses $imported baris data dari CSV.");
            redirect('admin/electives.php');
        }

        if ($op === 'save') {
            $id = int_or_null($_POST['id'] ?? null);
            $kode = req_str($_POST, 'kode', 20);
            $nama = req_str($_POST, 'nama', 120);
            $jenjang = req_str($_POST, 'jenjang', 4);
            $categoryId = int_or_null($_POST['category_id'] ?? null);
            if (!in_array($jenjang, ['TK','SD','SMP','SMA'], true)) {
                throw new RuntimeException('Jenjang invalid.');
            }
            if (!$categoryId) {
                throw new RuntimeException('Pilih kategori mapel.');
            }
            $stmt = $pdo->prepare("SELECT 1 FROM subject_categories WHERE id = :id AND academic_year_id = :y");
            $stmt->execute(['id' => $categoryId, 'y' => $yearId]);
            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('Kategori mapel invalid.');
            }

            $rombelIds = array_filter(array_map('intval', (array)($_POST['rombel_ids'] ?? [])));
            if (!$rombelIds) {
                throw new RuntimeException('Pilih minimal 1 rombel untuk mapel pilihan ini.');
            }

            $kkmDefaultRaw = $_POST['kkm_default'] ?? null;
            $kkmDefault = null;
            if ($kkmDefaultRaw !== null && $kkmDefaultRaw !== '') {
                $kkmDefault = (float)$kkmDefaultRaw;
                if ($kkmDefault < 0 || $kkmDefault > 100) {
                    throw new RuntimeException('KKM default harus antara 0-100.');
                }
            }
            $kkmDefaults = [];
            if ($jenjang !== 'TK' && $kkmDefault !== null) {
                $kkmDefaults[$jenjang] = $kkmDefault;
            }
            $kkmOverrides = [];
            $rawOverrides = $_POST['kkm_tingkat'] ?? [];
            if (is_array($rawOverrides)) {
                foreach ($rawOverrides as $t => $v) {
                    $t = (int)$t;
                    if ($t < 1 || $t > 12) continue;
                    if ($v === '' || $v === null) continue;
                    $fv = (float)$v;
                    if ($fv < 0 || $fv > 100) {
                        throw new RuntimeException('KKM kelas ' . $t . ' harus antara 0-100.');
                    }
                    $kkmOverrides[$t] = $fv;
                }
            }

            $classNames = $_POST['classes']['name'] ?? [];
            $classCodes = $_POST['classes']['kode'] ?? [];
            $classCaps = $_POST['classes']['kapasitas'] ?? [];
            $classIds = $_POST['classes']['id'] ?? [];
            if (!is_array($classNames) || !is_array($classCodes) || !is_array($classCaps) || !is_array($classIds)) {
                throw new RuntimeException('Format data opsi pilihan tidak valid.');
            }

            $options = [];
            foreach ($classNames as $index => $name) {
                $name = trim((string)$name);
                if ($name === '') {
                    continue;
                }
                $optionKode = trim((string)($classCodes[$index] ?? ''));
                if ($optionKode === '') {
                    throw new RuntimeException('Kode opsi mapel pilihan wajib diisi.');
                }
                $kapasitas = max(0, intval($classCaps[$index] ?? 0));
                $cid = intval($classIds[$index] ?? 0);
                $options[] = ['id' => $cid, 'nama' => $name, 'kode' => $optionKode, 'kapasitas' => $kapasitas];
            }
            if (!$options) {
                throw new RuntimeException('Masukkan minimal 1 opsi mapel pilihan.');
            }

            // Validate rombel IDs belong to the same year and jenjang.
            $in = implode(',', array_map('intval', $rombelIds));
            $count = (int)$pdo->query(
                "SELECT COUNT(*) FROM rombel WHERE id IN ($in) AND academic_year_id = $yearId AND jenjang = " . $pdo->quote($jenjang) . " AND deleted_at IS NULL"
            )->fetchColumn();
            if ($count !== count($rombelIds)) {
                throw new RuntimeException('Salah satu rombel tidak valid untuk jenjang ini.');
            }

            $pdo->beginTransaction();
            if ($id) {
                $stmt = $pdo->prepare("UPDATE electives SET kode = :k, nama = :n, jenjang = :j, category_id = :c WHERE id = :id");
                $stmt->execute(['k' => $kode, 'n' => $nama, 'j' => $jenjang, 'c' => $categoryId, 'id' => $id]);
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO electives (kode, nama, jenjang, category_id, academic_year_id) VALUES (:k, :n, :j, :c, :y)"
                );
                $stmt->execute(['k' => $kode, 'n' => $nama, 'j' => $jenjang, 'c' => $categoryId, 'y' => $yearId]);
                $id = (int)$pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("DELETE FROM elective_rombels WHERE elective_id = :e");
            $stmt->execute(['e' => $id]);
            $insertRombel = $pdo->prepare("INSERT INTO elective_rombels (elective_id, rombel_id) VALUES (:e, :r)");
            foreach ($rombelIds as $rid) {
                $insertRombel->execute(['e' => $id, 'r' => $rid]);
            }

            $existingClassIds = [];
            $stmt = $pdo->prepare("SELECT id FROM elective_classes WHERE elective_id = :e AND deleted_at IS NULL");
            $stmt->execute(['e' => $id]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $existingId) {
                $existingClassIds[] = (int)$existingId;
            }

            $usedIds = [];
            foreach ($options as $option) {
                if ($option['id'] > 0 && in_array($option['id'], $existingClassIds, true)) {
                    $update = $pdo->prepare(
                        "UPDATE elective_classes SET nama = :n, kapasitas = :k, deleted_at = NULL WHERE id = :id AND elective_id = :e"
                    );
                    $update->execute(['n' => $option['nama'], 'k' => $option['kapasitas'], 'id' => $option['id'], 'e' => $id]);
                    $classId = $option['id'];
                } else {
                    $insert = $pdo->prepare(
                        "INSERT INTO elective_classes (elective_id, nama, kapasitas) VALUES (:e, :n, :k)"
                    );
                    $insert->execute(['e' => $id, 'n' => $option['nama'], 'k' => $option['kapasitas']]);
                    $classId = (int)$pdo->lastInsertId();
                }
                $usedIds[] = $classId;
                // Treat this option exactly like a regular mata pelajaran:
                elective_class_sync_subject($classId, $option['nama'], $yearId, $jenjang, $categoryId, $kkmDefaults, $kkmOverrides, $option['kode']);
            }

            if ($existingClassIds) {
                $toDelete = array_diff($existingClassIds, $usedIds);
                if ($toDelete) {
                    $pdo->prepare(
                        "UPDATE elective_classes SET deleted_at = NOW() WHERE id IN (" . implode(',', array_map('intval', $toDelete)) . ")"
                    )->execute();
                    foreach ($toDelete as $removedClassId) {
                        elective_class_archive_subject((int)$removedClassId);
                    }
                }
            }

            $pdo->commit();
            audit('save', 'elective:' . $id);
            flash('success', 'Mapel pilihan disimpan.');
            redirect('admin/electives.php');
        }

        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->beginTransaction();
            $classIds = $pdo->prepare("SELECT id FROM elective_classes WHERE elective_id = :e AND deleted_at IS NULL");
            $classIds->execute(['e' => $id]);
            foreach ($classIds->fetchAll(PDO::FETCH_COLUMN) as $classId) {
                elective_class_archive_subject((int)$classId);
            }
            $pdo->prepare("UPDATE electives SET deleted_at = NOW() WHERE id = :id")->execute(['id' => $id]);
            $pdo->commit();
            audit('delete', 'elective:' . $id);
            flash('success', 'Mapel pilihan dihapus.');
            redirect('admin/electives.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

$rows = electives_for_year($yearId);
$allRowsCount = count($rows);
$rowRombels = [];

foreach ($rows as $row) {
    $rowRombels[(int)$row['id']] = elective_rombels_for((int)$row['id']);
}

if ($search !== '') {
    $needle = function_exists('mb_strtolower') ? mb_strtolower($search, 'UTF-8') : strtolower($search);

    $rows = array_values(array_filter($rows, function ($row) use ($needle, $categoryMap, $rowRombels) {
        $parts = [
            $row['kode'] ?? '',
            $row['nama'] ?? '',
            $row['jenjang'] ?? '',
            $categoryMap[(int)($row['category_id'] ?? 0)] ?? '',
        ];

        foreach ($rowRombels[(int)$row['id']] ?? [] as $rb) {
            $parts[] = ($rb['jenjang'] ?? '') . ' ' . ($rb['tingkat'] ?? '') . ' ' . ($rb['nama'] ?? '');
        }

        $haystack = implode(' ', $parts);
        $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);

        return strpos($haystack, $needle) !== false;
    }));
}

$totalRows = count($rows);
$totalPages = max(1, (int)ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$rowsPage = array_slice($rows, $offset, $perPage);

$paginationUrl = static function (int $targetPage) use ($search): string {
    $params = ['page' => max(1, $targetPage)];

    if ($search !== '') {
        $params['q'] = $search;
    }

    return url('admin/electives.php') . '?' . http_build_query($params);
};

$edit = null;
$editRombels = [];
$editClasses = [];
$editKkm = [];
$editDefaultKkm = 70;
if ($editId) {
    $edit = elective_by_id($editId, $yearId);
    if ($edit) {
        $editRombels = elective_rombel_ids($editId);
        $editClasses = elective_classes($editId);
        foreach ($editClasses as $class) {
            if (!empty($class['subject_id'])) {
                $editKkm = subject_kkm_map((int)$class['subject_id']);
                break;
            }
        }
    }
}
if ($editKkm) {
    $editDefaultKkm = round(array_sum($editKkm) / count($editKkm), 2);
}

$page_title = 'Mapel Pilihan';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="row">
  <div class="card" style="flex: 1 1 320px; min-width: 320px">
    <div class="card-header between" style="align-items:flex-start; gap:.75rem;">
      <div>
        <h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Mapel Pilihan</h3>
        <div class="text-xs text-muted">Buat mapel pilihan dengan opsi sub-kelas dan rombel yang digabung dalam jenjang yang sama.</div>
      </div>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/electives.php')) ?>">Batal</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="row">
          <div class="field"><label class="label">Kode *</label><input class="input" name="kode" required value="<?= esc($edit['kode'] ?? '') ?>"></div>
          <div class="field" style="flex: 2"><label class="label">Nama *</label><input class="input" name="nama" required value="<?= esc($edit['nama'] ?? '') ?>"></div>
        </div>
        <div class="field">
          <label class="label">Jenjang *</label>
          <select id="elective-jenjang" class="select" name="jenjang" onchange="filterRombels(); updateKkmUi();" required>
            <option value="">- Pilih jenjang -</option>
            <?php foreach (['TK','SD','SMP','SMA'] as $j): ?>
              <option value="<?= $j ?>" <?= ($edit['jenjang'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label">Kategori Mapel *</label>
          <select class="select" name="category_id" required>
            <option value="">- Pilih kategori -</option>
            <?php foreach ($subjectCategories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= ((int)($edit['category_id'] ?? 0)) === (int)$cat['id'] ? 'selected' : '' ?>><?= esc($cat['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label">KKM</label>
          <p class="text-muted text-sm" style="margin:0 0 .5rem;">Atur KKM default untuk opsi mapel pilihan ini. Nilai ini akan diterapkan ke setiap opsi mapel pilihan yang dibuat dari form ini.</p>
          <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <label class="text-sm text-muted" id="elective-kkm-label" style="min-width:100px;">KKM default</label>
            <input type="number" class="input" id="elective-kkm-default" name="kkm_default" min="0" max="100" step="0.01" value="<?= esc((string)$editDefaultKkm) ?>">
          </div>
          <button type="button" id="elective-kkm-advanced-toggle" class="btn btn-secondary btn-sm" style="margin-top:.5rem;">Sesuaikan KKM per tingkat kelas</button>
          <div id="elective-kkm-advanced-panel" style="display:none; margin-top:.5rem; padding:.75rem; border:1px solid var(--border); border-radius:10px; background:rgba(0,0,0,.02);">
            <div id="elective-kkm-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap:8px;"></div>
          </div>
        </div>
        <div class="field">
          <label class="label">Rombel yang digabung *</label>
          <div id="rombel-options" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:.75rem; max-height:320px; overflow:auto; padding:1rem; border:1px solid var(--border); border-radius:var(--r-md); background:var(--surface);">
            <?php foreach ($allRombels as $r): ?>
              <?php $checked = in_array((int)$r['id'], $editRombels, true); ?>
              <label class="checkbox-row" data-jenjang="<?= esc($r['jenjang']) ?>" style="width:100%; padding:.75rem 1rem; border:1px solid var(--border); border-radius:var(--r-sm); background:var(--surface);">
                <input type="checkbox" name="rombel_ids[]" value="<?= (int)$r['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                <span><?= esc($r['jenjang'] . ' ' . $r['tingkat'] . ' - ' . $r['nama']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="text-xs text-muted">Pilih rombel dari jenjang yang sama. Siswa dari rombel terpilih dapat memilih salah satu opsi mapel.</div>
        </div>
        <div class="field">
          <label class="label">Opsi Mapel Pilihan *</label>
          <div id="class-options">
            <?php if ($editClasses): ?>
              <?php foreach ($editClasses as $class): ?>
                <div class="row class-row" style="gap:.5rem; align-items:flex-end; margin-bottom:.5rem">
                  <input type="hidden" name="classes[id][]" value="<?= (int)$class['id'] ?>">
                  <div class="field" style="flex:1; min-width:100px"><label class="label">Kode</label><input class="input" name="classes[kode][]" required value="<?= esc($class['subject_kode'] ?? '') ?>"></div>
                  <div class="field" style="flex:2"><label class="label">Nama</label><input class="input" name="classes[name][]" required value="<?= esc($class['nama']) ?>"></div>
                  <div class="field" style="flex:1; min-width:120px"><label class="label">Kapasitas</label><input class="input" type="number" min="0" name="classes[kapasitas][]" value="<?= (int)$class['kapasitas'] ?>"></div>
                  <button type="button" class="btn btn-ghost btn-sm" onclick="removeClassRow(this)">Hapus</button>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="row class-row" style="gap:.5rem; align-items:flex-end; margin-bottom:.5rem">
                <input type="hidden" name="classes[id][]" value="0">
                <div class="field" style="flex:1; min-width:100px"><label class="label">Kode</label><input class="input" name="classes[kode][]" required></div>
                <div class="field" style="flex:2"><label class="label">Nama</label><input class="input" name="classes[name][]" required></div>
                <div class="field" style="flex:1; min-width:120px"><label class="label">Kapasitas</label><input class="input" type="number" min="0" name="classes[kapasitas][]" value="0"></div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="removeClassRow(this)">Hapus</button>
              </div>
            <?php endif; ?>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="addClassRow()">Tambah Opsi</button>
        </div>
        <button class="btn btn-primary" type="submit">Simpan Mapel Pilihan</button>
      </form>
    </div>
  </div>

  <div class="card" style="flex: 2 1 380px; min-width: 380px">
    <div class="card-header" style="justify-content: space-between; align-items: center; flex-wrap:wrap; gap:1rem;">
      <div>
        <h3 class="card-title">
          Daftar Mapel Pilihan (<?= (int)$totalRows ?><?= $search !== '' ? ' dari ' . (int)$allRowsCount : '' ?>)
        </h3>
        <div class="text-xs text-muted">Kelola mapel pilihan dan lihat rombel yang sudah digabung.</div>
      </div>
      
      <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; justify-content:flex-end;">
        <a href="?action=download_template" class="btn btn-secondary btn-sm">Template Import CSV</a>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('import-panel').style.display='block'">Import CSV</button>
        <a href="?action=export_xlsx" class="btn btn-secondary btn-sm" target="_blank">Export Data</a>
      </div>
    </div>

    <div class="card-body" id="import-panel" style="display:none; padding-bottom: 0;">
      <div style="padding: 1rem; border: 1px dashed var(--border); border-radius: var(--r-md); background: rgba(0,0,0,.02);">
        <form method="post" enctype="multipart/form-data" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
          <?= csrf_field() ?>
          <input type="hidden" name="op" value="import">
          <div class="field" style="flex:1; min-width: 200px;">
            <label class="label">Pilih File CSV (.csv)</label>
            <input type="file" name="file_import" accept=".csv" class="input" required style="background:#fff;">
          </div>
          <div style="display:flex; gap:.5rem;">
            <button type="submit" class="btn btn-primary">Proses Import</button>
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('import-panel').style.display='none'">Batal</button>
          </div>
        </form>
        <div class="text-xs text-muted" style="margin-top:.5rem;">Gunakan pemisah koma (,). Pastikan Anda menggunakan Template Import agar format kolom sesuai.</div>
      </div>
    </div>

    <div class="card-body" style="padding-bottom:0;">
      <form method="get" class="row" style="display:grid; grid-template-columns: 1fr auto auto; gap:.5rem; align-items:flex-end;">
        <div class="field" style="min-width:220px; margin:0;">
          <label class="label">Search</label>
          <input class="input" type="search" name="q" value="<?= esc($search) ?>" placeholder="Cari kode, nama, kategori, jenjang, atau rombel">
        </div>
        <button class="btn btn-primary" type="submit" style="align-self:flex-end;">Cari</button>
        <?php if ($search !== ''): ?>
          <a class="btn btn-ghost" href="<?= esc(url('admin/electives.php')) ?>" style="align-self:flex-end;">Reset</a>
        <?php endif; ?>
      </form>

      <div class="text-xs text-muted" style="margin-top:.5rem;">
        Menampilkan <?= $totalRows ? (int)($offset + 1) : 0 ?>-<?= (int)min($offset + $perPage, $totalRows) ?> dari <?= (int)$totalRows ?> data.
      </div>
    </div>

    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jenjang</th><th>Rombel</th><th>Opsi</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rowsPage): ?><tr><td colspan="7"><div class="empty">Belum ada data.</div></td></tr><?php endif; ?>
        <?php foreach ($rowsPage as $r): ?>
          <?php $rombelList = $rowRombels[(int)$r['id']] ?? []; ?>
          <tr>
            <td><strong><?= esc($r['kode']) ?></strong></td>
            <td><?= esc($r['nama']) ?></td>
            <td><?= esc($categoryMap[(int)$r['category_id']] ?? '-') ?></td>
            <td><span class="badge badge-primary"><?= esc($r['jenjang']) ?></span></td>
            <td>
              <?php foreach ($rombelList as $rb): ?>
                <div><?= esc($rb['jenjang'] . ' ' . $rb['tingkat'] . ' - ' . $rb['nama']) ?></div>
              <?php endforeach; ?>
            </td>
            <td><?= count(elective_classes((int)$r['id'])) ?> opsi</td>
            <td style="text-align:right; white-space:nowrap">
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Hapus mapel pilihan <?= esc($r['nama']) ?>?">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="card-body" style="display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap;">
        <div class="text-xs text-muted">
          Halaman <?= (int)$page ?> dari <?= (int)$totalPages ?>
        </div>

        <div style="display:flex; gap:.35rem; flex-wrap:wrap;">
          <?php if ($page > 1): ?>
            <a class="btn btn-secondary btn-sm" href="<?= esc($paginationUrl($page - 1)) ?>">Sebelumnya</a>
          <?php endif; ?>

          <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>" href="<?= esc($paginationUrl($p)) ?>">
              <?= (int)$p ?>
            </a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a class="btn btn-secondary btn-sm" href="<?= esc($paginationUrl($page + 1)) ?>">Selanjutnya</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  var existingOverrides = <?= $editKkm ? json_encode($editKkm, JSON_NUMERIC_CHECK | JSON_FORCE_OBJECT) : '{}' ?>;

  function filterRombels() {
    const jenjang = document.getElementById('elective-jenjang').value;
    document.querySelectorAll('#rombel-options [data-jenjang]').forEach(el => {
      el.style.display = jenjang && el.getAttribute('data-jenjang') !== jenjang ? 'none' : 'inline-flex';
    });
  }

  function getElectiveJenjang() {
    return document.getElementById('elective-jenjang').value;
  }

  function updateKkmUi() {
    const jenjang = getElectiveJenjang();
    const label = document.getElementById('elective-kkm-label');
    const input = document.getElementById('elective-kkm-default');
    const toggleBtn = document.getElementById('elective-kkm-advanced-toggle');
    const panel = document.getElementById('elective-kkm-advanced-panel');
    const grid = document.getElementById('elective-kkm-grid');
    if (!label || !input || !toggleBtn || !panel || !grid) return;

    if (!jenjang || jenjang === 'TK') {
      label.textContent = 'KKM default';
      input.value = '';
      input.disabled = true;
      toggleBtn.style.display = 'none';
      panel.style.display = 'none';
      grid.innerHTML = '';
      return;
    }

    label.textContent = 'KKM default (' + jenjang + ')';
    input.disabled = false;
    toggleBtn.style.display = 'inline-block';
    if (panel.style.display !== 'none') {
      renderElectiveKkmGrid(jenjang);
    }
  }

  function renderElectiveKkmGrid(jenjang) {
    const grid = document.getElementById('elective-kkm-grid');
    if (!grid) return;

    const levels = jenjang === 'SD' ? [1,2,3,4,5,6] : jenjang === 'SMP' ? [7,8,9] : [10,11,12];
    const typed = {};
    grid.querySelectorAll('.elective-tingkat-kkm-input').forEach(function (inp) {
      typed[inp.dataset.tingkat] = inp.value;
    });

    grid.innerHTML = '';
    levels.forEach(function (t) {
      const val = typed[t] !== undefined ? typed[t] : (existingOverrides[t] !== undefined ? existingOverrides[t] : (parseFloat(document.getElementById('elective-kkm-default').value) || 70));
      const cell = document.createElement('div');
      cell.style.cssText = 'background:rgba(0,0,0,.03); border-radius:8px; padding:6px 8px;';
      cell.innerHTML = '<div class="text-muted" style="font-size:11px; margin-bottom:3px;">Kelas ' + t + '</div>' +
        '<input type="number" class="input elective-tingkat-kkm-input" style="width:100%; padding:4px 6px;" min="0" max="100" step="0.01" ' +
        'name="kkm_tingkat[' + t + ']" data-tingkat="' + t + '" value="' + val + '">';
      grid.appendChild(cell);
    });
  }

  function removeClassRow(button) {
    const row = button.closest('.class-row');
    if (row) row.remove();
  }

  function addClassRow() {
    const container = document.getElementById('class-options');
    const row = document.createElement('div');
    row.className = 'row class-row';
    row.style = 'gap:.5rem; align-items:flex-end; margin-bottom:.5rem';
    row.innerHTML = `
      <input type="hidden" name="classes[id][]" value="0">
      <div class="field" style="flex:1; min-width:100px"><label class="label">Kode</label><input class="input" name="classes[kode][]" required></div>
      <div class="field" style="flex:2"><label class="label">Nama</label><input class="input" name="classes[name][]" required></div>
      <div class="field" style="flex:1; min-width:120px"><label class="label">Kapasitas</label><input class="input" type="number" min="0" name="classes[kapasitas][]" value="0"></div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="removeClassRow(this)">Hapus</button>
    `;
    container.appendChild(row);
  }

  document.addEventListener('DOMContentLoaded', function () {
    filterRombels();
    updateKkmUi();
  });

  document.getElementById('elective-kkm-advanced-toggle').addEventListener('click', function () {
    const panel = document.getElementById('elective-kkm-advanced-panel');
    const toggleBtn = document.getElementById('elective-kkm-advanced-toggle');
    const hidden = panel.style.display === 'none';
    panel.style.display = hidden ? 'block' : 'none';
    toggleBtn.textContent = hidden ? 'Sembunyikan KKM per tingkat kelas' : 'Sesuaikan KKM per tingkat kelas';
    if (hidden) {
      renderElectiveKkmGrid(getElectiveJenjang());
    }
  });

  document.getElementById('elective-kkm-default').addEventListener('input', function () {
    if (document.getElementById('elective-kkm-advanced-panel').style.display !== 'none') {
      renderElectiveKkmGrid(getElectiveJenjang());
    }
  });
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>