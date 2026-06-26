<?php
/**
 * Stage 8 — Rapor Printable.
 * Browser-side print → "Save as PDF" (cross-platform, zero dependency).
 * Roles: administrator / admin / kepsek / guru-wali (rombel-nya).
 *        Parent dilayani di Stage 9 (file terpisah, gating via 'published').
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
        if (!$ok) { $student = null; flash('error', 'Siswa bukan anggota rombel.'); }
    }
}

$school = $pdo->query("SELECT * FROM school_profile WHERE id = 1")->fetch() ?: [];
$tpl    = $rombel ? report_template_for($rombel['jenjang']) : null;
$resolved = rapor_layout_resolve($tpl);
$layout   = $resolved['order'];
$hiddenSet = $resolved['hidden'];
$sigs   = $rombel ? report_signatures_for($rombel['jenjang']) : [];

$page_title = 'Student Report';
require __DIR__ . '/../includes/header.php';
?>

<div class="card no-print">
  <div class="card-header">
    <h3 class="card-title">Select Student</h3>
    <span class="text-sm text-muted">Academic Year <?= esc($sc['year']) ?> · <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?></span>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="gap: var(--sp-3); align-items: end">
      <div class="field" style="flex:1; min-width:260px">
        <label class="label">Class</label>
        <select name="rombel_id" class="select" onchange="this.form.submit()">
          <?php if (!$rombels): ?><option value="">— No accessible class available —</option><?php endif; ?>
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
            <option value="<?= (int)$m['id'] ?>" <?= $sid==$m['id']?'selected':'' ?>><?= esc($m['nama']) ?> (<?= esc($m['nis']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="label">&nbsp;</label>
        <div style="display:flex; gap:8px">
          <button type="button" class="btn btn-secondary" onclick="window.print()" <?= $student?'':'disabled' ?>>🖨️ Print</button>
          <a class="btn btn-primary" href="<?= $student ? esc(url('rapor_pdf.php?rombel_id='.$rid.'&student_id='.$sid)) : '#' ?>"
             <?= $student?'':'aria-disabled="true" onclick="return false;" tabindex="-1"' ?>
             style="<?= $student?'':'opacity:.5; pointer-events:none;' ?>">⬇️ Download PDF</a>
        </div>
      </div>
    </form>
    <?php if ($student && $rombel): ?>
      <?php if (rapor_is_published($rid, $sid, $sc['semester'], $sc['period'], $sc['year_id'])): ?>
        <div class="alert alert-success" style="margin-top:12px">The report has already been <strong>published</strong> — parents can view it in the Parent Portal.</div>
      <?php else: ?>
        <div class="alert alert-warning" style="margin-top:12px">The report has not been published. This preview is for staff only.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($rombel && $student):
  $jenjang   = $rombel['jenjang'];
  $kkm       = kkm_scale($jenjang);
  $matrix    = leger_matrix($rid, $sc['semester'], $sc['period']);
  $cellsBySubj = $matrix['data'][(int)$student['id']] ?? [];
  $subjGroups  = subjects_grouped_for_rombel($rid, $sc['semester']);
  $charEvals   = character_evals_for_student($rid, (int)$student['id'], $sc['semester'], $sc['period']);
  $generalRow  = general_evals_for($rid, $sc['semester'], $sc['period']);
  $att         = attendance_summary_for_rombel($rid, $sc['semester'], $sc['year_id']);
  $myAtt       = $att[(int)$student['id']] ?? ['h'=>0,'i'=>0,'s'=>0,'a'=>0,'total'=>0];
  $scales      = character_scales();

  $sectionRenderers = [
    'identitas' => function() use ($student, $rombel, $sc, $school, $tpl) {
      $headerImg = $tpl['header_img'] ?? null;
      $hasBanner = $headerImg && file_exists(__DIR__ . '/' . ltrim('uploads/'.$headerImg,'/'));
      ?>
      <?php if ($hasBanner): ?>
        <div class="rapor-subhead">
          <span><strong>REPORT <?= esc($sc['period']) ?></strong> ·
          Semester <?= esc(ucfirst($sc['semester'])) ?> · Academic Year <?= esc($sc['year']) ?></span>
        </div>
      <?php else: ?>
        <header class="rapor-head">
          <?php
            if (!empty($school['logo_path']) && file_exists(__DIR__ . '/' . ltrim($school['logo_path'],'/'))) {
              echo '<img class="logo" src="' . esc(url(ltrim($school['logo_path'],'/'))) . '" alt="logo">';
            }
          ?>
          <div class="school">
            <h2><?= esc($school['nama'] ?? 'School') ?></h2>
            <div class="meta">
              <?= esc(trim(($school['alamat'] ?? '') . ' ' . ((($school['kota'] ?? '') === 'Jakarta') ? 'Badung' : ($school['kota'] ?? '')) . ' ' . ($school['provinsi'] ?? ''))) ?>
              <?php if (!empty($school['telepon'])): ?> · Phone <?= esc($school['telepon']) ?><?php endif; ?>
            </div>
          </div>
          <div style="text-align:right; font-size:12px; color:#475569">
            <strong style="color:#0f172a">REPORT <?= esc($sc['period']) ?></strong><br>
            Semester <?= esc(ucfirst($sc['semester'])) ?> · Academic Year <?= esc($sc['year']) ?>
          </div>
        </header>
      <?php endif; ?>
      <table class="t-print" style="margin-bottom: 6px">
        <tr>
          <td style="width:18%">Student Name</td><td style="width:32%"><strong><?= esc($student['nama']) ?></strong></td>
          <td style="width:18%">NISN / NIS</td><td><?= esc($student['nisn']) ?> / <?= esc($student['nis']) ?></td>
        </tr>
        <tr>
          <td>Class</td><td><?= esc($rombel['jenjang'] . ' ' . $rombel['nama']) ?></td>
          <td>Grade Level</td><td><?= esc((string)$rombel['tingkat']) ?></td>
        </tr>
        <tr>
          <td>Place / Date of Birth</td>
          <td><?= esc(($student['tempat_lahir'] ?? '—') . ', ' . date('d M Y', strtotime($student['tgl_lahir']))) ?></td>
          <td>Gender</td><td><?= $student['jk']==='L' ? 'Male' : 'Female' ?></td>
        </tr>
      </table>
    <?php },
    'character' => function() use ($charEvals, $scales) { ?>
      <div class="rapor-section">
        <h3>Character Assessment</h3>
        <?php if (!$charEvals): ?>
          <div style="font-size:12px; color:#666">No character assessment yet.</div>
        <?php else:
          $grouped = [];
          foreach ($charEvals as $ce) {
              $cat = $ce['kategori'] ? ucfirst($ce['kategori']) : 'Other';
              $grouped[$cat][] = $ce;
          }
        ?>
        <table class="t-print character-eval-table">
          <thead>
            <tr>
              <th style="width:18%">Aspect</th>
              <th style="width:38%">Description / Remarks</th>
              <th style="width:12%">Need Improvement</th>
              <th style="width:12%">Showing Improvement</th>
              <th style="width:10%">Well Improvement</th>
              <th style="width:10%">Proficient</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($grouped as $cat => $items): $total = count($items); ?>
            <?php foreach ($items as $index => $ce): $scale = $ce['scale'] ?? ''; $sk = $scales[$scale] ?? ['label'=>$scale]; ?>
            <tr>
              <?php if ($index === 0): ?>
                <td class="category-cell" rowspan="<?= $total ?>"><?= esc($cat) ?></td>
              <?php endif; ?>
              <td><?= esc(trim(($ce['aspek_nama'] ?? '') . (!empty($ce['remark']) ? ' — ' . $ce['remark'] : ''))) ?></td>
              <td class="scale-cell"><?= $scale === 'NI' ? '✓' : '' ?></td>
              <td class="scale-cell"><?= $scale === 'SI' ? '✓' : '' ?></td>
              <td class="scale-cell"><?= $scale === 'WI' ? '✓' : '' ?></td>
              <td class="scale-cell"><?= $scale === 'PR' ? '✓' : '' ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    <?php },
    'academic' => function() use ($subjGroups, $cellsBySubj, $kkm, $jenjang) {
      $isTK = ($jenjang === 'TK');
      ?>
      <div class="rapor-section">
        <h3>Academic Assessment (Combined Final SPK Score per Subject)</h3>
        <table class="t-print">
          <thead>
            <tr>
              <th style="width:32px">No</th>
              <th>Subject</th>
              <th style="width:110px">Score</th>
              <?php if ($isTK): ?>
                <th style="width:100px; text-align:center;">Star Rating</th>
                <th style="width:80px; text-align:center;">Photo</th>
              <?php endif; ?>
              <th>Remarks</th>
              <th style="width:90px">Grade</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $no = 0;
            $finalSum = 0.0; $finalCnt = 0;
            // TK punya 7 kolom (No, Subject, Score, Star, Photo, Remarks, Grade)
            // Non-TK punya 5 kolom (No, Subject, Score, Remarks, Grade)
            $colspanCat = $isTK ? 7 : 5;
            foreach ($subjGroups as $catNama => $subs): ?>
            <tr><td colspan="<?= $colspanCat ?>" style="background:#eef; font-weight:600"><?= esc($catNama) ?></td></tr>
            <?php foreach ($subs as $s): $no++;
                  $cell    = $cellsBySubj[(int)$s['id']] ?? null;
                  $overall = $cell ? ($cell['overall'] ?? null) : null;
                  $note    = $cell ? ($cell['note'] ?? null) : null;
                  $imgPath = $cell ? ($cell['image_path'] ?? null) : null;
                  $pred    = kkm_predikat($jenjang, $overall);
                  if ($overall !== null) { $finalSum += $overall; $finalCnt++; }
            ?>
              <tr>
                <td><?= $no ?></td>
                <td><?= esc($s['nama']) ?></td>
                <td style="text-align:center"><strong><?= $overall !== null ? esc((string)$overall) : '—' ?></strong></td>
                <?php if ($isTK): ?>
                  <td style="text-align:center; vertical-align:middle;">
                    <?php if ($overall !== null):
                        $starCount = max(1, min(4, (int)round((float)$overall)));
                    ?>
                        <div style="color:#f59e0b; font-size:1.15rem; letter-spacing:1px; white-space:nowrap;">
                            <?= str_repeat('★', $starCount) . str_repeat('☆', 4 - $starCount) ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center; vertical-align:middle;">
                    <?php
                        $imgResolved = $imgPath && file_exists(__DIR__ . '/' . ltrim('uploads/'.$imgPath,'/')) ? uploads_url($imgPath) : null;
                        if ($imgResolved):
                    ?>
                        <img src="<?= esc($imgResolved) ?>" alt="Photo" style="max-width:50px; max-height:50px; object-fit:cover; border-radius:4px; border:1px solid #ccc;">
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
                <td><?= esc($note ?? '—') ?></td>
                <td style="text-align:center"><strong><?= $overall !== null ? esc($pred['grade']) : '—' ?></strong></td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach;
            $finalAvg = $finalCnt > 0 ? round($finalSum / $finalCnt, 2) : null;
            $finalPred = kkm_predikat($jenjang, $finalAvg);
            $colspanTotal = $isTK ? 6 : 4;
          ?>
            <tr style="background:#fffbe6; font-weight:700">
              <td colspan="<?= $colspanTotal ?>" style="text-align:right">Combined Final Score (Average of all subjects)</td>
              <td style="text-align:center"><strong><?= $finalAvg !== null ? esc($finalPred['grade']) : '—' ?></strong></td>
            </tr>
          </tbody>
        </table>

        <div style="margin-top:8px; font-size:11px">
          <strong>KKM Scale <?= esc($jenjang) ?>:</strong>
          <?php foreach ($kkm as $k): ?>
            <span class="kkm-pill"><?= esc($k['grade']) ?> (<?= number_format((float)$k['min_val'],0) ?>–<?= number_format((float)$k['max_val'],0) ?>) <?= esc($k['predikat']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php },
    'attendance' => function() use ($myAtt) { ?>
      <div class="rapor-section">
        <h3>Semester Attendance</h3>
        <table class="t-print" style="width:100%; max-width:720px">
          <thead><tr><th>Present</th><th>Permit</th><th>Sick</th><th>Absent</th><th>Total Days</th></tr></thead>
          <tbody>
            <tr style="text-align:center">
              <td><?= $myAtt['h'] ?></td><td><?= $myAtt['i'] ?></td><td><?= $myAtt['s'] ?></td><td><?= $myAtt['a'] ?></td><td><?= $myAtt['total'] ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    <?php },
    'general_eval' => function() use ($generalRow, $student) { ?>
      <div class="rapor-section">
        <h3>General Description / Narrative</h3>
        <div style="border:1px solid #444; padding:10px; min-height:60px; font-size:12px; white-space:pre-wrap"><?= esc($generalRow[(int)$student['id']] ?? '—') ?></div>
      </div>
    <?php },
    'signatures' => function() use ($sigs, $school, $tpl) {
      $city = (($school['kota'] ?? '') === 'Jakarta') ? 'Badung' : ($school['kota'] ?? '—');
      $issued = $city . ', ' . date('d F Y');
      $footerImg = $tpl['footer_img'] ?? null;
      $roleLabels = ['wali' => 'Homeroom Teacher', 'kepsek' => 'Principal', 'direktur' => 'Director', 'parent' => 'Parent'];
      ?>
      <div class="rapor-section">
        <div style="text-align:right; font-size:12px; margin-bottom:8px"><?= esc($issued) ?></div>
        <div class="sig-grid">
          <?php foreach (['wali','kepsek','direktur','parent'] as $slot):
              $sg = $sigs[$slot] ?? ['nama'=>null,'jabatan'=>null,'ttd_path'=>null];
              $hasImg = !empty($sg['ttd_path']) && file_exists(__DIR__ . '/' . ltrim('uploads/'.$sg['ttd_path'],'/'));
          ?>
            <div class="sig-cell">
              <div class="role"><?= esc($roleLabels[$slot] ?? ucfirst($slot)) ?></div>
              <div class="ttd"><?php if ($slot !== 'parent' && $hasImg): ?><img src="<?= esc(uploads_url($sg['ttd_path'])) ?>" alt="ttd"><?php endif; ?></div>
              <div class="nama"><?= esc($sg['nama'] ?? ($slot==='parent' ? '(Parent signature)' : '—')) ?></div>
              <div class="jabatan"><?= esc($sg['jabatan'] ?? '') ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($footerImg && file_exists(__DIR__ . '/' . ltrim('uploads/'.$footerImg,'/'))): ?>
          <div style="text-align:center; margin-top: 12px"><img src="<?= esc(uploads_url($footerImg)) ?>" alt="footer" style="max-height:60px"></div>
        <?php endif; ?>
      </div>
    <?php },
  ];
?>

<div class="print-area">
  <div class="rapor-page">
    <?php
      $headerImg = $tpl['header_img'] ?? null;
      $hasBanner = $headerImg && file_exists(__DIR__ . '/' . ltrim('uploads/'.$headerImg,'/'));
      if ($hasBanner): ?>
      <img class="rapor-banner" src="<?= esc(uploads_url($headerImg)) ?>" alt="header rapor">
    <?php endif; ?>
    <div class="rapor-body">
      <?php
        foreach ($layout as $key) {
          if (!isset($sectionRenderers[$key])) continue;
          if ($key !== 'identitas' && isset($hiddenSet[$key])) continue;
          $sectionRenderers[$key]();
        }
      ?>
    </div>
  </div>
</div>
<?php else: ?>
  <div class="empty">Select a class and student to generate the report.</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
