<?php
/**
 * Stage 9 — Parent profile + change password.
 *
 * When ?force=1 (or must_change_pw=1) the page becomes a mandatory password
 * change form, gated by the parent shell.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/parent_helpers.php';

$p       = require_parent();
$student = parent_student($p);
$sc      = active_scope();
$rombel  = parent_rombel_for_year((int)$student['id'], (int)$sc['year_id']);
$wali    = $rombel ? rombel_wali_user((int)$rombel['id']) : null;

$force = !empty($p['must_change_pw']) || (($_GET['force'] ?? '') === '1');
$err = null; $ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $action = $_POST['action'] ?? '';
        if ($action === 'change_pw') {
            $cur  = (string)($_POST['current_password'] ?? '');
            $new  = (string)($_POST['new_password']     ?? '');
            $new2 = (string)($_POST['confirm_password'] ?? '');
            if (!$force) {
                // Verify current password
                $st = db()->prepare("SELECT password_hash FROM parents_auth WHERE id = :i");
                $st->execute(['i' => $p['id']]);
                $hash = (string)$st->fetchColumn();
                if (!$hash || !password_verify($cur, $hash)) throw new RuntimeException('Password lama salah.');
            }
            if ($new !== $new2) throw new RuntimeException('Konfirmasi password tidak sama.');
            parent_change_password((int)$p['id'], $new);
            flash('success', 'Password berhasil diperbarui.');
            redirect('parent/home.php');
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$page_title  = 'Profil';
$current_nav = 'profil';
include __DIR__ . '/_layout.php';
?>

<?php if ($force): ?>
  <div class="p-card">
    <div class="p-locked-banner">Untuk keamanan, Anda wajib mengganti password sebelum melanjutkan.</div>
  </div>
<?php endif; ?>

<div class="p-card">
  <h3>Profil Anak</h3>
  <ul class="p-list">
    <li><span class="muted">Nama</span><strong><?= esc($student['nama']) ?></strong></li>
    <li><span class="muted">NISN / NIS</span><span><?= esc($student['nisn']) ?> / <?= esc($student['nis']) ?></span></li>
    <li><span class="muted">Jenjang / Tingkat</span><span><?= esc($student['jenjang']) ?> · <?= (int)$student['tingkat'] ?></span></li>
    <li><span class="muted">Rombel TA <?= esc($sc['year']) ?></span><span><?= esc($rombel['nama'] ?? '—') ?></span></li>
    <li><span class="muted">Wali Kelas</span><span><?= esc($wali['nama'] ?? '—') ?></span></li>
    <?php if (!empty($student['telp_ortu'])): ?>
      <li><span class="muted">Telp Ortu</span><span><?= esc($student['telp_ortu']) ?></span></li>
    <?php endif; ?>
  </ul>
</div>

<div class="p-card">
  <h3>Ganti Password</h3>
  <?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="change_pw">
    <?php if (!$force): ?>
      <div class="field"><label class="label">Password Saat Ini</label>
        <input class="input" type="password" name="current_password" required autocomplete="current-password"></div>
    <?php endif; ?>
    <div class="field"><label class="label">Password Baru</label>
      <input class="input" type="password" name="new_password" required minlength="8" autocomplete="new-password">
      <small class="muted">Minimal 8 karakter, gabungan huruf dan angka.</small></div>
    <div class="field"><label class="label">Konfirmasi Password Baru</label>
      <input class="input" type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-primary" type="submit" style="width:100%">Simpan Password Baru</button>
  </form>
</div>

<a class="p-link-card" href="<?= esc(url('parent/notes.php')) ?>">
  <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12H6l-2 4z"/></svg></div>
  <div class="tt"><div class="a">Catatan Wali &amp; Karakter</div><div class="b">Hanya tampil jika periode dipublikasi</div></div>
  <div class="arr">›</div>
</a>

<form method="post" action="<?= esc(url('parent/logout.php')) ?>" style="margin-top:.5rem">
  <?= csrf_field() ?>
  <button class="btn btn-ghost" type="submit" style="width:100%; color:#b91c1c; border-color:#fecaca;">Keluar dari Akun</button>
</form>

<?php include __DIR__ . '/_layout_end.php'; ?>
