<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

if (current_user()) redirect('dashboard.php');

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $niy = trim((string)($_POST['niy'] ?? ''));
        $pw  = (string)($_POST['password'] ?? '');
        $rem = !empty($_POST['remember']);
        if ($niy === '' || $pw === '') throw new RuntimeException('NIY dan password wajib diisi.');
        staff_login($niy, $pw, $rem);
        redirect('dashboard.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
}
?><!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login · <?= esc(cfg()['app_name']) ?></title>
<link rel="stylesheet" href="<?= esc(url('../assets/css/design-system.css')) ?>">
</head><body><div class="auth-wrap"><div class="auth-card">
  <div class="auth-logo"><img src="..\assets\img\logo.png" width="50px"></div>
  <h2>Masuk</h2>
  <p class="text-muted mb-4">Gunakan NIY pegawai. Default password = 4 digit terakhir NIY.</p>
  <?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label class="label">NIY</label><input class="input" name="niy" autofocus required></div>
    <div class="field"><label class="label">Password</label><input class="input" type="password" name="password" required></div>
    <label class="checkbox-row mb-4"><input type="checkbox" name="remember" value="1"> Ingat saya 30 hari</label>
    <button class="btn btn-primary" type="submit" style="width:100%">Masuk</button>
  </form>
  <div class="text-sm text-muted mt-4" style="text-align:center">
    Akses ortu? <a href="<?= esc(url('parent/login.php')) ?>">Login Orang Tua</a>
  </div>
</div></div></body></html>