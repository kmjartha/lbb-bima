<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/elective_helpers.php';
require_once __DIR__ . '/../includes/wali_helpers.php';

$user = require_view('elective_assignment');
$canEdit = can_edit('elective_assignment', $user);

$pdo = db();
$sc  = active_scope();
$semester = $sc['semester']; // ganjil | genap
$err = null;

// Wali rombels visible to current user (admin sees all; guru wali sees own)
$myRombels = accessible_wali_rombel($user);
$myRombelIds = array_map(fn($r) => (int)$r['id'], $myRombels);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        require_edit('elective_assignment');
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'assign') {
            if (!$myRombelIds) {
                throw new RuntimeException('Akses tidak sah.');
            }

            $eid = (int)($_POST['elective_id'] ?? 0);
            $sid = (int)($_POST['student_id'] ?? 0);
            $cid = int_or_null($_POST['elective_class_id'] ?? null);

            $st = $pdo->prepare(
                "SELECT 1 FROM elective_rombels er
                   JOIN electives e ON e.id = er.elective_id
                  WHERE er.elective_id = :e
                    AND er.rombel_id IN (" . implode(',', $myRombelIds) . ")
                    AND e.academic_year_id = :y
                    AND e.deleted_at IS NULL"
            );
            $st->execute(['e' => $eid, 'y' => $sc['year_id']]);
            if (!$st->fetchColumn()) {
                throw new RuntimeException('Mapel pilihan tidak ditemukan atau tidak dapat diakses.');
            }

            $st = $pdo->prepare(
                "SELECT 1 FROM elective_rombels er
                   JOIN rombel_members rm ON rm.rombel_id = er.rombel_id
                  WHERE er.elective_id = :e AND rm.student_id = :s"
            );
            $st->execute(['e' => $eid, 's' => $sid]);
            if (!$st->fetchColumn()) {
                throw new RuntimeException('Siswa bukan anggota rombel yang terkait mapel ini.');
            }

            if ($cid === null) {
                $pdo->prepare("DELETE FROM elective_assignments WHERE elective_id = :e AND student_id = :s AND semester = :sem")
                    ->execute(['e' => $eid, 's' => $sid, 'sem' => $semester]);
            } else {
                $st = $pdo->prepare("SELECT id FROM elective_classes WHERE id = :c AND elective_id = :e AND deleted_at IS NULL");
                $st->execute(['c' => $cid, 'e' => $eid]);
                if (!$st->fetchColumn()) {
                    throw new RuntimeException('Sub-kelas tidak valid.');
                }

                $pdo->prepare(
                    "INSERT INTO elective_assignments (elective_id, elective_class_id, student_id, semester, assigned_by)
                     VALUES (:e, :c, :s, :sem, :by)
                     ON DUPLICATE KEY UPDATE elective_class_id = VALUES(elective_class_id), assigned_by = VALUES(assigned_by)"
                )->execute(['e' => $eid, 'c' => $cid, 's' => $sid, 'sem' => $semester, 'by' => $user['id']]);
            }

            audit('save', "elective_assign:e{$eid}:s{$sid}:" . $semester);
            flash('success', 'Penempatan siswa diperbarui.');
            redirect('elective_assignment.php?elective_id=' . $eid);
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

$accessibleElectives = [];
if ($myRombelIds) {
    $useCategory = false;
    try {
        $pdo->query('SELECT category_id FROM electives LIMIT 1');
        $useCategory = true;
    } catch (Throwable $e) {
        $useCategory = false;
    }

    if ($useCategory) {
        $sql = "SELECT DISTINCT e.*, sc.nama AS category_name
                 FROM electives e
                 LEFT JOIN subject_categories sc ON sc.id = e.category_id
                 JOIN elective_rombels er ON er.elective_id = e.id
                 WHERE er.rombel_id IN (" . implode(',', $myRombelIds) . ")
                   AND e.academic_year_id = :y
                   AND e.deleted_at IS NULL
                 ORDER BY e.nama";
    } else {
        $sql = "SELECT DISTINCT e.*
                 FROM electives e
                 JOIN elective_rombels er ON er.elective_id = e.id
                 WHERE er.rombel_id IN (" . implode(',', $myRombelIds) . ")
                   AND e.academic_year_id = :y
                   AND e.deleted_at IS NULL
                 ORDER BY e.nama";
    }

    $st = $pdo->prepare($sql);
    $st->execute(['y' => $sc['year_id']]);
    $accessibleElectives = $st->fetchAll();
}

$electiveId = int_or_null($_GET['elective_id'] ?? null);
$selectedElective = null;
$electiveRombels = [];
$students = [];
$classes = [];
$classCounts = [];

if ($electiveId) {
    foreach ($accessibleElectives as $e) {
        if ((int)$e['id'] === $electiveId) {
            $selectedElective = $e;
            break;
        }
    }
    if ($selectedElective) {
        $electiveRombels = elective_rombels_for($electiveId);
        $students = elective_students($electiveId);
        $classes = elective_classes($electiveId);
        $classCounts = elective_class_counts($electiveId, $semester);

        $assignMap = [];
        if ($students) {
            $st = $pdo->prepare(
                "SELECT student_id, elective_class_id FROM elective_assignments
                 WHERE elective_id = :e AND semester = :sem"
            );
            $st->execute(['e' => $electiveId, 'sem' => $semester]);
            foreach ($st->fetchAll() as $row) {
                $assignMap[(int)$row['student_id']] = (int)$row['elective_class_id'];
            }
        }
    } else {
        $err = $err ?: 'Mapel pilihan tidak ditemukan atau Anda tidak memiliki akses.';
    }
}

$page_title = 'Mapel Pilihan — Penempatan Siswa';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header between" style="align-items:flex-start; gap:.75rem;">
    <div>
      <h3 class="card-title">Penempatan Mapel Pilihan</h3>
      <div class="text-xs text-muted">Pilih mapel pilihan dari daftar, lalu buka detail untuk menugaskan siswa ke sub-kelas.</div>
    </div>
    <div class="text-xs text-muted">Semester aktif: <strong><?= esc(ucfirst($semester)) ?></strong></div>
  </div>
  <div class="card-body">
    <?php if (!$myRombels): ?>
      <div class="alert alert-info">Tidak ada rombel walikan untuk akun ini.</div>
    <?php elseif (!$accessibleElectives): ?>
      <div class="alert alert-info">Tidak ada mapel pilihan tersedia untuk rombel Anda. Hubungi admin.</div>
    <?php elseif (!$selectedElective): ?>
      <div class="table-wrap">
        <table class="t">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama</th>
              <th>Kategori</th>
              <th>Jenjang</th>
              <th>Rombel</th>
              <th>Sub-kelas</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($accessibleElectives as $el): ?>
              <?php $rombelList = elective_rombels_for((int)$el['id']); ?>
              <tr>
                <td><strong><?= esc($el['kode']) ?></strong></td>
                <td><?= esc($el['nama']) ?></td>
                <td><?= esc($el['category_name'] ?? '-') ?></td>
                <td><span class="badge badge-primary"><?= esc($el['jenjang']) ?></span></td>
                <td>
                  <?php foreach ($rombelList as $rb): ?>
                    <div><?= esc($rb['jenjang'].' '.$rb['tingkat'].' · '.$rb['nama']) ?></div>
                  <?php endforeach; ?>
                </td>
                <td><?= count(elective_classes((int)$el['id'])) ?> opsi</td>
                <td style="text-align:right; white-space:nowrap;">
                  <a class="btn btn-secondary btn-sm" href="?elective_id=<?= (int)$el['id'] ?>">Detail</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="card mb-3">
        <div class="card-body">
          <div class="between" style="gap:1rem; align-items:flex-start; flex-wrap:wrap;">
            <div>
              <h3 class="card-title" style="margin-bottom:.25rem;"><?= esc($selectedElective['kode'] . ' — ' . $selectedElective['nama']) ?></h3>
              <div class="text-sm text-muted">Kategori: <?= esc($selectedElective['category_name'] ?? '-') ?> · Jenjang <?= esc($selectedElective['jenjang']) ?></div>
            </div>
            <a class="btn btn-ghost btn-sm" href="<?= esc(url('elective_assignment.php')) ?>">Kembali ke daftar</a>
          </div>
          <div class="text-xs text-muted" style="margin-top:.75rem; display:flex; gap:1rem; flex-wrap:wrap;">
            <span><strong>Rombel terkait:</strong></span>
            <?php foreach ($electiveRombels as $rb): ?>
              <span><?= esc($rb['jenjang'].' '.$rb['tingkat'].' · '.$rb['nama']) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <?php if (!$classes): ?>
        <div class="alert alert-warning">Belum ada sub-kelas untuk mapel ini. Hubungi admin.</div>
      <?php elseif (!$students): ?>
        <div class="alert alert-info">Belum ada siswa pada rombel terkait.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="t">
            <thead>
              <tr>
                <th style="width:32px">#</th>
                <th>Rombel</th>
                <th>NISN</th>
                <th>Nama</th>
                <th colspan="2">Sub-kelas</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; foreach ($students as $s): $cur = $assignMap[(int)$selectedElective['id']][(int)$s['id']] ?? null; ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= esc($s['jenjang'] . ' ' . $s['tingkat'] . ' · ' . $s['rombel_nama']) ?></td>
                  <td class="text-muted"><?= esc($s['nisn']) ?></td>
                  <td><?= esc($s['nama']) ?></td>
                  <td colspan="2">
                    <form method="post" style="margin:0; display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
                      <?= csrf_field() ?><input type="hidden" name="op" value="assign">
                      <input type="hidden" name="elective_id" value="<?= (int)$selectedElective['id'] ?>">
                      <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                      <select class="select select-sm" name="elective_class_id" <?= $canEdit ? '' : 'disabled' ?> style="min-width:180px; max-width:320px; flex:1;">
                        <option value="">— pilih sub-kelas —</option>
                        <?php foreach ($classes as $c): ?>
                          <option value="<?= (int)$c['id'] ?>" <?= ($cur === (int)$c['id']) ? 'selected' : '' ?> >
                            <?= esc($c['nama']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <?php if ($canEdit): ?>
                        <button class="btn btn-primary btn-sm" type="submit"><?= $cur ? 'Switch' : 'Simpan' ?></button>
                      <?php endif; ?>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="text-xs text-muted" style="margin-top:.75rem;">Tip: pilih sub-kelas untuk siswa dan klik Simpan / Switch untuk memperbarui penempatan.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>