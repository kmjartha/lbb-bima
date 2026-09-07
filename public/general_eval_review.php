<?php
/**
 * Verifikasi Deskripsi Umum — sub-menu Verifikasi, khusus Kepsek (filtered
 * jenjang) / Admin mereview General Description / Narrative yang diisi wali
 * kelas per siswa (tabel general_evaluations). Aksi: Approve, Minta Revisi,
 * Bulk approve. Pola alur sama seperti final_grades_review.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/wali_helpers.php';

$user = require_view('general_eval_review');
$pdo  = db();
$sc   = active_scope();
$err  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        if (!can_edit('general_eval_review')) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        $op  = (string)($_POST['op'] ?? '');
        $ids = array_map('intval', (array)($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!$ids) throw new RuntimeException('Tidak ada baris terpilih.');
        if (!in_array($op, ['approve', 'revise'], true)) throw new RuntimeException('Aksi tidak dikenal.');

        // For kepsek: jenjang gating per-row
        $check = $pdo->prepare(
            "SELECT ge.id, ge.status, r.jenjang FROM general_evaluations ge
             JOIN rombel r ON r.id = ge.rombel_id
             WHERE ge.id=:i AND r.academic_year_id = :y"
        );

        $total   = count($ids);
        $changed = 0;
        foreach ($ids as $id) {
            $check->execute(['i' => $id, 'y' => $sc['year_id']]);
            $row = $check->fetch();
            if (!$row) continue;
            if ($user['role'] === 'kepsek' && !empty($user['jenjang']) && $row['jenjang'] !== $user['jenjang']) continue;

            if ($op === 'approve' && in_array($row['status'], ['submitted', 'revised'], true)) {
                general_eval_set_status($id, 'approved', (int)$user['id']); $changed++;
            } elseif ($op === 'revise' && in_array($row['status'], ['submitted', 'approved'], true)) {
                general_eval_set_status($id, 'revised', (int)$user['id']); $changed++;
            }
        }
        audit("review_{$op}_general_eval", null, ['n' => $changed, 'sem' => $sc['semester'], 'period' => $sc['period']]);

        $opLabel = $op === 'approve' ? 'disetujui' : 'dikembalikan untuk revisi';
        $msg = "$changed dari $total baris terpilih $opLabel.";
        $skipped = $total - $changed;
        if ($skipped > 0) {
            $reason = $op === 'approve'
                ? 'sudah berstatus Disetujui, atau di luar akses jenjang Anda'
                : 'masih berstatus Draft, atau di luar akses jenjang Anda';
            $msg .= " $skipped baris dilewati ($reason).";
        }
        flash($changed > 0 ? 'success' : 'error', $msg);
        $backTo = 'general_eval_review.php';
        $backRombel = (int)($_POST['rombel_id'] ?? 0);
        if ($backRombel > 0) $backTo .= '?rombel_id=' . $backRombel;
        redirect($backTo);
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$queue = general_eval_review_queue($user, $sc['semester'], $sc['period'], $sc['year_id']);
$queueByRombel = [];
foreach ($queue as $r) {
    $rid = (int)$r['rombel_id'];
    if (!isset($queueByRombel[$rid])) {
        $label = $r['jenjang'] . ' ' . $r['tingkat'] . ' · ' . $r['rombel_nama'];
        $sub   = $r['submitted_by_name']
            ? 'Wali: ' . $r['submitted_by_name'] . ($r['submitted_by_niy'] ? ' · ' . $r['submitted_by_niy'] : '')
            : 'Wali tidak diketahui';
        $queueByRombel[$rid] = ['label' => $label, 'sub' => $sub, 'rows' => []];
    }
    $queueByRombel[$rid]['rows'][] = $r;
}

// Flow: tampilkan daftar rombel dulu (ringkasan status per rombel), baru
// setelah klik "Detail" tampilkan tabel siswa untuk rombel tersebut.
$selectedRombel = isset($_GET['rombel_id']) ? (int)$_GET['rombel_id'] : 0;
if ($selectedRombel > 0 && !isset($queueByRombel[$selectedRombel])) {
    $selectedRombel = 0; // sudah tidak ada baris menunggu utk rombel ini
}

$page_title = 'Verifikasi Deskripsi Umum';
require __DIR__ . '/../includes/header.php';
$geStatuses = ge_statuses();
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Verifikasi Deskripsi Umum · <?= esc($sc['period']) ?> · Semester <?= esc(ucfirst($sc['semester'])) ?></h3>
    <div class="row" style="gap:.5rem">
      <a class="btn btn-ghost btn-sm" href="<?= esc(url('final_grades_review.php')) ?>">↔ Verifikasi Nilai</a>
      <a class="btn btn-ghost btn-sm" href="<?= esc(url('general_eval.php')) ?>">← Kembali ke Input</a>
    </div>
  </div>
  <div class="card-body">
    <div class="text-sm">
      <strong><?= count($queue) ?></strong> baris menunggu tindak lanjut
      <?= ($user['role'] === 'kepsek' && !empty($user['jenjang'])) ? '· jenjang <strong>' . esc($user['jenjang']) . '</strong>' : '' ?>.
      Mereview General Description / Narrative yang diisi wali kelas untuk masing-masing siswa.
    </div>
  </div>
</div>

<?php if (!$queue): ?>
  <div class="card mt-4"><div class="card-body"><div class="empty">Tidak ada narasi umum yang sedang menunggu verifikasi pada periode &amp; semester aktif.</div></div></div>

<?php elseif ($selectedRombel === 0): ?>
  <!-- Langkah 1: daftar rombel -->
  <div class="card mt-4">
    <div class="card-body">
      <div class="table-wrap">
        <table class="t">
          <thead>
            <tr>
              <th>Rombel</th>
              <th>Wali Kelas</th>
              <th style="width:110px" class="text-center">Jumlah Siswa</th>
              <th style="width:260px">Status</th>
              <th style="width:100px"></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($queueByRombel as $rid => $group):
            $counts = ['submitted' => 0, 'revised' => 0, 'approved' => 0];
            foreach ($group['rows'] as $r) { if (isset($counts[$r['status']])) $counts[$r['status']]++; }
          ?>
            <tr>
              <td><strong><?= esc($group['label']) ?></strong></td>
              <td class="text-sm text-muted"><?= esc($group['sub']) ?></td>
              <td class="text-center"><?= count($group['rows']) ?></td>
              <td>
                <?php foreach (['submitted', 'revised', 'approved'] as $stKey): if (!$counts[$stKey]) continue;
                  $stInfo = $geStatuses[$stKey];
                ?>
                  <span class="badge <?= esc($stInfo['class']) ?>"><?= esc($stInfo['label']) ?> <?= (int)$counts[$stKey] ?></span>
                <?php endforeach; ?>
              </td>
              <td class="text-right">
                <a class="btn btn-primary btn-sm" href="<?= esc(url('general_eval_review.php?rombel_id=' . $rid)) ?>">Detail →</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- Langkah 2: detail siswa untuk 1 rombel terpilih -->
  <?php $group = $queueByRombel[$selectedRombel]; ?>
  <div class="row mb-3">
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('general_eval_review.php')) ?>">← Kembali ke daftar rombel</a>
  </div>
  <form method="post" id="rvForm">
    <?= csrf_field() ?>
    <input type="hidden" name="rombel_id" value="<?= (int)$selectedRombel ?>">
    <div class="card">
      <div class="card-body">
        <div class="row mb-2" style="gap:.5rem; flex-wrap:wrap">
          <button class="btn btn-success btn-sm" type="submit" name="op" value="approve" id="btnApprove">✅ Setujui Terpilih</button>
          <button class="btn btn-warning btn-sm" type="submit" name="op" value="revise" id="btnRevise">↩ Minta Revisi</button>
          <span class="text-sm text-muted" style="align-self:center">Setujui hanya untuk baris ber-status <em>diajukan/revisi</em>; minta revisi bisa dari <em>disetujui</em>.</span>
        </div>
        <div class="row mb-3" style="gap:.5rem; flex-wrap:wrap; align-items:center">
          <span class="text-xs text-muted">Pilih cepat:</span>
          <button type="button" class="btn btn-ghost btn-sm" data-quicksel="submitted,revised">Yang perlu disetujui</button>
          <button type="button" class="btn btn-ghost btn-sm" data-quicksel="approved">Yang sudah disetujui</button>
          <button type="button" class="btn btn-ghost btn-sm" data-quicksel="">Kosongkan pilihan</button>
        </div>

        <div class="row" style="justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem">
          <div>
            <strong><?= esc($group['label']) ?></strong>
            <span class="text-xs text-muted"><?= esc($group['sub']) ?> · (<?= count($group['rows']) ?> siswa)</span>
          </div>
        </div>
        <div class="table-wrap">
          <table class="t">
            <thead>
              <tr>
                <th style="width:36px"><input type="checkbox" class="selAll"></th>
                <th style="min-width:160px">Siswa</th>
                <th>Narasi (deskripsi umum / kesimpulan periode)</th>
                <th style="width:100px">Status</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($group['rows'] as $r):
              $stInfo = $geStatuses[$r['status']] ?? $geStatuses['draft'];
            ?>
              <tr>
                <td class="text-center"><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" class="rowSel" data-status="<?= esc($r['status']) ?>"></td>
                <td>
                  <strong><?= esc($r['student_nama']) ?></strong>
                  <div class="text-xs text-muted"><?= esc($r['nis']) ?></div>
                </td>
                <td class="text-sm"><?= esc((string)$r['narasi']) ?></td>
                <td><span class="badge <?= esc($stInfo['class']) ?>"><?= esc($stInfo['label']) ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </form>

  <script>
  (function(){
    const form = document.getElementById('rvForm');

    document.querySelectorAll('.selAll').forEach(all => {
      const table = all.closest('table');
      all.addEventListener('change', () => {
        if (!table) return;
        table.querySelectorAll('.rowSel').forEach(cb => cb.checked = all.checked);
      });
    });

    document.querySelectorAll('[data-quicksel]').forEach(btn => {
      btn.addEventListener('click', () => {
        const wanted = btn.dataset.quicksel ? btn.dataset.quicksel.split(',') : [];
        form.querySelectorAll('.rowSel').forEach(cb => {
          cb.checked = wanted.includes(cb.dataset.status);
        });
      });
    });

    let lastOp = null;
    document.getElementById('btnApprove')?.addEventListener('click', () => { lastOp = 'approve'; });
    document.getElementById('btnRevise')?.addEventListener('click', () => { lastOp = 'revise'; });

    form?.addEventListener('submit', function (e) {
      const selected = Array.from(form.querySelectorAll('.rowSel:checked'));
      if (!selected.length) {
        alert('Pilih minimal satu baris terlebih dahulu.');
        e.preventDefault();
        return;
      }
      const actionable = lastOp === 'approve'
        ? ['submitted', 'revised']
        : ['submitted', 'approved'];
      const eligible = selected.filter(cb => actionable.includes(cb.dataset.status));

      let msg = lastOp === 'revise' ? 'Kirim balik untuk revisi?' : null;
      if (eligible.length === 0) {
        msg = lastOp === 'approve'
          ? 'Tidak ada satupun baris terpilih yang berstatus Diajukan/Revisi — tidak akan ada yang disetujui. Tetap lanjut?'
          : 'Tidak ada satupun baris terpilih yang bisa dikembalikan untuk revisi. Tetap lanjut?';
      } else if (eligible.length < selected.length) {
        const skip = selected.length - eligible.length;
        const base = lastOp === 'revise' ? 'Kirim balik untuk revisi?' : 'Setujui baris terpilih?';
        msg = `${base} (${skip} dari ${selected.length} baris terpilih berstatus tidak relevan dan akan dilewati.)`;
      }
      if (msg && !confirm(msg)) e.preventDefault();
    });
  })();
  </script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
