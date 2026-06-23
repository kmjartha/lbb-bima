<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
require_admin_any();

$pdo = db();
$sc = active_scope();
$yearId = (int)$sc['year_id'];
$jenjang_param = $_POST['jenjang'] ?? $_GET['jenjang'] ?? 'SD';
$jenjang = in_array($jenjang_param, ['SD','SMP','SMA'], true) ? $jenjang_param : 'SD';
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
            $pdo->prepare("UPDATE kkm_settings SET min_val=:mn, max_val=:mx, predikat=:p WHERE id=:id AND academic_year_id = :y")
                ->execute(['mn'=>$minVal,'mx'=>$maxVal,'p'=>$pred,'id'=>$id,'y'=>$yearId]);
            audit('save', 'predikat:' . $id);
            flash('success', 'Predikat diperbarui.');
            redirect('admin/predikat.php?jenjang=' . urlencode((string)($_POST['jenjang'] ?? 'SD')));
        }

        if ($op === 'add_row') {
            $grade   = req_str($_POST, 'grade', 10);
            $minVal  = (float)$_POST['min_val'];
            $maxVal  = (float)$_POST['max_val'];
            $pred    = req_str($_POST, 'predikat', 40);
            if ($grade === '') throw new RuntimeException('Grade tidak boleh kosong.');
            if ($minVal > $maxVal) throw new RuntimeException('Min tidak boleh lebih besar dari Max.');
            $stmt = $pdo->prepare("SELECT 1 FROM kkm_settings WHERE academic_year_id = :y AND jenjang = :j AND grade = :g LIMIT 1");
            $stmt->execute(['y' => $yearId, 'j' => $jenjang, 'g' => $grade]);
            if ($stmt->fetchColumn()) throw new RuntimeException('Grade sudah ada pada jenjang ini.');
            $pdo->prepare("INSERT INTO kkm_settings (academic_year_id, jenjang, grade, min_val, max_val, predikat) VALUES (:y,:j,:g,:mn,:mx,:p)")
                ->execute(['y'=>$yearId,'j'=>$jenjang,'g'=>$grade,'mn'=>$minVal,'mx'=>$maxVal,'p'=>$pred]);
            audit('save', 'predikat:new:' . $grade);
            flash('success', 'Predikat baru ditambahkan.');
            redirect('admin/predikat.php?jenjang=' . urlencode($jenjang));
        }

        if ($op === 'delete_row') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('ID Predikat invalid.');
            $pdo->prepare("DELETE FROM kkm_settings WHERE id = :id AND academic_year_id = :y")
                ->execute(['id' => $id, 'y' => $yearId]);
            audit('delete', 'predikat:' . $id);
            flash('success', 'Predikat dihapus.');
            redirect('admin/predikat.php?jenjang=' . urlencode($jenjang));
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$jenjang_param = $_GET['jenjang'] ?? 'SD';
$jenjang = in_array($jenjang_param, ['SD','SMP','SMA'], true) ? $jenjang_param : 'SD';
$rows = $pdo->prepare("SELECT * FROM kkm_settings WHERE academic_year_id = :y AND jenjang = :j ORDER BY min_val DESC");
$rows->execute(['y' => $yearId, 'j' => $jenjang]);
$rows = $rows->fetchAll();
if (!$rows) {
    $defaults = [
        ['grade' => 'A+', 'min_val' => 100.00, 'max_val' => 100.00, 'predikat' => 'Perfect'],
        ['grade' => 'A',  'min_val' => 95.98, 'max_val' => 99.99, 'predikat' => 'Nice'],
        ['grade' => 'A-', 'min_val' => 91.00, 'max_val' => 95.99, 'predikat' => 'Amazing'],
        ['grade' => 'B+', 'min_val' => 86.00, 'max_val' => 90.99, 'predikat' => 'Terrific'],
        ['grade' => 'B',  'min_val' => 81.00, 'max_val' => 85.99, 'predikat' => 'Good'],
        ['grade' => 'B-', 'min_val' => 76.00, 'max_val' => 80.99, 'predikat' => 'Good'],
        ['grade' => 'C',  'min_val' => 70.00, 'max_val' => 75.99, 'predikat' => 'Average'],
        ['grade' => 'D',  'min_val' =>  0.00, 'max_val' => 69.99, 'predikat' => 'Below Average'],
    ];
    $ins = $pdo->prepare("INSERT INTO kkm_settings (academic_year_id, jenjang, grade, min_val, max_val, predikat) VALUES (:y,:j,:g,:mn,:mx,:p)");
    foreach ($defaults as $d) {
        $ins->execute(['y'=>$yearId,'j'=>$jenjang,'g'=>$d['grade'],'mn'=>$d['min_val'],'mx'=>$d['max_val'],'p'=>$d['predikat']]);
    }
    $rows = $pdo->prepare("SELECT * FROM kkm_settings WHERE academic_year_id = :y AND jenjang = :j ORDER BY min_val DESC");
    $rows->execute(['y' => $yearId, 'j' => $jenjang]);
    $rows = $rows->fetchAll();
}

$page_title = 'Predikat Settings';
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
    <div style="display:flex; gap:var(--sp-2); align-items:center; justify-content:space-between; flex-wrap:wrap;">
      <p class="text-muted text-sm" style="margin:0; flex:1 1 320px;">Tiap baris adalah rentang nilai untuk satu grade. Edit min/max/predikat lalu klik Simpan pada baris itu.</p>
      <button type="button" class="btn btn-secondary btn-sm" onclick="var c=document.getElementById('predikat-add-card'); c.style.display = c.style.display === 'none' ? 'block' : 'none';">Tambah Predikat</button>
    </div>
  </div>
  <div id="predikat-add-card" style="display:none; margin:1rem 1.5rem 0 1.5rem; padding:1rem; border:1px solid var(--border); border-radius:12px; background:rgba(0,0,0,.02)">
    <form id="predikat-add-form" method="post" class="row" style="gap: var(--sp-2); align-items:flex-end;">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="add_row">
      <input type="hidden" name="jenjang" value="<?= esc($jenjang) ?>">
      <div class="field" style="flex: 1 1 140px;"><label class="label">Grade</label><input class="input" name="grade" required maxlength="10"></div>
      <div class="field" style="flex: 1 1 120px;"><label class="label">Min</label><input class="input" type="number" step="0.01" name="min_val" required></div>
      <div class="field" style="flex: 1 1 120px;"><label class="label">Max</label><input class="input" type="number" step="0.01" name="max_val" required></div>
      <div class="field" style="flex: 1 1 160px;"><label class="label">Predikat</label><input class="input" name="predikat" maxlength="40"></div>
      <div style="flex:0 0 auto; align-self:flex-end"><button class="btn btn-primary" type="submit">Tambah Baris</button></div>
    </form>
  </div>
  <div class="table-wrap">
    <?php /* Forms must live OUTSIDE the table; inputs reference them via form="..." */ ?>
    <?php foreach ($rows as $r): $fid = 'predikat-f-' . (int)$r['id']; $did = 'predikat-d-' . (int)$r['id']; ?>
      <form id="<?= esc($fid) ?>" method="post" style="display:none">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="save_row">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <input type="hidden" name="jenjang" value="<?= esc($jenjang) ?>">
      </form>
      <form id="<?= esc($did) ?>" method="post" style="display:none">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="delete_row">
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
          <th style="width:180px"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): $fid = 'predikat-f-' . (int)$r['id']; $did = 'predikat-d-' . (int)$r['id']; ?>
          <tr>
            <td><strong><?= esc($r['grade']) ?></strong></td>
            <td><input class="input" form="<?= esc($fid) ?>" type="number" step="0.01" name="min_val" value="<?= esc((string)$r['min_val']) ?>" style="max-width:110px;"></td>
            <td><input class="input" form="<?= esc($fid) ?>" type="number" step="0.01" name="max_val" value="<?= esc((string)$r['max_val']) ?>" style="max-width:110px;"></td>
            <td><input class="input" form="<?= esc($fid) ?>" type="text" name="predikat" value="<?= esc($r['predikat']) ?>"></td>
            <td style="text-align:right; white-space:nowrap">
              <button class="btn btn-primary btn-sm" form="<?= esc($fid) ?>" type="submit">Simpan</button>
              <button class="btn btn-danger btn-sm" form="<?= esc($did) ?>" type="submit" onclick="return confirm('Hapus Predikat <?= esc($r['grade']) ?>?')">Hapus</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
