<?php
/**
 * Stage 9 — Parent shell partial.
 *
 * Wraps every parent page with the mobile-first topbar + bottom nav.
 * Caller must:
 *   - require_once includes/guard.php
 *   - $p = require_parent();
 *   - $student = parent_student($p);
 *   - $page_title = 'Beranda';
 *   - $current_nav = 'home';      // home|rapor|nilai|kehadiran|profil
 *   - include this file (renders header) at the start of <body>
 *   - then echo content
 *   - include _layout_end.php at the bottom
 */
declare(strict_types=1);

if (!isset($p) || !isset($student)) {
    require_once __DIR__ . '/../../includes/guard.php';
    $p = require_parent();
    $student = parent_student($p);
}
$page_title = $page_title ?? 'Parent Portal';
$current_nav = $current_nav ?? 'home';

// Force change-pw guard (except when already on profile page).
if (!empty($p['must_change_pw']) && ($current_nav !== 'profil')) {
    redirect('parent/profile.php?force=1');
}

$sc_label = parent_scope_label(active_scope());
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1d4ed8">
<title><?= esc($page_title) ?> · <?= esc(cfg()['app_name']) ?></title>
<link rel="stylesheet" href="<?= esc(url('assets/css/design-system.css')) ?>">
</head>
<body>
<div class="parent-shell">

  <div class="parent-topbar">
    <div class="who">
      <small>Halo, Orang Tua</small>
      <strong><?= esc($student['nama']) ?></strong>
      <small><?= esc($student['jenjang'] . ' · Kelas ' . $student['tingkat']) ?> · <?= esc($sc_label) ?></small>
    </div>
    <form method="post" action="<?= esc(url('parent/logout.php')) ?>" style="margin:0">
      <?= csrf_field() ?>
      <button class="btn btn-ghost btn-sm" type="submit" title="Keluar">Keluar</button>
    </form>
  </div>

  <?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= esc($f['type']) ?>"><?= esc($f['msg']) ?></div>
  <?php endforeach; ?>
