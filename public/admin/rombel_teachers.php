<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
require_once __DIR__ . '/../../includes/elective_helpers.php';
$me = require_view('rombel_teachers');
$canEdit = can_edit('rombel_teachers', $me);

$pdo = db();
$sc = active_scope();
$err = null;

$rombelId = int_or_null($_GET['rombel_id'] ?? null);
$action = $_GET['action'] ?? '';

// =========================================================================
// FITUR DOWNLOAD DATA DAN TEMPLATE
// =========================================================================
if ($action === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_mapping_guru.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['kode_mapel', 'niy_guru', 'semester']);
    fputcsv($output, ['MTK', '12345678', 'ganjil']); // Contoh baris
    fclose($output);
    exit;
}

if ($action === 'download_data' && $rombelId) {
    $stmt = $pdo->prepare("
        SELECT s.kode AS s_kode, s.nama AS s_nama, u.niy AS t_niy, u.nama AS t_nama, rst.semester
        FROM rombel_subject_teachers rst
        JOIN subjects s ON s.id=rst.subject_id AND s.academic_year_id = :y
        JOIN teachers t ON t.id=rst.teacher_id
        JOIN users u ON u.id=t.user_id
        WHERE rst.rombel_id = :r ORDER BY s.kode, rst.semester
    ");
    $stmt->execute(['r' => $rombelId, 'y' => $sc['year_id']]);
    $data = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mapping_guru_rombel_'.$rombelId.'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['kode_mapel', 'nama_mapel', 'niy_guru', 'nama_guru', 'semester']);
    foreach ($data as $row) {
        fputcsv($output, [
            $row['s_kode'], 
            $row['s_nama'], 
            $row['t_niy'], 
            $row['t_nama'], 
            $row['semester'] ?: 'Keduanya'
        ]);
    }
    fclose($output);
    exit;
}
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        if (!$canEdit) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'assign') {
            $currentRombelId = (int)($_POST['current_rombel_id'] ?? 0);
            $selectedRombelIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['rombel_ids'] ?? [])))));
            $sid = (int)($_POST['subject_id'] ?? 0);
            $tid = (int)($_POST['teacher_id'] ?? 0);
            $sem = (string)($_POST['semester'] ?? '');
            if (!in_array($sem, ['ganjil','genap',''], true)) throw new RuntimeException('Semester invalid.');
            $semVal = $sem === '' ? null : $sem;
            if (!$currentRombelId || !$selectedRombelIds || !$sid || !$tid) throw new RuntimeException('Rombel, mapel, dan guru wajib dipilih.');

            $idPlaceholders = [];
            $params = ['y' => $sc['year_id']];
            foreach ($selectedRombelIds as $i => $rid) {
                $key = 'rid_' . $i;
                $idPlaceholders[] = ':' . $key;
                $params[$key] = $rid;
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rombel WHERE id IN (" . implode(',', $idPlaceholders) . ") AND academic_year_id=:y AND deleted_at IS NULL");
            $stmt->execute($params);
            if ((int)$stmt->fetchColumn() !== count($selectedRombelIds)) throw new RuntimeException('Salah satu rombel tidak ditemukan di tahun ajaran aktif.');

            $del = $pdo->prepare("DELETE FROM rombel_subject_teachers WHERE rombel_id=:r AND subject_id=:s AND (semester <=> :sem)");
            $ins = $pdo->prepare("INSERT INTO rombel_subject_teachers (rombel_id, subject_id, teacher_id, semester) VALUES (:r,:s,:t,:sem)");
            foreach ($selectedRombelIds as $rid) {
                $del->execute(['r' => $rid, 's' => $sid, 'sem' => $semVal]);
                $ins->execute(['r' => $rid, 's' => $sid, 't' => $tid, 'sem' => $semVal]);
            }

            audit('assign_teacher', 'rombel:' . implode(',', $selectedRombelIds) . '/subject:' . $sid, ['t' => $tid, 'sem' => $semVal]);
            flash('success', 'Guru pengampu disimpan ke ' . count($selectedRombelIds) . ' rombel.');
            redirect('admin/rombel_teachers.php?rombel_id=' . $currentRombelId);
        }

        if ($op === 'unassign') {
            $id = (int)($_POST['id'] ?? 0);
            $rid = (int)($_POST['rombel_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT 1 FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
            $stmt->execute(['id'=>$rid,'y'=>$sc['year_id']]);
            if (!$stmt->fetchColumn()) throw new RuntimeException('Rombel tidak ditemukan di tahun ajaran aktif.');
            $pdo->prepare("DELETE FROM rombel_subject_teachers WHERE id=:id")->execute(['id'=>$id]);
            audit('unassign_teacher', 'rst:' . $id);
            flash('success', 'Mapping dihapus.');
            redirect('admin/rombel_teachers.php?rombel_id=' . $rid);
        }

        if ($op === 'batch_unassign') {
          $ids = array_values(array_unique(array_map('intval', (array)($_POST['ids'] ?? []))));
          $rid = (int)($_POST['rombel_id'] ?? 0);
          if (!$ids) throw new RuntimeException('Pilih minimal 1 mapping.');
          if (!$rid) throw new RuntimeException('Rombel tidak valid.');

          $stmt = $pdo->prepare("SELECT 1 FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
          $stmt->execute(['id'=>$rid,'y'=>$sc['year_id']]);
          if (!$stmt->fetchColumn()) throw new RuntimeException('Rombel tidak ditemukan di tahun ajaran aktif.');

          $place = [];
          $params = ['y' => $sc['year_id'], 'rid' => $rid];
          foreach ($ids as $i => $idv) { $k = 'id_' . $i; $place[] = ':' . $k; $params[$k] = $idv; }

          $check = $pdo->prepare("SELECT COUNT(*) FROM rombel_subject_teachers rst JOIN rombel r ON r.id = rst.rombel_id AND r.academic_year_id = :y WHERE rst.id IN (" . implode(',', $place) . ") AND rst.rombel_id = :rid");
          $check->execute($params);
          if ((int)$check->fetchColumn() !== count($ids)) throw new RuntimeException('Salah satu mapping tidak ditemukan.');

          // delete only the selected ids
          $delParams = [];
          foreach ($ids as $i => $idv) { $delParams['id_' . $i] = $idv; }
          $del = $pdo->prepare("DELETE FROM rombel_subject_teachers WHERE id IN (" . implode(',', array_map(fn($k)=>':' . $k, array_keys($delParams))) . ")");
          $del->execute($delParams);

          audit('batch_unassign', 'rombel:' . $rid, ['count' => count($ids)]);
          flash('success', count($ids) . ' mapping berhasil dihapus.');
          redirect('admin/rombel_teachers.php?rombel_id=' . $rid);
        }

        if ($op === 'batch_copy') {
          $ids = array_values(array_unique(array_map('intval', (array)($_POST['ids'] ?? []))));
          $targets = array_values(array_unique(array_map('intval', (array)($_POST['target_rombel_ids'] ?? []))));
          $rid = (int)($_POST['rombel_id'] ?? 0);
          if (!$ids) throw new RuntimeException('Pilih minimal 1 mapping untuk dicopy.');
          if (!$targets) throw new RuntimeException('Pilih minimal 1 rombel tujuan.');
          if (!$rid) throw new RuntimeException('Rombel asal tidak valid.');

          // validate origin rombel
          $stmt = $pdo->prepare("SELECT jenjang FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
          $stmt->execute(['id'=>$rid,'y'=>$sc['year_id']]);
          $originJenjang = $stmt->fetchColumn();
          if (!$originJenjang) throw new RuntimeException('Rombel asal tidak ditemukan.');

          // validate target rombels belong to same jenjang
          $place = [];
          $params = ['y' => $sc['year_id']];
          foreach ($targets as $i => $t) { $k = 't_' . $i; $place[] = ':' . $k; $params[$k] = $t; }
          $check = $pdo->prepare("SELECT COUNT(*) FROM rombel WHERE id IN (" . implode(',', $place) . ") AND academic_year_id = :y AND jenjang = :j AND deleted_at IS NULL");
          $params['j'] = $originJenjang;
          $check->execute($params);
          if ((int)$check->fetchColumn() !== count($targets)) throw new RuntimeException('Salah satu rombel tujuan tidak valid atau tidak se-jenjang.');

          // validate mapping ids belong to origin rombel
          $placeIds = [];
          $p2 = ['rid' => $rid];
          foreach ($ids as $i => $idv) { $k = 'id_' . $i; $placeIds[] = ':' . $k; $p2[$k] = $idv; }
          $chk2 = $pdo->prepare("SELECT COUNT(*) FROM rombel_subject_teachers WHERE id IN (" . implode(',', $placeIds) . ") AND rombel_id = :rid");
          $chk2->execute($p2);
          if ((int)$chk2->fetchColumn() !== count($ids)) throw new RuntimeException('Salah satu mapping tidak ditemukan di rombel asal.');

          // Fetch mapping details
          $fetch = $pdo->prepare("SELECT subject_id, teacher_id, semester FROM rombel_subject_teachers WHERE id = :id");

          $pdo->beginTransaction();
          try {
            $del = $pdo->prepare("DELETE FROM rombel_subject_teachers WHERE rombel_id = :r AND subject_id = :s AND (semester <=> :sem)");
            $ins = $pdo->prepare("INSERT INTO rombel_subject_teachers (rombel_id, subject_id, teacher_id, semester) VALUES (:r,:s,:t,:sem)");

            foreach ($ids as $idv) {
              $fetch->execute(['id' => $idv]);
              $m = $fetch->fetch();
              if (!$m) continue;
              foreach ($targets as $tr) {
                $del->execute(['r' => $tr, 's' => $m['subject_id'], 'sem' => $m['semester']]);
                $ins->execute(['r' => $tr, 's' => $m['subject_id'], 't' => $m['teacher_id'], 'sem' => $m['semester']]);
              }
            }

            $pdo->commit();
            audit('batch_copy', 'rombel:' . $rid, ['targets' => $targets, 'count' => count($ids)]);
            flash('success', 'Mapping berhasil disalin ke ' . count($targets) . ' rombel.');
            redirect('admin/rombel_teachers.php?rombel_id=' . $rid);
          } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
          }
        }

        // =========================================================================
        // FITUR IMPORT CSV
        // =========================================================================
        if ($op === 'import') {
            $rid = (int)($_POST['rombel_id'] ?? 0);
            if (!$rid) throw new RuntimeException('Rombel tidak valid.');
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Gagal upload file CSV.');
            }

            $stmt = $pdo->prepare("SELECT 1 FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
            $stmt->execute(['id'=>$rid,'y'=>$sc['year_id']]);
            if (!$stmt->fetchColumn()) throw new RuntimeException('Rombel tidak ditemukan di tahun ajaran aktif.');

            $handle = fopen($_FILES['file']['tmp_name'], 'r');
            $header = fgetcsv($handle); // Lewati header
            $imported = 0;

            $pdo->beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 2) continue; // Skip jika kolom tidak lengkap
                    
                    $kodeMapel = trim($row[0]);
                    $niyGuru = trim($row[1]);
                    $semRaw = isset($row[2]) ? strtolower(trim($row[2])) : '';

                    if ($kodeMapel === '' || $niyGuru === '') continue;

                    $semVal = null;
                    if ($semRaw === 'ganjil') $semVal = 'ganjil';
                    elseif ($semRaw === 'genap') $semVal = 'genap';

                    // Cari ID Mapel
                    $sid = $pdo->prepare("SELECT id FROM subjects WHERE kode=:k AND academic_year_id=:y AND deleted_at IS NULL LIMIT 1");
                    $sid->execute(['k'=>$kodeMapel, 'y'=>$sc['year_id']]);
                    $subjectId = $sid->fetchColumn();

                    // Cari ID Guru
                    $tid = $pdo->prepare("SELECT t.id FROM teachers t JOIN users u ON u.id=t.user_id WHERE u.niy=:n AND u.deleted_at IS NULL LIMIT 1");
                    $tid->execute(['n'=>$niyGuru]);
                    $teacherId = $tid->fetchColumn();

                    // Insert jika mapel dan guru valid
                    if ($subjectId && $teacherId) {
                        $del = $pdo->prepare("DELETE FROM rombel_subject_teachers WHERE rombel_id=:r AND subject_id=:s AND (semester <=> :sem)");
                        $del->execute(['r'=>$rid,'s'=>$subjectId,'sem'=>$semVal]);

                        $pdo->prepare("INSERT INTO rombel_subject_teachers (rombel_id, subject_id, teacher_id, semester) VALUES (:r,:s,:t,:sem)")
                            ->execute(['r'=>$rid,'s'=>$subjectId,'t'=>$teacherId,'sem'=>$semVal]);
                        $imported++;
                    }
                }
                $pdo->commit();
                audit('import_teacher_mapping', "rombel:$rid", ['count'=>$imported]);
                flash('success', "$imported data mapping berhasil diimport.");
                redirect('admin/rombel_teachers.php?rombel_id=' . $rid);
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
        // =========================================================================
        
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$rombels = $pdo->prepare(
    "SELECT id, jenjang, tingkat, nama FROM rombel
     WHERE academic_year_id = :y AND deleted_at IS NULL
     ORDER BY FIELD(jenjang,'SD','SMP','SMA'), tingkat, nama"
);
$rombels->execute(['y'=>$sc['year_id']]);
$rombels = $rombels->fetchAll();

$current = null; $assignments = []; $subjects = []; $teachers = [];
if ($rombelId) {
    $stmt = $pdo->prepare("SELECT * FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
    $stmt->execute(['id'=>$rombelId,'y'=>$sc['year_id']]); $current = $stmt->fetch();
    if ($current) {
        $s = $pdo->prepare(
            "SELECT s.id, s.kode, s.nama, e.kode AS elective_kode
             FROM subjects s
             JOIN subject_jenjang_map jm ON jm.subject_id=s.id
             LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id OR ec.subject_id = s.id
             LEFT JOIN electives e ON e.id = ec.elective_id AND e.deleted_at IS NULL
             WHERE jm.jenjang=:j AND s.deleted_at IS NULL AND s.academic_year_id = :y ORDER BY s.kode"
        );
        $s->execute(['j'=>$current['jenjang'], 'y'=>$sc['year_id']]); $subjects = $s->fetchAll();
        $teachers = $pdo->prepare(
            "SELECT t.id, u.niy, u.nama
             FROM teachers t
             JOIN users u ON u.id=t.user_id
             JOIN teacher_years ty ON ty.teacher_id = t.id AND ty.academic_year_id = :y
             WHERE u.deleted_at IS NULL AND u.is_active=1 AND u.role='guru'
             ORDER BY u.nama"
        );
        $teachers = $teachers->execute(['y' => $sc['year_id']]) ? $teachers->fetchAll() : [];
        $a = $pdo->prepare(
               "SELECT rst.*, s.kode AS s_kode, s.nama AS s_nama, e.kode AS elective_kode, u.nama AS t_nama, u.niy AS t_niy
                 FROM rombel_subject_teachers rst
                 JOIN subjects s ON s.id=rst.subject_id AND s.academic_year_id = :y
                 LEFT JOIN elective_classes ec ON (ec.id = s.elective_class_id OR ec.subject_id = s.id)
                 LEFT JOIN electives e ON e.id = ec.elective_id
                 JOIN teachers t ON t.id=rst.teacher_id
                 JOIN users u ON u.id=t.user_id
                 WHERE rst.rombel_id = :r ORDER BY s.kode, rst.semester"
        );
        $a->execute(['r'=>$rombelId,'y'=>$sc['year_id']]); $assignments = $a->fetchAll();
    }
}

$page_title = 'Guru Pengampu';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Pilih Rombel</h3>
    <span class="text-sm text-muted">TA <?= esc($sc['year']) ?></span>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="align-items:end">
      <div class="field" style="flex:1; min-width:260px"><label class="label">Rombel</label>
        <select class="select" name="rombel_id" onchange="this.form.submit()">
          <option value="">— Pilih rombel —</option>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rombelId===(int)$r['id']?'selected':'' ?>><?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if ($current): ?>
<div class="row">
  <?php if ($canEdit): ?>
  <div style="flex: 1; min-width: 320px; display: flex; flex-direction: column; gap: 1rem;">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Tambah / Ubah Mapping</h3></div>
        <div class="card-body">
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="op" value="assign">
            <input type="hidden" name="current_rombel_id" value="<?= (int)$current['id'] ?>">
            <div class="field"><label class="label">Mapel *</label>
              <input id="subject-combobox" class="input" list="subject-options" autocomplete="off"
                     placeholder="Cari mapel / kode / kode pilihan" required>
              <datalist id="subject-options">
                <?php foreach ($subjects as $s): ?>
                  <?php $label = esc($s['kode'].' — '.elective_subject_label($s['nama'], $s['elective_kode'] ?? null)); ?>
                  <option value="<?= $label ?>" data-id="<?= (int)$s['id'] ?>"></option>
                <?php endforeach; ?>
              </datalist>
              <input type="hidden" name="subject_id" id="subject_id" value="">
            </div>
            <div class="field"><label class="label">Guru *</label>
              <input id="teacher-combobox" class="input" list="teacher-options" autocomplete="off"
                     placeholder="Cari guru / NIY" required>
              <datalist id="teacher-options">
                <?php foreach ($teachers as $t): ?>
                  <?php $label = esc($t['niy'].' — '.$t['nama']); ?>
                  <option value="<?= $label ?>" data-id="<?= (int)$t['id'] ?>"></option>
                <?php endforeach; ?>
              </datalist>
              <input type="hidden" name="teacher_id" id="teacher_id" value="">
            </div>
            <script>
            (function () {
              const form = document.querySelector('form[method="post"]');
              const subjectInput = document.getElementById('subject-combobox');
              const teacherInput = document.getElementById('teacher-combobox');
              const subjectHidden = document.getElementById('subject_id');
              const teacherHidden = document.getElementById('teacher_id');
              const subjectOptions = Array.from(document.querySelectorAll('#subject-options option'));
              const teacherOptions = Array.from(document.querySelectorAll('#teacher-options option'));

              function updateHidden(input, hidden, options) {
                const value = input.value.trim();
                const match = options.find(opt => opt.value === value);
                hidden.value = match ? match.dataset.id : '';
                if (value === '') {
                  input.setCustomValidity('');
                } else if (!match) {
                  input.setCustomValidity('Pilih dari daftar mapel yang tersedia.');
                } else {
                  input.setCustomValidity('');
                }
              }

              function attachInput(input, hidden, options) {
                input.addEventListener('input', function () {
                  updateHidden(input, hidden, options);
                });
                input.addEventListener('change', function () {
                  updateHidden(input, hidden, options);
                });
              }

              if (subjectInput && subjectHidden) {
                attachInput(subjectInput, subjectHidden, subjectOptions);
              }
              if (teacherInput && teacherHidden) {
                attachInput(teacherInput, teacherHidden, teacherOptions);
              }

              if (form) {
                form.addEventListener('submit', function (event) {
                  updateHidden(subjectInput, subjectHidden, subjectOptions);
                  updateHidden(teacherInput, teacherHidden, teacherOptions);

                  if (subjectHidden.value === '') {
                    event.preventDefault();
                    subjectInput.reportValidity();
                    return;
                  }
                  if (teacherHidden.value === '') {
                    event.preventDefault();
                    teacherInput.reportValidity();
                    return;
                  }
                });
              }
            })();
            </script>
            <div class="field"><label class="label">Rombel *</label>
              <div style="max-height:220px; overflow:auto; border:1px solid var(--border); border-radius:8px; padding:0.5rem; background: var(--bg);">
                <?php foreach ($rombels as $r): ?>
                  <label style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem;">
                    <input type="checkbox" name="rombel_ids[]" value="<?= (int)$r['id'] ?>" <?= $r['id'] === (int)$current['id'] ? 'checked' : '' ?>>
                    <span><?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <span class="text-sm text-muted">Pilih satu atau beberapa rombel. Mapping akan disimpan untuk semua rombel terpilih.</span>
            </div>
            <div class="field"><label class="label">Berlaku Untuk</label>
              <select class="select" name="semester">
                <option value="">Kedua semester</option>
                <option value="ganjil">Ganjil saja</option>
                <option value="genap">Genap saja</option>
              </select>
            </div>
            <div style="display: flex; gap: 0.5rem;">
              <button class="btn btn-primary" type="submit">Simpan Mapping</button>
              <button class="btn btn-secondary" type="reset" id="resetForm" style="display: none;">Reset</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
          <div class="card-header"><h3 class="card-title">Import / Export CSV</h3></div>
          <div class="card-body">
              <div style="margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                  <a href="?rombel_id=<?= (int)$current['id'] ?>&action=download_data" class="btn btn-sm btn-outline">⬇️ Download Data</a>
                  <a href="?action=download_template" class="btn btn-sm btn-outline">📝 Download Template</a>
              </div>
              <hr style="margin: 1rem 0; border: none; border-top: 1px solid #ddd;">
              <form method="post" enctype="multipart/form-data">
                  <?= csrf_field() ?>
                  <input type="hidden" name="op" value="import">
                  <input type="hidden" name="rombel_id" value="<?= (int)$current['id'] ?>">
                  <div class="field"><label class="label">Upload CSV</label>
                      <input type="file" name="file" accept=".csv" required style="width: 100%; margin-bottom: 0.5rem;">
                      <span class="text-sm text-muted">Format: kode_mapel, niy_guru, semester (opsional: ganjil/genap)</span>
                  </div>
                  <button class="btn btn-secondary btn-sm" type="submit">Import CSV</button>
              </form>
          </div>
      </div>
  </div>
  <?php endif; ?>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
      <h3 class="card-title" style="margin: 0; flex-shrink: 0;">Mapping Aktif (<?= count($assignments) ?>)</h3>
      <div style="display:flex; align-items:center; gap:1rem; margin-left:auto;">
        <input type="text" id="searchMapping" placeholder="Cari mapel atau guru..." style="width: 300px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" onkeyup="filterMappingTable()">
        <button class="btn btn-danger btn-sm" id="batchDeleteBtn" type="submit" form="batchDeleteForm" style="display:none;" disabled data-confirm="Hapus mapping terpilih?">Hapus Terpilih</button>
        <button class="btn btn-secondary btn-sm" id="batchCopyBtn" type="button" style="display:none;">Copy Mapping</button>
      </div>
    </div>
    <div class="table-wrap">
      <form method="post" id="batchDeleteForm">
        <?= csrf_field() ?><input type="hidden" name="op" value="batch_unassign"><input type="hidden" name="rombel_id" value="<?= (int)$current['id'] ?>">
        
        <table class="t" id="mappingTable">
          <thead>
            <tr>
              <?php if ($canEdit): ?><th style="width:36px"><input type="checkbox" id="selectAllMappings"></th><?php endif; ?>
              <th>Mapel</th><th>Guru</th><th>Semester</th><th></th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$assignments): ?><tr><td colspan="5"><div class="empty">Belum ada mapping.</div></td></tr><?php endif; ?>
          <?php foreach ($assignments as $a): ?>
            <tr>
              <?php if ($canEdit): ?>
                <td style="vertical-align:middle; text-align:center;">
                  <input type="checkbox" class="map-chk" name="ids[]" value="<?= (int)$a['id'] ?>">
                </td>
              <?php endif; ?>
              <td><strong><?= esc($a['s_kode']) ?></strong> · <?= esc(elective_subject_label($a['s_nama'], $a['elective_kode'] ?? null)) ?></td>
              <td><?= esc($a['t_nama']) ?> <span class="text-muted text-sm">(<?= esc($a['t_niy']) ?>)</span></td>
              <td><span class="badge"><?= esc($a['semester'] ?? 'Ganjil + Genap') ?></span></td>
              <td style="text-align:right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                <?php if ($canEdit): ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="editMapping(this, <?= (int)$a['subject_id'] ?>, <?= (int)$a['teacher_id'] ?>, '<?= esc($a['semester'] ?? '') ?>')">Edit</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="singleDelete(<?= (int)$a['id'] ?>)">Hapus</button><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </form>
      <form method="post" id="batchCopyForm" style="display:none;">
        <?= csrf_field() ?><input type="hidden" name="op" value="batch_copy"><input type="hidden" name="rombel_id" value="<?= (int)$current['id'] ?>">
        <div id="copyPanel" style="display:none; padding:0.75rem; border:1px solid var(--border); border-radius:8px; background:var(--bg); max-width:480px; margin:0.75rem 0;">
          <div style="margin-bottom:0.5rem; font-weight:600">Pilih rombel tujuan (harus se-jenjang)</div>
          <div style="max-height:220px; overflow:auto;">
            <?php foreach ($rombels as $r): if ($r['id'] === (int)$current['id']) continue; if ($r['jenjang'] !== $current['jenjang']) continue; ?>
              <label style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem;"><input type="checkbox" name="target_rombel_ids[]" value="<?= (int)$r['id'] ?>"><span><?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?></span></label>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:0.5rem; display:flex; gap:0.5rem;">
            <button class="btn btn-primary btn-sm" type="submit">Salin Mapping</button>
            <button class="btn btn-ghost btn-sm" type="button" id="cancelCopy">Batal</button>
          </div>
        </div>
      </form>
      <form method="post" id="singleDeleteForm" style="display:none;">
        <?= csrf_field() ?><input type="hidden" name="op" value="unassign"><input type="hidden" name="id" value=""><input type="hidden" name="rombel_id" value="<?= (int)$current['id'] ?>">
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function editMapping(button, subjectId, teacherId, semester) {
  // Populate form fields with the mapping data
  document.querySelector('select[name="subject_id"]').value = subjectId;
  document.querySelector('select[name="teacher_id"]').value = teacherId;
  document.querySelector('select[name="semester"]').value = semester;
  
  // Show reset button and scroll to form
  document.getElementById('resetForm').style.display = 'inline-block';
  document.querySelector('form').scrollIntoView({ behavior: 'smooth', block: 'center' });
  
  // Focus on the form
  document.querySelector('select[name="subject_id"]').focus();
}

function filterMappingTable() {
  const searchInput = document.getElementById('searchMapping').value.toLowerCase();
  const table = document.getElementById('mappingTable');
  const rows = table.querySelectorAll('tbody tr');
  let visibleCount = 0;
  
  rows.forEach(row => {
    // Skip the empty message row
    if (row.querySelector('.empty')) {
      return;
    }
    
    const mapelText = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
    const guruText = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
    const semesterText = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
    
    const matches = mapelText.includes(searchInput) || 
                   guruText.includes(searchInput) || 
                   semesterText.includes(searchInput);
    
    row.style.display = matches ? '' : 'none';
    if (matches) visibleCount++;
  });
  
  // Show empty message if no results
  const emptyRow = table.querySelector('tbody tr td .empty');
  if (emptyRow && visibleCount === 0 && searchInput !== '') {
    emptyRow.parentElement.parentElement.style.display = '';
  }
}
</script>

<script>
// Batch delete helpers: select-all, enable/disable button, confirm
(function () {
  const selectAll = document.getElementById('selectAllMappings');
  const batchBtn = document.getElementById('batchDeleteBtn');
  const batchCopyBtn = document.getElementById('batchCopyBtn');
  const form = document.getElementById('batchDeleteForm');
  const copyForm = document.getElementById('batchCopyForm');
  const copyPanel = document.getElementById('copyPanel');
  const cancelCopy = document.getElementById('cancelCopy');
  function toggleBatchButton() {
    const any = Array.from(document.querySelectorAll('.map-chk')).some(c => c.checked);
    if (batchBtn) { batchBtn.disabled = !any; batchBtn.style.display = any ? 'inline-block' : 'none'; }
    if (batchCopyBtn) { batchCopyBtn.style.display = any ? 'inline-block' : 'none'; }
  }

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      document.querySelectorAll('.map-chk').forEach(c => { c.checked = this.checked; });
      toggleBatchButton();
    });
  }

  document.querySelectorAll('.map-chk').forEach(c => c.addEventListener('change', toggleBatchButton));

  if (form && batchBtn) {
    form.addEventListener('submit', function (e) {
      if (!confirm(batchBtn.dataset.confirm || 'Hapus mapping terpilih?')) {
        e.preventDefault();
      }
    });
  }

  // Batch copy: populate copy form with selected ids and show panel
  if (batchCopyBtn && copyForm) {
    batchCopyBtn.addEventListener('click', function () {
      // remove previous id inputs
      copyForm.querySelectorAll('input[name="ids[]"]').forEach(n=>n.remove());
      const selected = Array.from(document.querySelectorAll('.map-chk:checked')).map(c => (c.value));
      selected.forEach(function (v) {
        const inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = v; copyForm.appendChild(inp);
      });
      // show panel
      copyPanel.style.display = '';
      copyForm.style.display = '';
      copyForm.scrollIntoView({behavior:'smooth', block:'center'});
    });

    // cancel
    if (cancelCopy) cancelCopy.addEventListener('click', function () { copyPanel.style.display = 'none'; copyForm.style.display = 'none'; });

    // confirm on submit
    copyForm.addEventListener('submit', function (e) {
      if (!confirm('Salin mapping terpilih ke rombel tujuan?')) e.preventDefault();
    });
  }
})();
</script>

<script>
function singleDelete(id) {
  if (!confirm('Hapus mapping ini?')) return;
  const f = document.getElementById('singleDeleteForm');
  if (!f) return;
  f.querySelector('input[name="id"]').value = id;
  f.submit();
}
</script>


<?php require __DIR__ . '/../../includes/footer.php'; ?>