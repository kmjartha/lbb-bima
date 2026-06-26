<?php
/**
 * Stage 5 — Rekap Nilai Harian per Topik.
 * Matrix: baris = siswa, kolom = topik (subjek penilaian).
 * Nilai = rata-rata semua entri grades_daily untuk topik tsb pada
 * semester aktif, diambil dari kolom ranah yang sesuai.
 * Kolom ringkasan: rata-rata berbobot per ranah (sikap/peng/keterampilan) ATAU Indikator Bintang (TK).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/grading_helpers.php';

$user = require_view('grades_topic_recap');
$sc   = active_scope();
$pdo  = db(); // Instance DB untuk query custom TK

$rombels = accessible_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
$sid     = int_or_null($_GET['subject_id'] ?? null);
$topicId = int_or_null($_GET['topic_id'] ?? null); // null/0 = "Semua Topik"

if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $subjects = []; $members = []; $rec = ['topics' => [], 'data' => []];
$isTK = false;
$tkData = [];
$tkOverall = [];
$subjectKkm = null; // KKM untuk subject+tingkat aktif; null = belum diset, tidak ada highlight

if ($rid) {
    $rombel   = assert_can_access_rombel($user, $rid);
    // Deteksi mode TK seperti di grade_daily
    $isTK     = (stripos($rombel['jenjang'] ?? '', 'TK') !== false) || (stripos($rombel['nama'] ?? '', 'TK') !== false);
    
    $subjects = accessible_subjects_for_rombel($user, $rid);
    if (!$sid && $subjects) $sid = (int)$subjects[0]['id'];
    if ($sid) {
        assert_can_grade_subject($user, $rid, $sid);
        $members = rombel_members($rid);
        $rec     = recap_topics($rid, $sid, $sc['semester']);
        if (!$isTK) $subjectKkm = subject_kkm_for($sid, (int)$rombel['tingkat']);

        // Validasi topic_id milik subject ini, kalau tidak ketemu -> "Semua Topik"
        if ($topicId) {
            $found = false;
            foreach ($rec['topics'] as $t) if ((int)$t['id'] === $topicId) { $found = true; break; }
            if (!$found) $topicId = null;
        }
        
        // Ambil data khusus TK (Bintang & Deskripsi) jika rombel adalah TK
        if ($isTK && !empty($rec['topics'])) {
            $stmt = $pdo->prepare("
                SELECT student_id, topic_id, 
                       AVG(bintang) as avg_bintang, 
                       GROUP_CONCAT(deskripsi SEPARATOR ' | ') as all_desc
                FROM grades_daily
                WHERE rombel_id = ? AND subject_id = ? AND semester = ? AND bintang IS NOT NULL
                GROUP BY student_id, topic_id
            ");
            $stmt->execute([$rid, $sid, $sc['semester']]);
            foreach ($stmt->fetchAll() as $rData) {
                $tkData[$rData['student_id']][$rData['topic_id']] = $rData;
            }

            // Hitung rata-rata Bintang Tertimbang (Weighted) per siswa
            foreach ($members as $m) {
                $msid = (int)$m['id'];
                $sumBintang = 0; $sumBobot = 0;
                foreach ($rec['topics'] as $t) {
                    $tid = (int)$t['id'];
                    if (isset($tkData[$msid][$tid]['avg_bintang'])) {
                        $bobot = (float)($t['bobot'] ?? 1);
                        $sumBintang += (float)$tkData[$msid][$tid]['avg_bintang'] * $bobot;
                        $sumBobot += $bobot;
                    }
                }
                $tkOverall[$msid] = $sumBobot > 0 ? round($sumBintang / $sumBobot, 2) : null;
            }
        }
    }
}

// CSV export
if (($_GET['export'] ?? '') === 'csv' && $rombel && $sid) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap-topik-rombel'.$rid.'-mapel'.$sid.'-'.$sc['semester'].'.csv"');
    $out = fopen('php://output', 'w');
    
    if ($isTK) {
        $hdr = ['NISN','Nama'];
        foreach ($rec['topics'] as $t) {
            $hdr[] = ($t['kode'] ? $t['kode'].' ' : '').$t['judul'].' (Avg Bintang)';
            $hdr[] = 'Deskripsi '.$t['kode'];
        }
        $hdr[] = 'Rata-rata Bintang Akhir';
        fputcsv($out, $hdr);
        
        foreach ($members as $m) {
            $msid = (int)$m['id'];
            $row = [$m['nisn'], $m['nama']];
            foreach ($rec['topics'] as $t) {
                $tid = (int)$t['id'];
                $row[] = isset($tkData[$msid][$tid]) ? number_format((float)$tkData[$msid][$tid]['avg_bintang'], 2) : '';
                $row[] = $tkData[$msid][$tid]['all_desc'] ?? '';
            }
            $row[] = $tkOverall[$msid] !== null ? number_format((float)$tkOverall[$msid], 2) : '';
            fputcsv($out, $row);
        }
    } else {
        $hdr = ['NISN','Nama'];
        foreach ($rec['topics'] as $t) {
            $hdr[] = ($t['kode'] ? $t['kode'].' ' : '').$t['judul'].' (Σ SPK)';
        }
        $hdr = array_merge($hdr, ['Avg Sikap','Avg Pengetahuan','Avg Keterampilan','Avg Gabungan SPK']);
        fputcsv($out, $hdr);
        
        foreach ($members as $m) {
            $msid = (int)$m['id'];
            $row = [$m['nisn'], $m['nama']];
            foreach ($rec['topics'] as $t) {
                $td = $rec['data'][$msid][(int)$t['id']] ?? null;
                $row[] = $td ? (spk_overall($td['sikap'] ?? null, $td['pengetahuan'] ?? null, $td['keterampilan'] ?? null) ?? '') : '';
            }
            $w = weighted_average_ranah($rid, $sid, $msid, $sc['semester']);
            $row[] = $w['sikap']        ?? '';
            $row[] = $w['pengetahuan']  ?? '';
            $row[] = $w['keterampilan'] ?? '';
            $row[] = spk_overall($w['sikap'], $w['pengetahuan'], $w['keterampilan']) ?? '';
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}

$page_title = 'Rekap Nilai Harian';
require __DIR__ . '/../includes/header.php';
$ranahDefs = ranah_defs();
// Kolom topik yang ditampilkan: semua topik, atau hanya 1 jika topic_id dipilih ("fokus topik").
$displayTopics = $topicId
    ? array_values(array_filter($rec['topics'], fn($t) => (int)$t['id'] === $topicId))
    : $rec['topics'];
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Rekap Topik <?= $isTK ? '(Mode TK)' : '' ?> · Semester <?= esc($sc['semester']) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('grades_daily.php' . ($rid ? '?rombel_id='.$rid.($sid?'&subject_id='.$sid:'') : ''))) ?>">← Input Harian</a>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="align-items:flex-end; flex-wrap:wrap; gap:.75rem">
      <div class="field" style="flex:2; min-width:240px">
        <label class="label">Rombel</label>
        <select class="select" name="rombel_id" onchange="this.form.submit()">
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rid==(int)$r['id']?'selected':'' ?>>
              <?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:1.5; min-width:200px">
        <label class="label">Mata Pelajaran</label>
        <select class="select" name="subject_id" onchange="this.form.submit()">
          <?php if (!$subjects): ?><option value="">— Tidak ada mapel —</option><?php endif; ?>
          <?php foreach ($subjects as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $sid==(int)$s['id']?'selected':'' ?>>
              <?= esc(($s['kode']?($s['kode'].' · '):'').elective_subject_label($s['nama'], $s['elective_kode'] ?? null)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:1.5; min-width:200px">
        <label class="label">Pilih Subjek (Subject Topics)</label>
        <select class="select" name="topic_id" onchange="this.form.submit()" <?= !$rec['topics'] ? 'disabled' : '' ?>>
          <option value="">— Semua Topik —</option>
          <?php foreach ($rec['topics'] as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= $topicId==(int)$t['id']?'selected':'' ?>>
              <?= esc(($t['kode'] ? $t['kode'].' · ' : '').$t['judul']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:0 0 auto">
        <button class="btn btn-secondary" type="submit">Muat</button>
        <?php if ($rec['topics']): ?>
          <a class="btn btn-ghost" href="?rombel_id=<?= (int)$rid ?>&subject_id=<?= (int)$sid ?><?= $topicId ? '&topic_id='.(int)$topicId : '' ?>&export=csv">Export CSV</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if ($rombel && $sid): ?>
<div class="card mt-4">
  <div class="card-body">
    <?php if (!$rec['topics']): ?>
      <div class="empty">Belum ada subjek penilaian untuk semester ini. Buat dulu di
        <a href="<?= esc(url('admin/subject_topics.php?rombel_id='.$rid.'&subject_id='.$sid)) ?>">Subjek Penilaian</a>.
      </div>
    <?php elseif (!$members): ?>
      <div class="empty">Rombel ini belum memiliki anggota.</div>
    <?php else: ?>
      <?php if (!$isTK && $subjectKkm !== null): ?>
        <div class="text-sm text-muted mb-2">
          KKM mapel ini untuk kelas <?= esc((string)$rombel['tingkat']) ?>:
          <strong class="text-kkm-below"><?= esc(fmt_kkm($subjectKkm)) ?></strong>
          — nilai di bawah ambang ini ditandai merah.
        </div>
      <?php endif; ?>
      <div class="table-wrap att-matrix-wrap">
        <table class="t att-matrix">
          <thead>
            <tr>
              <th class="sticky-col" style="width:48px">#</th>
              <th class="sticky-col sticky-col-2">Nama</th>
              <?php foreach ($displayTopics as $t): ?>
                <th title="<?= esc($t['judul']) ?>">
                  <div class="text-sm"><?= esc($t['kode'] ?: '—') ?></div>
                  <div class="text-xs text-muted">
                    <?= $isTK ? 'Avg Bintang' : 'Σ SPK' ?> · ×<?= esc((string)$t['bobot']) ?>
                  </div>
                </th>
              <?php endforeach; ?>
              
              <?php if ($isTK): ?>
                <th style="min-width:140px">Indikator Bintang</th>
              <?php elseif (!$topicId): ?>
                <th>Σ Sikap</th>
                <th>Σ Pengetahuan</th>
                <th>Σ Keterampilan</th>
                <th>Σ Gabungan SPK<?php if ($subjectKkm !== null): ?><div class="text-xs text-muted" style="font-weight:400;">KKM: <?= esc(fmt_kkm($subjectKkm)) ?></div><?php endif; ?></th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($members as $i => $m):
            $msid = (int)$m['id'];
            if (!$isTK) {
                $w = weighted_average_ranah($rid, $sid, $msid, $sc['semester']);
                $wAll = spk_overall($w['sikap'], $w['pengetahuan'], $w['keterampilan']);
            }
          ?>
            <tr>
              <td class="sticky-col text-center"><?= $i+1 ?></td>
              <td class="sticky-col sticky-col-2"><strong><?= esc($m['nama']) ?></strong>
                <div class="text-xs text-muted"><?= esc($m['nisn']) ?></div>
              </td>
              
              <?php foreach ($displayTopics as $t):
                $tid = (int)$t['id'];
                if ($isTK) {
                    $tData = $tkData[$msid][$tid] ?? null;
                    $val   = $tData ? number_format((float)$tData['avg_bintang'], 1) : null;
                    $tip   = $tData ? esc($tData['all_desc']) : '';
                    $isBelowKkm = false;
                } else {
                    $td   = $rec['data'][$msid][$tid] ?? null;
                    $val  = $td ? spk_overall($td['sikap'] ?? null, $td['pengetahuan'] ?? null, $td['keterampilan'] ?? null) : null;
                    $tip  = $td ? 'S: '.($td['sikap']??'—').' · P: '.($td['pengetahuan']??'—').' · K: '.($td['keterampilan']??'—') : '';
                    // Highlight per-topik hanya relevan saat fokus 1 topik (perbandingan thd KKM mapel).
                    $isBelowKkm = $topicId ? kkm_below($val !== null ? (float)$val : null, $subjectKkm) : false;
                }
              ?>
                <td class="text-center<?= $isBelowKkm ? ' cell-kkm-below' : '' ?>" title="<?= $tip ?>" <?= $isTK && $tip ? 'style="cursor:help"' : '' ?>>
                  <?php if ($val !== null): ?>
                    <span class="<?= $isBelowKkm ? 'text-kkm-below' : '' ?>"><?= esc((string)$val) ?></span>
                  <?php else: ?><span class="text-muted">·</span><?php endif; ?>
                  <?php if ($isTK && $tip): ?>
                    <span style="font-size:10px; color:var(--c-primary-500);">💬</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>

              <?php if ($isTK): ?>
                <td class="text-center">
                  <?php if ($tkOverall[$msid] !== null): 
                      $avgVal = (float)$tkOverall[$msid];
                      // Dibulatkan ke integer terdekat (1-4)
                      $starCount = max(1, min(4, (int)round($avgVal)));
                  ?>
                    <div style="color:#f59e0b; font-size:1.25rem; letter-spacing:2px;" title="Rata-rata desimal: <?= number_format($avgVal, 2) ?>">
                      <?= str_repeat('★', $starCount) . str_repeat('☆', 4 - $starCount) ?>
                    </div>
                    <div class="text-xs text-muted" style="margin-top:2px; font-weight:600;">
                      <?= number_format($avgVal, 2) ?>
                    </div>
                  <?php else: ?>
                    <strong>—</strong>
                  <?php endif; ?>
                </td>
              <?php elseif (!$topicId): ?>
                <td class="text-center"><strong><?= $w['sikap']        !== null ? esc((string)$w['sikap'])        : '—' ?></strong></td>
                <td class="text-center"><strong><?= $w['pengetahuan']  !== null ? esc((string)$w['pengetahuan'])  : '—' ?></strong></td>
                <td class="text-center"><strong><?= $w['keterampilan'] !== null ? esc((string)$w['keterampilan']) : '—' ?></strong></td>
                <td class="text-center<?= kkm_below($wAll, $subjectKkm) ? ' cell-kkm-below' : '' ?>">
                  <strong class="<?= kkm_below($wAll, $subjectKkm) ? 'text-kkm-below' : '' ?>"><?= $wAll !== null ? esc((string)$wAll) : '—' ?></strong>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="text-sm text-muted mt-3">
        <?php if ($isTK): ?>
          Setiap sel topik menampilkan <strong>Rata-rata Bintang</strong> pada topik tersebut. Arahkan kursor (hover) pada ikon 💬 untuk membaca <strong>Catatan Deskripsi</strong>. Kolom <strong>Indikator Bintang</strong> adalah rata-rata bintang berbobot yang dibulatkan (max 4 bintang), dengan nilai desimal aslinya tertulis di bawah indikator.
        <?php elseif ($topicId): ?>
          Menampilkan fokus 1 topik saja. Sel <strong class="text-kkm-below">merah</strong> menandai siswa dengan nilai topik ini di bawah KKM mapel. Pilih "— Semua Topik —" untuk kembali ke tampilan matrix lengkap.
        <?php else: ?>
          Setiap sel topik menampilkan <strong>Σ SPK</strong> = rata-rata Sikap + Pengetahuan + Keterampilan pada topik tsb (hover untuk rincian per ranah). Kolom Σ di kanan adalah rata-rata berbobot per ranah lintas semua topik. <strong>Σ Gabungan SPK</strong> adalah rata-rata ketiga ranah — nilai inilah yang tampil sebagai nilai akhir tunggal di rapor siswa. Sel <strong class="text-kkm-below">merah</strong> menandai siswa dengan nilai akhir di bawah KKM mapel.
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>