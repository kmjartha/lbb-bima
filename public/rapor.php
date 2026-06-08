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

$page_title = 'Rapor Siswa';
require __DIR__ . '/../includes/header.php';
?>

<div class="card no-print">
  <div class="card-header">
    <h3 class="card-title">Pilih Siswa</h3>
    <span class="text-sm text-muted">TA <?= esc($sc['year']) ?> · <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?></span>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="gap: var(--sp-3); align-items: end">
      <div class="field" style="flex:1; min-width:260px">
        <label class="label">Rombel</label>
        <select name="rombel_id" class="select" onchange="this.form.submit()">
          <?php if (!$rombels): ?><option value="">— Tidak ada rombel terjangkau —</option><?php endif; ?>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rid==$r['id']?'selected':'' ?>>
              <?= esc($r['jenjang'] . ' · ' . $r['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:1; min-width:260px">
        <label class="label">Siswa</label>
        <select name="student_id" class="select" onchange="this.form.submit()">
          <option value="">— Pilih siswa —</option>
          <?php foreach ($members as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= $sid==$m['id']?'selected':'' ?>><?= esc($m['nama']) ?> (<?= esc($m['nisn']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label class="label">&nbsp;</label>
        <button type="button" class="btn btn-primary" onclick="window.print()" <?= $student?'':'disabled' ?>>🖨️ Print / Save as PDF</button>
      </div>
    </form>
    <?php if ($student && $rombel): ?>
      <?php if (rapor_is_published($rid, $sid, $sc['semester'], $sc['period'])): ?>
        <div class="alert alert-success" style="margin-top:12px">Rapor sudah <strong>dipublikasi</strong> — orang tua bisa melihat di Parent Portal.</div>
      <?php else: ?>
        <div class="alert alert-warning" style="margin-top:12px">Rapor belum dipublikasi. Pratinjau ini hanya untuk staf.</div>
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
  $waliRow     = wali_notes_for($rid, $sc['semester'], $sc['period']);
  $ekskul      = ekskul_grades_for_student((int)$student['id'], $sc['semester'], $sc['year_id']);
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
          <span><strong>RAPOR <?= esc($sc['period']) ?></strong> ·
          Semester <?= esc(ucfirst($sc['semester'])) ?> · TA <?= esc($sc['year']) ?></span>
        </div>
      <?php else: ?>
        <header class="rapor-head">
          <?php
            if (!empty($school['logo_path']) && file_exists(__DIR__ . '/' . ltrim($school['logo_path'],'/'))) {
              echo '<img class="logo" src="' . esc(url(ltrim($school['logo_path'],'/'))) . '" alt="logo">';
            }
          ?>
          <div class="school">
            <h2><?= esc($school['nama'] ?? 'Sekolah') ?></h2>
            <div class="meta">
              <?= esc(trim(($school['alamat'] ?? '') . ' ' . ($school['kota'] ?? '') . ' ' . ($school['provinsi'] ?? ''))) ?>
              <?php if (!empty($school['telepon'])): ?> · Telp <?= esc($school['telepon']) ?><?php endif; ?>
            </div>
          </div>
          <div style="text-align:right; font-size:12px; color:#475569">
            <strong style="color:#0f172a">RAPOR <?= esc($sc['period']) ?></strong><br>
            Semester <?= esc(ucfirst($sc['semester'])) ?> · TA <?= esc($sc['year']) ?>
          </div>
        </header>
      <?php endif; ?>
      <table class="t-print" style="margin-bottom: 6px">
        <tr>
          <td style="width:18%">Nama Siswa</td><td style="width:32%"><strong><?= esc($student['nama']) ?></strong></td>
          <td style="width:18%">NISN / NIS</td><td><?= esc($student['nisn']) ?> / <?= esc($student['nis']) ?></td>
        </tr>
        <tr>
          <td>Kelas</td><td><?= esc($rombel['jenjang'] . ' ' . $rombel['nama']) ?></td>
          <td>Tingkat</td><td><?= esc((string)$rombel['tingkat']) ?></td>
        </tr>
        <tr>
          <td>Tempat / Tgl Lahir</td>
          <td><?= esc(($student['tempat_lahir'] ?? '—') . ', ' . date('d M Y', strtotime($student['tgl_lahir']))) ?></td>
          <td>Jenis Kelamin</td><td><?= $student['jk']==='L' ? 'Laki-laki' : 'Perempuan' ?></td>
        </tr>
      </table>
    <?php },
    'character' => function() use ($charEvals, $scales) { ?>
      <div class="rapor-section">
        <h3>Penilaian Karakter</h3>
        <?php if (!$charEvals): ?>
          <div style="font-size:12px; color:#666">Belum ada penilaian karakter.</div>
        <?php else: ?>
        <table class="t-print">
          <thead><tr><th style="width:90px">Kategori</th><th>Aspek</th><th style="width:60px">Skala</th><th>Predikat</th><th>Remark</th></tr></thead>
          <tbody>
          <?php foreach ($charEvals as $ce): $sk = $scales[$ce['scale']] ?? ['label'=>$ce['scale']]; ?>
            <tr>
              <td><?= esc(ucfirst($ce['kategori'])) ?></td>
              <td><?= esc($ce['aspek_nama']) ?></td>
              <td style="text-align:center"><strong><?= esc($ce['scale']) ?></strong></td>
              <td><?= esc($sk['label']) ?></td>
              <td><?= esc($ce['remark'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    <?php },
    'academic' => function() use ($subjGroups, $cellsBySubj, $kkm, $jenjang) { ?>
      <div class="rapor-section">
        <h3>Penilaian Akademik (Nilai Akhir Gabungan SPK per Mata Pelajaran)</h3>
        <table class="t-print">
          <thead>
            <tr>
              <th style="width:32px">No</th>
              <th>Mata Pelajaran</th>
              <th style="width:120px">Nilai Akhir (Σ SPK)</th>
              <th>Predikat</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $no = 0;
            $finalSum = 0.0; $finalCnt = 0;
            foreach ($subjGroups as $catNama => $subs): ?>
            <tr><td colspan="4" style="background:#eef; font-weight:600"><?= esc($catNama) ?></td></tr>
            <?php foreach ($subs as $s): $no++;
                  $cell    = $cellsBySubj[(int)$s['id']] ?? null;
                  $overall = $cell ? ($cell['overall'] ?? null) : null;
                  $pred    = kkm_predikat($jenjang, $overall);
                  if ($overall !== null) { $finalSum += $overall; $finalCnt++; }
            ?>
              <tr>
                <td><?= $no ?></td>
                <td><?= esc($s['nama']) ?> <span class="kkm-pill"><?= esc($s['kode']) ?></span></td>
                <td style="text-align:center"><strong><?= $overall !== null ? number_format($overall,1) : '—' ?></strong></td>
                <td><?= esc($pred['grade'] . ' · ' . $pred['predikat']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach;
            $finalAvg = $finalCnt > 0 ? round($finalSum / $finalCnt, 2) : null;
            $finalPred = kkm_predikat($jenjang, $finalAvg);
          ?>
            <tr style="background:#fffbe6; font-weight:700">
              <td colspan="2" style="text-align:right">Nilai Akhir Gabungan (Rata-rata seluruh mapel)</td>
              <td style="text-align:center"><?= $finalAvg !== null ? number_format($finalAvg,2) : '—' ?></td>
              <td><?= esc($finalPred['grade'] . ' · ' . $finalPred['predikat']) ?></td>
            </tr>
          </tbody>
        </table>

        <div style="margin-top:6px; font-size:11px; color:#444">
          <strong>Nilai Akhir (Σ SPK)</strong> per mata pelajaran adalah rata-rata gabungan
          dari nilai Sikap, Pengetahuan, dan Keterampilan pada periode <?= esc($jenjang) ?> ini.
        </div>

        <div style="margin-top:8px; font-size:11px">
          <strong>Skala KKM <?= esc($jenjang) ?>:</strong>
          <?php foreach ($kkm as $k): ?>
            <span class="kkm-pill"><?= esc($k['grade']) ?> (<?= number_format((float)$k['min_val'],0) ?>–<?= number_format((float)$k['max_val'],0) ?>) <?= esc($k['predikat']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php },
    'extracurricular' => function() use ($ekskul) { ?>
      <div class="rapor-section">
        <h3>Ekstrakurikuler</h3>
        <?php if (!$ekskul): ?>
          <div style="font-size:12px; color:#666">Tidak ada nilai ekstrakurikuler tercatat.</div>
        <?php else: ?>
        <table class="t-print" style="max-width:600px">
          <thead><tr><th>Ekstrakurikuler</th><th style="width:80px">Predikat</th><th>Catatan</th></tr></thead>
          <tbody>
          <?php foreach ($ekskul as $e): ?>
            <tr>
              <td><?= esc($e['ekskul_nama']) ?></td>
              <td style="text-align:center"><strong><?= esc($e['predikat'] ?? '—') ?></strong></td>
              <td><?= esc($e['catatan'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    <?php },
    'attendance' => function() use ($myAtt) { ?>
      <div class="rapor-section">
        <h3>Kehadiran Semester</h3>
        <table class="t-print" style="max-width:600px">
          <thead><tr><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>Total Hari</th></tr></thead>
          <tbody>
            <tr style="text-align:center">
              <td><?= $myAtt['h'] ?></td><td><?= $myAtt['i'] ?></td><td><?= $myAtt['s'] ?></td><td><?= $myAtt['a'] ?></td><td><?= $myAtt['total'] ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    <?php },
    'wali_note' => function() use ($waliRow, $student) { ?>
      <div class="rapor-section">
        <h3>Catatan Wali Kelas</h3>
        <div style="border:1px solid #444; padding:10px; min-height:60px; font-size:12px; white-space:pre-wrap"><?= esc($waliRow[(int)$student['id']] ?? '—') ?></div>
      </div>
    <?php },
    'general_eval' => function() use ($generalRow, $student) { ?>
      <div class="rapor-section">
        <h3>Deskripsi / Narasi Umum</h3>
        <div style="border:1px solid #444; padding:10px; min-height:60px; font-size:12px; white-space:pre-wrap"><?= esc($generalRow[(int)$student['id']] ?? '—') ?></div>
      </div>
    <?php },
    'signatures' => function() use ($sigs, $school, $tpl) {
      $issued = ($school['kota'] ?? '—') . ', ' . date('d F Y');
      $footerImg = $tpl['footer_img'] ?? null;
      ?>
      <div class="rapor-section">
        <div style="text-align:right; font-size:12px; margin-bottom:8px"><?= esc($issued) ?></div>
        <div class="sig-grid">
          <?php foreach (['wali','kepsek','direktur','parent'] as $slot):
              $sg = $sigs[$slot] ?? ['nama'=>null,'jabatan'=>null,'ttd_path'=>null];
              $hasImg = !empty($sg['ttd_path']) && file_exists(__DIR__ . '/' . ltrim('uploads/'.$sg['ttd_path'],'/'));
          ?>
            <div class="sig-cell">
              <div class="role"><?= esc(ucfirst($slot)) ?></div>
              <div class="ttd"><?php if ($slot !== 'parent' && $hasImg): ?><img src="<?= esc(uploads_url($sg['ttd_path'])) ?>" alt="ttd"><?php endif; ?></div>
              <div class="nama"><?= esc($sg['nama'] ?? ($slot==='parent' ? '(Tanda tangan orang tua)' : '—')) ?></div>
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
  <div class="empty">Pilih rombel &amp; siswa untuk membuat rapor.</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
