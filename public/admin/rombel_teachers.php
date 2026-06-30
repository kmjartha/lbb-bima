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
            $rid = (int)($_POST['rombel_id'] ?? 0);
            $sid = (int)($_POST['subject_id'] ?? 0);
            $tid = (int)($_POST['teacher_id'] ?? 0);
            $sem = (string)($_POST['semester'] ?? '');
            if (!in_array($sem, ['ganjil','genap',''], true)) throw new RuntimeException('Semester invalid.');
            $semVal = $sem === '' ? null : $sem;
            if (!$rid || !$sid || !$tid) throw new RuntimeException('Rombel, mapel, dan guru wajib dipilih.');
            $stmt = $pdo->prepare("SELECT 1 FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
            $stmt->execute(['id'=>$rid,'y'=>$sc['year_id']]);
            if (!$stmt->fetchColumn()) throw new RuntimeException('Rombel tidak ditemukan di tahun ajaran aktif.');

            // upsert
            $del = $pdo->prepare("DELETE FROM rombel_subject_teachers WHERE rombel_id=:r AND subject_id=:s AND (semester <=> :sem)");
            $del->execute(['r'=>$rid,'s'=>$sid,'sem'=>$semVal]);
            $pdo->prepare("INSERT INTO rombel_subject_teachers (rombel_id, subject_id, teacher_id, semester) VALUES (:r,:s,:t,:sem)")
                ->execute(['r'=>$rid,'s'=>$sid,'t'=>$tid,'sem'=>$semVal]);
            audit('assign_teacher', "rombel:$rid/subject:$sid", ['t'=>$tid,'sem'=>$semVal]);
            flash('success', 'Guru pengampu disimpan.');
            redirect('admin/rombel_teachers.php?rombel_id=' . $rid);
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
             LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
             LEFT JOIN electives e ON e.id = ec.elective_id
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
             LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
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
            <input type="hidden" name="rombel_id" value="<?= (int)$current['id'] ?>">
            <div class="field"><label class="label">Mapel *</label>
              <select class="select" name="subject_id" required>
                <option value="">— Pilih mapel —</option>
                <?php foreach ($subjects as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= esc($s['kode'].' — '.elective_subject_label($s['nama'], $s['elective_kode'] ?? null)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field"><label class="label">Guru *</label>
              <select class="select" name="teacher_id" required>
                <option value="">— Pilih guru —</option>
                <?php foreach ($teachers as $t): ?>
                  <option value="<?= (int)$t['id'] ?>"><?= esc($t['niy'].' — '.$t['nama']) ?></option>
                <?php endforeach; ?>
              </select>
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
      <input type="text" id="searchMapping" placeholder="Cari mapel atau guru..." style="width: 300px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" onkeyup="filterMappingTable()">
    </div>
    <div class="table-wrap">
      <table class="t" id="mappingTable">
        <thead><tr><th>Mapel</th><th>Guru</th><th>Semester</th><th></th></tr></thead>
        <tbody>
        <?php if (!$assignments): ?><tr><td colspan="4"><div class="empty">Belum ada mapping.</div></td></tr><?php endif; ?>
        <?php foreach ($assignments as $a): ?>
          <tr>
            <td><strong><?= esc($a['s_kode']) ?></strong> · <?= esc(elective_subject_label($a['s_nama'], $a['elective_kode'] ?? null)) ?></td>
            <td><?= esc($a['t_nama']) ?> <span class="text-muted text-sm">(<?= esc($a['t_niy']) ?>)</span></td>
            <td><span class="badge"><?= esc($a['semester'] ?? 'Ganjil + Genap') ?></span></td>
            <td style="text-align:right; display: flex; gap: 0.5rem; justify-content: flex-end;">
              <?php if ($canEdit): ?>
              <button type="button" class="btn btn-primary btn-sm" onclick="editMapping(this, <?= (int)$a['subject_id'] ?>, <?= (int)$a['teacher_id'] ?>, '<?= esc($a['semester'] ?? '') ?>')">Edit</button>
              <form method="post" style="display:inline" data-confirm="Hapus mapping ini?">
                <?= csrf_field() ?><input type="hidden" name="op" value="unassign">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="rombel_id" value="<?= (int)$current['id'] ?>">
                <button class="btn btn-danger btn-sm">Hapus</button>
              </form><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
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

<?php require __DIR__ . '/../../includes/footer.php'; ?>