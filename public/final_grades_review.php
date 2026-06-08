<?php
/**
 * Stage 6 — Verifikasi Nilai Akhir oleh Kepsek (filtered jenjang) / Admin.
 * Aksi: Approve, Revisi (kembalikan ke Guru), Publish, Bulk Approve, Lock periode.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/grading_helpers.php';
require_once __DIR__ . '/../includes/final_grades_helpers.php';

$user = require_view('final_grades_review');
$pdo  = db();
$sc   = active_scope();
$err  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        if (!can_edit('final_grades_review')) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        $op = (string)($_POST['op'] ?? '');
        $ids = array_map('intval', (array)($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i)=>$i>0);

        if ($op === 'lock_period') {
            if ($user['role'] !== 'administrator') throw new RuntimeException('Hanya Administrator yang dapat lock/unlock semester dari sini.');
            $val = (int)($_POST['val'] ?? 0) ? 1 : 0;
            $pdo->prepare("INSERT IGNORE INTO semesters_state (academic_year_id, semester) VALUES (:y, :s)")
                ->execute(['y'=>$sc['year_id'],'s'=>$sc['semester']]);
            $pdo->prepare("UPDATE semesters_state SET semester_locked=:v WHERE academic_year_id=:y AND semester=:s")
                ->execute(['v'=>$val,'y'=>$sc['year_id'],'s'=>$sc['semester']]);
            audit('toggle_semester_lock', "year:{$sc['year_id']}/{$sc['semester']}", ['val'=>$val]);
            flash('success', "Semester {$sc['semester']} ".($val?'dikunci':'dibuka').'.');
            redirect('final_grades_review.php');
        }

        if (!$ids) throw new RuntimeException('Tidak ada baris terpilih.');

        // For kepsek: jenjang gating per-row
        $check = $pdo->prepare(
            "SELECT fg.id, fg.status, r.jenjang FROM final_grades fg
             JOIN rombel r ON r.id=fg.rombel_id WHERE fg.id=:i"
        );

        $changed = 0;
        foreach ($ids as $id) {
            $check->execute(['i'=>$id]);
            $row = $check->fetch();
            if (!$row) continue;
            if ($user['role']==='kepsek' && !empty($user['jenjang']) && $row['jenjang'] !== $user['jenjang']) continue;

            switch ($op) {
                case 'approve':
                    if (in_array($row['status'], ['submitted','revised'], true)) {
                        final_grade_set_status($id, 'approved', (int)$user['id']); $changed++;
                    }
                    break;
                case 'revise':
                    if (in_array($row['status'], ['submitted','approved'], true)) {
                        final_grade_set_status($id, 'revised', (int)$user['id']); $changed++;
                    }
                    break;
                case 'publish':
                    if ($row['status'] === 'approved') {
                        final_grade_set_status($id, 'published', (int)$user['id']); $changed++;
                    }
                    break;
                default: throw new RuntimeException('Aksi tidak dikenal.');
            }
        }
        audit("review_{$op}_final_grades", null, ['n'=>$changed,'sem'=>$sc['semester'],'period'=>$sc['period']]);
        flash('success', "$changed baris diproses ($op).");
        redirect('final_grades_review.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$queue = review_queue($user, $sc['semester'], $sc['period']);

// Lock state — now per-semester (PTS/PAS no longer lockable separately)
$isLocked = semester_is_locked((int)$sc['year_id'], $sc['semester']);

$page_title = 'Verifikasi Nilai Akhir';
require __DIR__ . '/../includes/header.php';
$fgStatuses = fg_statuses();
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Verifikasi <?= esc($sc['period']) ?> · Semester <?= esc($sc['semester']) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('final_grades.php')) ?>">← Kembali ke Input</a>
  </div>
  <div class="card-body">
    <div class="row" style="justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.75rem">
      <div class="text-sm">
        <strong><?= count($queue) ?></strong> baris menunggu verifikasi
        <?= ($user['role']==='kepsek' && !empty($user['jenjang'])) ? '· jenjang <strong>'.esc($user['jenjang']).'</strong>' : '' ?>.
        Status: <span class="badge <?= $isLocked?'badge-warning':'badge-success' ?>">
          <?= $isLocked?'🔒 Semester terkunci':'🔓 Semester terbuka' ?>
        </span>
      </div>
      <?php if ($user['role']==='administrator'): ?>
        <form method="post" style="margin:0; display:inline-flex; align-items:center; gap:.5rem">
          <?= csrf_field() ?><input type="hidden" name="op" value="lock_period">
          <input type="hidden" name="val" value="<?= $isLocked?0:1 ?>">
          <label class="switch" title="Toggle kunci Semester <?= esc(ucfirst($sc['semester'])) ?>">
            <input type="checkbox" <?= $isLocked?'checked':'' ?> onchange="if(confirm('<?= $isLocked?'Buka kunci':'Kunci' ?> Semester <?= esc($sc['semester']) ?>?')){this.form.submit();}else{this.checked=<?= $isLocked?'true':'false' ?>;}">
            <span class="slider"></span>
          </label>
          <span class="text-sm"><?= $isLocked?'🔓 Buka Kunci Semester':'🔒 Kunci Semester' ?></span>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (!$queue): ?>
  <div class="card mt-4"><div class="card-body"><div class="empty">Tidak ada nilai akhir yang sedang menunggu verifikasi pada periode &amp; semester aktif.</div></div></div>
<?php else: ?>
<form method="post" id="rvForm">
  <?= csrf_field() ?>
  <div class="card mt-4">
    <div class="card-body">
      <div class="row mb-3" style="gap:.5rem; flex-wrap:wrap">
        <button class="btn btn-success btn-sm"  type="submit" name="op" value="approve">✅ Setujui Terpilih</button>
        <button class="btn btn-warning btn-sm"  type="submit" name="op" value="revise"
                onclick="return confirm('Kirim balik untuk revisi?')">↩ Minta Revisi</button>
        <button class="btn btn-primary btn-sm"  type="submit" name="op" value="publish"
                onclick="return confirm('Publish nilai? (status approved → published)')">📣 Publish Terpilih</button>
        <span class="text-sm text-muted" style="align-self:center">Publish hanya untuk baris ber-status <em>approved</em>.</span>
      </div>

      <div class="table-wrap">
        <table class="t">
          <thead>
            <tr>
              <th style="width:36px"><input type="checkbox" id="selAll"></th>
              <th>Rombel</th>
              <th>Mapel</th>
              <th>Siswa</th>
              <th style="width:70px"><span class="badge badge-info">Sikap</span></th>
              <th style="width:90px"><span class="badge badge-primary">Pengetahuan</span></th>
              <th style="width:90px"><span class="badge badge-success">Keterampilan</span></th>
              <th style="width:80px">Σ</th>
              <th style="width:100px">Status</th>
              <th>Catatan Guru</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($queue as $r):
            $vals = array_filter([$r['nilai_sikap'],$r['nilai_pengetahuan'],$r['nilai_keterampilan']], fn($x)=>$x!==null);
            $avg  = $vals ? array_sum(array_map('floatval',$vals))/count($vals) : null;
            $pk   = kkm_predikat($r['jenjang'], $avg);
            $stInfo = $fgStatuses[$r['status']] ?? $fgStatuses['draft'];
          ?>
            <tr>
              <td class="text-center"><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" class="rowSel"></td>
              <td><?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['rombel_nama']) ?></td>
              <td><?= esc(($r['subj_kode']?$r['subj_kode'].' · ':'').$r['subj_nama']) ?></td>
              <td>
                <strong><?= esc($r['student_nama']) ?></strong>
                <div class="text-xs text-muted"><?= esc($r['nisn']) ?></div>
              </td>
              <td class="text-center"><?= $r['nilai_sikap']!==null?esc((string)(float)$r['nilai_sikap']):'—' ?></td>
              <td class="text-center"><?= $r['nilai_pengetahuan']!==null?esc((string)(float)$r['nilai_pengetahuan']):'—' ?></td>
              <td class="text-center"><?= $r['nilai_keterampilan']!==null?esc((string)(float)$r['nilai_keterampilan']):'—' ?></td>
              <td class="text-center">
                <?php if ($avg !== null): ?>
                  <strong><?= esc((string)round($avg,2)) ?></strong>
                  <div class="text-xs text-muted"><?= esc($pk['grade']) ?></div>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><span class="badge <?= esc($stInfo['class']) ?>"><?= esc($stInfo['label']) ?></span></td>
              <td class="text-sm"><?= esc((string)$r['catatan_guru']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</form>

<script>
(function(){
  const all = document.getElementById('selAll');
  all && all.addEventListener('change', () => {
    document.querySelectorAll('.rowSel').forEach(cb => cb.checked = all.checked);
  });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
