<?php
/**
 * Stage 8 — Leger nilai per rombel.
 * Menampilkan nilai_pengetahuan / nilai_keterampilan / nilai_sikap per mapel,
 * rekap absensi semester, peringkat kelas + paralel.
 *
 * Roles:
 * - administrator/admin: semua rombel
 * - kepsek: jenjang-nya (read-only)
 * - guru wali / pengampu: rombel yang ia akses (lihat accessible_rombel)
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/report_helpers.php';

$user = require_view('leger');
$pdo  = db();
$sc   = active_scope();

$rombels = accessible_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $members = []; $matrix = ['subjects'=>[], 'data'=>[], 'avg'=>[]];
$attendance = []; $rankClass = []; $rankParalel = [];
if ($rid) {
    $rombel    = assert_can_access_rombel($user, $rid);
    $members   = rombel_members($rid);
    $matrix    = leger_matrix($rid, $sc['semester'], $sc['period']);
    $attendance = attendance_summary_for_rombel($rid, $sc['semester'], $sc['year_id']);

    $overallByStudent = [];
    foreach ($members as $m) {
        $sid = (int)$m['id'];
        $overallByStudent[$sid] = $matrix['avg'][$sid]['overall'] ?? null;
    }
    $rankClass = rank_overall($overallByStudent);
    $rankParalel = rank_paralel($rombel['jenjang'], (int)$rombel['tingkat'], $sc['year_id'], $sc['semester'], $sc['period']);
}

$school = $pdo->query("SELECT * FROM school_profile WHERE id = 1")->fetch() ?: [];

$page_title = 'Leger Nilai';
require __DIR__ . '/../includes/header.php';
?>

<div class="card no-print">
  <div class="card-header">
    <h3 class="card-title">Filter</h3>
    <span class="text-sm text-muted">TA <?= esc($sc['year']) ?> · <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?></span>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="gap: var(--sp-3); align-items: end">
      <div class="field" style="flex:1; min-width: 280px">
        <label class="label">Rombel</label>
        <select name="rombel_id" class="select" onchange="this.form.submit()">
          <?php if (!$rombels): ?><option value="">— Tidak ada rombel terjangkau —</option><?php endif; ?>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rid==$r['id']?'selected':'' ?>>
              <?= esc($r['jenjang'] . ' · ' . $r['nama']) ?> (Wali: <?= esc($r['wali_nama'] ?? '—') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="label">&nbsp;</label>
        <button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Print / Save PDF</button>
      </div>
    </form>
  </div>
</div>

<?php if ($rombel): 
    $isTK = ($rombel['jenjang'] === 'TK');
?>
<div class="print-area">
  <div class="leger-page">
    <header class="leger-head">
      <?php if (!empty($school['logo_path']) && file_exists(__DIR__ . '/' . ltrim($school['logo_path'], '/'))): ?>
        <img class="logo" src="<?= esc(url(ltrim($school['logo_path'],'/'))) ?>" alt="logo">
      <?php endif; ?>
      <div class="school">
        <h2><?= esc($school['nama'] ?? 'Sekolah') ?></h2>
        <div class="meta">
          <?= esc(trim(($school['alamat'] ?? '') . ' ' . ($school['kota'] ?? '') . ' ' . ($school['provinsi'] ?? ''))) ?>
          <?php if (!empty($school['telepon'])): ?>· Telp <?= esc($school['telepon']) ?><?php endif; ?>
        </div>
      </div>
      <div style="text-align:right; font-size:12px">
        <strong>LEGER NILAI</strong><br>
        <?= esc($rombel['jenjang'] . ' ' . $rombel['nama']) ?> · TA <?= esc($sc['year']) ?><br>
        Semester <?= esc(ucfirst($sc['semester'])) ?> · <?= esc($sc['period']) ?>
      </div>
    </header>

    <div class="rapor-section">
      <h3>Daftar Nilai Akhir per Mapel</h3>
      <div style="overflow-x:auto">
        <table class="t-print">
          <thead>
            <?php if ($isTK): ?>
                <tr>
                  <th style="width:32px">No</th>
                  <th style="min-width:160px">Nama Siswa</th>
                  <th style="width:90px">NIS</th>
                  <?php foreach ($matrix['subjects'] as $s): ?>
                    <th style="text-align:center; font-size:11px; max-width:140px; word-wrap:break-word;"><?= esc(elective_subject_label($s['nama'], $s['elective_kode'] ?? null)) ?></th>
                  <?php endforeach; ?>
                  <th style="text-align:center; background:#fffbe6">Rata-rata</th>
                  <th style="text-align:center">Rank Kelas</th>
                  <th style="text-align:center">Rank Paralel</th>
                </tr>
            <?php else: ?>
                <tr>
                  <th rowspan="2" style="width:32px">No</th>
                  <th rowspan="2" style="min-width:160px">Nama Siswa</th>
                  <th rowspan="2" style="width:90px">NIS</th>
                  <?php foreach ($matrix['subjects'] as $s): ?>
                    <th colspan="3" style="text-align:center" title="<?= esc(elective_subject_label($s['nama'], $s['elective_kode'] ?? null)) ?>"><?= esc($s['kode']) ?></th>
                  <?php endforeach; ?>
                  <th colspan="4" style="text-align:center">Rata-rata</th>
                  <th rowspan="2">Rank Kelas</th>
                  <th rowspan="2">Rank Paralel</th>
                </tr>
                <tr>
                  <?php foreach ($matrix['subjects'] as $s): ?>
                    <th title="Pengetahuan" style="background:#dbeafe">Pe</th><th title="Keterampilan" style="background:#dcfce7">Ke</th><th title="Sikap" style="background:#dbeafe">Si</th>
                  <?php endforeach; ?>
                  <th style="background:#dbeafe">Pe</th><th style="background:#dcfce7">Ke</th><th style="background:#dbeafe">Si</th><th style="background:#fffbe6">Σ SPK</th>
                </tr>
            <?php endif; ?>
          </thead>
          <tbody>
            <?php if (!$members): ?>
              <tr><td colspan="<?= $isTK ? (6 + count($matrix['subjects'])) : (5 + 3*count($matrix['subjects']) + 4) ?>"><div class="empty">Belum ada anggota.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($members as $i => $m): $sid = (int)$m['id']; $av = $matrix['avg'][$sid] ?? ['si'=>null,'pe'=>null,'ke'=>null,'overall'=>null]; ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><strong><?= esc($m['nama']) ?></strong></td>
                <td><?= esc($m['nis']) ?></td>

                <?php if ($isTK): ?>
                    <?php foreach ($matrix['subjects'] as $s):
                        $cell = $matrix['data'][$sid][(int)$s['id']] ?? null;
                        $overall = $cell ? $cell['overall'] : null;
                    ?>
                        <td style="text-align:center; vertical-align:middle;">
                            <?php if ($overall !== null): 
                                $starCount = max(1, min(4, (int)round((float)$overall)));
                            ?>
                                <div style="color:#f59e0b; font-size:1.05rem; letter-spacing:1px; white-space:nowrap;">
                                    <?= str_repeat('★', $starCount) . str_repeat('☆', 4 - $starCount) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td style="background:#fffbe6; text-align:center"><strong><?= ($av['overall'] ?? null)!==null ? number_format($av['overall'],2) : '—' ?></strong></td>
                    <td style="text-align:center"><?= $rankClass[$sid] ?? '—' ?></td>
                    <td style="text-align:center"><?= $rankParalel[$sid] ?? '—' ?></td>
                <?php else: ?>
                    <?php foreach ($matrix['subjects'] as $s):
                        $cell = $matrix['data'][$sid][(int)$s['id']] ?? null;
                    ?>
                      <td><?= $cell && $cell['pe']!==null ? number_format($cell['pe'],1) : '—' ?></td>
                      <td><?= $cell && $cell['ke']!==null ? number_format($cell['ke'],1) : '—' ?></td>
                      <td><?= $cell && $cell['si']!==null ? number_format($cell['si'],1) : '—' ?></td>
                    <?php endforeach; ?>
                    <td><?= $av['pe']!==null ? number_format($av['pe'],1) : '—' ?></td>
                    <td><?= $av['ke']!==null ? number_format($av['ke'],1) : '—' ?></td>
                    <td><?= $av['si']!==null ? number_format($av['si'],1) : '—' ?></td>
                    <td style="background:#fffbe6"><strong><?= ($av['overall'] ?? null)!==null ? number_format($av['overall'],2) : '—' ?></strong></td>
                    <td style="text-align:center"><?= $rankClass[$sid] ?? '—' ?></td>
                    <td style="text-align:center"><?= $rankParalel[$sid] ?? '—' ?></td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="rapor-section">
      <h3>Rekap Absensi Semester</h3>
      <table class="t-print" style="max-width: 700px">
        <thead>
          <tr><th style="width:32px">No</th><th>Nama</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>Total Hari</th></tr>
        </thead>
        <tbody>
        <?php foreach ($members as $i => $m): $sid=(int)$m['id']; $a = $attendance[$sid] ?? ['h'=>0,'i'=>0,'s'=>0,'a'=>0,'total'=>0]; ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= esc($m['nama']) ?></td>
            <td><?= $a['h'] ?></td><td><?= $a['i'] ?></td><td><?= $a['s'] ?></td><td><?= $a['a'] ?></td>
            <td><?= $a['total'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="rapor-section">
      <h3>Catatan</h3>
      <div style="font-size:11px; color:#444">
        <?php if ($isTK): ?>
            Penilaian menggunakan format Bintang (1-4) berdasarkan konversi Nilai Akhir Gabungan. 
            <strong>Rata-rata</strong> = Rata-rata dari seluruh mata pelajaran. Rank kelas/paralel dihitung dari nilai Rata-rata tersebut pada
            periode <?= esc($sc['period']) ?> · <?= esc(ucfirst($sc['semester'])) ?>.
        <?php else: ?>
            Pe = Pengetahuan, Ke = Keterampilan, Si = Sikap, <strong>Σ SPK</strong> = rata-rata gabungan Sikap + Pengetahuan + Keterampilan
            (nilai akhir tunggal yang ditampilkan di rapor siswa). Rata-rata berasal dari <em>final_grades</em>
            periode <?= esc($sc['period']) ?> · <?= esc(ucfirst($sc['semester'])) ?>. Rank kelas/paralel dihitung dari Σ SPK.
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
  <div class="empty">Pilih rombel di filter.</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>