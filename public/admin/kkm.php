<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_admin_any();

$pdo = db();
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');
        if ($op === 'save_row') {
            $id      = (int)($_POST['id'] ?? 0);
            $minVal  = (float)$_POST['min_val'];
            $maxVal  = (float)$_POST['max_val'];
            $pred    = req_str($_POST, 'predikat', 40);
            if ($minVal > $maxVal) throw new RuntimeException('Min tidak boleh lebih besar dari Max.');
            $pdo->prepare("UPDATE kkm_settings SET min_val=:mn, max_val=:mx, predikat=:p WHERE id=:id")
                ->execute(['mn'=>$minVal,'mx'=>$maxVal,'p'=>$pred,'id'=>$id]);
            audit('save', 'kkm:' . $id);
            flash('success', 'KKM diperbarui.');
            redirect('admin/kkm.php?jenjang=' . urlencode((string)($_POST['jenjang'] ?? 'SD')));
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$jenjang_param = $_GET['jenjang'] ?? 'SD';
$jenjang = in_array($jenjang_param, ['SD','SMP','SMA'], true) ? $jenjang_param : 'SD';
$rows = $pdo->prepare("SELECT * FROM kkm_settings WHERE jenjang = :j ORDER BY min_val DESC");
$rows->execute(['j' => $jenjang]);
$rows = $rows->fetchAll();

$page_title = 'KKM Settings';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Skala Nilai per Jenjang</h3>
    <div class="row" style="gap: var(--sp-1); flex: 0 0 auto">
      <?php foreach (['SD','SMP','SMA'] as $j): ?>
        <a class="btn <?= $j === $jenjang ? 'btn-primary' : 'btn-secondary' ?> btn-sm" href="?jenjang=<?= $j ?>"><?= $j ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card-body">
    <p class="text-muted text-sm">Tiap baris adalah rentang nilai untuk satu grade. Edit min/max/predikat lalu klik Simpan pada baris itu.</p>
  </div>
  <div class="table-wrap">
    <?php /* Forms must live OUTSIDE the table; inputs reference them via form="..." */ ?>
    <?php foreach ($rows as $r): $fid = 'kkm-f-' . (int)$r['id']; ?>
      <form id="<?= esc($fid) ?>" method="post" style="display:none">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="save_row">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <input type="hidden" name="jenjang" value="<?= esc($jenjang) ?>">
      </form>
    <?php endforeach; ?>
    <table class="t">
      <thead>
        <tr>
          <th style="width:90px">Grade</th>
          <th style="width:130px">Min</th>
          <th style="width:130px">Max</th>
          <th>Predikat</th>
          <th style="width:120px"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): $fid = 'kkm-f-' . (int)$r['id']; ?>
          <tr>
            <td><strong><?= esc($r['grade']) ?></strong></td>
            <td><input class="input" form="<?= esc($fid) ?>" type="number" step="0.01" name="min_val" value="<?= esc((string)$r['min_val']) ?>" style="max-width:110px;"></td>
            <td><input class="input" form="<?= esc($fid) ?>" type="number" step="0.01" name="max_val" value="<?= esc((string)$r['max_val']) ?>" style="max-width:110px;"></td>
            <td><input class="input" form="<?= esc($fid) ?>" type="text" name="predikat" value="<?= esc($r['predikat']) ?>"></td>
            <td style="text-align:right;"><button class="btn btn-primary btn-sm" form="<?= esc($fid) ?>" type="submit">Simpan</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
