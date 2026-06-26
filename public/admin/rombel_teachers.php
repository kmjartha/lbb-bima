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
        // Subjects available for this jenjang
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
  <?php if ($canEdit): ?><div class="card" style="flex: 1; min-width: 320px">
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
        <button class="btn btn-primary" type="submit">Simpan Mapping</button>
      </form>
    </div>
  </div><?php endif; ?>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header"><h3 class="card-title">Mapping Aktif (<?= count($assignments) ?>)</h3></div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Mapel</th><th>Guru</th><th>Semester</th><th></th></tr></thead>
        <tbody>
        <?php if (!$assignments): ?><tr><td colspan="4"><div class="empty">Belum ada mapping.</div></td></tr><?php endif; ?>
        <?php foreach ($assignments as $a): ?>
          <tr>
            <td><strong><?= esc($a['s_kode']) ?></strong> · <?= esc(elective_subject_label($a['s_nama'], $a['elective_kode'] ?? null)) ?></td>
            <td><?= esc($a['t_nama']) ?> <span class="text-muted text-sm">(<?= esc($a['t_niy']) ?>)</span></td>
            <td><span class="badge"><?= esc($a['semester'] ?? 'Ganjil + Genap') ?></span></td>
            <td style="text-align:right">
              <?php if ($canEdit): ?><form method="post" style="display:inline" data-confirm="Hapus mapping ini?">
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
<?php require __DIR__ . '/../../includes/footer.php'; ?>
