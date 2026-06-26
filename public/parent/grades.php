<?php
/**
 * Stage 9 — Parent grades (per-mapel list, only published rows).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/parent_helpers.php';

$p       = require_parent();
$student = parent_student($p);
$sc      = active_scope();

$sem  = in_array($_GET['sem'] ?? '', ['ganjil','genap'], true) ? $_GET['sem'] : $sc['semester'];
$pk   = in_array($_GET['pk']  ?? '', ['PTS','PAS'], true)      ? $_GET['pk']  : $sc['period'];

$availableYears = parent_available_years((int)$student['id']);
$requestedYearId = isset($_GET['year_id']) ? (int)$_GET['year_id'] : null;
$reportYearId = parent_resolve_year_id((int)$student['id'], $requestedYearId, (int)$sc['year_id']);

$rombel = parent_rombel_for_year((int)$student['id'], $reportYearId);
$publishMatrix = parent_publish_matrix((int)$student['id'], $reportYearId);
$published = $rombel ? rapor_is_published((int)$rombel['id'], (int)$student['id'], $sem, $pk, $reportYearId) : false;
$yearLabel = '';
foreach ($availableYears as $y) { if ((int)$y['id'] === $reportYearId) { $yearLabel = (string)$y['label']; break; } }
if ($yearLabel === '') $yearLabel = $sc['year'];

$rows = ($rombel && $published)
  ? parent_published_grades((int)$student['id'], (int)$rombel['id'], $sem, $pk)
  : [];
$avg = $rows ? parent_grades_overall_avg($rows) : null;

audit('parent_view_grades', 'student:' . $student['id'], ['sem'=>$sem,'pk'=>$pk,'year_id'=>$reportYearId]);

$page_title  = 'Daftar Nilai';
$current_nav = 'nilai';
include __DIR__ . '/_layout.php';

// Group by category.
$grouped = [];
foreach ($rows as $r) {
    $cat = $r['kategori_nama'] ?: 'Lainnya';
    $grouped[$cat][] = $r;
}
$jenjang = $rombel['jenjang'] ?? null;
?>

<?php if (count($availableYears) > 1): ?>
<div class="p-card no-print">
  <h3>Tahun Ajaran</h3>
  <div class="p-year-tabs">
    <?php foreach ($availableYears as $y): $isCur = (int)$y['id'] === $reportYearId; ?>
      <a class="ytab <?= $isCur ? 'is-active' : '' ?>"
         href="<?= esc(url('parent/grades.php?year_id='.(int)$y['id'].'&sem='.$sem.'&pk='.$pk)) ?>">
        <?= esc($y['label']) ?><?= !empty($y['is_active']) ? ' · Aktif' : '' ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="p-card no-print">
  <h3>Pilih Periode <span class="muted" style="font-weight:500;">· TA <?= esc($yearLabel) ?></span></h3>
  <div class="p-period-tabs">
    <?php foreach (['ganjil','genap'] as $s): foreach (['PTS','PAS'] as $k):
      $active = ($s === $sem && $k === $pk);
      $ok = $publishMatrix[$s][$k] ?? false;
    ?>
      <a class="tab <?= $active ? 'is-active' : '' ?> <?= $ok ? '' : 'is-locked' ?>"
         href="<?= esc(url('parent/grades.php?year_id='.$reportYearId.'&sem='.$s.'&pk='.$k)) ?>">
        <span><?= esc(ucfirst($s)) ?> · <?= esc($k) ?></span>
        <small><?= $ok ? '✓' : '🔒' ?></small>
      </a>
    <?php endforeach; endforeach; ?>
  </div>
</div>

<?php if (!$rombel): ?>
  <div class="p-card"><div class="p-empty"><div class="icon">🏫</div><div>Belum terdaftar di rombel pada TA <?= esc($yearLabel) ?>.</div></div></div>
<?php elseif (!$published): ?>
  <div class="p-card">
    <div class="p-locked-banner">Nilai <?= esc(ucfirst($sem)) ?> · <?= esc($pk) ?> TA <?= esc($yearLabel) ?> belum dipublikasi.</div>
    <div class="p-empty"><div class="icon">🔒</div><div>Anda akan otomatis melihat nilai setelah Kepala Sekolah memvalidasi.</div></div>
  </div>
<?php else: ?>
  <?php if ($avg !== null): ?>
    <div class="p-card" style="text-align:center;">
      <div class="muted" style="margin-bottom:.25rem">Nilai Akhir Gabungan (Sikap · Pengetahuan · Keterampilan)</div>
      <div style="font-size:2.4rem; font-weight:800; color: var(--c-primary-700,#1d4ed8); font-feature-settings:'tnum';"><?= number_format($avg,2) ?></div>
      <div class="muted">dari <?= count($rows) ?> mata pelajaran · <?= esc(ucfirst($sem)) ?> · <?= esc($pk) ?></div>
    </div>
  <?php endif; ?>

  <?php foreach ($grouped as $catName => $list): ?>
    <div class="p-card">
      <h3><?= esc($catName) ?></h3>
      <?php foreach ($list as $g):
        $pe = $g['nilai_pengetahuan'] !== null ? (float)$g['nilai_pengetahuan'] : null;
        $ke = $g['nilai_keterampilan'] !== null ? (float)$g['nilai_keterampilan'] : null;
        $si = $g['nilai_sikap']        !== null ? (float)$g['nilai_sikap']        : null;
        $overall = spk_overall($si, $pe, $ke);
        $pred = $jenjang ? kkm_predikat($jenjang, $overall) : ['grade'=>'-','predikat'=>''];
      ?>
        <div class="p-grade-row" style="padding:.55rem 0; border-bottom:1px solid var(--border,#eef0f3);">
          <div class="nm">
            <div class="t"><?= esc($g['subj_nama']) ?></div>
            <div class="s"><?= esc($g['subj_kode']) ?> · <?= esc($pred['grade'].' '.$pred['predikat']) ?></div>
            <?php if (!empty($g['catatan_guru'])): ?>
              <div class="s" style="margin-top:.15rem; font-style:italic; color:#475569;">“<?= esc($g['catatan_guru']) ?>”</div>
            <?php endif; ?>
          </div>
          <div class="nums">
            <span title="Nilai Akhir Gabungan SPK" style="background:#fffbe6; font-weight:800; font-size:1.1rem;">
              <?= $overall !== null ? number_format($overall,2) : '—' ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="p-card">
    <div class="muted" style="font-size:.75rem">
      Nilai per mata pelajaran adalah <strong>rata-rata gabungan</strong> dari Sikap, Pengetahuan, dan Keterampilan.
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/_layout_end.php'; ?>
