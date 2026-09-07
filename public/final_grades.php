<?php
/**
 * Stage 6 — Nilai Akhir PTS/PAS (input guru pengampu).
 * Workflow: draft → submitted → (approved | revised) → published.
 * Bulk autofill mengambil rata-rata berbobot dari grades_daily semester aktif.
 * Mode TK: Otomatis sinkron rata-rata Bintang & Deskripsi.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/grading_helpers.php';
require_once __DIR__ . '/../includes/final_grades_helpers.php';
require_once __DIR__ . '/../includes/report_helpers.php'; // Digunakan untuk save_image_upload()
require_once __DIR__ . '/../includes/kepsek_scope.php';

$user = require_view('final_grades');
$pdo  = db();
$sc   = active_scope();
$err  = null;

// Nilai Akhir PTS/PAS needs a combined scope for Kepsek: their whole jenjang
// (oversight) UNION any rombel elsewhere they personally teach.
$rombels = $user['role'] === 'kepsek' ? kepsek_combined_rombels($user) : accessible_rombel($user);
$rid = int_or_null($_GET['rombel_id'] ?? null);
$sid = int_or_null($_GET['subject_id'] ?? null);
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $subjects = []; $members = []; $existing = [];
$canWriteFinalGrades = false;
$counts = ['draft'=>0,'submitted'=>0,'revised'=>0,'approved'=>0,'published'=>0];
$isTK = false;
$tkSyncData = [];

if ($rid) {
    // Validate against the same combined scope used to build the dropdown,
    // not the generic accessible_rombel()/assert_can_access_rombel().
    $rombel   = assert_rombel_in_list($rombels, $rid);
    $isTK     = (stripos($rombel['jenjang'] ?? '', 'TK') !== false) || (stripos($rombel['nama'] ?? '', 'TK') !== false);

    // For Kepsek, a rombel outside their own jenjang is only visible here
    // because they personally teach there — so only their taught subjects
    // are shown/selectable for it. Rombel inside their jenjang keep the
    // existing full oversight subject list. Write access is still further
    // restricted below by user_teaches_subject_in_rombel() either way.
    $subjects = combined_scope_subjects_for_rombel($user, $rombel);
    if ($sid) {
        $validSubject = false;
        foreach ($subjects as $s) {
            if ((int)$s['id'] === $sid) {
                $validSubject = true;
                break;
            }
        }
        if (!$validSubject) {
            $sid = null;
        }
    }
    if (!$sid && $subjects) $sid = (int)$subjects[0]['id'];
    
    if ($sid) {
        assert_can_access_subject_list($subjects, $sid);
        // Kepsek's edit right on nilai akhir is additive (mengajar) and only applies to
        // subjects he/she is personally assigned to teach in this rombel; for other
        // roles this simply mirrors can_edit('final_grades').
        $canWriteFinalGrades = can_edit('final_grades', $user) && user_teaches_subject_in_rombel($user, $rid, $sid);
        $members  = rombel_members_for_subject($rid, $sid, $sc['semester']);
        $existing = final_grades_for($rid, $sid, $sc['semester'], $sc['period']);

        // Persiapkan data Bintang & Deskripsi khusus TK
        if ($isTK) {
            $topics = topics_for($rid, $sid, $sc['semester']) ?: [];
            $stmt = $pdo->prepare("
                SELECT student_id, topic_id,
                       AVG(bintang) as avg_bintang,
                       GROUP_CONCAT(deskripsi SEPARATOR ' | ') as all_desc
                FROM grades_daily
                WHERE rombel_id = ? AND subject_id = ? AND semester = ? AND bintang IS NOT NULL
                GROUP BY student_id, topic_id
            ");
            $stmt->execute([$rid, $sid, $sc['semester']]);
            $rawTk = [];
            foreach ($stmt->fetchAll() as $r) {
                $rawTk[$r['student_id']][$r['topic_id']] = $r;
            }

            foreach ($members as $m) {
                $msid = (int)$m['id'];
                $sumB = 0; $sumW = 0;
                $descs = [];
                foreach ($topics as $t) {
                    $tid = (int)$t['id'];
                    if (isset($rawTk[$msid][$tid])) {
                        $w = (float)($t['bobot'] ?? 1);
                        $sumB += (float)$rawTk[$msid][$tid]['avg_bintang'] * $w;
                        $sumW += $w;
                        
                        $rawDesc = (string)($rawTk[$msid][$tid]['all_desc'] ?? '');
                        if (trim($rawDesc) !== '') {
                            $descs[] = trim($rawDesc);
                        }
                    }
                }
                if ($sumW > 0) {
                    $tkSyncData[$msid] = [
                        'bintang' => round($sumB / $sumW, 2),
                        'desc'    => implode(' | ', $descs)
                    ];
                }
            }
        }

        // SPK / TK auto-sync saat halaman dimuat
        if ($canWriteFinalGrades && !scope_is_locked() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $autoCount = 0;
            foreach ($members as $m) {
                $msid = (int)$m['id'];
                $cur  = $existing[$msid] ?? null;
                if ($cur && in_array($cur['status'], ['submitted','approved','published'], true)) continue;

                if ($isTK) {
                    if (!isset($tkSyncData[$msid])) continue;
                    $bintang = $tkSyncData[$msid]['bintang'];
                    $desc    = $tkSyncData[$msid]['desc'];
                    
                    if ($cur && (float)($cur['nilai_sikap'] ?? -1) === (float)$bintang) continue;

                    final_grade_upsert([
                        'rombel_id'=>$rid,'subject_id'=>$sid,'student_id'=>$msid,
                        'semester'=>$sc['semester'],'period_kind'=>$sc['period'],
                        'nilai_sikap'=>$bintang,
                        'nilai_pengetahuan'=>null,
                        'nilai_keterampilan'=>null,
                        'catatan_guru'=> ($cur['catatan_guru'] ?? '') ?: $desc,
                        'image_path'=> $cur['image_path'] ?? null,
                        'status'=> $cur['status'] ?? 'draft',
                    ]);
                    $autoCount++;
                } else {
                    $w = weighted_average_ranah($rid, $sid, $msid, $sc['semester']);
                    if ($w['sikap'] === null && $w['pengetahuan'] === null && $w['keterampilan'] === null) continue;

                    if ($cur
                        && (float)($cur['nilai_sikap']        ?? -1) === (float)($w['sikap']        ?? -1)
                        && (float)($cur['nilai_pengetahuan']  ?? -1) === (float)($w['pengetahuan']  ?? -1)
                        && (float)($cur['nilai_keterampilan'] ?? -1) === (float)($w['keterampilan'] ?? -1)
                    ) continue;

                    final_grade_upsert([
                        'rombel_id'=>$rid,'subject_id'=>$sid,'student_id'=>$msid,
                        'semester'=>$sc['semester'],'period_kind'=>$sc['period'],
                        'nilai_sikap'=>$w['sikap'],
                        'nilai_pengetahuan'=>$w['pengetahuan'],
                        'nilai_keterampilan'=>$w['keterampilan'],
                        'catatan_guru'=>$cur['catatan_guru'] ?? null,
                        'image_path'=> $cur['image_path'] ?? null,
                        'status'=> $cur['status'] ?? 'draft',
                    ]);
                    $autoCount++;
                }
            }
            if ($autoCount > 0) {
                $existing = final_grades_for($rid, $sid, $sc['semester'], $sc['period']);
            }
        }
        $counts = final_grade_status_counts($rid, $sid, $sc['semester'], $sc['period']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rombel && $sid) {
    try {
        csrf_check();
        if (!can_edit('final_grades')) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        if (!$canWriteFinalGrades) throw new RuntimeException('Anda tidak mengajar mapel ini di rombel tersebut; gunakan halaman Verifikasi Nilai untuk meninjau.');

        $op = (string)($_POST['op'] ?? '');

        if ($op === 'autofill') {
            $count = 0;
            foreach ($members as $m) {
                $msid = (int)$m['id'];
                $cur  = $existing[$msid] ?? null;
                if ($cur && in_array($cur['status'], ['submitted','approved','published'], true)) continue;

                if ($isTK) {
                    if (!isset($tkSyncData[$msid])) continue;
                    $si = $tkSyncData[$msid]['bintang'];
                    $pe = null;
                    $ke = null;
                    $ca = ($cur['catatan_guru'] ?? '') ?: $tkSyncData[$msid]['desc'];
                } else {
                    $w = weighted_average_ranah($rid, $sid, $msid, $sc['semester']);
                    if ($w['sikap'] === null && $w['pengetahuan'] === null && $w['keterampilan'] === null) continue;
                    $si = $w['sikap'];
                    $pe = $w['pengetahuan'];
                    $ke = $w['keterampilan'];
                    $ca = $cur['catatan_guru'] ?? null;
                }

                final_grade_upsert([
                    'rombel_id'=>$rid,'subject_id'=>$sid,'student_id'=>$msid,
                    'semester'=>$sc['semester'],'period_kind'=>$sc['period'],
                    'nilai_sikap'=>$si, 'nilai_pengetahuan'=>$pe, 'nilai_keterampilan'=>$ke,
                    'catatan_guru'=>$ca, 'image_path'=>$cur['image_path'] ?? null,
                    'status'=> $cur['status'] ?? 'draft',
                ]);
                $count++;
            }
            audit('autofill_final_grades', "rombel:$rid/subj:$sid", ['sem'=>$sc['semester'],'period'=>$sc['period'],'n'=>$count]);
            flash('success', "Autofill dari penilaian harian: $count siswa.");
            redirect("final_grades.php?rombel_id=$rid&subject_id=$sid");
        }

        if ($op === 'save' || $op === 'submit') {
            if (scope_is_locked()) throw new RuntimeException('Semester terkunci, tidak bisa menyimpan/mengajukan.');

            $vCa = $_POST['ca'] ?? [];
            $sel = $_POST['sel'] ?? [];

            $count = 0;
            $selectedForSubmit = [];
            if ($op === 'submit') {
                foreach ($members as $m) {
                    $msid = (int)$m['id'];
                    if (!empty($sel[$msid])) {
                        $selectedForSubmit[$msid] = true;
                    }
                }
                if ($selectedForSubmit === []) {
                    throw new RuntimeException('Pilih minimal satu siswa untuk diajukan.');
                }
            }

            foreach ($members as $m) {
                $msid = (int)$m['id'];
                $cur  = $existing[$msid] ?? null;
                $existingId = $cur['id'] ?? null;

                if ($op === 'submit' && empty($sel[$msid])) {
                    continue;
                }

                if ($isTK) {
                    $si = isset($tkSyncData[$msid]) ? $tkSyncData[$msid]['bintang'] : null;
                    $pe = null;
                    $ke = null;
                } else {
                    $w  = weighted_average_ranah($rid, $sid, $msid, $sc['semester']);
                    $si = $w['sikap'];
                    $pe = $w['pengetahuan'];
                    $ke = $w['keterampilan'];
                }

                $ca = trim((string)($vCa[$msid] ?? ''));
                $ca = $ca === '' ? null : mb_substr($ca, 0, 1000);

                $shouldRequireNote = false;
                if ($op === 'submit') {
                    // Di titik ini baris yang tidak dicentang sudah di-skip
                    // (lihat continue di atas), jadi kalau op==='submit' berarti
                    // siswa ini memang sedang diajukan dan wajib punya catatan.
                    $shouldRequireNote = true;
                }
                if ($shouldRequireNote && $ca === null) {
                    throw new RuntimeException('Catatan guru wajib diisi untuk siswa ' . $m['nama'] . ' sebelum diajukan ke Kepsek.');
                }

                // Proses Upload Gambar
                $imgPath = $cur['image_path'] ?? null;
                if (!empty($_FILES['img']['tmp_name'][$msid])) {
                    $file = [
                        'tmp_name' => $_FILES['img']['tmp_name'][$msid],
                        'error'    => $_FILES['img']['error'][$msid],
                        'size'     => $_FILES['img']['size'][$msid],
                        'name'     => $_FILES['img']['name'][$msid]
                    ];
                    $imgPath = save_image_upload($file, 'rapor_tk', 'img_' . $msid);
                }

                if ($cur && in_array($cur['status'], ['approved','published'], true)) continue;
                if ($si === null && $pe === null && $ke === null && $ca === null && $imgPath === null && !$existingId) continue;

                $newStatus = $cur['status'] ?? 'draft';
                if ($op === 'submit' && !empty($sel[$msid])) {
                    $newStatus = 'submitted';
                } elseif ($cur && $cur['status'] === 'revised' && $op === 'save') {
                    $newStatus = 'revised';
                }

                final_grade_upsert([
                    'rombel_id'=>$rid,'subject_id'=>$sid,'student_id'=>$msid,
                    'semester'=>$sc['semester'],'period_kind'=>$sc['period'],
                    'nilai_sikap'=>$si,'nilai_pengetahuan'=>$pe,'nilai_keterampilan'=>$ke,
                    'catatan_guru'=>$ca, 'image_path'=>$imgPath, 'status'=>$newStatus,
                ]);
                $count++;
            }
            audit($op === 'submit' ? 'submit_final_grades' : 'save_final_grades', "rombel:$rid/subj:$sid", ['sem'=>$sc['semester'],'period'=>$sc['period'],'n'=>$count]);
            flash('success', ($op === 'submit' ? 'Nilai diajukan untuk verifikasi: ' : 'Nilai akhir disimpan: ') . "$count siswa.");
            redirect("final_grades.php?rombel_id=$rid&subject_id=$sid");
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$page_title = 'Nilai Akhir ' . $sc['period'];
require __DIR__ . '/../includes/header.php';
$predikatJenjang = $rombel['jenjang'] ?? 'SD';
// KKM (Kriteria Ketuntasan Minimal) sesungguhnya: per mapel + tingkat kelas numerik rombel ini.
// null jika belum diset utk subject+tingkat ini -> tidak ada highlight merah (lihat kkm_below()).
$subjectKkm = ($sid && $rombel) ? subject_kkm_for($sid, (int)$rombel['tingkat']) : null;
$fgStatuses = fg_statuses();
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Nilai Akhir <?= esc($sc['period']) ?> <?= $isTK ? '(Mode TK)' : '' ?> · Semester <?= esc($sc['semester']) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('final_grades_review.php')) ?>">Antrean Verifikasi →</a>
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
          <?php foreach ($subjects as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $sid==(int)$s['id']?'selected':'' ?>>
              <?= esc(($s['kode']?($s['kode'].' · '):'').elective_subject_label($s['nama'], $s['elective_kode'] ?? null)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:0 0 auto"><button class="btn btn-secondary">Muat</button></div>
    </form>
  </div>
</div>

<?php if ($rombel && $sid && $members): ?>
<div class="card mt-4">
  <div class="card-header">
    <div>
      <h3 class="card-title"><?= esc($rombel['jenjang'].' '.$rombel['tingkat'].' · '.$rombel['nama']) ?></h3>
      <div class="text-sm text-muted">
        Bucket nilai akhir: <code><?= esc($sc['period']) ?></code> · Lock periode:
        <strong><?= scope_is_locked() ? '🔒 LOCKED' : 'OPEN' ?></strong>
      </div>
    </div>
    <div class="row" style="gap:.4rem; flex-wrap:wrap">
      <?php foreach ($fgStatuses as $code => $info): ?>
        <span class="badge <?= esc($info['class']) ?>"><?= esc($info['label']) ?>: <?= (int)$counts[$code] ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card-body">
    <?php $ro = !$canWriteFinalGrades || scope_is_locked(); ?>

    <?php if (!$ro): ?>
      <div class="alert alert-info" style="margin-bottom:.75rem">
        ⚡ <strong>Auto-sync aktif</strong> — 
        <?php if ($isTK): ?>
            Nilai Rata-rata Bintang ditarik otomatis dari Penilaian Harian dan tidak dapat diedit manual. Catatan perkembangan awal digabungkan dari deskripsi harian, namun tetap bisa Anda edit di kolom <em>Catatan Guru</em>.
        <?php else: ?>
            Nilai Sikap, Pengetahuan & Keterampilan ditarik otomatis dari rata-rata berbobot Penilaian Harian dan tidak dapat diedit manual. Untuk mengubah angka, edit di Penilaian Harian.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="post" id="fgForm" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="table-wrap">
        <table class="t">
          <thead>
            <tr>
              <th style="width:36px"><input type="checkbox" id="selAll" <?= $ro?'disabled':'' ?>></th>
              <th style="width:36px">#</th>
              <th>NIS</th>
              <th>Nama</th>
              
              <?php if ($isTK): ?>
                <th style="width:100px; text-align:center;"><span class="badge badge-warning">Nilai Bintang</span></th>
                <th style="width:150px; text-align:center;">Indikator</th>
                <th>Catatan / Evaluasi Perkembangan</th>
                <th style="width:120px; text-align:center;">Foto Bukti</th> <?php else: ?>
                <th style="width:90px"><span class="badge badge-info">Sikap</span></th>
                <th style="width:110px"><span class="badge badge-primary">Pengetahuan</span></th>
                <th style="width:110px"><span class="badge badge-success">Keterampilan</span></th>
                <th style="width:140px">Σ Gabungan SPK · Predikat<?php if ($subjectKkm !== null): ?><div class="text-xs text-muted" style="font-weight:400;">KKM: <?= esc(fmt_kkm($subjectKkm)) ?></div><?php endif; ?></th>
                <th>Catatan Guru</th>
              <?php endif; ?>

              <th style="width:100px">Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($members as $i => $m):
            $msid = (int)$m['id'];
            $cur  = $existing[$msid] ?? null;
            $st   = $cur['status'] ?? 'draft';
            $stInfo = $fgStatuses[$st] ?? $fgStatuses['draft'];
            $locked = in_array($st, ['approved','published'], true);
            $disabledAttr = ($ro || $locked) ? 'disabled' : '';
            $vCa = $cur['catatan_guru'] ?? '';
            
            if ($isTK) {
                $vBintang = $cur['nilai_sikap'] ?? null; 
            } else {
                $vSi = $cur['nilai_sikap']        ?? '';
                $vPe = $cur['nilai_pengetahuan']  ?? '';
                $vKe = $cur['nilai_keterampilan'] ?? '';
                $vals = array_filter([$vSi,$vPe,$vKe], fn($x)=>$x!==null && $x!=='');
                $avg  = $vals ? array_sum(array_map('floatval',$vals))/count($vals) : null;
                $pk   = kkm_predikat($predikatJenjang, $avg);
            }
          ?>
            <tr>
              <td class="text-center">
                <input type="checkbox" name="sel[<?= $msid ?>]" value="1"
                  class="rowSel" <?= ($ro || $locked) ? 'disabled' : '' ?>>
              </td>
              <td><?= $i+1 ?></td>
              <td><?= esc($m['nis']) ?></td>
              <td><strong><?= esc($m['nama']) ?></strong></td>
              
              <?php if ($isTK): ?>
                <td class="text-center">
                    <?= $vBintang !== null ? '<strong>'.esc((string)(float)$vBintang).'</strong>' : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="text-center">
                    <?php if ($vBintang !== null): 
                        $starCount = max(1, min(4, (int)round((float)$vBintang)));
                    ?>
                        <div style="color:#f59e0b; font-size:1.15rem; letter-spacing:1px;">
                            <?= str_repeat('★', $starCount) . str_repeat('☆', 4 - $starCount) ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td style="min-width: 200px;">
                    <textarea class="input input-sm" name="ca[<?= $msid ?>]" maxlength="1000" rows="2"
                              style="width: 100%; resize: vertical;"
                              placeholder="Wajib diisi"
                              data-note-field data-row-id="<?= $msid ?>"
                              <?= $disabledAttr ?>><?= esc((string)$vCa) ?></textarea>
                </td>
                <td style="text-align:center;">
                    <?php if (!empty($cur['image_path'])): ?>
                        <img src="<?= esc(uploads_url($cur['image_path'])) ?>" style="max-width: 50px; height: 50px; object-fit: cover; display:block; margin: 0 auto 5px; border-radius: 4px; border: 1px solid #ccc;">
                    <?php endif; ?>
                    <input type="file" name="img[<?= $msid ?>]" accept="image/*" class="input input-sm" style="width: 100px;" <?= $disabledAttr ?>>
                </td>
              <?php else: ?>
                <td class="text-center"><?= $vSi !== '' && $vSi !== null ? '<strong>'.esc((string)(float)$vSi).'</strong>' : '<span class="text-muted">—</span>' ?></td>
                <td class="text-center"><?= $vPe !== '' && $vPe !== null ? '<strong>'.esc((string)(float)$vPe).'</strong>' : '<span class="text-muted">—</span>' ?></td>
                <td class="text-center"><?= $vKe !== '' && $vKe !== null ? '<strong>'.esc((string)(float)$vKe).'</strong>' : '<span class="text-muted">—</span>' ?></td>
                <td class="text-center<?= kkm_below($avg, $subjectKkm) ? ' cell-kkm-below' : '' ?>">
                  <?php if ($avg !== null): ?>
                    <strong class="<?= kkm_below($avg, $subjectKkm) ? 'text-kkm-below' : '' ?>"><?= esc((string)round($avg,2)) ?></strong>
                    <div class="text-xs text-muted"><?= esc($pk['grade']) ?> · <?= esc($pk['predikat']) ?></div>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><input class="input input-sm" name="ca[<?= $msid ?>]" maxlength="1000"
                           value="<?= esc((string)$vCa) ?>" placeholder="Wajib diisi"
                           data-note-field data-row-id="<?= $msid ?>"
                           <?= $disabledAttr ?>></td>
              <?php endif; ?>

              <td><span class="badge <?= esc($stInfo['class']) ?>"><?= esc($stInfo['label']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if (!$ro): ?>
        <div class="between mt-4">
          <span class="text-sm text-muted">"Simpan Draft" boleh sebagian/satu per satu — catatan guru tidak wajib dan tidak memengaruhi siswa lain. Catatan guru baru diwajibkan saat mengajukan ke Kepsek: centang baris yang akan diajukan, lalu klik "Ajukan ke Kepsek" — hanya baris yang dicentang yang wajib melengkapi catatan. File foto (opsional) akan langsung tersimpan.</span>
          <div class="row" style="gap:.5rem">
            <button class="btn btn-secondary" type="submit" name="op" value="save">💾 Simpan Draft</button>
            <button class="btn btn-primary"   type="submit" name="op" value="submit">📤 Ajukan ke Kepsek</button>
          </div>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('fgForm');
  const all = document.getElementById('selAll');
  if (all) {
    all.addEventListener('change', () => {
      document.querySelectorAll('.rowSel').forEach(cb => { if (!cb.disabled) cb.checked = all.checked; });
    });
  }

  if (!form) return;

  form.addEventListener('submit', function(e){
    const submitter = e.submitter;
    const action = submitter && submitter.name === 'op' ? submitter.value : null;
    const noteFields = form.querySelectorAll('[data-note-field]');
    const shouldRequireAll = action !== 'submit';

    noteFields.forEach(field => {
      const rowId = field.getAttribute('data-row-id');
      const checkbox = rowId ? form.querySelector('input.rowSel[name="sel[' + rowId + ']" ]') : null;
      const shouldRequire = shouldRequireAll || (checkbox && checkbox.checked);
      field.required = shouldRequire;
    });

    if (action === 'submit') {
      const selectedCount = form.querySelectorAll('.rowSel:checked').length;
      if (selectedCount === 0) {
        e.preventDefault();
        alert('Pilih minimal satu siswa untuk diajukan.');
      }
    }
  });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>