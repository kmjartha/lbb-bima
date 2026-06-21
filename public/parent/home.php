<?php
/**
 * Stage 9 — Parent home (mobile-first dashboard).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/parent_helpers.php';

$p       = require_parent();
$student = parent_student($p);
$sc      = active_scope();
$rombel  = parent_rombel_for_year((int)$student['id'], (int)$sc['year_id']);

$publishMatrix = parent_publish_matrix((int)$student['id'], (int)$sc['year_id']);
$attSummary = $rombel
    ? parent_attendance_summary((int)$student['id'], (int)$rombel['id'], $sc['semester'], (int)$sc['year_id'])
    : ['h'=>0,'i'=>0,'s'=>0,'a'=>0,'total'=>0];

$publishedNow = $rombel
    ? rapor_is_published((int)$rombel['id'], (int)$student['id'], $sc['semester'], $sc['period'], (int)$sc['year_id'])
    : false;

$gradesPreview = ($publishedNow && $rombel)
    ? parent_published_grades((int)$student['id'], (int)$rombel['id'], $sc['semester'], $sc['period'])
    : [];
$avg = $gradesPreview ? parent_grades_overall_avg($gradesPreview) : null;
$wali = $rombel ? rombel_wali_user((int)$rombel['id']) : null;

audit('parent_view_home', 'student:' . $student['id']);

$page_title  = 'Beranda';
$current_nav = 'home';
include __DIR__ . '/_layout.php';
?>

<div class="p-card">
  <div style="display:flex; align-items:center; gap:.75rem;">
    <div style="width:54px; height:54px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#a855f7); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:700;">
      <?= esc(mb_strtoupper(mb_substr($student['nama'],0,1))) ?>
    </div>
    <div style="flex:1;">
      <div style="font-weight:700; font-size:1.05rem; line-height:1.15;"><?= esc($student['nama']) ?></div>
      <div class="muted">NISN <?= esc($student['nisn']) ?> · <?= esc($rombel['nama'] ?? '— belum di rombel —') ?></div>
      <?php if ($wali): ?>
        <div class="muted">Wali Kelas: <?= esc($wali['nama']) ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="p-card">
  <h3>Status Rapor (TA <?= esc($sc['year']) ?>)</h3>
  <div class="muted" style="margin-bottom:.5rem;">Rapor hanya tampil setelah Kepala Sekolah mempublikasikan.</div>
  <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:.4rem;">
    <?php foreach (['ganjil','genap'] as $sem): foreach (['PTS','PAS'] as $pk):
      $ok = $publishMatrix[$sem][$pk] ?? false; ?>
      <div style="display:flex; align-items:center; justify-content:space-between; padding:.5rem .65rem; border:1px solid var(--border,#e5e7eb); border-radius:10px;">
        <span style="font-size:.82rem; font-weight:600;"><?= esc(ucfirst($sem)) ?> · <?= esc($pk) ?></span>
        <?php if ($ok): ?>
          <a class="pill ok" href="<?= esc(url('parent/rapor.php?sem='.$sem.'&pk='.$pk)) ?>">Lihat ✓</a>
        <?php else: ?>
          <span class="pill no">Belum</span>
        <?php endif; ?>
      </div>
    <?php endforeach; endforeach; ?>
  </div>
</div>

<div class="p-card">
  <h3>Kehadiran Semester <?= esc(ucfirst($sc['semester'])) ?></h3>
  <div class="p-stat-grid">
    <div class="p-stat h"><div class="num"><?= (int)$attSummary['h'] ?></div><div class="lab">Hadir</div></div>
    <div class="p-stat i"><div class="num"><?= (int)$attSummary['i'] ?></div><div class="lab">Izin</div></div>
    <div class="p-stat s"><div class="num"><?= (int)$attSummary['s'] ?></div><div class="lab">Sakit</div></div>
    <div class="p-stat a"><div class="num"><?= (int)$attSummary['a'] ?></div><div class="lab">Alpa</div></div>
  </div>
  <div class="muted" style="margin-top:.5rem;">Total hari tercatat: <strong><?= (int)$attSummary['total'] ?></strong></div>
  <div style="margin-top:.5rem;">
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('parent/attendance.php')) ?>">Lihat detail kehadiran →</a>
  </div>
</div>

<?php if ($publishedNow && $gradesPreview): ?>
<div class="p-card">
  <h3>Nilai <?= esc($sc['period']) ?> (sudah dipublikasi)</h3>
  <?php if ($avg !== null): ?>
    <div style="display:flex; align-items:baseline; gap:.5rem; margin:.25rem 0 .5rem;">
      <span style="font-size:2rem; font-weight:800; color: var(--c-primary-700,#1d4ed8); font-feature-settings:'tnum';"><?= number_format($avg,1) ?></span>
      <span class="muted">rata-rata Pengetahuan + Keterampilan</span>
    </div>
  <?php endif; ?>
  <ul class="p-list">
    <?php foreach (array_slice($gradesPreview, 0, 5) as $g):
      $pe = $g['nilai_pengetahuan'] !== null ? (float)$g['nilai_pengetahuan'] : null;
      $ke = $g['nilai_keterampilan'] !== null ? (float)$g['nilai_keterampilan'] : null;
      $main = $pe !== null ? $pe : ($ke !== null ? $ke : null);
      $cls = $main === null ? '' : ($main >= 80 ? 'ok' : ($main >= 65 ? 'warn' : 'bad'));
    ?>
      <li>
        <span><?= esc($g['subj_nama']) ?> <span class="muted">· <?= esc($g['subj_kode']) ?></span></span>
        <span class="grade-num <?= $cls ?>"><?= $main !== null ? number_format($main,1) : '—' ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php if (count($gradesPreview) > 5): ?>
    <div style="margin-top:.5rem;"><a class="btn btn-ghost btn-sm" href="<?= esc(url('parent/grades.php')) ?>">Lihat semua mapel →</a></div>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="p-card">
  <div class="p-empty">
    <div class="icon">📋</div>
    <div><strong>Nilai <?= esc($sc['period']) ?> belum dipublikasi</strong></div>
    <div class="muted">Anda akan otomatis melihat nilai dan rapor di sini setelah Kepala Sekolah memvalidasi.</div>
  </div>
</div>
<?php endif; ?>

<a class="p-link-card" href="<?= esc(url('parent/grades.php')) ?>">
  <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h11l3 3v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M8 12h8M8 16h6"/></svg></div>
  <div class="tt"><div class="a">Daftar Nilai per Mapel</div><div class="b">Pengetahuan, Keterampilan, Sikap</div></div>
  <div class="arr">›</div>
</a>
<a class="p-link-card" href="<?= esc(url('parent/rapor.php')) ?>">
  <div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l3 3v15H6z"/><path d="M9 9h6M9 13h6M9 17h4"/></svg></div>
  <div class="tt"><div class="a">Cetak Rapor</div><div class="b">Tersedia setelah dipublikasi</div></div>
  <div class="arr">›</div>
</a>

<?php include __DIR__ . '/_layout_end.php'; ?>
