<?php
/**
 * Stage 7 — Nilai Ekstrakurikuler.
 * Per (extracurricular, academic_year, semester) — TIDAK terbagi PTS/PAS.
 * Filter: ekskul + rombel (untuk memilih siswa kelas yang dinilai).
 *
 * Roles:
 *   - administrator/admin: semua rombel + semua ekskul
 *   - kepsek: rombel jenjang-nya (read-only)
 *   - guru wali: rombel-nya
 *   - guru (non wali): tidak diberi akses langsung di sini; admin/wali yang input.
 *
 * (Catatan: skema ekstrakurikuler tidak memiliki tabel anggota; pemilihan
 *  dilakukan via filter rombel agar pengisian fokus per kelas.)
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/wali_helpers.php';

redirect('dashboard.php');

$user = require_view('extracurricular_grades');
$pdo  = db();
$sc   = active_scope();
$err  = null;

// ekskul list — selalu tampilkan semua ekskul aktif di TA aktif
$ekskuls = $pdo->prepare("SELECT id, nama FROM extracurriculars WHERE academic_year_id = :y AND deleted_at IS NULL ORDER BY nama");
$ekskuls->execute(['y' => $sc['year_id']]);
$ekskuls = $ekskuls->fetchAll();
$eid     = int_or_null($_GET['ekskul_id'] ?? null);
if (!$eid && $ekskuls) $eid = (int)$ekskuls[0]['id'];

$rombels = accessible_wali_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $members = []; $ekRows = []; $ekskul = null;
if ($eid) {
    foreach ($ekskuls as $e) if ((int)$e['id'] === $eid) { $ekskul = $e; break; }
    if (!$ekskul) { $eid = null; }
}
if ($rid) {
    $rombel  = assert_wali_rombel($user, $rid);
    $members = rombel_members($rid);
}
if ($eid) {
    $ekRows = ekskul_grades_for($eid, $sc['semester'], $sc['year_id']);
}

$readonly = is_view_only('extracurricular_grades', $user);
$predikats = ekskul_predikats();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rombel && $eid) {
    try {
        csrf_check();
        if (!can_edit('extracurricular_grades')) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        if ($readonly) throw new RuntimeException('Anda tidak diizinkan mengedit nilai ekskul.');
        $vP = $_POST['predikat'] ?? [];
        $vC = $_POST['catatan']  ?? [];
        $saved = 0; $cleared = 0;
        foreach ($members as $m) {
            $mid = (int)$m['id'];
            $pr  = (string)($vP[$mid] ?? '');
            $ct  = trim((string)($vC[$mid] ?? ''));
            if ($pr === '' && $ct === '') {
                // Hapus baris jika sebelumnya ada (clearing)
                if (isset($ekRows[$mid])) { ekskul_grade_delete($eid, $mid, $sc['semester'], $sc['year_id']); $cleared++; }
                continue;
            }
            if ($pr !== '' && !isset($predikats[$pr])) continue;
            ekskul_grade_upsert($eid, $mid, $sc['semester'], $sc['year_id'],
                $pr !== '' ? $pr : null, $ct !== '' ? $ct : null);
            $saved++;
        }
        audit('save_ekskul_grades', "ekskul:$eid/rombel:$rid",
            ['sem'=>$sc['semester'],'year'=>$sc['year_id'],'saved'=>$saved,'cleared'=>$cleared]);
        flash('success', "Nilai ekskul: $saved tersimpan, $cleared dibersihkan.");
        redirect("extracurricular_grades.php?ekskul_id=$eid&rombel_id=$rid");
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$page_title = 'Nilai Ekstrakurikuler';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Filter</h3>
    <span class="text-sm text-muted">TA <?= esc($sc['year']) ?> · <?= esc(ucfirst($sc['semester'])) ?> <em>(PTS/PAS tidak berlaku untuk ekskul)</em></span>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="gap: var(--sp-3); align-items:end">
      <div class="field" style="flex:1; min-width:240px">
        <label class="label">Ekstrakurikuler</label>
        <select name="ekskul_id" class="select" onchange="this.form.submit()">
          <?php if (!$ekskuls): ?><option value="">— Belum ada ekskul —</option><?php endif; ?>
          <?php foreach ($ekskuls as $e): ?>
            <option value="<?= (int)$e['id'] ?>" <?= $eid==$e['id']?'selected':'' ?>><?= esc($e['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:1; min-width:240px">
        <label class="label">Rombel (sumber daftar siswa)</label>
        <select name="rombel_id" class="select" onchange="this.form.submit()">
          <?php if (!$rombels): ?><option value="">— Tidak ada rombel terjangkau —</option><?php endif; ?>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rid==$r['id']?'selected':'' ?>>
              <?= esc($r['jenjang'] . ' · ' . $r['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if (!$ekskul || !$rombel): ?>
  <div class="empty">Pilih ekskul &amp; rombel untuk mulai mengisi nilai.</div>
<?php else: ?>

<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="card-header">
    <h3 class="card-title">
      <?= esc($ekskul['nama']) ?> · <?= esc($rombel['jenjang'] . ' ' . $rombel['nama']) ?>
    </h3>
    <span class="badge <?= $readonly?'badge-warning':'badge-success' ?>"><?= $readonly?'Read-only':'Editable' ?></span>
  </div>
  <div class="table-wrap">
    <table class="t">
      <thead>
        <tr>
          <th style="width:36px">#</th>
          <th style="min-width:200px">Siswa</th>
          <th style="width:200px">Predikat</th>
          <th>Catatan</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$members): ?><tr><td colspan="4"><div class="empty">Belum ada anggota rombel.</div></td></tr><?php endif; ?>
      <?php foreach ($members as $i => $m): $mid = (int)$m['id']; $cur = $ekRows[$mid] ?? null; ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td>
            <strong><?= esc($m['nama']) ?></strong>
            <div class="text-sm text-muted">NISN <?= esc($m['nisn']) ?></div>
          </td>
          <td>
            <select class="select" name="predikat[<?= $mid ?>]" <?= $readonly?'disabled':'' ?>>
              <option value="">— Tidak diikuti / kosong —</option>
              <?php foreach ($predikats as $code => $label): ?>
                <option value="<?= esc($code) ?>" <?= ($cur['predikat'] ?? '')===$code?'selected':'' ?>><?= esc($code . ' · ' . $label) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <input class="input" type="text" maxlength="500"
              name="catatan[<?= $mid ?>]"
              value="<?= esc($cur['catatan'] ?? '') ?>"
              placeholder="Catatan singkat (opsional)…"
              <?= $readonly?'disabled':'' ?>>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$readonly && $members): ?>
    <div class="card-body between">
      <span class="text-sm text-muted">Kosongkan predikat &amp; catatan untuk menghapus entri siswa tsb.</span>
      <button class="btn btn-primary" type="submit">Simpan Nilai Ekskul</button>
    </div>
  <?php endif; ?>
</form>

<div class="card">
  <div class="card-header"><h3 class="card-title">Legenda Predikat</h3></div>
  <div class="card-body row" style="gap: var(--sp-3); flex-wrap: wrap">
    <?php foreach ($predikats as $code => $label): ?>
      <div class="badge"><?= esc($code . ' · ' . $label) ?></div>
    <?php endforeach; ?>
  </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
