<?php
/**
 * Stage 8 — Rapor Printable.
 * Browser-side print → "Save as PDF" (cross-platform, zero dependency).
 * Roles: administrator / admin / kepsek / guru-wali (rombel-nya).
 * Parent dilayani di Stage 9 (file terpisah, gating via 'published').
 *
 * Query: ?rombel_id=&student_id=  (period & semester dari scope topbar).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/wali_helpers.php';
require_once __DIR__ . '/../includes/report_helpers.php';
require_once __DIR__ . '/../includes/rapor_render.php';

$user = require_view('rapor');
$pdo  = db();
$sc   = active_scope();

$rombels = accessible_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $members = []; $student = null;
if ($rid) {
    $rombel  = assert_can_access_rombel($user, $rid);
    $members = rombel_members($rid);
}
$sid = int_or_null($_GET['student_id'] ?? null);
if ($sid) {
    $student = student_by_id($sid);
    if ($student) {
        // Confirm membership
        $ok = false;
        foreach ($members as $m) if ((int)$m['id'] === $sid) { $ok = true; break; }
        if (!$ok) { $student = null; flash('error', 'Student is not a member of this class.'); }
    }
}

$school = $pdo->query("SELECT * FROM school_profile WHERE id = 1")->fetch() ?: [];
$tpl    = $rombel ? report_template_for($rombel['jenjang']) : null;
$sigs   = $rombel ? report_signatures_for($rombel['jenjang'], (int)$rombel['id']) : [];

$page_title = 'Student Report Card';
require __DIR__ . '/../includes/header.php';
?>

<div class="card no-print">
  <div class="card-header">
    <h3 class="card-title">Select Student</h3>
    <span class="text-sm text-muted">AY <?= esc($sc['year']) ?> · <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?></span>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="gap: var(--sp-3); align-items: end">
      <div class="field" style="flex:1; min-width:260px">
        <label class="label">Class</label>
        <select name="rombel_id" class="select" onchange="this.form.submit()">
          <?php if (!$rombels): ?><option value="">— No accessible classes —</option><?php endif; ?>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rid==$r['id']?'selected':'' ?>>
              <?= esc($r['jenjang'] . ' · ' . $r['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:1; min-width:260px">
        <label class="label">Student</label>
        <select name="student_id" class="select" onchange="this.form.submit()">
          <option value="">— Select student —</option>
          <?php foreach ($members as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= $sid==$m['id']?'selected':'' ?>><?= esc($m['nama']) ?> (<?= esc($m['nisn']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label class="label">&nbsp;</label>
        <div style="display:flex; gap:8px">
          <button type="button" class="btn btn-primary" onclick="window.print()" <?= $student?'':'disabled' ?>>🖨️ Print</button>
          <a class="btn btn-secondary" href="<?= esc(url('rapor_pdf.php?rombel_id='.$rid.'&student_id='.$sid)) ?>" target="_blank" rel="noopener" <?= ($student && $rid && $sid) ? '' : 'aria-disabled="true" onclick="return false;" style="opacity:.5;pointer-events:none"' ?>>⬇️ Download PDF</a>
        </div>
      </div>
    </form>
    <?php if ($student && $rombel): ?>
      <?php if (rapor_is_published($rid, $sid, $sc['semester'], $sc['period'], $sc['year_id'])): ?>
        <div class="alert alert-success" style="margin-top:12px">Report card has been <strong>published</strong> — parents can view it in the Parent Portal.</div>
      <?php else: ?>
        <div class="alert alert-warning" style="margin-top:12px">Report card is not yet published. This preview is for staff only.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($rombel && $student): ?>
<div class="print-area">
  <?= rapor_render_body([
        'student'     => $student,
        'rombel'      => $rombel,
        'school'      => $school,
        'tpl'         => $tpl,
        'sigs'        => $sigs,
        'scope'       => $sc,
        'uploadsBase' => __DIR__,
        'forPdf'      => false,
      ]) ?>
</div>
<?php else: ?>
  <div class="empty">Please select a class & student to generate the report card.</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>