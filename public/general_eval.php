<?php
/**
 * Stage 7 — General Evaluation: narasi umum per siswa per (semester, period).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/wali_helpers.php';

$user = require_view('general_eval');
$pdo  = db();
$sc   = active_scope();
$err  = null;

$rombels = accessible_wali_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $members = []; $evals = [];
if ($rid) {
    $rombel  = assert_wali_rombel($user, $rid);
    $members = rombel_members($rid);
    $evals   = general_evals_for($rid, $sc['semester'], $sc['period']);
}
$readonly = $rombel ? wali_readonly($user) : true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rombel) {
    try {
        csrf_check();
        if (!can_edit('general_eval')) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        if ($readonly) throw new RuntimeException('Periode terkunci atau Anda tidak diizinkan mengedit.');
        $vals = $_POST['narasi'] ?? [];
        $count = 0;
        foreach ($members as $m) {
            $mid = (int)$m['id'];
            $val = isset($vals[$mid]) ? trim((string)$vals[$mid]) : '';
            general_eval_upsert($rid, $mid, $sc['semester'], $sc['period'], $val !== '' ? $val : null);
            $count++;
        }
        audit('save_general_eval', "rombel:$rid", ['sem'=>$sc['semester'],'period'=>$sc['period'],'n'=>$count]);
        flash('success', "General evaluation tersimpan untuk $count siswa.");
        redirect("general_eval.php?rombel_id=$rid");
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$page_title = 'General Evaluation';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Filter</h3>
    <span class="text-sm text-muted">TA <?= esc($sc['year']) ?> · <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?></span>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="gap: var(--sp-3); align-items:end">
      <div class="field" style="flex:1; min-width:280px">
        <label class="label">Rombel</label>
        <select name="rombel_id" class="select" onchange="this.form.submit()">
          <?php if (!$rombels): ?><option value="">— Tidak ada rombel terjangkau —</option><?php endif; ?>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rid==$r['id']?'selected':'' ?>>
              <?= esc($r['jenjang'] . ' · ' . $r['nama']) ?> (Wali: <?= esc($r['wali_nama'] ?? '—') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if ($rombel): ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="card-header">
    <h3 class="card-title">Narasi Umum · <?= esc($rombel['jenjang'] . ' ' . $rombel['nama']) ?></h3>
    <span class="badge <?= $readonly?'badge-warning':'badge-success' ?>"><?= $readonly?'Read-only':'Editable' ?></span>
  </div>
  <div class="table-wrap">
    <table class="t">
      <thead>
        <tr><th style="width:36px">#</th><th style="min-width:160px">Siswa</th><th>Narasi (deskripsi umum / kesimpulan periode)</th></tr>
      </thead>
      <tbody>
      <?php if (!$members): ?><tr><td colspan="3"><div class="empty">Belum ada anggota.</div></td></tr><?php endif; ?>
      <?php foreach ($members as $i => $m): $val = $evals[(int)$m['id']] ?? ''; ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td>
            <strong><?= esc($m['nama']) ?></strong>
            <div class="text-sm text-muted">NIS <?= esc($m['nis']) ?></div>
          </td>
          <td>
            <textarea class="input" name="narasi[<?= (int)$m['id'] ?>]" rows="3" maxlength="3000"
              placeholder="Tuliskan narasi umum untuk rapor periode ini…"
              <?= $readonly?'disabled':'' ?>><?= esc($val) ?></textarea>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$readonly && $members): ?>
    <div class="card-body" style="text-align:right">
      <button class="btn btn-primary" type="submit">Simpan Semua Narasi</button>
    </div>
  <?php endif; ?>
</form>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
