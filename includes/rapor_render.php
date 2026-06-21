<?php
/**
 * Shared Rapor HTML renderer.
 *
 * Both the staff screen preview (public/rapor.php), the parent portal
 * (public/parent/rapor.php) and the new PDF export
 * (public/rapor_pdf.php / public/parent/rapor_pdf.php) build the exact
 * same markup by calling rapor_render_body() below — this is the single
 * source of truth for "what a rapor looks like" so a layout fix only has
 * to happen once.
 *
 * rapor_render_body() returns a pure HTML string (no echo) so callers can
 * either print it directly into a page, or hand it to Dompdf for PDF
 * conversion.
 */
declare(strict_types=1);

require_once __DIR__ . '/report_helpers.php';
require_once __DIR__ . '/wali_helpers.php';
require_once __DIR__ . '/grading_helpers.php';
require_once __DIR__ . '/helpers.php';

/**
 * Build the full rapor document body (banner + identitas + all visible
 * sections) as an HTML string.
 *
 * @param array $args {
 *   @var array      student     Row from students table
 *   @var array      rombel      Row from rombel table
 *   @var array      school      Row from school_profile
 *   @var array|null tpl         report_templates row (header/footer img, layout)
 *   @var array      sigs        report_signatures_for() result
 *   @var array      scope       active_scope() shape: ['year','semester','period','year_id']
 *   @var string     uploadsBase Absolute filesystem path to /public (for file_exists checks)
 *   @var bool       forPdf      When true, image src become absolute file:// or filesystem
 *                                paths Dompdf can read directly instead of URLs.
 * }
 */
function rapor_render_body(array $args): string
{
    $student   = $args['student'];
    $rombel    = $args['rombel'];
    $school    = $args['school'];
    $tpl       = $args['tpl'];
    $sigs      = $args['sigs'];
    $sc        = $args['scope'];
    $publicDir = rtrim($args['uploadsBase'], '/'); // absolute path to /public
    $forPdf    = $args['forPdf'] ?? false;

    $jenjang     = $rombel['jenjang'];
    $rid         = (int)$rombel['id'];
    $sid         = (int)$student['id'];
    $kkm         = kkm_scale($jenjang);
    $matrix      = leger_matrix($rid, $sc['semester'], $sc['period']);
    $cellsBySubj = $matrix['data'][$sid] ?? [];
    $subjGroups  = subjects_grouped_for_rombel($rid, $sc['semester']);
    $charEvals   = character_evals_for_student($rid, $sid, $sc['semester'], $sc['period'], $jenjang);
    $generalRow  = general_evals_for($rid, $sc['semester'], $sc['period']);
    $att         = attendance_summary_for_rombel($rid, $sc['semester'], (int)$sc['year_id']);
    $myAtt       = $att[$sid] ?? ['h'=>0,'i'=>0,'s'=>0,'a'=>0,'total'=>0];
    $scales      = character_scales();

    $resolved  = rapor_layout_resolve($tpl);
    $layout    = $resolved['order'];
    $hiddenSet = $resolved['hidden'];

    /** Resolve an uploaded image to whatever <img src> the current
     *  rendering context needs: a normal URL on screen, or an absolute
     *  filesystem path for Dompdf (which fetches local files directly —
     *  no HTTP round-trip, no auth cookies needed). */
    $imgSrc = function (?string $relPath) use ($publicDir, $forPdf): ?string {
        if (!$relPath) return null;
        $abs = $publicDir . '/uploads/' . ltrim($relPath, '/');
        if (!file_exists($abs)) return null;
        return $forPdf ? $abs : uploads_url($relPath);
    };
    $logoSrc = function () use ($school, $publicDir, $forPdf): ?string {
        if (empty($school['logo_path'])) return null;
        $abs = $publicDir . '/' . ltrim($school['logo_path'], '/');
        if (!file_exists($abs)) return null;
        return $forPdf ? $abs : url(ltrim($school['logo_path'], '/'));
    };

    $headerImg = $tpl['header_img'] ?? null;
    $footerImg = $tpl['footer_img'] ?? null;
    $bannerSrc = $imgSrc($headerImg);
    $footerSrc = $imgSrc($footerImg);

    ob_start();
    ?>
    <div class="rapor-page">
      <?php if ($bannerSrc): ?>
        <img class="rapor-banner" src="<?= esc($bannerSrc) ?>" alt="header rapor">
      <?php endif; ?>
      <div class="rapor-body">

        <?php /* ---------- identitas (always shown) ---------- */ ?>
        <?php if ($bannerSrc): ?>
          <div class="rapor-subhead">
            <span><strong>RAPOR <?= esc($sc['period']) ?></strong> ·
            Semester <?= esc(ucfirst($sc['semester'])) ?> · TA <?= esc($sc['year']) ?></span>
          </div>
        <?php else:
          $logoSrcResolved = $logoSrc();
        ?>
          <?php if ($forPdf): ?>
          <table class="rapor-head" style="width:100%; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:14px;"><tr>
            <?php if ($logoSrcResolved): ?>
              <td style="width:60px; vertical-align:middle;"><img class="logo" src="<?= esc($logoSrcResolved) ?>" alt="logo"></td>
            <?php endif; ?>
            <td style="vertical-align:middle;">
              <h2 style="margin:0 0 3px; font-size:16px; color:#0f172a;"><?= esc($school['nama'] ?? 'Sekolah') ?></h2>
              <div class="meta" style="font-size:10px; color:#64748b;">
                <?= esc(trim(($school['alamat'] ?? '') . ' ' . ($school['kota'] ?? '') . ' ' . ($school['provinsi'] ?? ''))) ?>
                <?php if (!empty($school['telepon'])): ?> · Telp <?= esc($school['telepon']) ?><?php endif; ?>
              </div>
            </td>
            <td class="rapor-head-right" style="width:160px; vertical-align:middle; text-align:right;">
              <strong><?= esc('RAPOR ' . $sc['period']) ?></strong><br>
              Semester <?= esc(ucfirst($sc['semester'])) ?> · TA <?= esc($sc['year']) ?>
            </td>
          </tr></table>
          <?php else: ?>
          <header class="rapor-head">
            <?php if ($logoSrcResolved): ?>
              <img class="logo" src="<?= esc($logoSrcResolved) ?>" alt="logo">
            <?php endif; ?>
            <div class="school">
              <h2><?= esc($school['nama'] ?? 'Sekolah') ?></h2>
              <div class="meta">
                <?= esc(trim(($school['alamat'] ?? '') . ' ' . ($school['kota'] ?? '') . ' ' . ($school['provinsi'] ?? ''))) ?>
                <?php if (!empty($school['telepon'])): ?> · Telp <?= esc($school['telepon']) ?><?php endif; ?>
              </div>
            </div>
            <div class="rapor-head-right">
              <strong><?= esc('RAPOR ' . $sc['period']) ?></strong><br>
              Semester <?= esc(ucfirst($sc['semester'])) ?> · TA <?= esc($sc['year']) ?>
            </div>
          </header>
          <?php endif; ?>
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

        <?php foreach ($layout as $key):
          if ($key === 'identitas') continue; // already rendered above
          if (isset($hiddenSet[$key])) continue;
          switch ($key):
            case 'character': ?>
              <div class="rapor-section">
                <h3>Penilaian Karakter</h3>
                <?php if (!$charEvals): ?>
                  <div class="rapor-empty-note">Belum ada penilaian karakter.</div>
                <?php else:
                  $grouped = [];
                  foreach ($charEvals as $ce) {
                      $cat = $ce['kategori'] ? ucfirst($ce['kategori']) : 'Lainnya';
                      $grouped[$cat][] = $ce;
                  }
                ?>
                <table class="t-print character-eval-table">
                  <thead>
                    <tr>
                      <th class="category-heading">Aspect</th>
                      <th class="aspect-heading">Description / Remarks</th>
                      <th class="scale-heading"><span class="scale-label">Need Improvement</span></th>
                      <th class="scale-heading"><span class="scale-label">Showing Improvement</span></th>
                      <th class="scale-heading"><span class="scale-label">Well Improvement</span></th>
                      <th class="scale-heading"><span class="scale-label">Proficient</span></th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($grouped as $cat => $items): $total = count($items); ?>
                    <?php foreach ($items as $index => $ce): $scale = $ce['scale'] ?? ''; ?>
                    <tr>
                      <?php if ($index === 0): ?>
                        <td class="category-cell" rowspan="<?= $total ?>"><?= esc($cat) ?></td>
                      <?php endif; ?>
                      <td><?= esc($ce['aspek_nama']) ?></td>
                      <td class="scale-cell"><?= $scale === 'NI' ? '&#10003;' : '' ?></td>
                      <td class="scale-cell"><?= $scale === 'SI' ? '&#10003;' : '' ?></td>
                      <td class="scale-cell"><?= $scale === 'WI' ? '&#10003;' : '' ?></td>
                      <td class="scale-cell"><?= $scale === 'PR' ? '&#10003;' : '' ?></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>
              </div>
            <?php break;

            case 'academic': ?>
              <div class="rapor-section">
                <h3>Penilaian Akademik (Nilai Akhir Gabungan SPK per Mata Pelajaran)</h3>
                <table class="t-print">
                  <thead>
                    <tr>
                      <th style="width:32px">No</th>
                      <th>Mata Pelajaran</th>
                      <th>Catatan</th>
                      <th style="width:90px">Grade</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $no = 0;
                    $finalSum = 0.0; $finalCnt = 0;
                    foreach ($subjGroups as $catNama => $subs): ?>
                    <tr><td colspan="4" class="rapor-cat-row"><?= esc($catNama) ?></td></tr>
                    <?php foreach ($subs as $s): $no++;
                          $cell    = $cellsBySubj[(int)$s['id']] ?? null;
                          $overall = $cell ? ($cell['overall'] ?? null) : null;
                          $note    = $cell ? ($cell['note'] ?? null) : null;
                          $pred    = kkm_predikat($jenjang, $overall);
                          if ($overall !== null) { $finalSum += $overall; $finalCnt++; }
                    ?>
                      <tr>
                        <td><?= $no ?></td>
                        <td><?= esc($s['nama']) ?></td>
                        <td><?= esc($note ?? '—') ?></td>
                        <td style="text-align:center"><strong><?= $overall !== null ? esc($pred['grade']) : '—' ?></strong></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endforeach;
                    $finalAvg = $finalCnt > 0 ? round($finalSum / $finalCnt, 2) : null;
                    $finalPred = kkm_predikat($jenjang, $finalAvg);
                  ?>
                    <tr class="rapor-total-row">
                      <td colspan="3" style="text-align:right">Nilai Akhir Gabungan (Rata-rata seluruh mapel)</td>
                      <td style="text-align:center"><strong><?= $finalAvg !== null ? esc($finalPred['grade']) : '—' ?></strong></td>
                    </tr>
                  </tbody>
                </table>

                <div class="rapor-foot-note">
                  <strong>Nilai Akhir (&Sigma; SPK)</strong> per mata pelajaran adalah rata-rata gabungan
                  dari nilai Sikap, Pengetahuan, dan Keterampilan pada periode <?= esc($jenjang) ?> ini.
                </div>

                <div class="rapor-kkm-legend">
                  <strong>Skala KKM <?= esc($jenjang) ?>:</strong>
                  <?php foreach ($kkm as $k): ?>
                    <span class="kkm-pill"><?= esc($k['grade']) ?> (<?= number_format((float)$k['min_val'],0) ?>&ndash;<?= number_format((float)$k['max_val'],0) ?>) <?= esc($k['predikat']) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php break;

            case 'attendance': ?>
              <div class="rapor-section">
                <h3>Kehadiran Semester</h3>
                <table class="t-print rapor-table-narrow">
                  <thead><tr><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>Total Hari</th></tr></thead>
                  <tbody>
                    <tr style="text-align:center">
                      <td><?= (int)$myAtt['h'] ?></td><td><?= (int)$myAtt['i'] ?></td><td><?= (int)$myAtt['s'] ?></td><td><?= (int)$myAtt['a'] ?></td><td><?= (int)$myAtt['total'] ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            <?php break;

            case 'general_eval': ?>
              <div class="rapor-section">
                <h3>Deskripsi / Narasi Umum</h3>
                <div class="rapor-note-box"><?= esc($generalRow[$sid] ?? '—') ?></div>
              </div>
            <?php break;

            case 'signatures':
              $issued = ($school['kota'] ?? '—') . ', ' . date('d F Y');
            ?>
              <div class="rapor-section rapor-signatures">
                <div class="rapor-issued"><?= esc($issued) ?></div>
                <?php if ($forPdf): ?>
                <table class="sig-table"><tr>
                  <?php foreach (['wali','kepsek','direktur','parent'] as $slot):
                      $sg = $sigs[$slot] ?? ['nama'=>null,'jabatan'=>null,'ttd_path'=>null];
                      $ttdSrc = $slot !== 'parent' ? $imgSrc($sg['ttd_path'] ?? null) : null;
                  ?>
                    <td>
                      <div class="sig-cell">
                        <div class="role"><?= esc(ucfirst($slot)) ?></div>
                        <div class="ttd"><?php if ($ttdSrc): ?><img src="<?= esc($ttdSrc) ?>" alt="ttd"><?php endif; ?></div>
                        <div class="nama"><?= esc($sg['nama'] ?? ($slot==='parent' ? '(Tanda tangan orang tua)' : '—')) ?></div>
                        <div class="jabatan"><?= esc($sg['jabatan'] ?? '') ?></div>
                      </div>
                    </td>
                  <?php endforeach; ?>
                </tr></table>
                <?php else: ?>
                <div class="sig-grid">
                  <?php foreach (['wali','kepsek','direktur','parent'] as $slot):
                      $sg = $sigs[$slot] ?? ['nama'=>null,'jabatan'=>null,'ttd_path'=>null];
                      $ttdSrc = $slot !== 'parent' ? $imgSrc($sg['ttd_path'] ?? null) : null;
                  ?>
                    <div class="sig-cell">
                      <div class="role"><?= esc(ucfirst($slot)) ?></div>
                      <div class="ttd"><?php if ($ttdSrc): ?><img src="<?= esc($ttdSrc) ?>" alt="ttd"><?php endif; ?></div>
                      <div class="nama"><?= esc($sg['nama'] ?? ($slot==='parent' ? '(Tanda tangan orang tua)' : '—')) ?></div>
                      <div class="jabatan"><?= esc($sg['jabatan'] ?? '') ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($footerSrc): ?>
                  <div class="rapor-footer-img"><img src="<?= esc($footerSrc) ?>" alt="footer"></div>
                <?php endif; ?>
              </div>
            <?php break;

          endswitch;
        endforeach; ?>

      </div><!-- /.rapor-body -->
    </div><!-- /.rapor-page -->
    <?php
    return (string)ob_get_clean();
}
