<?php
/**
 * Stage 9 — Parent rapor (gated by published status).
 *
 * Parents can only view rapor for their own child, only for periods that have
 * been *published* by the school. Period selection happens via ?sem=&pk=
 * (defaults to active scope).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/parent_helpers.php';
require_once __DIR__ . '/../../includes/rapor_render.php';

$p       = require_parent();
$student = parent_student($p);
$sc      = active_scope();

$sem  = in_array($_GET['sem'] ?? '', ['ganjil','genap'], true) ? $_GET['sem'] : $sc['semester'];
$pk   = in_array($_GET['pk']  ?? '', ['PTS','PAS'], true)      ? $_GET['pk']  : $sc['period'];

$reportYearId = parent_effective_year_id((int)$student['id'], (int)$sc['year_id']);
$rombel = parent_rombel_for_year((int)$student['id'], $reportYearId);
$publishMatrix = parent_publish_matrix((int)$student['id'], $reportYearId);
$published = $rombel ? rapor_is_published((int)$rombel['id'], (int)$student['id'], $sem, $pk, $reportYearId) : false;

audit('parent_view_rapor', 'student:' . $student['id'], ['sem'=>$sem,'pk'=>$pk,'ok'=>$published?1:0]);

$page_title  = 'Rapor';
$current_nav = 'rapor';
include __DIR__ . '/_layout.php';

$pdo  = db();
$school = $pdo->query("SELECT * FROM school_profile WHERE id = 1")->fetch() ?: [];
$tpl    = $rombel ? report_template_for($rombel['jenjang']) : null;
$sigs   = $rombel ? report_signatures_for($rombel['jenjang'], (int)$rombel['id']) : [];
?>

<div class="p-card no-print">
  <h3>Pilih Periode</h3>
  <div class="p-period-tabs">
    <?php foreach (['ganjil','genap'] as $s): foreach (['PTS','PAS'] as $k):
      $active = ($s === $sem && $k === $pk);
      $ok = $publishMatrix[$s][$k] ?? false;
    ?>
      <a class="tab <?= $active ? 'is-active' : '' ?> <?= $ok ? '' : 'is-locked' ?>"
         href="<?= esc(url('parent/rapor.php?sem='.$s.'&pk='.$k)) ?>">
        <span><?= esc(ucfirst($s)) ?> · <?= esc($k) ?></span>
        <small><?= $ok ? '✓ tersedia' : '🔒 belum' ?></small>
      </a>
    <?php endforeach; endforeach; ?>
  </div>
  <?php if ($published): ?>
    <div style="display:flex; gap:8px">
      <button class="btn btn-primary" onclick="window.print()" style="flex:1">🖨️ Print</button>
      <a class="btn btn-secondary" href="<?= esc(url('parent/rapor_pdf.php?sem='.$sem.'&pk='.$pk)) ?>" style="flex:1; text-align:center">⬇️ Download PDF</a>
    </div>
  <?php endif; ?>
</div>

<?php if (!$rombel): ?>
  <div class="p-card"><div class="p-empty"><div class="icon">🏫</div><div><strong>Belum terdaftar di rombel.</strong></div><div class="muted">Silakan hubungi sekolah.</div></div></div>
<?php elseif (!$published): ?>
  <div class="p-card">
    <div class="p-locked-banner">Rapor <strong><?= esc(ucfirst($sem)) ?> · <?= esc($pk) ?></strong> belum dipublikasi oleh Kepala Sekolah.</div>
    <div class="p-empty">
      <div class="icon">⏳</div>
      <div>Mohon menunggu hingga proses verifikasi selesai.</div>
    </div>
  </div>
<?php else: ?>

<div class="p-published-banner no-print">✓ Rapor <strong><?= esc(ucfirst($sem)) ?> · <?= esc($pk) ?></strong> resmi dipublikasi.</div>

<div class="print-area">
  <?= rapor_render_body([
        'student'     => $student,
        'rombel'      => $rombel,
        'school'      => $school,
        'tpl'         => $tpl,
        'sigs'        => $sigs,
        'scope'       => ['year' => $sc['year'], 'year_id' => $reportYearId, 'semester' => $sem, 'period' => $pk],
        'uploadsBase' => __DIR__ . '/..',
        'forPdf'      => false,
      ]) ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_layout_end.php'; ?>
