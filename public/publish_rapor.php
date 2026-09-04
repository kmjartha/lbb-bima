<?php
/**
 * Stage 6b — Publish Rapor oleh Kepsek (filtered jenjang) / Admin.
 * Dipisah dari final_grades_review.php: verifikasi (approve/revisi) tetap di
 * sana, publish/batal-publish ke Parent Portal ada di sini.
 *
 * MIGRASI (2026-09): unit publish sekarang RAPOR PER SISWA (per semester x
 * periode) — bukan lagi per (mapel, siswa). Sumber kebenarannya tabel
 * rapor_publications (lihat includes/report_helpers.php), terpisah total
 * dari final_grades.status. Status per-mapel (draft/submitted/revised/
 * approved) tetap berjalan sebagai proses verifikasi guru<->kepsek dan
 * ditampilkan di sini sebagai INFORMASI KELENGKAPAN saja — bukan lagi
 * mekanisme yang menentukan siapa yang tampil ke ortu. Publish rapor
 * BOLEH dilakukan walau belum semua mapel siswa itu approved (partial
 * publish disengaja, sesuai keputusan: kepsek yang menimbang, sistem
 * tidak blokir).
 *
 * UX 2 level:
 *  - Tanpa ?rombel_id  -> daftar kelas, dengan info kelengkapan verifikasi
 *    mapel per kelas DAN jumlah siswa yang rapornya sudah/belum terbit.
 *  - Dengan ?rombel_id -> daftar siswa di kelas itu, masing-masing dengan
 *    badge "X/Y mapel disetujui" (info) + badge Terbit/Draft (status
 *    publish rapor sebenarnya), dan aksi Publish/Batal Publish per siswa
 *    terpilih.
 *
 * Disesuaikan per Semester × PTS/PAS lewat panel ringkasan di atas.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php'; // assert_can_access_rombel
require_once __DIR__ . '/../includes/grading_helpers.php';
require_once __DIR__ . '/../includes/final_grades_helpers.php';
require_once __DIR__ . '/../includes/report_helpers.php'; // rapor_publish_students() dkk.

$user = require_view('publish_rapor');
$pdo  = db();
$sc   = active_scope();
$err  = null;

$rombelId = int_or_null($_GET['rombel_id'] ?? ($_POST['rombel_id'] ?? null));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        if (!can_edit('publish_rapor')) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        $op = (string)($_POST['op'] ?? '');
        if (!in_array($op, ['publish', 'unpublish', 'publish_all', 'unpublish_all'], true)) {
            throw new RuntimeException('Aksi tidak dikenal.');
        }

        // ---- Aksi massal: publish/batal-publish rapor SEMUA siswa yang
        // punya nilai di scope aktif sekaligus, tanpa perlu buka tiap kelas
        // satu-satu. Kalau rombel_id dikirim (tombol cepat per baris di
        // Level 1), dibatasi ke kelas itu saja; kalau tidak, berlaku untuk
        // seluruh scope aktif kepsek/admin (Semester × Periode aktif,
        // difilter jenjang untuk kepsek). TIDAK mensyaratkan status mapel
        // apapun — publish rapor boleh partial secara sadar.
        if ($op === 'publish_all' || $op === 'unpublish_all') {
            $scopeRombelId = $rombelId;
            if ($scopeRombelId) {
                assert_can_access_rombel($user, $scopeRombelId); // jenjang gate + validasi rombel
            }

            $result = rapor_publish_scope(
                $user, $sc['semester'], $sc['period'], (int)$sc['year_id'],
                $scopeRombelId, $op === 'publish_all', (int)$user['id']
            );
            $nStudents = count($result['student_ids']);
            $nClasses  = count($result['rombel_ids']);

            audit("rapor_{$op}", $scopeRombelId ? "rombel:$scopeRombelId" : null,
                ['siswa' => $nStudents, 'kelas' => $nClasses, 'sem' => $sc['semester'], 'period' => $sc['period']]);

            $opVerb = $op === 'publish_all' ? 'dipublish' : 'dibatalkan publikasinya';
            if ($nStudents > 0) {
                $scopeText = $scopeRombelId ? '' : " di $nClasses kelas";
                flash('success', "Rapor $nStudents siswa $opVerb$scopeText.");
            } else {
                $reqStat = $op === 'publish_all' ? 'belum terbit' : 'sudah terbit';
                flash('error', "Tidak ada rapor $reqStat yang bisa diproses" . ($scopeRombelId ? ' di kelas ini.' : ' pada scope aktif ini.'));
            }
            redirect($scopeRombelId ? 'publish_rapor.php?rombel_id=' . $scopeRombelId : 'publish_rapor.php');
        }

        // ---- Aksi per-siswa terpilih (form Level 2) ----
        if (!$rombelId) throw new RuntimeException('Kelas tidak ditemukan.');

        // Gating akses (jenjang kepsek) sekaligus pastikan rombel valid.
        assert_can_access_rombel($user, $rombelId);

        $studentIds = array_map('intval', (array)($_POST['student_ids'] ?? []));
        $studentIds = array_values(array_filter($studentIds, fn($i) => $i > 0));
        if (!$studentIds) throw new RuntimeException('Tidak ada siswa terpilih.');

        if ($op === 'publish') {
            $affectedStudentIds = rapor_publish_students(
                $studentIds, $rombelId, $sc['semester'], $sc['period'], (int)$sc['year_id'], (int)$user['id']
            );
        } else { // unpublish
            $affectedStudentIds = rapor_unpublish_students(
                $studentIds, $rombelId, $sc['semester'], $sc['period'], (int)$sc['year_id']
            );
        }

        audit("rapor_{$op}", "rombel:$rombelId",
            ['siswa' => count($affectedStudentIds), 'sem' => $sc['semester'], 'period' => $sc['period']]);

        // Pesan hasil dibuat eksplisit: berapa siswa benar-benar berubah,
        // dan — kalau ada — berapa siswa terpilih yang DILEWATI (untuk
        // unpublish: siswa yang memang belum pernah published). Publish
        // sendiri selalu idempoten (memanggil ulang untuk siswa yang sudah
        // published tidak masalah), jadi tidak ada yang dilewati di sana.
        $skipped = count($studentIds) - count($affectedStudentIds);
        $opVerb  = $op === 'publish' ? 'dipublish' : 'dibatalkan publikasinya';
        $msg = 'Rapor ' . count($affectedStudentIds) . " siswa $opVerb.";
        if ($op === 'unpublish' && $skipped > 0) {
            $msg .= " $skipped dari " . count($studentIds) . ' siswa terpilih dilewati (rapornya memang belum terbit).';
        }
        flash($affectedStudentIds ? 'success' : 'error', $msg);
        redirect('publish_rapor.php?rombel_id=' . $rombelId);
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

// Ringkasan per Semester × PTS/PAS (panel penyesuaian di atas, level manapun).
// 'approved' = kelengkapan verifikasi mapel (info); 'published' = jumlah
// siswa yang rapornya sudah terbit ke ortu (dari rapor_publications).
$summary = publish_summary_counts($user, (int)$sc['year_id']);

$rombel = null;
if ($rombelId) {
    $rombel = assert_can_access_rombel($user, $rombelId);
}

$page_title = 'Publish Rapor';
require __DIR__ . '/../includes/header.php';
$fgStatuses = fg_statuses();
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Publish Rapor · <?= esc($sc['period']) ?> · Semester <?= esc(ucfirst($sc['semester'])) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('final_grades_review.php')) ?>">← Verifikasi Nilai</a>
  </div>
  <div class="card-body">
    <p class="text-sm text-muted" style="margin:0 0 .75rem">
      Pilih kombinasi Semester &amp; Periode yang ingin dipublikasikan. Setiap semester punya nilai PTS dan PAS
      tersendiri, jadi status publikasi dihitung terpisah untuk masing-masing. "Siap publish" di bawah ini murni
      informasi kelengkapan verifikasi mapel — rapor boleh dipublish walau belum 100% lengkap.
    </p>
    <div class="row" style="gap:.75rem; flex-wrap:wrap">
      <?php foreach (['ganjil' => 'Ganjil', 'genap' => 'Genap'] as $semKey => $semLabel): ?>
        <?php foreach (['PTS', 'PAS'] as $pk):
          $cnt      = $summary[$semKey][$pk];
          $isActive = ($sc['semester'] === $semKey && $sc['period'] === $pk);
        ?>
          <form method="post" action="<?= esc(url('scope_switch.php')) ?>" style="margin:0">
            <?= csrf_field() ?>
            <input type="hidden" name="year_id" value="<?= (int)$sc['year_id'] ?>">
            <input type="hidden" name="semester" value="<?= esc($semKey) ?>">
            <input type="hidden" name="period" value="<?= esc($pk) ?>">
            <button type="submit" class="card" style="margin:0; padding:.75rem 1rem; min-width:170px; text-align:left; cursor:pointer; border:2px solid <?= $isActive ? 'var(--c-primary-500)' : 'var(--c-border, #e2e8f0)' ?>; background:<?= $isActive ? 'var(--c-primary-50)' : 'transparent' ?>">
              <div class="text-sm" style="font-weight:600"><?= esc($semLabel) ?> · <?= esc($pk) ?><?= $isActive ? ' <span class="badge badge-primary" style="margin-left:.35rem">aktif</span>' : '' ?></div>
              <div class="text-xs text-muted mt-1">
                <span class="badge badge-warning"><?= (int)$cnt['approved'] ?> mapel disetujui</span>
                <span class="badge badge-success"><?= (int)$cnt['published'] ?> siswa terbit</span>
              </div>
            </button>
          </form>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if (!$rombelId): ?>
<?php
  // ============== LEVEL 1: Daftar Kelas ==============
  $rows          = publish_overview_rows($user, $sc['semester'], $sc['period'], (int)$sc['year_id']);
  $classes       = publish_class_summary($rows); // kelengkapan verifikasi mapel (info)
  $publishByRomb = rapor_class_publish_summary($user, $sc['semester'], $sc['period'], (int)$sc['year_id']); // status publish rapor sebenarnya
?>
<div class="card mt-4">
  <div class="card-header">
    <h3 class="card-title">Daftar Kelas</h3>
    <?php if (can_edit('publish_rapor')):
      $totalStudentsAll     = array_sum(array_column($publishByRomb, 'n_students'));
      $publishedStudentsAll = (int)($summary[$sc['semester']][$sc['period']]['published'] ?? 0);
      $draftStudentsAll     = $totalStudentsAll - $publishedStudentsAll;
    ?>
    <div class="row" style="gap:.5rem; flex-wrap:wrap">
      <form method="post" style="margin:0"
            onsubmit="return confirm('Publish rapor SEMUA siswa (<?= $draftStudentsAll ?> siswa) yang belum terbit di seluruh kelas untuk Semester <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?> sekaligus? Ini termasuk siswa yang belum 100% mapelnya disetujui. Orang tua akan bisa melihat rapor yang terbit di Parent Portal.')">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="publish_all">
        <button class="btn btn-primary btn-sm" type="submit" <?= $draftStudentsAll <= 0 ? 'disabled' : '' ?>>📣 Publish Semua Siswa (<?= max(0, $draftStudentsAll) ?>)</button>
      </form>
      <form method="post" style="margin:0"
            onsubmit="return confirm('Batalkan publikasi rapor SEMUA siswa (<?= $publishedStudentsAll ?> siswa) yang sudah terbit di seluruh kelas untuk Semester <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?> sekaligus?')">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="unpublish_all">
        <button class="btn btn-secondary btn-sm" type="submit" <?= $publishedStudentsAll === 0 ? 'disabled' : '' ?>>↺ Batal Publish Semua (<?= $publishedStudentsAll ?>)</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
  <?php if (!$classes): ?>
    <div class="card-body"><div class="empty">Belum ada nilai akhir yang tercatat untuk Semester <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?>.</div></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="t">
        <thead>
          <tr>
            <th>Kelas</th>
            <th style="width:220px">Status Mapel (info)</th>
            <th style="width:200px">Status Publish Rapor</th>
            <th style="width:200px"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($classes as $c):
          $pub = $publishByRomb[$c['rombel_id']] ?? ['n_students' => 0, 'n_published' => 0, 'n_draft' => 0];
        ?>
          <tr>
            <td><strong><?= esc($c['label']) ?></strong></td>
            <td>
              <div class="row" style="gap:.3rem; flex-wrap:wrap">
                <?php if ($c['n_approved'] > 0): ?>
                  <span class="badge badge-success" title="Sudah disetujui kepsek"><?= (int)$c['n_approved'] ?> disetujui</span>
                <?php endif; ?>
                <?php if ($c['n_pending'] > 0): ?>
                  <span class="badge badge-danger" title="Belum disetujui kepsek"><?= (int)$c['n_pending'] ?> belum disetujui</span>
                <?php endif; ?>
              </div>
              <div class="text-xs text-muted mt-1">dari <?= (int)$c['n_total'] ?> mapel-siswa · <?= (int)$c['n_students_complete'] ?>/<?= (int)$c['n_students'] ?> siswa 100% disetujui</div>
            </td>
            <td>
              <span class="badge badge-success"><?= (int)$pub['n_published'] ?> terbit</span>
              <span class="badge badge-warning"><?= (int)$pub['n_draft'] ?> draft</span>
              <div class="text-xs text-muted mt-1">dari <?= (int)$pub['n_students'] ?> siswa</div>
            </td>
            <td class="text-right">
              <div class="row" style="gap:.35rem; flex-wrap:wrap; justify-content:flex-end">
                <?php if (can_edit('publish_rapor') && $pub['n_draft'] > 0): ?>
                  <form method="post" style="margin:0"
                        onsubmit="return confirm('Publish rapor semua (<?= (int)$pub['n_draft'] ?>) siswa yang belum terbit di kelas <?= esc($c['label']) ?>?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="op" value="publish_all">
                    <input type="hidden" name="rombel_id" value="<?= (int)$c['rombel_id'] ?>">
                    <button class="btn btn-success btn-sm" type="submit">📣 Publish Kelas</button>
                  </form>
                <?php endif; ?>
                <a class="btn btn-ghost btn-sm" href="<?= esc(url('publish_rapor.php?rombel_id=' . $c['rombel_id'])) ?>">Lihat Siswa →</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php else: ?>
<?php
  // ============== LEVEL 2: Daftar Siswa dalam satu Kelas ==============
  $rows         = publish_overview_rows($user, $sc['semester'], $sc['period'], (int)$sc['year_id'], $rombelId);
  $studentsList = publish_student_summary($rows); // kelengkapan verifikasi mapel (info)
  $publishedMap = rapor_published_map($rombelId, $sc['semester'], $sc['period'], (int)$sc['year_id']); // status publish rapor sebenarnya
?>
<div class="card mt-4">
  <div class="card-header">
    <h3 class="card-title"><?= esc($rombel['jenjang'] . ' ' . $rombel['tingkat'] . ' · ' . $rombel['nama']) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('publish_rapor.php')) ?>">← Daftar Kelas</a>
  </div>
  <div class="card-body">
    <?php if (!$studentsList): ?>
      <div class="empty">Belum ada nilai akhir yang tercatat untuk kelas ini pada Semester <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?>.</div>
    <?php else: ?>
    <form method="post" id="pubForm">
      <?= csrf_field() ?>
      <input type="hidden" name="rombel_id" value="<?= (int)$rombelId ?>">
      <div class="row mb-3" style="gap:.5rem; flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" type="submit" name="op" value="publish" id="btnPublish">📣 Publish Terpilih</button>
        <button class="btn btn-secondary btn-sm" type="submit" name="op" value="unpublish" id="btnUnpublish">↺ Batal Publish Terpilih</button>
        <span class="text-sm text-muted" style="align-self:center">Publish rapor tidak mensyaratkan semua mapel disetujui — Anda yang memutuskan. Mapel yang belum disetujui akan tetap tampil kosong di rapor ortu sampai disetujui.</span>
      </div>
      <div class="table-wrap">
        <table class="t">
          <thead>
            <tr>
              <th style="width:36px"><input type="checkbox" class="selAll"></th>
              <th>Siswa</th>
              <th style="width:200px">Status Mapel (info)</th>
              <th style="width:120px">Rapor</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($studentsList as $s):
            $isPublished = !empty($publishedMap[$s['student_id']]);
          ?>
            <tr>
              <td class="text-center">
                <input type="checkbox" name="student_ids[]" value="<?= (int)$s['student_id'] ?>" class="rowSel"
                       data-published="<?= $isPublished ? '1' : '0' ?>">
              </td>
              <td>
                <strong><?= esc($s['nama']) ?></strong>
                <div class="text-xs text-muted"><?= esc($s['nis']) ?></div>
              </td>
              <td>
                <div class="row" style="gap:.3rem; flex-wrap:wrap">
                  <?php if ($s['n_approved'] > 0): ?>
                    <span class="badge badge-success" title="Sudah disetujui kepsek"><?= (int)$s['n_approved'] ?> disetujui</span>
                  <?php endif; ?>
                  <?php if ($s['n_pending'] > 0): ?>
                    <span class="badge badge-danger" title="Belum disetujui kepsek"><?= (int)$s['n_pending'] ?> belum disetujui</span>
                  <?php endif; ?>
                </div>
                <div class="text-xs text-muted mt-1"><?= (int)$s['n_approved'] ?>/<?= (int)$s['n_total'] ?> mapel disetujui</div>
              </td>
              <td>
                <?php if ($isPublished): ?>
                  <span class="badge badge-primary">📣 Terbit</span>
                <?php else: ?>
                  <span class="badge">Draft</span>
                <?php endif; ?>
              </td>
              <td>
                <details>
                  <summary class="text-sm" style="cursor:pointer; color:var(--c-primary-700)">Rincian mapel</summary>
                  <ul style="margin:.5rem 0 0; padding-left:1.1rem; font-size:var(--fs-12)">
                    <?php foreach ($s['subjects'] as $sub):
                      $stInfo = $fgStatuses[$sub['status']] ?? $fgStatuses['draft'];
                    ?>
                      <li><?= esc($sub['nama']) ?> — <span class="badge <?= esc($stInfo['class']) ?>"><?= esc($stInfo['label']) ?></span></li>
                    <?php endforeach; ?>
                  </ul>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<script>
(function(){
  document.querySelectorAll('.selAll').forEach(all => {
    const table = all.closest('table');
    all.addEventListener('change', () => {
      if (!table) return;
      table.querySelectorAll('.rowSel').forEach(cb => cb.checked = all.checked);
    });
  });

  // Validasi sebelum submit: kasih tahu di muka kalau ada siswa terpilih
  // yang statusnya sudah tidak relevan untuk aksi ini (mis. unpublish
  // siswa yang rapornya memang belum terbit), daripada membiarkan mereka
  // "diproses" tanpa efek secara diam-diam. Publish sendiri selalu
  // berlaku untuk siapapun terpilih (idempoten, tidak butuh validasi ini).
  const form = document.getElementById('pubForm');
  let lastOp = null;
  document.getElementById('btnPublish')?.addEventListener('click', () => { lastOp = 'publish'; });
  document.getElementById('btnUnpublish')?.addEventListener('click', () => { lastOp = 'unpublish'; });

  form?.addEventListener('submit', function (e) {
    const selected = Array.from(form.querySelectorAll('.rowSel:checked'));
    if (!selected.length) {
      alert('Pilih minimal satu siswa terlebih dahulu.');
      e.preventDefault();
      return;
    }

    let msg;
    if (lastOp === 'publish') {
      msg = 'Publish rapor siswa terpilih? Orang tua akan bisa melihatnya di Parent Portal, termasuk untuk siswa yang belum 100% mapelnya disetujui.';
    } else {
      const eligible = selected.filter(cb => cb.dataset.published === '1');
      msg = 'Batalkan publikasi rapor siswa terpilih? Orang tua tidak akan bisa melihatnya lagi.';
      if (eligible.length === 0) {
        msg = 'Tidak ada satupun siswa terpilih yang rapornya berstatus "Terbit" — tidak akan ada yang dibatalkan. Tetap lanjut?';
      } else if (eligible.length < selected.length) {
        const skip = selected.length - eligible.length;
        msg += ` (${skip} dari ${selected.length} siswa terpilih rapornya belum terbit dan akan dilewati.)`;
      }
    }
    if (!confirm(msg)) e.preventDefault();
  });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
