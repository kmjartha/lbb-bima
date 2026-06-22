<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
require_edit('character_aspects');

$pdo = db();
$sc = active_scope();
$yearId = (int)$sc['year_id'];
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);
$tabJenjang = in_array($_GET['jenjang'] ?? '', ['SD','SMP','SMA'], true) ? $_GET['jenjang'] : 'SD';
$tabJenjangFromGet = isset($_GET['jenjang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');
        if ($op === 'save') {
            $id     = int_or_null($_POST['id'] ?? null);
            $nama   = req_str($_POST, 'nama', 120);
            $jenjang = req_str($_POST, 'jenjang', 3);
            $kat    = req_str($_POST, 'kategori', 64);
            $validJenjang = ['SD','SMP','SMA'];
            $validCategories = [
                'Spiritual and morality',
                'Discipline',
                'Manner',
                'Obedience',
                'Focus and Confidence',
                'spiritual',
                'sosial',
            ];
            if (!in_array($jenjang, $validJenjang, true)) throw new RuntimeException('Jenjang invalid.');
            if (!in_array($kat, $validCategories, true)) throw new RuntimeException('Kategori invalid.');
            if ($id) {
                $pdo->prepare("UPDATE character_aspects SET nama=:n, kategori=:k, jenjang=:j WHERE id=:id AND academic_year_id = :y")
                    ->execute(['n'=>$nama,'k'=>$kat,'j'=>$jenjang,'id'=>$id,'y'=>$yearId]);
            } else {
                $pdo->prepare("INSERT INTO character_aspects (academic_year_id, jenjang, nama, kategori) VALUES (:y,:j,:n,:k)")
                    ->execute(['y'=>$yearId,'j'=>$jenjang,'n'=>$nama,'k'=>$kat]);
            }
            audit('save', 'aspect:' . ($id ?? $pdo->lastInsertId()));
            flash('success', 'Aspek karakter disimpan.');
            redirect('admin/character_aspects.php?jenjang=' . urlencode($jenjang));
        }
        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM character_aspects WHERE id = :id AND academic_year_id = :y")
                ->execute(['id'=>$id,'y'=>$yearId]);
            audit('delete', 'aspect:' . $id);
            flash('success', 'Aspek dihapus.');
            redirect('admin/character_aspects.php?jenjang=' . urlencode($tabJenjang));
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$edit = null;
if ($editId) {
    $s = $pdo->prepare("SELECT * FROM character_aspects WHERE id=:id AND academic_year_id = :y");
    $s->execute(['id'=>$editId,'y'=>$yearId]);
    $edit = $s->fetch();
    if ($edit && !$tabJenjangFromGet) {
        $tabJenjang = $edit['jenjang'];
    }
}

$rows = $pdo->prepare(
    "SELECT * FROM character_aspects
     WHERE academic_year_id = :y AND jenjang = :j
     ORDER BY FIELD(kategori,'Spiritual and morality','Discipline','Manner','Obedience','Focus and Confidence','spiritual','sosial'), nama"
);
$rows->execute(['y'=>$yearId, 'j'=>$tabJenjang]);
$rows = $rows->fetchAll();

$page_title = 'Aspek Karakter';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>
<div class="row">
  <div class="card" style="flex: 1; min-width: 300px">
    <div class="card-header"><h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Aspek</h3></div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="field"><label class="label">Nama *</label><input class="input" name="nama" required value="<?= esc($edit['nama'] ?? '') ?>"></div>
        <div class="field"><label class="label">Jenjang *</label>
          <select class="select" name="jenjang" required>
            <?php foreach (['SD','SMP','SMA'] as $j): ?>
              <option value="<?= esc($j) ?>" <?= (($edit['jenjang'] ?? $tabJenjang) === $j) ? 'selected' : '' ?>><?= esc($j) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label class="label">Kategori *</label>
          <select class="select" name="kategori" required>
            <?php
              $aspectCategories = [
                'Spiritual and morality',
                'Discipline',
                'Manner',
                'Obedience',
                'Focus and Confidence',
                'spiritual',
                'sosial',
              ];
              foreach ($aspectCategories as $cat):
            ?>
              <option value="<?= esc($cat) ?>" <?= ($edit['kategori'] ?? '')===$cat?'selected':'' ?>><?= esc($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>
  <div class="card" style="flex: 2; min-width: 360px">
    <div class="card-header"><h3 class="card-title">Daftar Aspek (<?= count($rows) ?>)</h3></div>
    <div class="card-body" style="padding-bottom:0">
      <div class="p-period-tabs" style="margin-bottom:1rem">
        <?php foreach (['SD','SMP','SMA'] as $j): $active = $tabJenjang === $j; ?>
          <a class="tab <?= $active ? 'is-active' : '' ?>" href="?jenjang=<?= esc($j) ?>"><?= esc($j) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Jenjang</th><th>Kategori</th><th>Nama</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <?php
              $badgeClass = match ($r['kategori']) {
                'Spiritual and morality' => 'badge-info',
                'Discipline' => 'badge-primary',
                'Manner' => 'badge-success',
                'Obedience' => 'badge-warning',
                'Focus and Confidence' => 'badge-secondary',
                'spiritual' => 'badge-info',
                'sosial' => 'badge-primary',
                default => 'badge-primary',
              };
            ?>
            <td><?= esc($r['jenjang']) ?></td>
            <td><span class="badge <?= esc($badgeClass) ?>"><?= esc($r['kategori'] === 'spiritual' || $r['kategori'] === 'sosial' ? ucfirst($r['kategori']) : $r['kategori']) ?></span></td>
            <td><strong><?= esc($r['nama']) ?></strong></td>
            <td style="text-align:right">
              <a class="btn btn-secondary btn-sm" href="?jenjang=<?= esc($tabJenjang) ?>&edit=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Hapus aspek ini?">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
