<?php
/**
 * Stage 4 — Absensi Harian (input per rombel per tanggal).
 * Roles: administrator, admin, kepsek (read-mostly), guru (wali/pengampu).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';

$user = require_view('attendance');
$pdo  = db();
$sc   = active_scope();
$err  = null;

$rombels = accessible_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
$tanggal = (string)($_GET['tanggal'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) $tanggal = date('Y-m-d');

// Default: first accessible rombel
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$rombel = null; $members = []; $existing = [];
if ($rid) {
    $rombel  = assert_can_access_rombel($user, $rid);
    $members = rombel_members($rid);
    $existing = attendance_for($rid, $tanggal);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rombel) {
    try {
        csrf_check();
        if (!can_edit('attendance')) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        if (is_view_only('attendance', $user)) throw new RuntimeException('Kepsek hanya melihat data, tidak menyimpan absensi.');

        $op = (string)($_POST['op'] ?? '');
        if ($op === 'save') {
            $statuses = $_POST['status'] ?? [];   // [student_id => H/I/S/A]
            $catatans = $_POST['catatan'] ?? [];  // [student_id => string]

            $valid = array_keys(att_statuses());
            $upsert = $pdo->prepare(
                "INSERT INTO attendance (rombel_id, student_id, tanggal, status, catatan, recorded_by)
                 VALUES (:r, :s, :d, :st, :c, :u)
                 ON DUPLICATE KEY UPDATE status=VALUES(status), catatan=VALUES(catatan), recorded_by=VALUES(recorded_by)"
            );
            $del = $pdo->prepare("DELETE FROM attendance WHERE rombel_id=:r AND student_id=:s AND tanggal=:d");

            $count = 0;
            foreach ($members as $m) {
                $sid = (int)$m['id'];
                $st  = (string)($statuses[$sid] ?? '');
                $ct  = trim((string)($catatans[$sid] ?? ''));
                if ($ct !== '' && mb_strlen($ct) > 160) $ct = mb_substr($ct, 0, 160);

                if ($st === '') {
                    $del->execute(['r'=>$rid,'s'=>$sid,'d'=>$tanggal]);
                    continue;
                }
                if (!in_array($st, $valid, true)) continue;
                $upsert->execute([
                    'r'  => $rid,
                    's'  => $sid,
                    'd'  => $tanggal,
                    'st' => $st,
                    'c'  => $ct ?: null,
                    'u'  => $user['id'],
                ]);
                $count++;
            }
            audit('save_attendance', "rombel:$rid", ['date'=>$tanggal, 'n'=>$count]);
            flash('success', "Absensi tanggal $tanggal disimpan ($count siswa).");
            redirect('attendance.php?rombel_id=' . $rid . '&tanggal=' . $tanggal);
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$page_title = 'Absensi Harian';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Pilih Rombel &amp; Tanggal</h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('attendance_recap.php' . ($rid ? '?rombel_id='.$rid : ''))) ?>">Rekap Bulanan →</a>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="align-items:flex-end">
      <div class="field" style="flex:2; min-width:240px">
        <label class="label">Rombel</label>
        <select class="select" name="rombel_id" onchange="this.form.submit()">
          <?php if (!$rombels): ?><option value="">— Tidak ada rombel yang dapat Anda akses —</option><?php endif; ?>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rid==(int)$r['id']?'selected':'' ?>>
              <?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?><?= $r['wali_nama']?' (wali: '.esc($r['wali_nama']).')':'' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:1; min-width:160px">
        <label class="label">Tanggal</label>
        <input class="input" type="date" name="tanggal" value="<?= esc($tanggal) ?>" onchange="this.form.submit()">
      </div>
      <div class="field" style="flex:0 0 auto">
        <button class="btn btn-secondary" type="submit">Muat</button>
      </div>
    </form>
  </div>
</div>

<?php if ($rombel): ?>
<div class="card mt-4">
  <div class="card-header">
    <h3 class="card-title">
      <?= esc($rombel['jenjang'].' '.$rombel['tingkat'].' · '.$rombel['nama']) ?>
      <span class="text-sm text-muted">— <?= esc(date('l, d M Y', strtotime($tanggal))) ?></span>
    </h3>
    <div class="row" style="gap:.5rem">
      <span class="badge badge-success" id="cntH">H: 0</span>
      <span class="badge badge-warning" id="cntI">I: 0</span>
      <span class="badge badge-info"    id="cntS">S: 0</span>
      <span class="badge badge-danger"  id="cntA">A: 0</span>
    </div>
  </div>
  <div class="card-body">
    <?php if (!$members): ?>
      <div class="empty">Belum ada anggota di rombel ini. Tambahkan anggota di halaman <a href="<?= esc(url('admin/rombel.php?manage='.$rid)) ?>">Rombel</a>.</div>
    <?php else: ?>
      <form method="post" id="attForm">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">

        <?php if (!is_view_only('attendance', $user)): ?>
          <div class="row mb-3" style="gap:.5rem; flex-wrap:wrap">
            <span class="text-sm text-muted" style="align-self:center">Tandai semua sebagai:</span>
            <?php foreach (att_statuses() as $code => $info): ?>
              <button type="button" class="btn btn-secondary btn-sm" data-bulk="<?= esc($code) ?>">
                <?= esc($info['label']) ?> (<?= esc($code) ?>)
              </button>
            <?php endforeach; ?>
            <button type="button" class="btn btn-ghost btn-sm" data-bulk="">Kosongkan</button>
          </div>
        <?php endif; ?>

        <div class="table-wrap">
          <table class="t att-table">
            <thead>
              <tr>
                <th style="width:48px">#</th>
                <th>NISN</th>
                <th>Nama</th>
                <th style="width:60px">JK</th>
                <th style="width:320px">Status</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($members as $i => $m): $sid = (int)$m['id']; $cur = $existing[$sid]['status'] ?? ''; $note = $existing[$sid]['catatan'] ?? ''; ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= esc($m['nisn']) ?></td>
                <td><strong><?= esc($m['nama']) ?></strong></td>
                <td><?= esc($m['jk']) ?></td>
                <td>
                  <div class="seg seg-att">
                    <?php foreach (att_statuses() as $code => $info): ?>
                      <label class="seg-btn att-<?= esc($code) ?> <?= $cur===$code?'is-on':'' ?>">
                        <input type="radio" name="status[<?= $sid ?>]" value="<?= esc($code) ?>" <?= $cur===$code?'checked':'' ?> <?= is_view_only('attendance', $user)?'disabled':'' ?>>
                        <?= esc($code) ?>
                      </label>
                    <?php endforeach; ?>
                    <label class="seg-btn att-N <?= $cur===''?'is-on':'' ?>">
                      <input type="radio" name="status[<?= $sid ?>]" value="" <?= $cur===''?'checked':'' ?> <?= is_view_only('attendance', $user)?'disabled':'' ?>>
                      —
                    </label>
                  </div>
                </td>
                <td>
                  <input class="input input-sm" name="catatan[<?= $sid ?>]" maxlength="160"
                         value="<?= esc($note) ?>" placeholder="(opsional)" <?= is_view_only('attendance', $user)?'disabled':'' ?>>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (!is_view_only('attendance', $user)): ?>
          <div class="between mt-4">
            <span class="text-sm text-muted">Status kosong = catatan absensi dihapus untuk siswa tsb.</span>
            <button class="btn btn-primary" type="submit">Simpan Absensi</button>
          </div>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
(function() {
  const form = document.getElementById('attForm');
  if (!form) return;

  function refresh() {
    const counts = { H:0, I:0, S:0, A:0 };
    form.querySelectorAll('input[type=radio]:checked').forEach(r => {
      if (r.value && counts[r.value] !== undefined) counts[r.value]++;
    });
    document.getElementById('cntH').textContent = 'H: ' + counts.H;
    document.getElementById('cntI').textContent = 'I: ' + counts.I;
    document.getElementById('cntS').textContent = 'S: ' + counts.S;
    document.getElementById('cntA').textContent = 'A: ' + counts.A;

    // Repaint segment highlight
    form.querySelectorAll('.seg-att').forEach(seg => {
      seg.querySelectorAll('label').forEach(lab => {
        const r = lab.querySelector('input');
        lab.classList.toggle('is-on', r && r.checked);
      });
    });
  }

  form.addEventListener('change', refresh);

  document.querySelectorAll('[data-bulk]').forEach(btn => {
    btn.addEventListener('click', () => {
      const v = btn.getAttribute('data-bulk');
      form.querySelectorAll('input[type=radio]').forEach(r => {
        if (r.value === v) r.checked = true;
      });
      refresh();
    });
  });

  refresh();
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>