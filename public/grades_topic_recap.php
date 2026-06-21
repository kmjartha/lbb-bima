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

if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $subjects = []; $members = []; $rec = ['topics' => [], 'data' => []];
$isTK = false;
$tkData = [];
$tkOverall = [];

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
              <?= esc(($s['kode']?($s['kode'].' · '):'').$s['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:0 0 auto">
        <button class="btn btn-secondary" type="submit">Muat</button>
        <?php if ($rec['topics']): ?>
          <a class="btn btn-ghost" href="?rombel_id=<?= (int)$rid ?>&subject_id=<?= (int)$sid ?>&export=csv">Export CSV</a>
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
      <div class="table-wrap att-matrix-wrap">
        <table class="t att-matrix">
          <thead>
            <tr>
              <th class="sticky-col" style="width:48px">#</th>
              <th class="sticky-col sticky-col-2">Nama</th>
              <?php foreach ($rec['topics'] as $t): ?>
                <th title="<?= esc($t['judul']) ?>">
                  <div class="text-sm"><?= esc($t['kode'] ?: '—') ?></div>
                  <div class="text-xs text-muted">
                    <?= $isTK ? 'Avg Bintang' : 'Σ SPK' ?> · ×<?= esc((string)$t['bobot']) ?>
                  </div>
                </th>
              <?php endforeach; ?>
              
              <?php if ($isTK): ?>
                <th style="min-width:140px">Indikator Bintang</th>
              <?php else: ?>
                <th>Σ Sikap</th>
                <th>Σ Pengetahuan</th>
                <th>Σ Keterampilan</th>
                <th>Σ Gabungan SPK</th>
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
              
              <?php foreach ($rec['topics'] as $t):
                $tid = (int)$t['id'];
                if ($isTK) {
                    $tData = $tkData[$msid][$tid] ?? null;
                    $val   = $tData ? number_format((float)$tData['avg_bintang'], 1) : null;
                    $tip   = $tData ? esc($tData['all_desc']) : '';
                } else {
                    $td   = $rec['data'][$msid][$tid] ?? null;
                    $val  = $td ? spk_overall($td['sikap'] ?? null, $td['pengetahuan'] ?? null, $td['keterampilan'] ?? null) : null;
                    $tip  = $td ? 'S: '.($td['sikap']??'—').' · P: '.($td['pengetahuan']??'—').' · K: '.($td['keterampilan']??'—') : '';
                }
              ?>
                <td class="text-center" title="<?= $tip ?>" <?= $isTK && $tip ? 'style="cursor:help"' : '' ?>>
                  <?= $val !== null ? esc((string)$val) : '<span class="text-muted">·</span>' ?>
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
              <?php else: ?>
                <td class="text-center"><strong><?= $w['sikap']        !== null ? esc((string)$w['sikap'])        : '—' ?></strong></td>
                <td class="text-center"><strong><?= $w['pengetahuan']  !== null ? esc((string)$w['pengetahuan'])  : '—' ?></strong></td>
                <td class="text-center"><strong><?= $w['keterampilan'] !== null ? esc((string)$w['keterampilan']) : '—' ?></strong></td>
                <td class="text-center"><strong><?= $wAll !== null ? esc((string)$wAll) : '—' ?></strong></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="text-sm text-muted mt-3">
        <?php if ($isTK): ?>
          Setiap sel topik menampilkan <strong>Rata-rata Bintang</strong> pada topik tersebut. Arahkan kursor (hover) pada ikon 💬 untuk membaca <strong>Catatan Deskripsi</strong>. Kolom <strong>Indikator Bintang</strong> adalah rata-rata bintang berbobot yang dibulatkan (max 4 bintang), dengan nilai desimal aslinya tertulis di bawah indikator.
        <?php else: ?>
          Setiap sel topik menampilkan <strong>Σ SPK</strong> = rata-rata Sikap + Pengetahuan + Keterampilan pada topik tsb (hover untuk rincian per ranah). Kolom Σ di kanan adalah rata-rata berbobot per ranah lintas semua topik. <strong>Σ Gabungan SPK</strong> adalah rata-rata ketiga ranah — nilai inilah yang tampil sebagai nilai akhir tunggal di rapor siswa.
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>