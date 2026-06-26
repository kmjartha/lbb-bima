<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
if (current_parent()) redirect('parent/home.php');

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $nis = trim((string)($_POST['nis'] ?? $_POST['nisn'] ?? ''));
        $pw  = (string)($_POST['password'] ?? '');
        if ($nis === '' || $pw === '') throw new RuntimeException('NIS dan password wajib diisi.');
        parent_login($nis, $pw, !empty($_POST['remember']));
        // Flag to reset sidebar scroll on fresh login
        $_SESSION['_fresh_login'] = true;
        redirect('parent/home.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
}
?><!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#1568d8">
<title>Login Orang Tua · <?= esc(cfg()['app_name']) ?></title>
<link rel="stylesheet" href="<?= esc(url('../assets/css/design-system.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../assets/css/parent-theme.css')) ?>">
</head><body><div class="auth-wrap bm"><div class="auth-card">
  <div class="auth-logo">⭐</div>
  <h2>Portal Orang Tua</h2>
  <p class="text-muted mb-4"><?= esc(cfg()['app_name']) ?> · Gunakan NIS anak. Default password = tanggal lahir <strong>ddmmyyyy</strong>.</p>
  <?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label class="label">NIS Anak</label><input class="input" name="nis" autofocus required></div>
    <div class="field"><label class="label">Password</label><input class="input" type="password" name="password" required></div>
    <label class="checkbox-row mb-4"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
    <button class="btn btn-primary" type="submit" style="width:100%">Masuk</button>
  </form>
  <div class="text-sm text-muted mt-4" style="text-align:center">
    Pegawai? <a href="<?= esc(url('login.php')) ?>">Login Pegawai</a>
  </div>
</div></div></body></html>
