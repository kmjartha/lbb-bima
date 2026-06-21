<?php
/**
 * Stage 9 — Parent notes & character (gated by published).
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
$published = $rombel ? rapor_is_published((int)$rombel['id'], (int)$student['id'], $sem, $pk, (int)$sc['year_id']) : false;

$wali = $rombel ? rombel_wali_user((int)$rombel['id']) : null;

audit('parent_view_notes', 'student:' . $student['id'], ['sem'=>$sem,'pk'=>$pk]);

$page_title  = 'Catatan';
$current_nav = 'profil'; // shares the "Profil" slot in the bottom nav (no separate icon)
include __DIR__ . '/_layout.php';
?>

<div class="p-card no-print">
  <h3>Catatan &amp; Karakter</h3>
  <div class="p-period-tabs">
    <?php foreach (['ganjil','genap'] as $s): foreach (['PTS','PAS'] as $k):
      $active = ($s === $sem && $k === $pk);
      $ok = $publishMatrix[$s][$k] ?? false;
    ?>
      <a class="tab <?= $active ? 'is-active' : '' ?> <?= $ok ? '' : 'is-locked' ?>"
         href="<?= esc(url('parent/notes.php?sem='.$s.'&pk='.$k)) ?>">
        <span><?= esc(ucfirst($s)) ?> · <?= esc($k) ?></span>
        <small><?= $ok ? '✓' : '🔒' ?></small>
      </a>
    <?php endforeach; endforeach; ?>
  </div>
</div>

<?php if (!$rombel): ?>
  <div class="p-card"><div class="p-empty"><div class="icon">🏫</div><div>Belum terdaftar di rombel.</div></div></div>
<?php elseif (!$published): ?>
  <div class="p-card">
    <div class="p-locked-banner">Catatan untuk <?= esc(ucfirst($sem)) ?> · <?= esc($pk) ?> belum dipublikasi.</div>
  </div>
<?php else:
  $note = parent_wali_note((int)$student['id'], (int)$rombel['id'], $sem, $pk);
  $charEvals = character_evals_for_student((int)$rombel['id'], (int)$student['id'], $sem, $pk, $rombel['jenjang'] ?? null);
  $generalRow = general_evals_for((int)$rombel['id'], $sem, $pk);
  $narasi = $generalRow[(int)$student['id']] ?? null;
  $scales = character_scales();
?>
  <div class="p-card">
    <h3>Pesan Wali Kelas</h3>
    <?php if ($wali): ?>
      <div class="muted" style="margin-bottom:.5rem">Dari: <strong><?= esc($wali['nama']) ?></strong></div>
    <?php endif; ?>
    <?php if ($note): ?>
      <div style="background:#fffbeb; border-left:3px solid #f59e0b; padding:.75rem; border-radius:6px; white-space:pre-wrap; font-size:.9rem;"><?= esc($note) ?></div>
    <?php else: ?>
      <div class="muted" style="font-size:.85rem">Tidak ada catatan khusus.</div>
    <?php endif; ?>
  </div>

  <?php if ($narasi): ?>
  <div class="p-card">
    <h3>Narasi Umum</h3>
    <div style="white-space:pre-wrap; font-size:.9rem;"><?= esc($narasi) ?></div>
  </div>
  <?php endif; ?>

  <div class="p-card">
    <h3>Penilaian Karakter</h3>
    <?php if (!$charEvals): ?>
      <div class="muted" style="font-size:.85rem">Belum ada penilaian karakter.</div>
    <?php else: ?>
      <ul class="p-list">
      <?php foreach ($charEvals as $ce): $sk = $scales[$ce['scale']] ?? ['label'=>$ce['scale']]; ?>
        <li>
          <div>
            <div style="font-weight:600;"><?= esc($ce['aspek_nama']) ?></div>
            <div class="muted" style="font-size:.72rem"><?= esc(ucfirst($ce['kategori'])) ?> · <?= esc($sk['label']) ?></div>
            <?php if (!empty($ce['remark'])): ?>
              <div class="muted" style="font-size:.78rem; font-style:italic; margin-top:.15rem;">“<?= esc($ce['remark']) ?>”</div>
            <?php endif; ?>
          </div>
          <span class="pill"><?= esc($ce['scale']) ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/_layout_end.php'; ?>
