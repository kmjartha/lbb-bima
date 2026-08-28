<?php
/**
 * Review Pengisian Nilai Harian.
 * Tujuan: Kepsek (dan Admin/Administrator) dapat memantau apakah para guru
 * rajin mengisi Penilaian Harian (grades_daily) untuk setiap mapel yang
 * mereka ampu — tanpa harus membuka satu-persatu halaman Rekap Nilai Harian.
 *
 * Alur 3 langkah (semua via GET, murni read-only, tidak ada aksi tulis):
 *   1) Pilih Guru      -> daftar guru + status pengisian sekilas
 *   2) Pilih Mapel      -> daftar rombel+mapel yang diampu guru terpilih
 *   3) Rekap Mingguan/Bulanan -> breakdown minggu/bulan mana yang terisi
 *      dan mana yang kosong, untuk kombinasi guru+rombel+mapel terpilih.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/grading_helpers.php';
require_once __DIR__ . '/../includes/teacher_review_helpers.php';

$user = require_view('teacher_grading_review');
$sc   = active_scope();

$teacherId = int_or_null($_GET['teacher_id'] ?? null);
$rid       = int_or_null($_GET['rombel_id'] ?? null);
$sid       = int_or_null($_GET['subject_id'] ?? null);
$view      = (string)($_GET['view'] ?? 'week');
if (!in_array($view, ['week', 'month'], true)) $view = 'week';

$teachers = reviewable_teachers($user);

$selectedTeacher = null;
if ($teacherId) {
    foreach ($teachers as $t) {
        if ((int)$t['teacher_id'] === $teacherId) { $selectedTeacher = $t; break; }
    }
    if (!$selectedTeacher) { $teacherId = null; $rid = null; $sid = null; }
}

$assignments = [];
if ($selectedTeacher) {
    $assignments = teacher_assignments_for_review($user, $teacherId);
}

$selectedAssignment = null;
$recap = null;
if ($selectedTeacher && $rid && $sid) {
    $selectedAssignment = teacher_assignment_find($assignments, $rid, $sid);
    if (!$selectedAssignment) {
        $rid = null; $sid = null;
    } else {
        $recap = teacher_period_recap(
            $rid, $sid, $selectedAssignment['teacher_user_id'],
            $sc['semester'], $sc['year_id'], $view
        );
    }
}

// Summary counters for the step-1 dashboard strip.
$sumActive = 0; $sumWarn = 0; $sumStale = 0; $sumNever = 0;
foreach ($teachers as $t) {
    switch ($t['status']['key']) {
        case 'active':  $sumActive++; break;
        case 'warning': $sumWarn++;   break;
        case 'stale':   $sumStale++;  break;
        case 'never':   $sumNever++;  break;
    }
}

function tgr_initials(string $nama): string
{
    $parts = preg_split('/\s+/', trim($nama));
    $parts = array_values(array_filter($parts, fn($p) => $p !== '' && ctype_alpha($p[0] ?? '')));
    if (!$parts) return '?';
    $first = mb_substr($parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function tgr_fmt_date(?string $d): string
{
    if (!$d) return '—';
    return date('d M Y', strtotime($d));
}

$page_title = 'Review Pengisian Guru';
require __DIR__ . '/../includes/header.php';
?>

<div class="scope-banner">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
  <span>Anda sedang meninjau:</span>
  <span class="sbl"><?= esc('TA ' . $sc['year']) ?></span>
  <span>·</span>
  <span class="sbl"><?= esc(ucfirst($sc['semester'])) ?></span>
  <?php if ($user['role'] === 'kepsek' && !empty($user['jenjang'])): ?>
    <span>·</span>
    <span class="sbl">Jenjang <?= esc($user['jenjang']) ?></span>
  <?php endif; ?>
</div>

<?php
// ---------------------------------------------------------------------
// Breadcrumb
// ---------------------------------------------------------------------
?>
<div class="tgr-breadcrumb">
  <?php if (!$selectedTeacher): ?>
    <span class="cur">1. Pilih Guru</span>
  <?php else: ?>
    <a href="<?= esc(url('teacher_grading_review.php')) ?>">1. Pilih Guru</a>
    <span class="sep">/</span>
    <?php if (!$selectedAssignment): ?>
      <span class="cur">2. Pilih Mapel — <?= esc($selectedTeacher['nama']) ?></span>
    <?php else: ?>
      <a href="<?= esc(url('teacher_grading_review.php?teacher_id=' . $teacherId)) ?>">2. Pilih Mapel — <?= esc($selectedTeacher['nama']) ?></a>
      <span class="sep">/</span>
      <span class="cur">3. Rekap Pengisian</span>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php if (!$selectedTeacher): ?>
  <?php // =============================== STEP 1 =============================== ?>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Review Pengisian Nilai Harian</h3>
    </div>
    <div class="card-body">
      <p class="text-sm text-muted mb-3">
        Pilih seorang guru untuk melihat apakah nilai harian sudah rutin diisi untuk setiap mapel yang diampunya.
        Status dihitung dari tanggal pengisian nilai harian yang terakhir.
      </p>

      <?php if (!$teachers): ?>
        <div class="empty">
          Belum ada guru dengan mapel yang diampu pada tahun ajaran &amp; semester aktif<?= ($user['role']==='kepsek' && !empty($user['jenjang'])) ? ' untuk jenjang '.esc($user['jenjang']) : '' ?>.
        </div>
      <?php else: ?>
        <div class="tgr-summary-row">
          <div class="stat-card green">
            <div class="stat-value"><?= $sumActive ?></div>
            <div class="stat-label">Aktif Mengisi</div>
          </div>
          <div class="stat-card amber">
            <div class="stat-value"><?= $sumWarn ?></div>
            <div class="stat-label">Perlu Diperhatikan</div>
          </div>
          <div class="stat-card red">
            <div class="stat-value"><?= $sumStale + $sumNever ?></div>
            <div class="stat-label">Tidak Aktif / Belum Pernah</div>
          </div>
          <div class="stat-card blue">
            <div class="stat-value"><?= count($teachers) ?></div>
            <div class="stat-label">Total Guru</div>
          </div>
        </div>

        <div class="tgr-search">
          <input type="text" id="tgrSearch" placeholder="Cari nama guru…" autocomplete="off">
        </div>

        <div class="tgr-grid" id="tgrTeacherGrid">
          <?php foreach ($teachers as $t): $st = $t['status']; ?>
            <a class="tgr-card" data-name="<?= esc(mb_strtolower($t['nama'])) ?>"
               href="<?= esc(url('teacher_grading_review.php?teacher_id=' . (int)$t['teacher_id'])) ?>">
              <div class="tgr-card-head">
                <div class="tgr-avatar"><?= esc(tgr_initials($t['nama'])) ?></div>
                <div style="min-width:0">
                  <div class="tgr-card-name"><?= esc($t['nama']) ?></div>
                  <div class="tgr-card-sub"><?= esc($t['niy'] ?: '—') ?></div>
                </div>
              </div>
              <div class="tgr-card-meta">
                <span class="badge <?= esc($st['class']) ?>"><?= esc($st['label']) ?></span>
                <span><strong><?= (int)$t['jumlah_mapel'] ?></strong> mapel diampu</span>
              </div>
              <div class="tgr-card-foot">Terakhir mengisi: <strong><?= esc(tgr_fmt_date($t['last_fill_date'])) ?></strong></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  (function(){
    const inp = document.getElementById('tgrSearch');
    if (!inp) return;
    inp.addEventListener('input', () => {
      const q = inp.value.trim().toLowerCase();
      document.querySelectorAll('#tgrTeacherGrid .tgr-card').forEach(c => {
        c.style.display = c.dataset.name.includes(q) ? '' : 'none';
      });
    });
  })();
  </script>

<?php elseif (!$selectedAssignment): ?>
  <?php // =============================== STEP 2 =============================== ?>
  <div class="card">
    <div class="card-header">
      <div style="display:flex; align-items:center; gap:.75rem">
        <div class="tgr-avatar"><?= esc(tgr_initials($selectedTeacher['nama'])) ?></div>
        <div>
          <h3 class="card-title" style="margin-bottom:2px"><?= esc($selectedTeacher['nama']) ?></h3>
          <span class="badge <?= esc($selectedTeacher['status']['class']) ?>"><?= esc($selectedTeacher['status']['label']) ?></span>
          <span class="text-xs text-muted">· Terakhir mengisi: <?= esc(tgr_fmt_date($selectedTeacher['last_fill_date'])) ?></span>
        </div>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= esc(url('teacher_grading_review.php')) ?>">← Ganti Guru</a>
    </div>
    <div class="card-body">
      <p class="text-sm text-muted mb-3">Pilih salah satu mapel yang diampu untuk melihat rekap pengisian mingguan/bulanan.</p>

      <?php if (!$assignments): ?>
        <div class="empty">Guru ini tidak memiliki mapel dalam cakupan Anda pada semester aktif.</div>
      <?php else: ?>
        <div class="tgr-grid">
          <?php foreach ($assignments as $a): $st = $a['status']; ?>
            <a class="tgr-card"
               href="<?= esc(url('teacher_grading_review.php?teacher_id=' . $teacherId . '&rombel_id=' . $a['rombel_id'] . '&subject_id=' . $a['subject_id'])) ?>">
              <div class="tgr-card-name"><?= esc(($a['subj_kode'] ? $a['subj_kode'].' · ' : '') . elective_subject_label($a['subj_nama'], $a['elective_kode'] ?? null)) ?></div>
              <div class="tgr-card-sub"><?= esc($a['jenjang'].' '.$a['tingkat'].' · '.$a['rombel_nama']) ?></div>
              <div class="tgr-card-meta" style="margin-top:.6rem">
                <span class="badge <?= esc($st['class']) ?>"><?= esc($st['label']) ?></span>
                <span><strong><?= $a['hari_terisi'] ?></strong> hari terisi</span>
              </div>
              <div class="tgr-card-foot">
                <?= (int)$a['topics_count'] ?> subjek penilaian · terakhir mengisi <strong><?= esc(tgr_fmt_date($a['last_fill_date'])) ?></strong>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php else: ?>
  <?php // =============================== STEP 3 =============================== ?>
  <?php
    $a = $selectedAssignment;
    $subjLabel = ($a['subj_kode'] ? $a['subj_kode'].' · ' : '') . elective_subject_label($a['subj_nama'], $a['elective_kode'] ?? null);
    $periods = $recap['periods'];
    $totalPast = 0; $filledPast = 0;
    foreach ($periods as $p) {
        if ($p['status'] === 'akan_datang') continue;
        $totalPast++;
        if ($p['status'] !== 'kosong') $filledPast++;
    }
    $pct = $totalPast > 0 ? round(($filledPast / $totalPast) * 100) : 0;
  ?>
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title" style="margin-bottom:2px"><?= esc($subjLabel) ?></h3>
        <span class="text-sm text-muted"><?= esc($a['jenjang'].' '.$a['tingkat'].' · '.$a['rombel_nama']) ?> — diampu oleh <strong><?= esc($selectedTeacher['nama']) ?></strong></span>
      </div>
      <div style="display:flex; gap:.5rem">
        <a class="btn btn-ghost btn-sm" href="<?= esc(url('teacher_grading_review.php?teacher_id=' . $teacherId)) ?>">← Ganti Mapel</a>
        <a class="btn btn-secondary btn-sm" href="<?= esc(url('grades_topic_recap.php?rombel_id=' . $rid . '&subject_id=' . $sid)) ?>">Lihat Detail Nilai →</a>
      </div>
    </div>
    <div class="card-body">
      <div class="tgr-summary-row">
        <div class="stat-card <?= $pct >= 80 ? 'green' : ($pct >= 50 ? 'amber' : 'red') ?>">
          <div class="stat-value"><?= $pct ?>%</div>
          <div class="stat-label"><?= $view === 'week' ? 'Minggu' : 'Bulan' ?> terisi (berjalan)</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-value"><?= $recap['total_students'] ?></div>
          <div class="stat-label">Siswa di rombel ini</div>
        </div>
        <div class="stat-card <?= $a['status']['key'] === 'active' ? 'green' : ($a['status']['key'] === 'warning' ? 'amber' : 'red') ?>">
          <div class="stat-value" style="font-size:var(--fs-16)"><?= esc($a['status']['label']) ?></div>
          <div class="stat-label">Status saat ini</div>
        </div>
      </div>

      <div class="row" style="justify-content:space-between; align-items:center; margin-bottom:1rem">
        <div class="seg">
          <a class="seg-btn <?= $view==='week'?'is-on':'' ?>" href="<?= esc(url('teacher_grading_review.php?teacher_id='.$teacherId.'&rombel_id='.$rid.'&subject_id='.$sid.'&view=week')) ?>">Mingguan</a>
          <a class="seg-btn <?= $view==='month'?'is-on':'' ?>" href="<?= esc(url('teacher_grading_review.php?teacher_id='.$teacherId.'&rombel_id='.$rid.'&subject_id='.$sid.'&view=month')) ?>">Bulanan</a>
        </div>
        <div class="text-xs text-muted">
          <span class="counter-dot" style="background:var(--c-success-500)"></span> Terisi
          &nbsp;<span class="counter-dot" style="background:var(--c-warning-500)"></span> Sebagian
          &nbsp;<span class="counter-dot" style="background:var(--c-danger-500)"></span> Kosong
          &nbsp;<span class="counter-dot" style="background:var(--c-n-300)"></span> Akan datang
        </div>
      </div>

      <?php if (!$periods): ?>
        <div class="empty">Rentang semester aktif belum memiliki periode untuk direkap.</div>
      <?php else: ?>
        <div class="tgr-period-list">
          <?php foreach (array_reverse($periods) as $p): ?>
            <div class="tgr-period-row st-<?= esc($p['status']) ?><?= $p['is_current'] ? ' is-current' : '' ?>">
              <div class="tgr-period-label">
                <?= esc($p['label']) ?>
                <?php if ($p['is_current']): ?><span class="badge badge-primary" style="margin-left:4px">berjalan</span><?php endif; ?>
              </div>
              <div class="tgr-period-stats">
                <?php if ($p['status'] === 'akan_datang'): ?>
                  <span>Periode mendatang</span>
                <?php else: ?>
                  <span><b><?= $p['hari_terisi'] ?></b> hari diisi</span>
                  <span><b><?= $p['entri_count'] ?></b> entri nilai</span>
                  <?php if ($recap['total_students'] > 0): ?>
                    <span><b><?= $p['siswa_disentuh'] ?>/<?= $recap['total_students'] ?></b> siswa tersentuh</span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <?php if ($p['tanggal_list']): ?>
                <div class="tgr-date-chips">
                  <?php foreach ($p['tanggal_list'] as $tgl): ?>
                    <span class="tgr-date-chip" title="<?= esc($tgl) ?>"><?= esc(date('d/m', strtotime($tgl))) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php elseif ($p['status'] === 'kosong'): ?>
                <span class="badge badge-danger">Tidak ada pengisian</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="text-sm text-muted mt-3">
          <?= $view === 'week' ? 'Minggu' : 'Bulan' ?> ditandai <strong class="text-kkm-below">merah</strong> berarti tidak ada nilai harian yang diisi guru ini untuk mapel ini pada periode tersebut (dan periode tersebut sudah lewat). Periode <strong>kuning</strong> berarti sudah ada pengisian namun belum menyentuh seluruh siswa di rombel. Periode abu-abu adalah periode yang belum berjalan.
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
