<?php
/**
 * Stage 4 — Rekap Absensi (per rombel, rentang bulan).
 * Read-only for all roles; CSV export available.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/attendance_helpers.php';

$user = require_view('attendance_recap');
$sc   = active_scope();

$rombels = accessible_attendance_rombel($user);
$rid     = int_or_null($_GET['rombel_id'] ?? null);
if (!$rid && $rombels) $rid = (int)$rombels[0]['id'];

$bulan = (string)($_GET['bulan'] ?? '');
if ($bulan !== '' && !preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    $bulan = '';
}

if ($bulan !== '') {
    $from = $bulan . '-01';
    $to   = date('Y-m-t', strtotime($from));
} else {
    [$from, $to] = semester_date_window((int)$sc['year_id'], $sc['semester']);
}

$rombel = null; $recap = []; $dates = []; $matrix = [];
if ($rid) {
    $rombel = assert_can_access_attendance_rombel($user, $rid);
    $recap  = attendance_recap($rid, $from, $to);
    $dates  = attendance_dates($rid, $from, $to);

    if ($dates) {
        $st = db()->prepare(
            "SELECT student_id, tanggal, status FROM attendance
             WHERE rombel_id=:r AND tanggal BETWEEN :f AND :t"
        );
        $st->execute(['r'=>$rid, 'f'=>$from, 't'=>$to]);
        foreach ($st->fetchAll() as $row) {
            $matrix[(int)$row['student_id']][$row['tanggal']] = $row['status'];
        }
    }
}

// CSV export
if ($rombel && (($_GET['export'] ?? '') === 'csv')) {
    $fname = 'rekap-absensi-'.$rombel['nama'].'-'.$bulan.'.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIS','Nama','JK','H','I','S','A','Total Tercatat']);
    foreach ($recap as $r) {
      fputcsv($out, [$r['nis'],$r['nama'],$r['jk'],(int)$r['h'],(int)$r['i'],(int)$r['s_'],(int)$r['a'],(int)$r['total']]);
    }
    fclose($out); exit;
}

$page_title = 'Rekap Absensi';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Filter</h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('attendance.php' . ($rid?'?rombel_id='.$rid:''))) ?>">← Input Harian</a>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="align-items:flex-end">
      <div class="field" style="flex:2; min-width:240px">
        <label class="label">Rombel</label>
        <select class="select" name="rombel_id" onchange="this.form.submit()">
          <?php if (!$rombels): ?><option value="">— Tidak ada rombel —</option><?php endif; ?>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rid==(int)$r['id']?'selected':'' ?>>
              <?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex:1; min-width:180px">
        <label class="label">Bulan</label>
        <input class="input" type="month" name="bulan" value="<?= esc($bulan) ?>" onchange="this.form.submit()">
      </div>
      <div class="field" style="flex:0 0 auto">
        <button class="btn btn-secondary" type="submit">Tampilkan</button>
        <?php if ($rombel): ?>
          <a class="btn btn-primary" href="?rombel_id=<?= (int)$rid ?>&bulan=<?= esc($bulan) ?>&export=csv">Export CSV</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if ($rombel): ?>
<div class="card mt-4">
  <div class="card-header">
    <h3 class="card-title">
      Ringkasan — <?= esc($rombel['jenjang'].' '.$rombel['tingkat'].' · '.$rombel['nama']) ?>
      <span class="text-sm text-muted">— <?= esc(date('F Y', strtotime($from))) ?> · <?= count($dates) ?> hari tercatat</span>
    </h3>
  </div>
  <div class="table-wrap">
    <table class="t">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>NIS</th><th>Nama</th><th>JK</th>
          <th class="text-center">H</th>
          <th class="text-center">I</th>
          <th class="text-center">S</th>
          <th class="text-center">A</th>
          <th class="text-center">Total</th>
          <th class="text-center">% Hadir</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recap): ?>
          <tr><td colspan="10"><div class="empty">Belum ada anggota rombel ini.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($recap as $i => $r): $tot=(int)$r['total']; $pct = $tot ? round(((int)$r['h']/$tot)*100) : 0; ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= esc($r['nis']) ?></td>
            <td><strong><?= esc($r['nama']) ?></strong></td>
            <td><?= esc($r['jk']) ?></td>
            <td class="text-center"><span class="badge badge-success"><?= (int)$r['h'] ?></span></td>
            <td class="text-center"><span class="badge badge-warning"><?= (int)$r['i'] ?></span></td>
            <td class="text-center"><span class="badge badge-info"><?= (int)$r['s_'] ?></span></td>
            <td class="text-center"><span class="badge badge-danger"><?= (int)$r['a'] ?></span></td>
            <td class="text-center"><?= $tot ?></td>
            <td class="text-center"><strong><?= $pct ?>%</strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($dates): ?>
<div class="card mt-4">
  <div class="card-header">
    <h3 class="card-title">Matrix Harian</h3>
    <span class="text-sm text-muted">Klik tanggal untuk membuka input</span>
  </div>
  <div class="table-wrap">
    <table class="t att-matrix">
      <thead>
        <tr>
          <th class="sticky-col">Nama</th>
          <?php foreach ($dates as $d): ?>
            <th class="text-center" title="<?= esc($d) ?>">
              <a class="text-muted" href="<?= esc(url('attendance.php?rombel_id='.$rid.'&tanggal='.$d)) ?>">
                <?= (int)substr($d, -2) ?>
              </a>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recap as $r): $sid = (int)$r['id']; ?>
          <tr>
            <td class="sticky-col"><strong><?= esc($r['nama']) ?></strong></td>
            <?php foreach ($dates as $d): $st = $matrix[$sid][$d] ?? ''; ?>
              <td class="text-center att-cell att-<?= esc($st ?: 'N') ?>"><?= esc($st ?: '·') ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>