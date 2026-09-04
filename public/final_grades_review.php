<?php
/**
 * Stage 6 — Verifikasi Nilai Akhir oleh Kepsek (filtered jenjang) / Admin.
 * Aksi: Approve, Revisi (kembalikan ke Guru), Bulk Approve, Lock periode.
 * Publish rapor ke Parent Portal sekarang di halaman terpisah, lihat
 * publish_rapor.php (juga per Semester × PTS/PAS).
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
            [$startDate, $endDate] = semester_date_window($sc['year_id'], $sc['semester']);
            $pdo->prepare(
                "INSERT IGNORE INTO semesters_state (academic_year_id, semester, start_date, end_date)
                 VALUES (:y, :s, :sd, :ed)"
            )->execute(['y'=>$sc['year_id'],'s'=>$sc['semester'],'sd'=>$startDate,'ed'=>$endDate]);
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
             JOIN rombel r ON r.id=fg.rombel_id
             WHERE fg.id=:i AND r.academic_year_id = :y"
        );

        if (!in_array($op, ['approve', 'revise'], true)) throw new RuntimeException('Aksi tidak dikenal.');

        $total   = count($ids);
        $changed = 0;
        foreach ($ids as $id) {
            $check->execute(['i'=>$id, 'y'=>$sc['year_id']]);
            $row = $check->fetch();
            if (!$row) continue;
            if ($user['role']==='kepsek' && !empty($user['jenjang']) && $row['jenjang'] !== $user['jenjang']) continue;

            if ($op === 'approve' && in_array($row['status'], ['submitted','revised'], true)) {
                final_grade_set_status($id, 'approved', (int)$user['id']); $changed++;
            } elseif ($op === 'revise' && in_array($row['status'], ['submitted','approved'], true)) {
                final_grade_set_status($id, 'revised', (int)$user['id']); $changed++;
            }
        }
        audit("review_{$op}_final_grades", null, ['n'=>$changed,'sem'=>$sc['semester'],'period'=>$sc['period']]);

        // Pesan hasil menjelaskan apa yang berubah DAN apa yang dilewati, supaya
        // kepsek tidak bingung kalau jumlah "diproses" lebih kecil dari jumlah
        // baris yang dia centang (mis. karena statusnya sudah tidak relevan
        // untuk aksi ini, atau di luar akses jenjangnya).
        $opLabel = $op === 'approve' ? 'disetujui' : 'dikembalikan untuk revisi';
        $msg = "$changed dari $total baris terpilih $opLabel.";
        $skipped = $total - $changed;
        if ($skipped > 0) {
            $reason = $op === 'approve'
                ? 'sudah berstatus Disetujui, atau di luar akses jenjang Anda'
                : 'masih berstatus Draft, atau di luar akses jenjang Anda';
            $msg .= " $skipped baris dilewati ($reason).";
        }
        flash($changed > 0 ? 'success' : 'error', $msg);
        redirect('final_grades_review.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$queue = review_queue($user, $sc['semester'], $sc['period'], $sc['year_id']);
$queueBySubmitter = [];
foreach ($queue as $r) {
    $uid = $r['submitted_by'] ? 'user_'.$r['submitted_by'] : 'unknown';
    if (!isset($queueBySubmitter[$uid])) {
        $label = $r['submitted_by_name']
            ? $r['submitted_by_name'] . ($r['submitted_by_niy'] ? ' · '.$r['submitted_by_niy'] : '')
            : 'Pengaju tidak diketahui';
        $queueBySubmitter[$uid] = ['label'=>$label, 'rows'=>[]];
    }
    $queueBySubmitter[$uid]['rows'][] = $r;
}

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
    <div class="row" style="gap:.5rem">
      <a class="btn btn-ghost btn-sm" href="<?= esc(url('final_grades.php')) ?>">← Kembali ke Input</a>
      <a class="btn btn-primary btn-sm" href="<?= esc(url('publish_rapor.php')) ?>">📣 Publish Rapor →</a>
    </div>
  </div>
  <div class="card-body">
    <div class="row" style="justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.75rem">
      <div class="text-sm">
        <strong><?= count($queue) ?></strong> baris menunggu tindak lanjut
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
  <div class="card mt-4"><div class="card-body"><div class="empty">Tidak ada nilai akhir yang sedang menunggu verifikasi atau publikasi pada periode &amp; semester aktif.</div></div></div>
<?php else: ?>
<form method="post" id="rvForm">
  <?= csrf_field() ?>
  <div class="card mt-4">
    <div class="card-body">
      <div class="row mb-2" style="gap:.5rem; flex-wrap:wrap">
        <button class="btn btn-success btn-sm" type="submit" name="op" value="approve" id="btnApprove">✅ Setujui Terpilih</button>
        <button class="btn btn-warning btn-sm" type="submit" name="op" value="revise" id="btnRevise">↩ Minta Revisi</button>
        <span class="text-sm text-muted" style="align-self:center">Setujui hanya untuk baris ber-status <em>diajukan/revisi</em>; minta revisi bisa dari <em>disetujui</em>. Mengubah status di sini <strong>tidak</strong> memengaruhi rapor yang sudah terbit ke ortu — visibilitas rapor diatur terpisah di <a href="<?= esc(url('publish_rapor.php')) ?>">Publish Rapor →</a>.</span>
      </div>
      <div class="row mb-3" style="gap:.5rem; flex-wrap:wrap; align-items:center">
        <span class="text-xs text-muted">Pilih cepat:</span>
        <button type="button" class="btn btn-ghost btn-sm" data-quicksel="submitted,revised">Yang perlu disetujui</button>
        <button type="button" class="btn btn-ghost btn-sm" data-quicksel="approved">Yang sudah disetujui</button>
        <button type="button" class="btn btn-ghost btn-sm" data-quicksel="">Kosongkan pilihan</button>
      </div>

      <?php foreach ($queueBySubmitter as $group): ?>
        <div class="card mb-4">
          <div class="card-body">
            <div class="row" style="justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem">
              <div>
                <strong>Diajukan oleh</strong>: <?= esc($group['label']) ?>
                <span class="text-xs text-muted">(<?= count($group['rows']) ?> baris)</span>
              </div>
            </div>
            <div class="table-wrap">
              <table class="t">
                <thead>
                  <tr>
                    <th style="width:36px"><input type="checkbox" class="selAll"></th>
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
                <?php foreach ($group['rows'] as $r):
                  $vals = array_filter([$r['nilai_sikap'],$r['nilai_pengetahuan'],$r['nilai_keterampilan']], fn($x)=>$x!==null);
                  $avg  = $vals ? array_sum(array_map('floatval',$vals))/count($vals) : null;
                  $pk   = kkm_predikat($r['jenjang'], $avg);
                  $rowKkm = subject_kkm_for((int)$r['subject_id'], (int)$r['tingkat']);
                  $stInfo = $fgStatuses[$r['status']] ?? $fgStatuses['draft'];
                ?>
                  <tr>
                    <td class="text-center"><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" class="rowSel" data-status="<?= esc($r['status']) ?>"></td>
                    <td><?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['rombel_nama']) ?></td>
                    <td><?= esc(($r['subj_kode']?$r['subj_kode'].' · ':'').elective_subject_label($r['subj_nama'], $r['elective_kode'] ?? null)) ?></td>
                    <td>
                      <strong><?= esc($r['student_nama']) ?></strong>
                      <div class="text-xs text-muted"><?= esc($r['nis']) ?></div>
                    </td>
                    <td class="text-center"><?= $r['nilai_sikap']!==null?esc((string)(float)$r['nilai_sikap']):'—' ?></td>
                    <td class="text-center"><?= $r['nilai_pengetahuan']!==null?esc((string)(float)$r['nilai_pengetahuan']):'—' ?></td>
                    <td class="text-center"><?= $r['nilai_keterampilan']!==null?esc((string)(float)$r['nilai_keterampilan']):'—' ?></td>
                    <td class="text-center<?= kkm_below($avg, $rowKkm) ? ' cell-kkm-below' : '' ?>">
                      <?php if ($avg !== null): ?>
                        <strong class="<?= kkm_below($avg, $rowKkm) ? 'text-kkm-below' : '' ?>"><?= esc((string)round($avg,2)) ?></strong>
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
      <?php endforeach; ?>
    </div>
  </div>
</form>

<script>
(function(){
  const form = document.getElementById('rvForm');

  document.querySelectorAll('.selAll').forEach(all => {
    const table = all.closest('table');
    all.addEventListener('change', () => {
      if (!table) return;
      table.querySelectorAll('.rowSel').forEach(cb => cb.checked = all.checked);
    });
  });

  // Tombol "pilih cepat": mencentang hanya baris dengan status yang relevan,
  // di SEMUA tabel/mapel sekaligus — supaya kepsek tidak perlu klik satu-satu
  // dan tidak salah pilih baris yang statusnya sudah tidak actionable.
  document.querySelectorAll('[data-quicksel]').forEach(btn => {
    btn.addEventListener('click', () => {
      const wanted = btn.dataset.quicksel ? btn.dataset.quicksel.split(',') : [];
      form.querySelectorAll('.rowSel').forEach(cb => {
        cb.checked = wanted.includes(cb.dataset.status);
      });
    });
  });

  // Validasi sebelum submit: kalau ada baris terpilih yang statusnya sudah
  // tidak relevan untuk aksi yang ditekan, beri tahu di muka alih-alih
  // membiarkannya dilewati diam-diam di server.
  let lastOp = null;
  document.getElementById('btnApprove')?.addEventListener('click', () => { lastOp = 'approve'; });
  document.getElementById('btnRevise')?.addEventListener('click', () => { lastOp = 'revise'; });

  form?.addEventListener('submit', function (e) {
    const selected = Array.from(form.querySelectorAll('.rowSel:checked'));
    if (!selected.length) {
      alert('Pilih minimal satu baris terlebih dahulu.');
      e.preventDefault();
      return;
    }
    const actionable = lastOp === 'approve'
      ? ['submitted', 'revised']
      : ['submitted', 'approved'];
    const eligible = selected.filter(cb => actionable.includes(cb.dataset.status));

    let msg = lastOp === 'revise' ? 'Kirim balik untuk revisi?' : null;
    if (eligible.length === 0) {
      msg = lastOp === 'approve'
        ? 'Tidak ada satupun baris terpilih yang berstatus Diajukan/Revisi — tidak akan ada yang disetujui. Tetap lanjut?'
        : 'Tidak ada satupun baris terpilih yang bisa dikembalikan untuk revisi. Tetap lanjut?';
    } else if (eligible.length < selected.length) {
      const skip = selected.length - eligible.length;
      const base = lastOp === 'revise' ? 'Kirim balik untuk revisi?' : 'Setujui baris terpilih?';
      msg = `${base} (${skip} dari ${selected.length} baris terpilih berstatus tidak relevan dan akan dilewati.)`;
    }
    if (msg && !confirm(msg)) e.preventDefault();
  });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
