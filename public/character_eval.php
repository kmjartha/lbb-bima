<?php
/**
 * Stage 7 — Character Evaluation per siswa per aspek (NI/SI/WI/PR + remark).
 * Per (rombel, semester, period_kind).
 * Roles: guru wali (rombel-nya), administrator/admin, kepsek (read-only).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/wali_helpers.php';

$user = require_view('character_eval');
$pdo  = db();
$sc   = active_scope();
$err  = null;

$rombels = accessible_wali_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $members = []; $aspects = []; $existing = [];
if ($rid) {
    $rombel   = assert_wali_rombel($user, $rid);
    $members  = rombel_members($rid);
    $aspects  = character_aspects_all($rombel['jenjang'] ?? null);
    $existing = character_evals_for($rid, $sc['semester'], $sc['period']);
}

$readonly = $rombel ? wali_readonly($user) : true;
$scales   = character_scales();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rombel) {
    try {
        csrf_check();
        if (!can_edit('character_eval')) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        if ($readonly) throw new RuntimeException('Periode terkunci atau Anda tidak diizinkan mengedit.');
        $sVals = $_POST['scale']  ?? [];   // scale[student_id][aspect_id]
        $rVals = $_POST['remark'] ?? [];   // remark[student_id][aspect_id]
        $count = 0;
        foreach ($members as $m) {
            $mid = (int)$m['id'];
            foreach ($aspects as $a) {
                $aid = (int)$a['id'];
                $sc1 = (string)($sVals[$mid][$aid] ?? '');
                if ($sc1 === '') continue; // skip not-set
                if (!isset($scales[$sc1])) continue;
                $rm  = trim((string)($rVals[$mid][$aid] ?? ''));
                character_eval_upsert($rid, $mid, $aid, $sc['semester'], $sc['period'], $sc1, $rm !== '' ? $rm : null);
                $count++;
            }
        }
        audit('save_character_eval', "rombel:$rid", ['sem'=>$sc['semester'],'period'=>$sc['period'],'n'=>$count]);
        flash('success', "Penilaian karakter tersimpan ($count entri).");
        redirect("character_eval.php?rombel_id=$rid" . ($_GET['student_id'] ?? '' ? '&student_id=' . (int)$_GET['student_id'] : ''));
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

// Optional: focus on one student (mobile-friendly form)
$focusSid = int_or_null($_GET['student_id'] ?? null);

$page_title = 'Character Evaluation';
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
      <div class="field" style="min-width:240px">
        <label class="label">Fokus Siswa (opsional)</label>
        <select name="student_id" class="select" onchange="this.form.submit()">
          <option value="">— Semua siswa (matrix) —</option>
          <?php foreach ($members as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= $focusSid==$m['id']?'selected':'' ?>><?= esc($m['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label class="label">&nbsp;</label>
        <span class="text-sm text-muted">Skala: NI · SI · WI · PR</span>
      </div>
    </form>
  </div>
</div>

<?php if (!$rombel): ?>
  <div class="empty">Pilih rombel untuk mulai menilai.</div>
<?php elseif (!$aspects): ?>
  <div class="alert alert-warning">Belum ada aspek karakter terdaftar. Tambahkan dulu di menu <strong>Master Data → Aspek Karakter</strong>.</div>
<?php elseif (!$members): ?>
  <div class="empty">Belum ada anggota rombel.</div>
<?php else: ?>

<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="card-header">
    <h3 class="card-title">
      <?= esc($rombel['jenjang'] . ' ' . $rombel['nama']) ?>
      <?php if ($focusSid):
        $fm = null; foreach ($members as $m) if ((int)$m['id']===$focusSid) $fm = $m;
      ?>
        · Fokus: <?= esc($fm['nama'] ?? '?') ?>
      <?php endif; ?>
    </h3>
    <span class="badge <?= $readonly?'badge-warning':'badge-success' ?>"><?= $readonly?'Read-only':'Editable' ?></span>
  </div>

  <?php
    // Filter members ke fokus jika ada
    $renderMembers = $focusSid ? array_values(array_filter($members, fn($m) => (int)$m['id'] === $focusSid)) : $members;
  ?>

  <?php if ($focusSid && $renderMembers): /* Vertical per-aspect form (mobile friendly) */ ?>
    <?php $m = $renderMembers[0]; $mid = (int)$m['id']; ?>
    <div class="card-body">
      <div class="text-sm text-muted">NISN <?= esc($m['nisn']) ?> · <?= esc($m['jk']) ?></div>
    </div>
    <div class="table-wrap">
      <table class="t">
        <thead>
          <tr><th>Aspek</th><th style="width:160px">Skala</th><th>Remark</th></tr>
        </thead>
        <tbody>
        <?php foreach ($aspects as $a): $aid = (int)$a['id']; $cur = $existing[$mid][$aid] ?? null; ?>
          <tr>
            <td>
              <strong><?= esc($a['nama']) ?></strong>
              <div><span class="badge <?= $a['kategori']==='spiritual'?'badge-info':'badge-primary' ?>"><?= esc(ucfirst($a['kategori'])) ?></span></div>
            </td>
            <td>
              <select class="select" name="scale[<?= $mid ?>][<?= $aid ?>]" <?= $readonly?'disabled':'' ?>>
                <option value="">—</option>
                <?php foreach ($scales as $code => $def): ?>
                  <option value="<?= esc($code) ?>" <?= ($cur['scale'] ?? '')===$code?'selected':'' ?>><?= esc($code . ' · ' . $def['label']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ($cur): ?><div class="badge <?= esc($scales[$cur['scale']]['class'] ?? '') ?>" style="margin-top:6px"><?= esc($cur['scale']) ?></div><?php endif; ?>
            </td>
            <td>
              <input class="input" type="text" maxlength="500"
                name="remark[<?= $mid ?>][<?= $aid ?>]"
                placeholder="Catatan opsional…"
                value="<?= esc($cur['remark'] ?? '') ?>" <?= $readonly?'disabled':'' ?>>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  <?php else: /* Matrix view — siswa × aspek (skala saja, tanpa remark) */ ?>
    <div class="alert alert-info" style="margin: var(--sp-4)">
      Mode matrix hanya menampilkan kolom <strong>Skala</strong>. Untuk mengedit remark per siswa, pilih
      <em>Fokus Siswa</em> di filter di atas.
    </div>
    <div class="table-wrap">
      <table class="t">
        <thead>
          <tr>
            <th style="position:sticky; left:0; background:var(--surface, #fff); z-index:1; min-width:180px">Siswa</th>
            <?php foreach ($aspects as $a): ?>
              <th title="<?= esc($a['nama']) ?>" style="min-width:130px">
                <div class="text-sm"><?= esc($a['nama']) ?></div>
                <span class="badge <?= $a['kategori']==='spiritual'?'badge-info':'badge-primary' ?>" style="margin-top:4px"><?= esc(ucfirst($a['kategori'])) ?></span>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($members as $m): $mid = (int)$m['id']; ?>
          <tr>
            <td style="position:sticky; left:0; background:var(--surface, #fff); z-index:1">
              <strong><?= esc($m['nama']) ?></strong>
              <div class="text-sm text-muted">
                <a href="?rombel_id=<?= $rid ?>&student_id=<?= $mid ?>">Buka detail ›</a>
              </div>
            </td>
            <?php foreach ($aspects as $a): $aid = (int)$a['id']; $cur = $existing[$mid][$aid] ?? null; ?>
              <td>
                <select class="select select-sm" name="scale[<?= $mid ?>][<?= $aid ?>]" <?= $readonly?'disabled':'' ?>>
                  <option value="">—</option>
                  <?php foreach ($scales as $code => $def): ?>
                    <option value="<?= esc($code) ?>" <?= ($cur['scale'] ?? '')===$code?'selected':'' ?>><?= esc($code) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if ($cur && !empty($cur['remark'])): ?>
                  <div class="text-sm text-muted" title="<?= esc($cur['remark']) ?>">📝 …</div>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if (!$readonly): ?>
    <div class="card-body" style="text-align:right">
      <button class="btn btn-primary" type="submit">Simpan Penilaian Karakter</button>
    </div>
  <?php endif; ?>
</form>

<div class="card">
  <div class="card-header"><h3 class="card-title">Legenda Skala</h3></div>
  <div class="card-body row" style="gap: var(--sp-3); flex-wrap: wrap">
    <?php foreach ($scales as $code => $def): ?>
      <div class="badge <?= esc($def['class']) ?>"><?= esc($code . ' · ' . $def['label']) ?></div>
    <?php endforeach; ?>
  </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
