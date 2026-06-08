<?php
/**
 * Stage 9 — Parent attendance (semester recap + daily log).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/parent_helpers.php';

$p       = require_parent();
$student = parent_student($p);
$sc      = active_scope();

$sem = in_array($_GET['sem'] ?? '', ['ganjil','genap'], true) ? $_GET['sem'] : $sc['semester'];
$rombel = parent_rombel_for_year((int)$student['id'], (int)$sc['year_id']);

$summary = $rombel
  ? parent_attendance_summary((int)$student['id'], (int)$rombel['id'], $sem, (int)$sc['year_id'])
  : ['h'=>0,'i'=>0,'s'=>0,'a'=>0,'total'=>0];
$log = $rombel
  ? parent_attendance_log((int)$student['id'], (int)$rombel['id'], $sem, (int)$sc['year_id'], 60)
  : [];

audit('parent_view_attendance', 'student:' . $student['id'], ['sem'=>$sem]);

$labels = ['H'=>'Hadir','I'=>'Izin','S'=>'Sakit','A'=>'Alpa'];
$cls    = ['H'=>'ok','I'=>'warn','S'=>'pill','A'=>'no'];

$page_title  = 'Kehadiran';
$current_nav = 'kehadiran';
include __DIR__ . '/_layout.php';
?>

<div class="p-card">
  <div class="p-period-tabs">
    <?php foreach (['ganjil','genap'] as $s): ?>
      <a class="tab <?= $s === $sem ? 'is-active' : '' ?>" href="<?= esc(url('parent/attendance.php?sem='.$s)) ?>">
        <span>Semester <?= esc(ucfirst($s)) ?></span><small>TA <?= esc($sc['year']) ?></small>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="p-stat-grid">
    <div class="p-stat h"><div class="num"><?= (int)$summary['h'] ?></div><div class="lab">Hadir</div></div>
    <div class="p-stat i"><div class="num"><?= (int)$summary['i'] ?></div><div class="lab">Izin</div></div>
    <div class="p-stat s"><div class="num"><?= (int)$summary['s'] ?></div><div class="lab">Sakit</div></div>
    <div class="p-stat a"><div class="num"><?= (int)$summary['a'] ?></div><div class="lab">Alpa</div></div>
  </div>
  <?php
    $total = (int)$summary['total'];
    $pct = $total > 0 ? round(((int)$summary['h']) / $total * 100) : 0;
  ?>
  <div class="muted" style="margin-top:.5rem">Total hari: <strong><?= $total ?></strong> · Persentase Hadir: <strong><?= $pct ?>%</strong></div>
</div>

<div class="p-card">
  <h3>Riwayat Harian</h3>
  <?php if (!$log): ?>
    <div class="p-empty"><div class="icon">📅</div><div class="muted">Belum ada catatan kehadiran.</div></div>
  <?php else: ?>
    <ul class="p-list">
      <?php foreach ($log as $row):
        $st = strtoupper((string)$row['status']);
        $pillCls = $cls[$st] ?? 'pill';
        $lbl = $labels[$st] ?? $st;
      ?>
        <li>
          <div>
            <div style="font-weight:600;"><?= esc(date('D, d M Y', strtotime($row['tanggal']))) ?></div>
            <?php if (!empty($row['keterangan'])): ?>
              <div class="muted" style="font-size:.75rem">“<?= esc($row['keterangan']) ?>”</div>
            <?php endif; ?>
          </div>
          <span class="pill <?= esc($pillCls) ?>"><?= esc($lbl) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
