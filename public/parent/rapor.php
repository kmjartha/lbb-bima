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

$p       = require_parent();
$student = parent_student($p);
$sc      = active_scope();

$sem  = in_array($_GET['sem'] ?? '', ['ganjil','genap'], true) ? $_GET['sem'] : $sc['semester'];
$pk   = in_array($_GET['pk']  ?? '', ['PTS','PAS'], true)      ? $_GET['pk']  : $sc['period'];

$rombel = parent_rombel_for_year((int)$student['id'], (int)$sc['year_id']);
$publishMatrix = parent_publish_matrix((int)$student['id'], (int)$sc['year_id']);
$published = $rombel ? rapor_is_published((int)$rombel['id'], (int)$student['id'], $sem, $pk) : false;

audit('parent_view_rapor', 'student:' . $student['id'], ['sem'=>$sem,'pk'=>$pk,'ok'=>$published?1:0]);

$page_title  = 'Rapor';
$current_nav = 'rapor';
include __DIR__ . '/_layout.php';

$pdo  = db();
$school = $pdo->query("SELECT * FROM school_profile WHERE id = 1")->fetch() ?: [];
$tpl    = $rombel ? report_template_for($rombel['jenjang']) : null;
$layout = $tpl && !empty($tpl['layout_json']) ? (array)json_decode($tpl['layout_json'], true) : rapor_default_layout();
$sigs   = $rombel ? report_signatures_for($rombel['jenjang']) : [];
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
    <button class="btn btn-primary" onclick="window.print()" style="width:100%">🖨️ Print / Save as PDF</button>
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
<?php else:
  $rid       = (int)$rombel['id'];
  $sid       = (int)$student['id'];
  $jenjang   = $rombel['jenjang'];
  $kkm       = kkm_scale($jenjang);
  $matrix    = leger_matrix($rid, $sem, $pk);
  $cellsBySubj = $matrix['data'][$sid] ?? [];
  $subjGroups  = subjects_grouped_for_rombel($rid, $sem);
  $charEvals   = character_evals_for_student($rid, $sid, $sem, $pk);
  $generalRow  = general_evals_for($rid, $sem, $pk);
  $waliRow     = wali_notes_for($rid, $sem, $pk);
  $ekskul      = ekskul_grades_for_student($sid, $sem, (int)$sc['year_id']);
  $att         = attendance_summary_for_rombel($rid, $sem, (int)$sc['year_id']);
  $myAtt       = $att[$sid] ?? ['h'=>0,'i'=>0,'s'=>0,'a'=>0,'total'=>0];
  $scales      = character_scales();
?>

<div class="p-published-banner no-print">✓ Rapor <strong><?= esc(ucfirst($sem)) ?> · <?= esc($pk) ?></strong> resmi dipublikasi.</div>

<div class="print-area">
  <div class="rapor-page">
    <?php
      $headerImg = $tpl['header_img'] ?? null;
      $hasBanner = $headerImg && file_exists(__DIR__ . '/../uploads/' . ltrim($headerImg,'/'));
    ?>
    <?php if ($hasBanner): ?>
      <img class="rapor-banner" src="<?= esc(uploads_url($headerImg)) ?>" alt="header rapor">
    <?php endif; ?>
    <div class="rapor-body">
    <!-- Identitas -->
    <?php if ($hasBanner): ?>
      <div class="rapor-subhead">
        <span><strong>RAPOR <?= esc($pk) ?></strong> ·
        Semester <?= esc(ucfirst($sem)) ?> · TA <?= esc($sc['year']) ?></span>
      </div>
    <?php else: ?>
      <header class="rapor-head">
        <?php
          if (!empty($school['logo_path']) && file_exists(__DIR__ . '/../' . ltrim($school['logo_path'],'/'))) {
            echo '<img class="logo" src="' . esc(url(ltrim($school['logo_path'],'/'))) . '" alt="logo">';
          }
        ?>
        <div class="school">
          <h2><?= esc($school['nama'] ?? 'Sekolah') ?></h2>
          <div class="meta">
            <?= esc(trim(($school['alamat'] ?? '') . ' ' . ($school['kota'] ?? '') . ' ' . ($school['provinsi'] ?? ''))) ?>
          </div>
        </div>
        <div style="text-align:right; font-size:12px; color:#475569">
          <strong style="color:#0f172a">RAPOR <?= esc($pk) ?></strong><br>
          Semester <?= esc(ucfirst($sem)) ?> · TA <?= esc($sc['year']) ?>
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

    <!-- Akademik -->
    <div class="rapor-section">
      <h3>Penilaian Akademik</h3>
      <table class="t-print">
        <thead>
          <tr><th style="width:32px">No</th><th>Mata Pelajaran</th><th style="width:80px">Pengetahuan</th><th style="width:80px">Keterampilan</th><th style="width:80px">Sikap</th><th>Predikat (Pe)</th></tr>
        </thead>
        <tbody>
        <?php $no = 0; foreach ($subjGroups as $catNama => $subs): ?>
          <tr><td colspan="6" style="background:#eef; font-weight:600"><?= esc($catNama) ?></td></tr>
          <?php foreach ($subs as $s): $no++; $cell = $cellsBySubj[(int)$s['id']] ?? null;
                $pred = kkm_predikat($jenjang, $cell ? $cell['pe'] : null); ?>
            <tr>
              <td><?= $no ?></td>
              <td><?= esc($s['nama']) ?> <span class="kkm-pill"><?= esc($s['kode']) ?></span></td>
              <td style="text-align:center"><?= $cell && $cell['pe']!==null ? number_format($cell['pe'],1) : '—' ?></td>
              <td style="text-align:center"><?= $cell && $cell['ke']!==null ? number_format($cell['ke'],1) : '—' ?></td>
              <td style="text-align:center"><?= $cell && $cell['si']!==null ? number_format($cell['si'],1) : '—' ?></td>
              <td><?= esc($pred['grade'] . ' · ' . $pred['predikat']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div style="margin-top:8px; font-size:11px">
        <strong>Skala KKM <?= esc($jenjang) ?>:</strong>
        <?php foreach ($kkm as $k): ?>
          <span class="kkm-pill"><?= esc($k['grade']) ?> (<?= number_format((float)$k['min_val'],0) ?>–<?= number_format((float)$k['max_val'],0) ?>) <?= esc($k['predikat']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Karakter -->
    <?php if ($charEvals): ?>
    <div class="rapor-section">
      <h3>Penilaian Karakter</h3>
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
    </div>
    <?php endif; ?>

    <!-- Ekskul -->
    <?php if ($ekskul): ?>
    <div class="rapor-section">
      <h3>Ekstrakurikuler</h3>
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
    </div>
    <?php endif; ?>

    <!-- Kehadiran -->
    <div class="rapor-section">
      <h3>Kehadiran Semester</h3>
      <table class="t-print" style="max-width:600px">
        <thead><tr><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>Total Hari</th></tr></thead>
        <tbody>
          <tr style="text-align:center">
            <td><?= (int)$myAtt['h'] ?></td><td><?= (int)$myAtt['i'] ?></td><td><?= (int)$myAtt['s'] ?></td><td><?= (int)$myAtt['a'] ?></td><td><?= (int)$myAtt['total'] ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Catatan Wali -->
    <div class="rapor-section">
      <h3>Catatan Wali Kelas</h3>
      <div style="border:1px solid #444; padding:10px; min-height:60px; font-size:12px; white-space:pre-wrap"><?= esc($waliRow[(int)$student['id']] ?? '—') ?></div>
    </div>

    <!-- Narasi umum -->
    <?php if (!empty($generalRow[(int)$student['id']])): ?>
    <div class="rapor-section">
      <h3>Deskripsi / Narasi Umum</h3>
      <div style="border:1px solid #444; padding:10px; min-height:60px; font-size:12px; white-space:pre-wrap"><?= esc($generalRow[(int)$student['id']]) ?></div>
    </div>
    <?php endif; ?>

    <!-- Tanda tangan -->
    <div class="rapor-section">
      <div style="text-align:right; font-size:12px; margin-bottom:8px"><?= esc(($school['kota'] ?? '—') . ', ' . date('d F Y')) ?></div>
      <div class="sig-grid">
        <?php foreach (['wali','kepsek','direktur','parent'] as $slot):
            $sg = $sigs[$slot] ?? ['nama'=>null,'jabatan'=>null,'ttd_path'=>null];
            $hasImg = !empty($sg['ttd_path']) && file_exists(__DIR__ . '/../uploads/' . ltrim($sg['ttd_path'],'/'));
        ?>
          <div class="sig-cell">
            <div class="role"><?= esc(ucfirst($slot)) ?></div>
            <div class="ttd"><?php if ($slot !== 'parent' && $hasImg): ?><img src="<?= esc(uploads_url($sg['ttd_path'])) ?>" alt="ttd"><?php endif; ?></div>
            <div class="nama"><?= esc($sg['nama'] ?? ($slot==='parent' ? '(Tanda tangan orang tua)' : '—')) ?></div>
            <div class="jabatan"><?= esc($sg['jabatan'] ?? '') ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    </div><!-- /.rapor-body -->
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_layout_end.php'; ?>
