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
$rombelId  = int_or_null($_GET['rombel_id'] ?? null);
if (!$rombelId && $myRombels) $rombelId = (int)$myRombels[0]['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        require_edit('elective_assignment');
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'assign') {
            $eid = (int)($_POST['elective_id'] ?? 0);
            $sid = (int)($_POST['student_id'] ?? 0);
            $cid = int_or_null($_POST['elective_class_id'] ?? null);
            // Validate elective and student belong to chosen rombel + active TA
            $st = $pdo->prepare(
                "SELECT 1 FROM elective_rombels er
                   JOIN electives e ON e.id = er.elective_id
                  WHERE er.elective_id=:e AND er.rombel_id=:r AND e.academic_year_id=:y AND e.deleted_at IS NULL"
            );
            $st->execute(['e'=>$eid,'r'=>$rombelId,'y'=>$sc['year_id']]);
            if (!$st->fetchColumn()) throw new RuntimeException('Mapel pilihan tidak terhubung ke rombel ini.');

            $st = $pdo->prepare("SELECT 1 FROM rombel_members WHERE rombel_id=:r AND student_id=:s");
            $st->execute(['r'=>$rombelId,'s'=>$sid]);
            if (!$st->fetchColumn()) throw new RuntimeException('Siswa bukan anggota rombel ini.');

            if ($cid === null) {
                // remove assignment
                $pdo->prepare("DELETE FROM elective_assignments WHERE elective_id=:e AND student_id=:s AND semester=:sem")
                    ->execute(['e'=>$eid,'s'=>$sid,'sem'=>$semester]);
            } else {
                // validate class belongs to elective
                $st = $pdo->prepare("SELECT id FROM elective_classes WHERE id=:c AND elective_id=:e AND deleted_at IS NULL");
                $st->execute(['c'=>$cid,'e'=>$eid]);
                if (!$st->fetchColumn()) throw new RuntimeException('Sub-kelas tidak valid.');

                $pdo->prepare(
                    "INSERT INTO elective_assignments (elective_id, elective_class_id, student_id, semester, assigned_by)
                     VALUES (:e,:c,:s,:sem,:by)
                     ON DUPLICATE KEY UPDATE elective_class_id = VALUES(elective_class_id), assigned_by = VALUES(assigned_by)"
                )->execute(['e'=>$eid,'c'=>$cid,'s'=>$sid,'sem'=>$semester,'by'=>$user['id']]);
            }
            audit('save', "elective_assign:e{$eid}:s{$sid}:" . $semester);
            flash('success', 'Penempatan siswa diperbarui.');
            redirect('elective_assignment.php?rombel_id=' . (int)$rombelId);
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

// Build view data
$currentRombel = null;
foreach ($myRombels as $r) if ((int)$r['id'] === (int)$rombelId) { $currentRombel = $r; break; }

// Get unique jenjang from accessible rombels
$uniqueJenjang = [];
foreach ($myRombels as $r) {
    if (!in_array($r['jenjang'], $uniqueJenjang)) {
        $uniqueJenjang[] = $r['jenjang'];
    }
}
usort($uniqueJenjang, fn($a, $b) => ['SD' => 0, 'SMP' => 1, 'SMA' => 2][$a] <=> ['SD' => 0, 'SMP' => 1, 'SMA' => 2][$b]);

// Get selected jenjang from currentRombel or GET parameter
$selectedJenjang = null;
if ($currentRombel) {
    $selectedJenjang = $currentRombel['jenjang'];
} else {
    $selectedJenjang = (string)($_GET['jenjang'] ?? '');
    if (!in_array($selectedJenjang, $uniqueJenjang)) {
        $selectedJenjang = $uniqueJenjang[0] ?? '';
    }
}

$electives = $currentRombel ? electives_for_rombel((int)$currentRombel['id']) : [];
$students = [];
if ($currentRombel) {
    $st = $pdo->prepare(
        "SELECT s.id, s.nama, s.nisn FROM students s
           JOIN rombel_members rm ON rm.student_id = s.id
          WHERE rm.rombel_id = :r AND s.deleted_at IS NULL
          ORDER BY s.nama"
    );
    $st->execute(['r'=>$currentRombel['id']]);
    $students = $st->fetchAll();
}
$totalStudents = count($students);

// Pre-fetch assignments map: [elective_id][student_id] => class_id (current semester)
$assignMap = [];
if ($electives && $students) {
    $eids = implode(',', array_map(fn($e) => (int)$e['id'], $electives));
    $sids = implode(',', array_map(fn($s) => (int)$s['id'], $students));
    $rs = $pdo->query(
        "SELECT elective_id, student_id, elective_class_id FROM elective_assignments
          WHERE elective_id IN ($eids) AND student_id IN ($sids) AND semester = " . $pdo->quote($semester)
    )->fetchAll();
    foreach ($rs as $r) $assignMap[(int)$r['elective_id']][(int)$r['student_id']] = (int)$r['elective_class_id'];
}

$page_title = 'Mapel Pilihan — Penempatan Siswa';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Pilih Rombel Wali</h3>
    <span class="text-xs text-muted">Semester aktif: <strong><?= esc(ucfirst($semester)) ?></strong></span>
  </div>
  <div class="card-body">
    <?php if (!$myRombels): ?>
      <div class="alert alert-info">Tidak ada rombel walikan untuk akun ini.</div>
    <?php else: ?>
      <form method="get" class="row" id="rombel-filter-form">
        <?php if (count($uniqueJenjang) > 1): ?>
          <div class="field" style="flex:0 0 auto; min-width:120px">
            <select class="select" id="jenjang-select" name="jenjang" onchange="onJenjangChange(this.value)">
              <option value="">Semua Jenjang</option>
              <?php foreach ($uniqueJenjang as $j): ?>
                <option value="<?= esc($j) ?>" <?= ($selectedJenjang === $j) ? 'selected' : '' ?>>
                  <?= esc($j) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <div class="field" style="flex:1; min-width:240px">
          <select class="select" id="rombel-select" name="rombel_id" onchange="this.form.submit()">
            <?php if (!$rombelId): ?>
              <option value="">Pilih rombel...</option>
            <?php endif; ?>
            <?php foreach ($myRombels as $r): 
              if ($selectedJenjang && $r['jenjang'] !== $selectedJenjang) continue;
            ?>
              <option value="<?= (int)$r['id'] ?>" data-jenjang="<?= esc($r['jenjang']) ?>" <?= ((int)$r['id']===(int)$rombelId)?'selected':'' ?>>
                <?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
      <script>
        const rombelsByJenjang = {
          <?php foreach ($uniqueJenjang as $j): 
            $rombelsByJen = array_filter($myRombels, fn($r) => $r['jenjang'] === $j);
          ?>
          '<?= $j ?>': [
            <?php foreach ($rombelsByJen as $r): ?>
              {id: <?= (int)$r['id'] ?>, label: '<?= esc(addslashes($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama'])) ?>'},
            <?php endforeach; ?>
          ],
          <?php endforeach; ?>
        };
        
        function onJenjangChange(jenjang) {
          const select = document.getElementById('rombel-select');
          const currentValue = select.value;
          
          // Clear all options
          while (select.options.length > 0) {
            select.remove(0);
          }
          
          // Add default option
          const optDefault = document.createElement('option');
          optDefault.value = '';
          optDefault.textContent = 'Pilih rombel...';
          select.appendChild(optDefault);
          
          // Add filtered options
          let rombels = jenjang ? (rombelsByJenjang[jenjang] || []) : 
                        Object.values(rombelsByJenjang).flat();
          
          rombels.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.label;
            if (r.id == currentValue) opt.selected = true;
            select.appendChild(opt);
          });
        }
      </script>
    <?php endif; ?>
  </div>
</div>

<?php if ($currentRombel): ?>
  <?php if (!$electives): ?>
    <div class="alert alert-info mt-3">Rombel ini belum dihubungkan ke mapel pilihan manapun. Hubungi admin.</div>
  <?php else: ?>
    <?php foreach ($electives as $el):
      $classes = elective_classes((int)$el['id']);
      $counts  = elective_class_counts((int)$el['id'], $semester);
      $assignedCount = array_sum($counts);
      $unassigned = max(0, $totalStudents - $assignedCount);
    ?>
      <div class="card mt-3">
        <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:.75rem;">
          <div class="between" style="width:100%; gap:1rem; align-items:flex-start;">
            <div>
              <h3 class="card-title" style="margin-bottom:.35rem"><?= esc($el['kode'].' — '.$el['nama']) ?></h3>
              <div class="text-xs text-muted">Mapel pilihan jenjang <?= esc($el['jenjang']) ?> — rombel digabung dan ditugaskan oleh wali</div>
            </div>
            <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
              <span class="badge badge-primary"><?= $totalStudents ?> siswa</span>
              <span class="badge badge-success"><?= $assignedCount ?> terisi</span>
              <span class="badge badge-warning"><?= $unassigned ?> belum</span>
            </div>
          </div>
          <div class="text-xs text-muted" style="width:100%; display:flex; gap:1rem; flex-wrap:wrap;">
            <span><strong>Sub-kelas:</strong></span>
            <?php foreach ($classes as $c): ?>
              <span style="margin-right:.5rem"><strong><?= esc($c['nama']) ?></strong> (<?= (int)($counts[(int)$c['id']] ?? 0) ?>/<?= (int)$c['kapasitas'] ?>)</span>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card-body">
          <?php if (!$classes): ?>
            <div class="alert alert-warning">Belum ada sub-kelas. Hubungi admin.</div>
          <?php else: ?>
            <div class="table-wrap">
              <table class="t">
              <thead><tr><th style="width:32px">#</th><th>NISN</th><th>Nama Siswa</th><th colspan="2">Pilihan (semester <?= esc(ucfirst($semester)) ?>)</th></tr></thead>
              <tbody>
                <?php $i=1; foreach ($students as $s): $cur = $assignMap[(int)$el['id']][(int)$s['id']] ?? null; ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td class="text-muted"><?= esc($s['nisn']) ?></td>
                  <td><?= esc($s['nama']) ?></td>
                  <td colspan="2">
                    <form method="post" style="margin:0; display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
                      <?= csrf_field() ?><input type="hidden" name="op" value="assign">
                      <input type="hidden" name="elective_id" value="<?= (int)$el['id'] ?>">
                      <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                      <select class="select select-sm" name="elective_class_id" <?= $canEdit?'':'disabled' ?> style="min-width:180px; max-width:320px; flex:1;">
                        <option value="">— belum memilih —</option>
                        <?php foreach ($classes as $c): ?>
                          <option value="<?= (int)$c['id'] ?>" <?= ($cur===(int)$c['id'])?'selected':'' ?>>
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
                <?php if (!$students): ?>
                  <tr><td colspan="5" class="text-muted">Rombel belum punya anggota.</td></tr>
                <?php endif; ?>
              </tbody>
              </table>
            </div>
            <div class="text-xs text-muted">Tip: ganti pilihan lalu klik <em>Switch</em> untuk memindahkan siswa antar sub-kelas dalam mapel pilihan ini (berlaku untuk semester aktif).</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
