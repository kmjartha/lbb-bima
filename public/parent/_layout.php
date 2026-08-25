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

$sc_label = parent_scope_label(active_scope());
$studentName = (string)($student['nama'] ?? 'Orang Tua');
$studentMetaParts = array_filter([
    (string)($student['jenjang'] ?? ''),
    ((string)($student['tingkat'] ?? '') !== '') ? ('Kelas ' . $student['tingkat']) : '',
]);
$studentMeta = $studentMetaParts ? implode(' · ', $studentMetaParts) : 'Data belum lengkap';
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1568d8">
<title><?= esc($page_title) ?> · <?= esc(cfg()['app_name']) ?></title>
<link rel="stylesheet" href="<?= esc(url('../assets/css/design-system.css')) ?>">
<link rel="stylesheet" href="<?= esc(url('../assets/css/parent-theme.css')) ?>">
<link rel="icon" type="image/x-icon" href="<?= esc(url('../assets/img/logo.png')) ?>">
</head>
<body>
<div class="parent-shell">

  <?php
    $bm_nav = [
      ['key'=>'home',      'label'=>'Beranda',   'href'=>'parent/home.php',       'icon'=>'<path d="M3 11l9-8 9 8v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/>'],
      ['key'=>'nilai',     'label'=>'Nilai',     'href'=>'parent/grades.php',     'icon'=>'<path d="M5 4h11l3 3v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M8 12h8M8 16h6"/>'],
      ['key'=>'rapor',     'label'=>'Rapor',     'href'=>'parent/rapor.php',      'icon'=>'<path d="M6 3h9l3 3v15H6z"/><path d="M9 9h6M9 13h6M9 17h4"/>'],
      ['key'=>'kehadiran', 'label'=>'Hadir',     'href'=>'parent/attendance.php', 'icon'=>'<path d="M4 4h16v16H4z"/><path d="M4 9h16M9 4v16"/>'],
      ['key'=>'profil',    'label'=>'Profil',    'href'=>'parent/profile.php',    'icon'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
    ];
  ?>
  <nav class="p-side-nav no-print" aria-label="Menu utama (desktop)">
    <div class="brand">
      <div class="auth-logo"><img src="..\..\assets\img\logo.png" width="50px"></div>
      <span><?= esc(cfg()['app_name']) ?></span>
    </div>
    <?php foreach ($bm_nav as $n): $active = $current_nav === $n['key']; ?>
      <a href="<?= esc(url($n['href'])) ?>" class="<?= $active ? 'is-active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><?= $n['icon'] ?></svg>
        <span><?= esc($n['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="parent-topbar">
    <div class="auth-logo"><img src="..\..\assets\img\logo.png" width="50px"></div>
    <div class="who" style="flex:1">
      <small>Halo, Orang Tua</small>
      <strong><?= esc($studentName) ?></strong>
      <small><?= esc($studentMeta) ?> · <?= esc($sc_label) ?></small>
    </div>
    <form method="post" action="<?= esc(url('parent/logout.php')) ?>" style="margin:0">
      <?= csrf_field() ?>
      <button class="btn btn-ghost btn-sm" type="submit" title="Keluar">Keluar</button>
    </form>
  </div>

  <?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= esc($f['type']) ?>"><?= esc($f['msg']) ?></div>
  <?php endforeach; ?>
