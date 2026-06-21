<?php
/**
 * Stage 10 — Role-aware dashboard.
 *
 * Widgets:
 *   - Counter cards (admin/administrator: global; kepsek: jenjang scope; guru: my rombel)
 *   - Kepsek: Pending review queue + published count
 *   - Administrator: Today's audit-by-action breakdown + link to full log
 *   - All: Recent activity feed
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/audit_helpers.php';

$me  = require_staff();
$pdo = db();
$sc  = active_scope();
$role = $me['role'] ?? '';

$counts = dashboard_counters_for($me, (int)$sc['year_id']);

// Recent activity — administrator sees all, others see filtered to themselves.
if ($role === 'administrator') {
    $recent = $pdo->query("SELECT action, target, user_label, created_at FROM audit_log ORDER BY id DESC LIMIT 10")->fetchAll();
} else {
    $st = $pdo->prepare("SELECT action, target, user_label, created_at FROM audit_log WHERE user_id = :u ORDER BY id DESC LIMIT 10");
    $st->execute(['u' => $me['id']]);
    $recent = $st->fetchAll();
}

$kepsekPending = ($role === 'kepsek') ? notif_pending_review_list($me['jenjang'] ?? null, 6, (int)$sc['year_id']) : [];
$todayByAction = ($role === 'administrator') ? audit_today_by_action(8) : [];

$page_title = 'Dashboard';
require __DIR__ . '/../includes/header.php';

$quickLinks = [];
if ($role === 'guru') {
    $quickLinks = [
        ['label' => 'Input Absensi', 'href' => url('attendance.php')],
        ['label' => 'Nilai Harian', 'href' => url('grades_daily.php')],
        ['label' => 'Nilai Akhir', 'href' => url('final_grades.php')],
        ['label' => 'Catatan Wali', 'href' => url('wali_notes.php')],
        ['label' => 'Penilaian Karakter', 'href' => url('character_eval.php')],
    ];
} elseif ($role === 'kepsek') {
    $quickLinks = [
        ['label' => 'Verifikasi Rapor', 'href' => url('final_grades_review.php')],
        ['label' => 'Rapor Siswa', 'href' => url('rapor.php')],
        ['label' => 'Rekap Absensi', 'href' => url('attendance_recap.php')],
        ['label' => 'Catatan Wali', 'href' => url('wali_notes.php')],
        ['label' => 'Penilaian Karakter', 'href' => url('character_eval.php')],
    ];
} elseif ($role !== 'administrator') {
    $quickLinks = [
        ['label' => 'Rombel', 'href' => url('leger.php')],
        ['label' => 'Absensi', 'href' => url('attendance.php')],
        ['label' => 'Nilai Akhir', 'href' => url('final_grades.php')],
    ];
}
?>

<!-- ============== Counters ============== -->
<div class="row" style="gap: var(--sp-4); flex-wrap:wrap">
  <?php
    $cards = [];
    if ($role === 'kepsek') {
        $cards = [
            ['Siswa ' . esc($me['jenjang'] ?? '—'), $counts['siswa_jenjang'] ?? 0, 'badge-primary'],
            ['Rombel ' . esc($me['jenjang'] ?? '—'), $counts['rombel_jenjang'] ?? 0, 'badge-info'],
            ['Menunggu Verifikasi', $counts['pending_review'] ?? 0, ($counts['pending_review'] ?? 0) > 0 ? 'badge-warning' : 'badge-success'],
            ['Sudah Dipublikasi', $counts['published'] ?? 0, 'badge-success'],
        ];
    } elseif ($role === 'guru') {
        $cards = [
            ['Rombel Saya', $counts['my_rombel'] ?? 0, 'badge-primary'],
            ['Siswa Saya', $counts['my_students'] ?? 0, 'badge-info'],
            ['Mata Pelajaran Saya', $counts['my_subjects'] ?? 0, 'badge-warning'],
            ['Draft Nilai', $counts['my_draft_grades'] ?? 0, ($counts['my_draft_grades'] ?? 0) > 0 ? 'badge-danger' : 'badge-success'],
        ];
    } elseif ($role === 'administrator') {
        $cards = [
            ['Siswa Aktif',   $counts['siswa'],  'badge-primary'],
            ['Guru',           $counts['guru'],   'badge-success'],
            ['Rombel',         $counts['rombel'], 'badge-info'],
            ['Mata Pelajaran', $counts['mapel'],  'badge-warning'],
        ];
    } else {
        $cards = [
            ['Siswa Aktif',   $counts['siswa'],  'badge-primary'],
            ['Rombel',         $counts['rombel'], 'badge-info'],
            ['Mata Pelajaran', $counts['mapel'],  'badge-warning'],
            ['Guru',           $counts['guru'],   'badge-success'],
        ];
    }
    foreach ($cards as $c): ?>
    <div class="card" style="flex: 1; min-width: 200px; margin: 0">
      <div class="card-body">
        <div class="text-muted text-sm"><?= esc($c[0]) ?></div>
        <div style="font-size: var(--fs-30); font-weight: 700; margin-top: 4px"><?= number_format((int)$c[1]) ?></div>
        <span class="badge <?= esc($c[2]) ?> mt-2"><?= $role === 'kepsek' ? 'jenjang ' . esc($me['jenjang'] ?? '') : 'global' ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mt-4">
  <div class="card-header"><h3 class="card-title">Selamat datang, <?= esc($me['nama']) ?></h3></div>
  <div class="card-body">
    <p class="text-muted">Anda masuk sebagai <strong><?= esc(ucfirst($role)) ?></strong>. Pilih Tahun Ajaran, Semester, dan Periode (PTS/PAS) di topbar untuk mengubah scope global.</p>
    <?php if ($role === 'guru'): ?>
      <p class="text-sm">Dashboard ini menampilkan rombel, siswa, dan mapel yang Anda ampu; gunakan menu di bawah untuk langsung ke input absensi, nilai, atau catatan wali.</p>
    <?php elseif ($role === 'kepsek'): ?>
      <p class="text-sm">Gunakan ringkasan jenjang dan daftar verifikasi rapor untuk memantau status nilai dan publikasi.</p>
    <?php elseif ($role !== 'administrator'): ?>
      <p class="text-sm">Tampilan ini difokuskan ke tugas harian Anda. Hanya Administrator yang melihat audit dan statistik global penuh.</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($quickLinks): ?>
<div class="card mt-4">
  <div class="card-header"><h3 class="card-title">Tindakan Cepat</h3></div>
  <div class="card-body">
    <div class="row" style="gap: .75rem; flex-wrap: wrap;">
      <?php foreach ($quickLinks as $link): ?>
        <a class="btn btn-ghost" href="<?= esc($link['href']) ?>"><?= esc($link['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ============== Kepsek: pending review widget ============== -->
<?php if ($role === 'kepsek'): ?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">🔔 Rapor Menunggu Verifikasi</h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('final_grades_review.php')) ?>">Buka Verifikasi →</a>
  </div>
  <div class="table-wrap">
    <table class="t">
      <thead><tr><th>Rombel</th><th>Mata Pelajaran</th><th>Periode</th><th style="text-align:right">Siswa</th><th>Update Terakhir</th></tr></thead>
      <tbody>
        <?php if (!$kepsekPending): ?>
          <tr><td colspan="5"><div class="empty">🎉 Tidak ada rapor menunggu verifikasi.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($kepsekPending as $n): ?>
          <tr>
            <td><?= esc($n['jenjang'] . ' ' . $n['rombel_nama']) ?></td>
            <td><strong><?= esc($n['subj_nama']) ?></strong> <span class="text-xs text-muted">· <?= esc($n['subj_kode']) ?></span></td>
            <td><span class="badge"><?= esc(ucfirst($n['semester']) . ' ' . $n['period_kind']) ?></span></td>
            <td style="text-align:right"><strong><?= (int)$n['n_rows'] ?></strong></td>
            <td class="text-sm text-muted"><?= esc(date('d M Y H:i', strtotime($n['last_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ============== Administrator: today's activity breakdown ============== -->
<?php if ($role === 'administrator' && $todayByAction): ?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">📊 Aktivitas Hari Ini per Aksi</h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/audit_log.php?date_from=' . date('Y-m-d'))) ?>">Lihat detail di Audit Log →</a>
  </div>
  <div class="card-body">
    <div class="row" style="gap:.4rem; flex-wrap:wrap">
      <?php foreach ($todayByAction as $a): ?>
        <a class="badge badge-primary" style="text-decoration:none; padding:.4rem .65rem; font-size:.82rem"
           href="<?= esc(url('admin/audit_log.php?action=' . urlencode($a['action']) . '&date_from=' . date('Y-m-d'))) ?>">
          <?= esc($a['action']) ?> · <strong><?= (int)$a['n'] ?></strong>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ============== Recent activity ============== -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><?= $role === 'administrator' ? 'Aktivitas Terbaru (semua pengguna)' : 'Aktivitas Saya Terbaru' ?></h3>
    <?php if ($role === 'administrator'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/audit_log.php')) ?>">Buka Audit Log →</a>
    <?php endif; ?>
  </div>
  <div class="table-wrap">
    <table class="t">
      <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Target</th></tr></thead>
      <tbody>
      <?php if (!$recent): ?><tr><td colspan="4"><div class="empty">Belum ada aktivitas.</div></td></tr><?php endif; ?>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td class="text-sm text-muted"><?= esc($r['created_at']) ?></td>
          <td><?= esc($r['user_label'] ?? '—') ?></td>
          <td><span class="badge"><?= esc($r['action']) ?></span></td>
          <td class="text-sm" style="word-break:break-all"><?= esc($r['target'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
