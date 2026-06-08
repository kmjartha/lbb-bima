<?php
/**
 * Stage 6 — Nilai Akhir PTS/PAS (input guru pengampu).
 * Workflow: draft → submitted → (approved | revised) → published.
 * Bulk autofill mengambil rata-rata berbobot dari grades_daily semester aktif.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/grading_helpers.php';
require_once __DIR__ . '/../includes/final_grades_helpers.php';

$user = require_view('final_grades');
$pdo  = db();
$sc   = active_scope();
$err  = null;

$rombels = accessible_rombel($user);
$rid = int_or_null($_GET['rombel_id'] ?? null);
$sid = int_or_null($_GET['subject_id'] ?? null);
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $subjects = []; $members = []; $existing = [];
$counts = ['draft'=>0,'submitted'=>0,'revised'=>0,'approved'=>0,'published'=>0];

if ($rid) {
    $rombel   = assert_can_access_rombel($user, $rid);
    $subjects = accessible_subjects_for_rombel($user, $rid);
    if (!$sid && $subjects) $sid = (int)$subjects[0]['id'];
    if ($sid) {
        assert_can_grade_subject($user, $rid, $sid);
        $members  = rombel_members($rid);
        $existing = final_grades_for($rid, $sid, $sc['semester'], $sc['period']);

        // SPK auto-sync: nilai S/P/K di Nilai Akhir TIDAK boleh diinput manual.
        // Setiap kali halaman dimuat, kita sinkronkan otomatis dari rata-rata
        // berbobot grades_daily. Baris yang sudah submitted/approved/published
        // TIDAK ditimpa (menjaga audit trail). Kepsek juga tidak menulis di sini.
        if (can_edit('final_grades', $user) && !scope_is_locked() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $autoCount = 0;
            foreach ($members as $m) {
                $msid = (int)$m['id'];
                $cur  = $existing[$msid] ?? null;
                if ($cur && in_array($cur['status'], ['submitted','approved','published'], true)) continue;

                $w = weighted_average_ranah($rid, $sid, $msid, $sc['semester']);
                if ($w['sikap'] === null && $w['pengetahuan'] === null && $w['keterampilan'] === null) continue;

                // Skip jika nilai sudah identik (hindari write-loop yg sia-sia)
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
                    'status'=> $cur['status'] ?? 'draft',
                ]);
                $autoCount++;
            }
            if ($autoCount > 0) {
                // refresh snapshot so the table renders the just-synced values
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
        if (is_view_only('final_grades', $user)) throw new RuntimeException('Kepsek tidak menginput nilai akhir; gunakan halaman Verifikasi.');

        $op = (string)($_POST['op'] ?? '');

        if ($op === 'autofill') {
            $count = 0;
            foreach ($members as $m) {
                $msid = (int)$m['id'];
                $cur  = $existing[$msid] ?? null;
                // Jangan timpa baris yang sudah submitted/approved/published
                if ($cur && in_array($cur['status'], ['submitted','approved','published'], true)) continue;

                $w = weighted_average_ranah($rid, $sid, $msid, $sc['semester']);
                if ($w['sikap'] === null && $w['pengetahuan'] === null && $w['keterampilan'] === null) continue;

                final_grade_upsert([
                    'rombel_id'=>$rid,'subject_id'=>$sid,'student_id'=>$msid,
                    'semester'=>$sc['semester'],'period_kind'=>$sc['period'],
                    'nilai_sikap'=>$w['sikap'],
                    'nilai_pengetahuan'=>$w['pengetahuan'],
                    'nilai_keterampilan'=>$w['keterampilan'],
                    'catatan_guru'=>$cur['catatan_guru'] ?? null,
                    'status'=> $cur['status'] ?? 'draft',
                ]);
                $count++;
            }
            audit('autofill_final_grades', "rombel:$rid/subj:$sid",
                ['sem'=>$sc['semester'],'period'=>$sc['period'],'n'=>$count]);
            flash('success', "Autofill rata-rata berbobot dari penilaian harian: $count siswa.");
            redirect("final_grades.php?rombel_id=$rid&subject_id=$sid");
        }

        if ($op === 'save' || $op === 'submit') {
            if (scope_is_locked()) throw new RuntimeException('Semester terkunci, tidak bisa menyimpan/mengajukan.');

            $vCa = $_POST['ca'] ?? [];
            $sel = $_POST['sel'] ?? [];

            $count = 0;
            foreach ($members as $m) {
                $msid = (int)$m['id'];
                $cur  = $existing[$msid] ?? null;
                $existingId = $cur['id'] ?? null;

                // SPK auto-sync: nilai S/P/K SELALU dihitung ulang dari rata-rata
                // berbobot grades_daily — POST untuk si/pe/ke diabaikan total.
                $w  = weighted_average_ranah($rid, $sid, $msid, $sc['semester']);
                $si = $w['sikap'];
                $pe = $w['pengetahuan'];
                $ke = $w['keterampilan'];

                $ca = trim((string)($vCa[$msid] ?? ''));
                $ca = $ca === '' ? null : mb_substr($ca, 0, 1000);

                if ($cur && in_array($cur['status'], ['approved','published'], true)) continue;

                if ($si === null && $pe === null && $ke === null && $ca === null && !$existingId) continue;

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
                    'catatan_guru'=>$ca, 'status'=>$newStatus,
                ]);
                $count++;
            }
            audit($op === 'submit' ? 'submit_final_grades' : 'save_final_grades',
                "rombel:$rid/subj:$sid",
                ['sem'=>$sc['semester'],'period'=>$sc['period'],'n'=>$count]);
            flash('success', ($op === 'submit' ? 'Nilai diajukan untuk verifikasi: ' : 'Nilai akhir disimpan: ') . "$count siswa.");
            redirect("final_grades.php?rombel_id=$rid&subject_id=$sid");
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$page_title = 'Nilai Akhir ' . $sc['period'];
require __DIR__ . '/../includes/header.php';
$kkmJenjang = $rombel['jenjang'] ?? 'SD';
$fgStatuses = fg_statuses();
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Nilai Akhir <?= esc($sc['period']) ?> · Semester <?= esc($sc['semester']) ?></h3>
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
              <?= esc(($s['kode']?($s['kode'].' · '):'').$s['nama']) ?>
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
    <?php $ro = (is_view_only('final_grades', $user)) || scope_is_locked(); ?>

    <?php if (!$ro): ?>
      <div class="alert alert-info" style="margin-bottom:.75rem">
        ⚡ <strong>Auto-sync aktif</strong> — nilai Sikap, Pengetahuan & Keterampilan diambil otomatis
        dari rata-rata berbobot Penilaian Harian (<code>weighted_average_ranah</code>) dan tidak dapat
        diedit manual. Untuk mengubah nilai, edit di Penilaian Harian.
      </div>
    <?php endif; ?>

    <form method="post" id="fgForm">
      <?= csrf_field() ?>

      <div class="table-wrap">
        <table class="t">
          <thead>
            <tr>
              <th style="width:36px"><input type="checkbox" id="selAll" <?= $ro?'disabled':'' ?>></th>
              <th style="width:36px">#</th>
              <th>NISN</th>
              <th>Nama</th>
              <th style="width:90px"><span class="badge badge-info">Sikap</span></th>
              <th style="width:110px"><span class="badge badge-primary">Pengetahuan</span></th>
              <th style="width:110px"><span class="badge badge-success">Keterampilan</span></th>
              <th style="width:140px">Σ Gabungan SPK · Predikat</th>
              <th>Catatan Guru</th>
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
            $vSi = $cur['nilai_sikap']        ?? '';
            $vPe = $cur['nilai_pengetahuan']  ?? '';
            $vKe = $cur['nilai_keterampilan'] ?? '';
            $vCa = $cur['catatan_guru']       ?? '';
            // Σ Gabungan SPK: rata-rata dari Sikap + Pengetahuan + Keterampilan,
            // dipetakan ke predikat KKM. Nilai inilah yang akan tampil di rapor siswa.
            $vals = array_filter([$vSi,$vPe,$vKe], fn($x)=>$x!==null && $x!=='');
            $avg  = $vals ? array_sum(array_map('floatval',$vals))/count($vals) : null;
            $pk   = kkm_predikat($kkmJenjang, $avg);
          ?>
            <tr>
              <td class="text-center">
                <input type="checkbox" name="sel[<?= $msid ?>]" value="1"
                  class="rowSel" <?= ($ro || $locked) ? 'disabled' : '' ?>>
              </td>
              <td><?= $i+1 ?></td>
              <td><?= esc($m['nisn']) ?></td>
              <td><strong><?= esc($m['nama']) ?></strong></td>
              <td class="text-center"><?= $vSi !== '' && $vSi !== null ? '<strong>'.esc((string)(float)$vSi).'</strong>' : '<span class="text-muted">—</span>' ?></td>
              <td class="text-center"><?= $vPe !== '' && $vPe !== null ? '<strong>'.esc((string)(float)$vPe).'</strong>' : '<span class="text-muted">—</span>' ?></td>
              <td class="text-center"><?= $vKe !== '' && $vKe !== null ? '<strong>'.esc((string)(float)$vKe).'</strong>' : '<span class="text-muted">—</span>' ?></td>
              <td class="text-center">
                <?php if ($avg !== null): ?>
                  <strong><?= esc((string)round($avg,2)) ?></strong>
                  <div class="text-xs text-muted"><?= esc($pk['grade']) ?> · <?= esc($pk['predikat']) ?></div>
                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
              </td>
              <td><input class="input input-sm" name="ca[<?= $msid ?>]" maxlength="1000"
                         value="<?= esc((string)$vCa) ?>" placeholder="(opsional)" <?= $disabledAttr ?>></td>
              <td><span class="badge <?= esc($stInfo['class']) ?>"><?= esc($stInfo['label']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if (!$ro): ?>
        <div class="between mt-4">
          <span class="text-sm text-muted">Centang baris yang akan diajukan, lalu klik "Ajukan ke Kepsek". "Simpan" hanya menyimpan draft tanpa ubah status.</span>
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
  const all = document.getElementById('selAll');
  if (!all) return;
  all.addEventListener('change', () => {
    document.querySelectorAll('.rowSel').forEach(cb => { if (!cb.disabled) cb.checked = all.checked; });
  });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
