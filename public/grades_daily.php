<?php
/**
 * Stage 5 — Penilaian Harian SKP (enhanced UX).
 * Pick rombel + mapel + topik + tanggal, then input Si/Pe/Ke per siswa.
 * Mode Otomatis: Jika rombel TK, mode input berubah menjadi Bintang & Deskripsi.
 * Keyboard: Tab between inputs, Enter to save, ↑↓ to move rows.
 * Absent students dimmed & locked unless Override is checked.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';
require_once __DIR__ . '/../includes/grading_helpers.php';

$user   = require_view('grades_daily');
$pdo    = db();
$sc     = active_scope();
$bucket = active_bucket();
$err    = null;

$rombels = accessible_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
$sid     = int_or_null($_GET['subject_id'] ?? null);
$tid     = int_or_null($_GET['topic_id'] ?? null);
$tanggal = (string)($_GET['tanggal'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) $tanggal = date('Y-m-d');

if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $subjects = []; $topics = []; $members = [];
$existing = []; $att = []; $topic = null;
$isTK = false;

if ($rid) {
    $rombel   = assert_can_access_rombel($user, $rid);
    // Cek apakah rombel ini TK (dari jenjang atau nama)
    $isTK     = (stripos($rombel['jenjang'] ?? '', 'TK') !== false) || (stripos($rombel['nama'] ?? '', 'TK') !== false);
    
    $subjects = accessible_subjects_for_rombel($user, $rid);
    if (!$sid && $subjects) $sid = (int)$subjects[0]['id'];
    if ($sid) {
        assert_can_grade_subject($user, $rid, $sid);
        $topics = topics_for($rid, $sid, $sc['semester']);
        if (!$tid && $topics) $tid = (int)$topics[0]['id'];
        if ($tid) {
            $topic = topic_by_id($tid);
            if (!$topic || (int)$topic['rombel_id'] !== $rid || (int)$topic['subject_id'] !== $sid) {
                $tid = null; $topic = null;
            }
        }
        $members  = rombel_members($rid);
        $att      = attendance_for($rid, $tanggal);
        if ($tid) $existing = grades_for_topic_date($rid, $sid, $tid, $sc['semester'], $bucket, $tanggal);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rombel && $topic) {
    try {
        csrf_check();
        if (!can_edit('grades_daily')) throw new RuntimeException('Anda hanya memiliki akses lihat.');
        if (scope_is_locked()) throw new RuntimeException('Semester aktif terkunci. Buka lock di Tahun Ajaran.');

        $ranahList = ['sikap', 'pengetahuan', 'keterampilan'];
        $values    = $_POST['nilai']     ?? [];
        $descs     = $_POST['deskripsi'] ?? []; // Khusus TK
        $overrides = $_POST['override']  ?? [];

        $del = $pdo->prepare(
            "DELETE FROM grades_daily
             WHERE rombel_id=:r AND subject_id=:s AND topic_id=:t
               AND semester=:sem AND period_bucket=:b AND tanggal=:d"
        );
        $pdo->beginTransaction();
        $del->execute(['r'=>$rid,'s'=>$sid,'t'=>$tid,'sem'=>$sc['semester'],'b'=>$bucket,'d'=>$tanggal]);

        $count = 0;
        foreach ($members as $m) {
            $msid   = (int)$m['id'];
            $st     = $att[$msid]['status'] ?? '';
            $isAbs  = in_array($st, ['I','S','A'], true);
            $ovr    = !empty($overrides[$msid]);
            if ($isAbs && !$ovr) continue;

            $cols = ['rombel_id','subject_id','topic_id','student_id','semester','period_bucket','tanggal','recorded_by'];
            $vals = [':r',':s',':t',':st',':sem',':b',':d',':u'];
            $params = ['r'=>$rid,'s'=>$sid,'t'=>$tid,'st'=>$msid,
                       'sem'=>$sc['semester'],'b'=>$bucket,'d'=>$tanggal,'u'=>$user['id']];

            $any = false;
            
            // Mode TK
            if ($isTK) {
                $rawBintang = trim((string)($values[$msid]['bintang'] ?? ''));
                $rawDesc    = trim((string)($descs[$msid] ?? ''));
                
                if ($rawBintang !== '' || $rawDesc !== '') {
                    if ($rawBintang !== '') {
                        $cols[] = 'bintang'; $vals[] = ':bintang';
                        $params['bintang'] = max(1, min(4, (int)$rawBintang));
                    }
                    if ($rawDesc !== '') {
                        $cols[] = 'deskripsi'; $vals[] = ':deskripsi';
                        $params['deskripsi'] = $rawDesc;
                    }
                    $any = true;
                }
            } 
            // Mode Non-TK
            else {
                foreach ($ranahList as $ranah) {
                    $col = ranah_column($ranah);
                    $raw = trim((string)($values[$msid][$ranah] ?? ''));
                    if ($raw === '' || !is_numeric($raw)) continue;
                    $v = max(0.0, min(100.0, (float)$raw));
                    $cols[] = $col; $vals[] = ':' . $ranah;
                    $params[$ranah] = $v; $any = true;
                }
            }

            if (!$any) continue;

            $ins = $pdo->prepare("INSERT INTO grades_daily (".implode(',',$cols).") VALUES (".implode(',',$vals).")");
            $ins->execute($params);
            $count++;
        }
        $pdo->commit();
        audit('save_grades_daily', "rombel:$rid/subj:$sid/topic:$tid",
              ['date'=>$tanggal,'bucket'=>$bucket,'n'=>$count]);
        flash('success', "Nilai \"{$topic['judul']}\" tanggal $tanggal disimpan ($count siswa).");
        redirect("grades_daily.php?rombel_id=$rid&subject_id=$sid&topic_id=$tid&tanggal=$tanggal");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $err = $e->getMessage();
    }
}

$page_title = 'Penilaian Harian';
require __DIR__ . '/../includes/header.php';
$ranahDefs = ranah_defs();
$isLocked  = scope_is_locked();
$isReadonly = is_view_only('grades_daily', $user) || $isLocked;
?>

<?php if ($err): ?>
  <div class="alert alert-error"><?= esc($err) ?></div>
<?php endif; ?>

<div class="scope-banner">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
  <span>Anda sedang bekerja di:</span>
  <span class="sbl"><?= esc('TA ' . $sc['year']) ?></span>
  <span>·</span>
  <span class="sbl"><?= esc(ucfirst($sc['semester'])) ?></span>
  <span>·</span>
  <span class="sbl"><?= esc($sc['period']) ?></span>
  <?php if ($isLocked): ?>
    <span class="lock-icon" style="margin-left:auto; display:flex; align-items:center; gap:4px;">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="5" y="11" width="14" height="11" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
      Semester terkunci — mode baca saja
    </span>
  <?php endif; ?>
</div>

<div class="grade-step-card">
  <div class="grade-step-header">
    <span class="step-pill">1</span>
    <h3>Pilih Rombel, Mapel, Topik &amp; Tanggal</h3>
    <a class="btn btn-ghost btn-sm" style="margin-left:auto"
       href="<?= esc(url('grades_topic_recap.php' . ($rid ? '?rombel_id='.$rid.($sid?'&subject_id='.$sid:'') : ''))) ?>">
       Lihat Rekap →
    </a>
  </div>
  <form method="get" id="filterForm">
    <div class="filter-chips">
      <select class="filter-chip-select <?= $rid ? 'is-set' : '' ?>" name="rombel_id"
              onchange="this.form.submit()" title="Pilih Rombel">
        <?php if (!$rombels): ?><option value="">— Belum ada rombel —</option><?php endif; ?>
        <?php foreach ($rombels as $r): ?>
          <option value="<?= (int)$r['id'] ?>" <?= $rid==(int)$r['id']?'selected':'' ?>>
            <?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select class="filter-chip-select <?= $sid ? 'is-set' : '' ?>" name="subject_id"
              onchange="this.form.submit()" title="Pilih Mata Pelajaran">
        <?php if (!$subjects): ?><option value="">— Pilih rombel dulu —</option><?php endif; ?>
        <?php foreach ($subjects as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $sid==(int)$s['id']?'selected':'' ?>>
            <?= esc(($s['kode']?$s['kode'].' · ':'').elective_subject_label($s['nama'], $s['elective_kode'] ?? null)) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select class="filter-chip-select <?= $tid ? 'is-set' : '' ?>" name="topic_id"
              onchange="this.form.submit()" title="Pilih Topik/Subjek Penilaian" style="max-width:260px">
        <?php if (!$topics): ?><option value="">— Pilih mapel dulu —</option><?php endif; ?>
        <?php foreach ($topics as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $tid==(int)$t['id']?'selected':'' ?>>
            <?= esc(($t['kode']?$t['kode'].' · ':'').$t['judul']) ?> (×<?= esc((string)$t['bobot']) ?>)
          </option>
        <?php endforeach; ?>
      </select>

      <input class="date-chip" type="date" name="tanggal" value="<?= esc($tanggal) ?>"
             onchange="this.form.submit()" title="Tanggal penilaian" max="<?= date('Y-m-d') ?>">

      <button class="btn btn-secondary btn-sm" type="submit">Muat</button>
    </div>

    <?php if ($rombel && $sid && !$topics): ?>
      <div class="alert alert-warning" style="margin:.5rem 1rem 1rem">
        Belum ada subjek penilaian untuk mapel ini di semester <strong><?= esc($sc['semester']) ?></strong>.
        <a href="<?= esc(url('admin/subject_topics.php?rombel_id='.$rid.'&subject_id='.$sid)) ?>">Tambahkan sekarang →</a>
      </div>
    <?php endif; ?>
  </form>
</div>

<?php if ($rombel && $topic && $members): ?>
<?php
  $ranahList   = ['sikap', 'pengetahuan', 'keterampilan'];
  $ranahColors = ['sikap'=>'si','pengetahuan'=>'pe','keterampilan'=>'ke'];
  $totalMembers = count($members);
  $absentCount  = 0;
  foreach ($members as $m) {
      $st = $att[(int)$m['id']]['status'] ?? '';
      if (in_array($st, ['I','S','A'], true)) $absentCount++;
  }
  $filledCount = count(array_filter($existing));
  $tanggalFmt  = date('l, d M Y', strtotime($tanggal));
  $isTodayStr  = ($tanggal === date('Y-m-d')) ? '(hari ini)' : '';
?>

<div class="grade-step-card">
  <div class="grade-step-header">
    <span class="step-pill">2</span>
    <h3>Input Nilai <?= $isTK ? '(Mode TK)' : '' ?></h3>
    <div style="margin-left:auto; display:flex; align-items:center; gap:.5rem">
      <div class="prog-ring" id="progRing" title="Siswa sudah terisi">
        <svg width="36" height="36" viewBox="0 0 36 36">
          <circle class="track" cx="18" cy="18" r="14"/>
          <circle class="fill" id="progFill" cx="18" cy="18" r="14"
                  stroke-dasharray="88" stroke-dashoffset="88"/>
        </svg>
        <span class="label" id="progLabel">0</span>
      </div>
      <span class="text-sm text-muted" id="progText">terisi</span>
    </div>
  </div>

  <div class="topic-strip">
    <div>
      <div class="ts-title"><?= esc($topic['judul']) ?></div>
      <div class="ts-meta">
        <?= $topic['kode'] ? esc($topic['kode']).' · ' : '' ?>
        <?= esc($topic['kategori']) ?> · bobot ×<?= esc((string)$topic['bobot']) ?> ·
        <strong><?= esc($tanggalFmt) ?></strong> <?= esc($isTodayStr) ?>
      </div>
    </div>
    <div class="ts-ranah">
      <?php if ($isTK): ?>
        <span class="ranah-badge si">Bintang (1-4)</span>
        <span class="ranah-badge pe">Deskripsi</span>
      <?php else: ?>
        <?php foreach ($ranahList as $r): ?>
          <span class="ranah-badge <?= $ranahColors[$r] ?>">
            <?= esc($ranahDefs[$r]['label']) ?>
          </span>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="counter-bar">
    <div class="counter-item">
      <span class="counter-dot total"></span>
      <span><?= $totalMembers ?> siswa</span>
    </div>
    <div class="counter-item">
      <span class="counter-dot filled"></span>
      <span id="cntFilled">0</span> terisi
    </div>
    <div class="counter-item">
      <span class="counter-dot empty"></span>
      <span id="cntEmpty"><?= $totalMembers ?></span> belum
    </div>
    <div class="counter-item">
      <span class="counter-dot absent"></span>
      <span><?= $absentCount ?> absen</span>
    </div>
    <?php if (!$isReadonly): ?>
      <span class="text-sm text-muted" style="margin-left:auto">
        <span class="kbd">Tab</span> pindah · <span class="kbd">↑↓</span> pindah baris · <span class="kbd">Enter</span> simpan
      </span>
    <?php endif; ?>
  </div>

  <?php if ($isLocked): ?>
    <div class="alert alert-warning" style="margin:1rem 1rem 0">
      Semester ini terkunci — input dinonaktifkan.
    </div>
  <?php endif; ?>

  <form method="post" id="gdForm">
    <?= csrf_field() ?>
    <div class="grade-table-wrap">
      <table class="grade-table" id="gradeTable">
        <thead>
          <tr>
            <th style="width:42px">#</th>
            <th style="min-width:80px">NISN</th>
            <th style="min-width:180px">Nama Siswa</th>
            <th style="width:64px">Absen</th>
            
            <?php if ($isTK): ?>
              <th class="ranah-si" style="width:130px">Rating Bintang</th>
              <th class="ranah-pe" style="min-width:200px">Deskripsi Perkembangan</th>
            <?php else: ?>
              <?php foreach ($ranahList as $r): ?>
                <th class="ranah-<?= esc($ranahColors[$r]) ?>" style="width:100px">
                  <?= esc($ranahDefs[$r]['label']) ?>
                  <span style="font-weight:400; opacity:.7">(0–100)</span>
                </th>
              <?php endforeach; ?>
            <?php endif; ?>

            <th style="width:100px">Override</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($members as $i => $m):
          $msid     = (int)$m['id'];
          $st       = $att[$msid]['status'] ?? '';
          $stInfo   = att_statuses()[$st] ?? null;
          $isAbsent = in_array($st, ['I','S','A'], true);
          $cur      = $existing[$msid] ?? null;
          $hasData  = $cur !== null;
        ?>
          <tr data-row data-sid="<?= $msid ?>" class="<?= $isAbsent ? 'is-absent' : '' ?>">
            <td class="num-cell"><?= $i+1 ?></td>
            <td class="text-sm text-muted" style="font-family:monospace"><?= esc($m['nisn']) ?></td>
            <td class="name-cell">
              <strong><?= esc($m['nama']) ?></strong>
            </td>
            <td>
              <?php if ($stInfo): ?>
                <span class="badge badge-<?= $st==='H'?'success':($st==='I'?'warning':($st==='S'?'info':'danger')) ?>">
                  <?= esc($st) ?>
                </span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            
            <?php if ($isTK): ?>
              <?php 
                $valBintang = $cur ? ($cur['bintang'] ?? null) : null;
                $valDesc    = $cur ? ($cur['deskripsi'] ?? '') : '';
              ?>
              <td>
                <select class="grade-input si" name="nilai[<?= $msid ?>][bintang]"
                        data-row-idx="<?= $i ?>"
                        <?= $isReadonly ? 'readonly disabled' : '' ?>
                        <?= ($isAbsent && !$isReadonly) ? 'data-gated="1" disabled tabindex="-1"' : '' ?>
                        <?= $valBintang !== null ? 'data-filled="1"' : '' ?>>
                  <option value="">-- Pilih --</option>
                  <option value="1" <?= $valBintang == 1 ? 'selected' : '' ?>>★ (1)</option>
                  <option value="2" <?= $valBintang == 2 ? 'selected' : '' ?>>★★ (2)</option>
                  <option value="3" <?= $valBintang == 3 ? 'selected' : '' ?>>★★★ (3)</option>
                  <option value="4" <?= $valBintang == 4 ? 'selected' : '' ?>>★★★★ (4)</option>
                </select>
              </td>
              <td>
                <input class="grade-input pe" type="text" name="deskripsi[<?= $msid ?>]"
                       value="<?= esc($valDesc) ?>" placeholder="Catatan perkembangan..."
                       data-row-idx="<?= $i ?>"
                       <?= $isReadonly ? 'readonly disabled' : '' ?>
                       <?= ($isAbsent && !$isReadonly) ? 'data-gated="1" disabled tabindex="-1"' : '' ?>
                       <?= $valDesc !== '' ? 'data-filled="1"' : '' ?>
                       autocomplete="off" style="width: 100%; min-width:180px;">
              </td>
            <?php else: ?>
              <?php foreach ($ranahList as $r):
                $col   = ranah_column($r);
                $val   = $cur ? $cur[$col] : null;
                $rColor = $ranahColors[$r];
              ?>
                <td>
                  <input class="grade-input <?= esc($rColor) ?>"
                         type="number" step="0.5" min="0" max="100"
                         name="nilai[<?= $msid ?>][<?= esc($r) ?>]"
                         value="<?= $val !== null ? esc(number_format((float)$val, 1)) : '' ?>"
                         data-row-idx="<?= $i ?>" data-ranah="<?= esc($r) ?>"
                         <?= $isReadonly ? 'readonly disabled' : '' ?>
                         <?= ($isAbsent && !$isReadonly) ? 'data-gated="1" disabled tabindex="-1"' : '' ?>
                         autocomplete="off"
                         <?= $val !== null ? 'data-filled="1"' : '' ?>>
                </td>
              <?php endforeach; ?>
            <?php endif; ?>

            <td>
              <?php if ($isAbsent && !$isReadonly): ?>
                <label class="check" title="Izinkan penilaian meski absen">
                  <input type="checkbox" class="ovr" name="override[<?= $msid ?>]" value="1"
                         <?= $hasData ? 'checked' : '' ?>>
                  <span>Izinkan</span>
                </label>
              <?php else: ?>
                <span class="text-muted text-sm">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if (!$isReadonly): ?>
      <div class="save-bar">
        <div class="hint">
          Field kosong = siswa dilewati. Siswa absen perlu centang <em>Izinkan</em> untuk dinilai.
        </div>
        <button class="btn btn-primary" type="submit" id="btnSave">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 13l4 4L19 7"/></svg>
          Simpan Nilai
        </button>
      </div>
    <?php endif; ?>
  </form>
</div>

<div class="sg-toast-wrap" id="toastWrap"></div>

<script>
(function(){
  const form     = document.getElementById('gdForm');
  if (!form) return;
  const progFill = document.getElementById('progFill');
  const progLbl  = document.getElementById('progLabel');
  const progTxt  = document.getElementById('progText');
  const cntFill  = document.getElementById('cntFilled');
  const cntEmpty = document.getElementById('cntEmpty');
  const total    = <?= $totalMembers ?>;
  const CIRCUM   = 88; // 2π × r(14)
  
  // Tentukan jumlah kolom input per baris (2 untuk TK, 3 untuk Non-TK)
  const colsPerRow = <?= $isTK ? 2 : count($ranahList) ?>;

  /* --- Counters & progress ring --- */
  function refreshCounters() {
    let filled = 0;
    form.querySelectorAll('[data-row]').forEach(tr => {
      const inps = tr.querySelectorAll('.grade-input:not([disabled])');
      let rowFilled = false;
      inps.forEach(inp => { if (inp.value.trim() !== '') rowFilled = true; });
      if (rowFilled) filled++;
    });
    cntFill.textContent  = filled;
    cntEmpty.textContent = total - filled;
    progLbl.textContent  = filled;
    const pct = total > 0 ? filled / total : 0;
    progFill.style.strokeDashoffset = (CIRCUM * (1 - pct)).toFixed(2);
    progFill.style.stroke = pct === 1 ? 'var(--c-success-500)' : 'var(--c-primary-500)';
  }

  /* --- Input fill indicator --- */
  function syncFilled(inp) {
    inp.dataset.filled = inp.value.trim() !== '' ? '1' : '0';
  }

  /* --- Keyboard navigation: ↑↓ between rows, Enter = save --- */
  function getInputs() {
    return Array.from(form.querySelectorAll('.grade-input:not([disabled])'));
  }
  form.addEventListener('keydown', e => {
    const inp = e.target;
    if (!inp.classList.contains('grade-input')) return;
    const all = getInputs();
    const idx = all.indexOf(inp);

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const next = all[idx + colsPerRow];
      if (next) { next.focus(); if(next.tagName !== 'SELECT') next.select(); }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const prev = all[idx - colsPerRow];
      if (prev) { prev.focus(); if(prev.tagName !== 'SELECT') prev.select(); }
    } else if (e.key === 'Enter' && inp.tagName !== 'TEXTAREA') {
      e.preventDefault();
      document.getElementById('btnSave')?.click();
    }
  });

  /* --- Override checkbox toggles row inputs --- */
  form.querySelectorAll('.ovr').forEach(cb => {
    const tr   = cb.closest('[data-row]');
    const inps = tr.querySelectorAll('.grade-input');
    const enableRow = on => {
      inps.forEach(inp => {
        if (on) { inp.disabled = false; inp.removeAttribute('tabindex'); }
        else    { inp.disabled = true;  inp.setAttribute('tabindex','-1'); inp.value = ''; }
      });
      if (on) inps[0]?.focus();
      refreshCounters();
    };
    cb.addEventListener('change', () => enableRow(cb.checked));
    if (cb.checked) enableRow(true);
  });

  /* --- Live input events --- */
  form.addEventListener('input', e => {
    if (e.target.classList.contains('grade-input')) {
      syncFilled(e.target);
      refreshCounters();
    }
  });

  /* Initial state */
  form.querySelectorAll('.grade-input').forEach(syncFilled);
  refreshCounters();

  /* --- Toast helper (auto-dismiss) --- */
  window.sgToast = function(msg, type = 'success') {
    const wrap = document.getElementById('toastWrap');
    const t = document.createElement('div');
    t.className = 'sg-toast ' + type;
    t.textContent = msg;
    wrap.appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 280); }, 3000);
  };

  /* --- Submit feedback --- */
  const btnSave = document.getElementById('btnSave');
  if (btnSave) {
    form.addEventListener('submit', () => {
      btnSave.disabled = true;
      btnSave.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Menyimpan…';
    });
  }
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>