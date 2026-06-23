<?php
declare(strict_types=1);

require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/audit_helpers.php';

$__user   = current_user();   // null if parent context (parent area renders its own shell)
$__scope  = active_scope();
$__years  = db()->query("SELECT id, label, is_active FROM academic_years ORDER BY label DESC")->fetchAll();

// Per-semester lock state (Ganjil & Genap) of the active TA. PTS/PAS is no longer lockable.
$__lockStates  = year_lock_states((int)$__scope['year_id']);
$__ganjilLock  = !empty($__lockStates['ganjil']);
$__genapLock   = !empty($__lockStates['genap']);
$__locked      = $__scope['semester'] === 'ganjil' ? $__ganjilLock : $__genapLock;
$__title  = $page_title ?? 'Dashboard';

// Determine current path for sidebar active state
$__here = $_SERVER['PHP_SELF'] ?? '';
function nav_active(string $needle): string { global $__here; return str_contains($__here, $needle) ? 'is-active' : ''; }

$__role = $__user['role'] ?? '';

// Stage 10 — bell notifications (kepsek shows own jenjang only; admin/administrator see all).
$__notif_count = 0; $__notif_list = [];
if (in_array($__role, ['kepsek','administrator','admin'], true)) {
    $__nf_jenjang = ($__role === 'kepsek') ? ($__user['jenjang'] ?? null) : null;
    $__notif_count = notif_pending_review_count($__nf_jenjang, (int)$__scope['year_id']);
    $__notif_list  = $__notif_count > 0 ? notif_pending_review_list($__nf_jenjang, 8, (int)$__scope['year_id']) : [];
}
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($__title) ?> · <?= esc(cfg()['app_name']) ?></title>
<link rel="stylesheet" href="<?= esc(url('../assets/css/design-system.css')) ?>">
<link rel="icon" type="image/x-icon" href="<?= esc(url('../assets/img/logo.png')) ?>">
<script defer src="<?= esc(url('../assets/js/app.js')) ?>"></script>
</head>
<body class="app">
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="brand-logo" aria-hidden="true">
      <img src="<?= esc(url('../assets/img/logo.png')) ?>" width="30px">
    </div>
    <div>
      <div class="brand-title"><?= esc(cfg()['app_name']) ?></div>
      <div class="brand-sub">TK · SD · SMP · SMA</div>
    </div>
  </div>
  <!-- Role + scope pill in sidebar -->
  <?php if ($__user): ?>
  <div style="padding:.25rem var(--sp-2) var(--sp-3); display:flex; align-items:center; gap:.4rem; flex-wrap:wrap;">
    <span style="display:inline-flex;align-items:center;gap:.3rem;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:var(--c-primary-50);color:var(--c-primary-800);border:1px solid var(--c-primary-100)">
      <?= esc(ucfirst($__user['role'])) ?>
      <?= !empty($__user['jenjang']) ? '· '.esc($__user['jenjang']) : '' ?>
    </span>
    <?php if ($__locked): ?>
    <span style="display:inline-flex;align-items:center;gap:.3rem;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:hsl(0 86% 97%);color:var(--c-danger-700);border:1px solid hsl(0 86% 90%)">
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="5" y="11" width="14" height="11" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
      Terkunci
    </span>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <nav class="nav">
    <a class="nav-item <?= nav_active('/dashboard.php') ?>" href="<?= esc(url('dashboard.php')) ?>">
      <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
      Dashboard
    </a>

    <?php
      // Inline SVG icon factory (24x24 stroke icons). Group headings & semester
      // lock controls intentionally have no icon per spec.
      $icon = function(string $name): string {
        $paths = [
          'profile'   => '<path d="M3 21V8l9-5 9 5v13"/><path d="M9 21v-6h6v6"/>',
          'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
          'target'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
          'book'      => '<path d="M4 5a2 2 0 0 1 2-2h13v18H6a2 2 0 0 1-2-2z"/><path d="M4 19a2 2 0 0 1 2-2h13"/>',
          'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 21c0-3.5 3-6 7-6s7 2.5 7 6"/><circle cx="17" cy="9" r="2.5"/><path d="M22 20c0-2.6-2-4.6-5-4.6"/>',
          'student'   => '<path d="M3 10l9-4 9 4-9 4-9-4z"/><path d="M7 12v4c0 1.5 2.5 3 5 3s5-1.5 5-3v-4"/><path d="M21 10v6"/>',
          'key'       => '<circle cx="8" cy="14" r="4"/><path d="M11 14h11l-3 3M19 14v4"/>',
          'star'      => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
          'trophy'    => '<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M5 6H3v2a3 3 0 0 0 3 3M19 6h2v2a3 3 0 0 1-3 3"/><path d="M9 13h6v3H9z"/><path d="M7 21h10M10 18v3M14 18v3"/>',
          'group'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/>',
          'teacher'   => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M7 10h10M7 14h7"/>',
          'topic'     => '<path d="M5 4h11l3 3v13H5z"/><path d="M14 4v4h4"/><path d="M8 12h8M8 16h6"/>',
          'check'     => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 12l3 3 5-6"/>',
          'list'      => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
          'edit'      => '<path d="M5 19h4l10-10-4-4L5 15z"/><path d="M14 6l4 4"/>',
          'medal'     => '<circle cx="12" cy="15" r="5"/><path d="M9 3l3 7 3-7"/><path d="M10 13l-1 1M14 13l1 1"/>',
          'verify'    => '<path d="M12 3l8 4v6c0 4.5-3.5 7.5-8 8-4.5-.5-8-3.5-8-8V7z"/><path d="M9 12l2 2 4-4"/>',
          'note'      => '<path d="M5 4h11l3 3v13H5z"/><path d="M9 13h6M9 17h4"/>',
          'shield'    => '<path d="M12 3l8 4v6c0 4.5-3.5 7.5-8 8-4.5-.5-8-3.5-8-8V7z"/>',
          'pulse'     => '<path d="M3 12h4l2-6 4 12 2-6h6"/>',
          'football'  => '<circle cx="12" cy="12" r="9"/><path d="M12 4v6l5 3-2 5-6-2-3-5 3-4 3 1z"/>',
          'leger'     => '<path d="M5 4h14v16H5z"/><path d="M9 4v16M5 9h14M5 14h14"/>',
          'rapor'     => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5"/><path d="M9 13h7M9 17h5M9 9h3"/>',
          'template'  => '<rect x="3" y="3" width="18" height="6" rx="1"/><rect x="3" y="11" width="8" height="10" rx="1"/><rect x="13" y="11" width="8" height="10" rx="1"/>',
          'audit'     => '<circle cx="11" cy="11" r="6"/><path d="M20 20l-4.5-4.5"/>',
        ];
        $p = $paths[$name] ?? $paths['list'];
        return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
      };

      // Group items by section so we can hide a group entirely when empty.
      // Each item: [feature, href, label, icon-key]
      $__nav = [
        'Master Data' => [
          ['school_profile',    'admin/school_profile.php',    'Profil Sekolah',    'profile'],
          ['academic_years',    'admin/academic_years.php',    'Tahun Ajaran',      'calendar'],
          ['predikat',          'admin/predikat.php',          'Predikat',          'target'],
          ['subject_categories','admin/subject_categories.php','Kategori Mapel',    'list'],
          ['subjects',          'admin/subjects.php',          'Mata Pelajaran',    'book'],
          ['electives',         'admin/electives.php',         'Mapel Pilihan',    'star'],
          ['character_aspects', 'admin/character_aspects.php','Aspek Karakter',   'shield'],
          ['teachers',          'admin/teachers.php',          'Guru',              'users'],
          ['students',          'admin/students.php',          'Siswa',             'student'],
          ['users',             'admin/users.php',             'Login Pegawai',     'key'],
          
        ],
        'KBM' => [
          ['rombel',          'admin/rombel.php',          'Rombel & Anggota', 'group'],
          ['rombel_teachers', 'admin/rombel_teachers.php', 'Guru Pengampu',    'teacher'],
          ['subject_topics',  'admin/subject_topics.php',  'Subjek Penilaian', 'topic'],
        ],
        'Penilaian' => [
          ['attendance',         'attendance.php',         'Absensi Harian',      'check'],
          ['attendance_recap',   'attendance_recap.php',   'Rekap Absensi',       'list'],
          ['grades_daily',       'grades_daily.php',       'Penilaian Harian',    'edit'],
          ['grades_topic_recap', 'grades_topic_recap.php', 'Rekap Nilai Harian',  'list'],
          ['elective_assignment','elective_assignment.php','Penempatan Mapel Pilihan','star'],
          ['final_grades',       'final_grades.php',       'Nilai Akhir PTS/PAS', 'medal'],
          ['final_grades_review','final_grades_review.php','Verifikasi Nilai',    'verify'],
        ],
        'Catatan & Karakter' => [
          ['character_eval',         'character_eval.php',         'Character Evaluation', 'shield'],
          ['general_eval',           'general_eval.php',           'General Evaluation',   'pulse'],
        ],
        'Rapor & Leger' => [
          ['leger', 'leger.php', 'Leger Nilai', 'leger'],
          ['rapor', 'rapor.php', 'Rapor Siswa', 'rapor'],
        ],
        'Lainnya' => [
          ['profile',          'profile.php',               'Profil Saya',      'profile'],
          ['report_templates', 'admin/report_templates.php', 'Template Rapor', 'template'],
          ['audit_log',        'admin/audit_log.php',        'Audit Log',      'audit'],
        ],
      ];
      foreach ($__nav as $group => $items):
        $visible = array_values(array_filter($items, fn($it) => can_view($it[0], $__user)));
        if (!$visible) continue;
    ?>
      <div class="nav-group"><?= esc($group) ?></div>
      <?php foreach ($visible as $it):
        [$feat, $href, $label, $ikey] = $it;
        $needle = basename($href);
      ?>
        <a class="nav-item <?= nav_active($needle) ?>" href="<?= esc(url($href)) ?>">
          <?= $icon($ikey) ?>
          <span class="nav-label"><?= esc($label) ?></span>
          <?php if (is_view_only($feat, $__user)): ?>
            <span class="badge badge-info" style="margin-left:.4rem; font-size:10px">view</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <form method="post" action="<?= esc(url('logout.php')) ?>" style="margin:0">
      <?= csrf_field() ?>
      <button class="btn btn-ghost btn-sm" style="width:100%">Logout</button>
    </form>
  </div>
</aside>

<main class="main">
  <header class="topbar">
    <button class="icon-btn only-mobile" id="btnMenu" aria-label="Menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <div class="page-title"><?= esc($__title) ?></div>

    <form class="scope-switcher" method="post" action="<?= esc(url('scope_switch.php')) ?>" id="scopeForm">
      <?= csrf_field() ?>
      <input type="hidden" name="period" value="<?= esc($__scope['period']) ?>" id="scopePeriodInput">
      <select name="year_id" class="select select-sm" onchange="document.getElementById('scopeForm').submit()">
        <?php foreach ($__years as $y): ?>
          <option value="<?= (int)$y['id'] ?>" <?= $y['id']==$__scope['year_id']?'selected':'' ?>>TA <?= esc($y['label']) ?><?= $y['is_active']?' · aktif':'' ?></option>
        <?php endforeach; ?>
      </select>
      <select name="semester" class="select select-sm" onchange="document.getElementById('scopeForm').submit()" title="Pilih Semester">
        <option value="ganjil" <?= $__scope['semester']==='ganjil'?'selected':'' ?>>Ganjil<?= $__ganjilLock ? ' [kunci]' : '' ?></option>
        <option value="genap"  <?= $__scope['semester']==='genap' ?'selected':'' ?>>Genap<?= $__genapLock  ? ' [kunci]' : '' ?></option>
      </select>
      <div class="seg" role="group" aria-label="Periode">
        <button type="button" class="seg-btn <?= $__scope['period']==='PTS'?'is-on':'' ?>"
                onclick="document.getElementById('scopePeriodInput').value='PTS'; document.getElementById('scopeForm').submit();">PTS</button>
        <button type="button" class="seg-btn <?= $__scope['period']==='PAS'?'is-on':'' ?>"
                onclick="document.getElementById('scopePeriodInput').value='PAS'; document.getElementById('scopeForm').submit();">PAS</button>
      </div>
    </form>


    <?php if (in_array($__role, ['kepsek','administrator','admin'], true)): ?>
      <div class="bell-wrap">
        <button type="button" class="bell-btn" id="btnBell" aria-label="Notifikasi" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 19a2 2 0 0 0 4 0"/></svg>
          <?php if ($__notif_count > 0): ?>
            <span class="bell-dot"><?= $__notif_count > 99 ? '99+' : (int)$__notif_count ?></span>
          <?php endif; ?>
        </button>
        <div class="bell-pop" id="bellPop" role="menu" aria-hidden="true">
          <div class="bell-head">
            <strong>Notifikasi</strong>
            <span class="text-xs text-muted"><?= (int)$__notif_count ?> menunggu verifikasi</span>
          </div>
          <?php if (!$__notif_list): ?>
            <div class="bell-empty">🎉 Tidak ada yang menunggu verifikasi.</div>
          <?php else: ?>
            <ul class="bell-list">
              <?php foreach ($__notif_list as $n): ?>
                <li>
                  <a href="<?= esc(url('final_grades_review.php?rombel_id=' . (int)$n['rombel_id'] . '&subject_id=' . (int)$n['subject_id'])) ?>">
                    <div class="t"><?= esc($n['subj_kode']) ?> · <?= esc($n['subj_nama']) ?></div>
                    <div class="b">
                      <?= esc($n['jenjang'] . ' ' . $n['rombel_nama']) ?> ·
                      <?= esc(ucfirst($n['semester']) . ' ' . $n['period_kind']) ?> ·
                      <strong><?= (int)$n['n_rows'] ?> siswa</strong>
                    </div>
                    <div class="t-time"><?= esc(date('d M H:i', strtotime($n['last_at']))) ?></div>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
            <a class="bell-foot" href="<?= esc(url('final_grades_review.php')) ?>">Buka semua →</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="topbar-user">
      <div class="avatar"><?= esc(mb_substr($__user['nama'] ?? '?', 0, 1)) ?></div>
      <div class="text-sm">
        <div><strong><?= esc($__user['nama'] ?? '—') ?></strong></div>
        <div class="text-muted"><?= esc(ucfirst((string)$__role)) ?><?= !empty($__user['jenjang']) ? ' · ' . esc($__user['jenjang']) : '' ?></div>
      </div>
    </div>
  </header>

  <div class="content">
    <?php foreach (take_flashes() as $f): ?>
      <div class="alert alert-<?= esc($f['type']) ?>"><?= esc($f['msg']) ?></div>
    <?php endforeach; ?>
