<?php
/**
 * Stage 9 — Parent shell footer (bottom nav + closing tags).
 */
declare(strict_types=1);
$current_nav = $current_nav ?? 'home';
$nav = [
  ['key'=>'home',      'label'=>'Beranda',   'href'=>'parent/home.php',       'icon'=>'<path d="M3 11l9-8 9 8v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/>'],
  ['key'=>'nilai',     'label'=>'Nilai',     'href'=>'parent/grades.php',     'icon'=>'<path d="M5 4h11l3 3v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M8 12h8M8 16h6"/>'],
  ['key'=>'rapor',     'label'=>'Rapor',     'href'=>'parent/rapor.php',      'icon'=>'<path d="M6 3h9l3 3v15H6z"/><path d="M9 9h6M9 13h6M9 17h4"/>'],
  ['key'=>'kehadiran', 'label'=>'Hadir',     'href'=>'parent/attendance.php', 'icon'=>'<path d="M4 4h16v16H4z"/><path d="M4 9h16M9 4v16"/>'],
  ['key'=>'profil',    'label'=>'Profil',    'href'=>'parent/profile.php',    'icon'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
];
?>
  </div><!-- /.parent-shell -->

  <nav class="p-bottom-nav no-print" aria-label="Menu utama">
    <?php foreach ($nav as $n): $active = $current_nav === $n['key']; ?>
      <a href="<?= esc(url($n['href'])) ?>" class="<?= $active ? 'is-active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><?= $n['icon'] ?></svg>
        <span><?= esc($n['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

</body>
</html>
