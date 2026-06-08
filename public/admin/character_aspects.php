<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_administrator();

$pdo = db();
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');
        if ($op === 'save') {
            $id   = int_or_null($_POST['id'] ?? null);
            $nama = req_str($_POST, 'nama', 120);
            $kat  = req_str($_POST, 'kategori', 12);
            if (!in_array($kat, ['spiritual','sosial'], true)) throw new RuntimeException('Kategori invalid.');
            if ($id) {
                $pdo->prepare("UPDATE character_aspects SET nama=:n, kategori=:k WHERE id=:id")
                    ->execute(['n'=>$nama,'k'=>$kat,'id'=>$id]);
            } else {
                $pdo->prepare("INSERT INTO character_aspects (nama, kategori) VALUES (:n,:k)")
                    ->execute(['n'=>$nama,'k'=>$kat]);
            }
            audit('save', 'aspect:' . ($id ?? $pdo->lastInsertId()));
            flash('success', 'Aspek karakter disimpan.');
            redirect('admin/character_aspects.php');
        }
        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE character_aspects SET deleted_at = NOW() WHERE id = :id")->execute(['id'=>$id]);
            audit('delete', 'aspect:' . $id);
            flash('success', 'Aspek dihapus.');
            redirect('admin/character_aspects.php');
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$rows = $pdo->query("SELECT * FROM character_aspects WHERE deleted_at IS NULL ORDER BY kategori, nama")->fetchAll();
$edit = null;
if ($editId) {
    $s = $pdo->prepare("SELECT * FROM character_aspects WHERE id=:id AND deleted_at IS NULL");
    $s->execute(['id'=>$editId]);
    $edit = $s->fetch();
}

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
        <div class="field"><label class="label">Kategori *</label>
          <select class="select" name="kategori" required>
            <option value="spiritual" <?= ($edit['kategori'] ?? '')==='spiritual'?'selected':'' ?>>Spiritual</option>
            <option value="sosial" <?= ($edit['kategori'] ?? '')==='sosial'?'selected':'' ?>>Sosial</option>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>
  <div class="card" style="flex: 2; min-width: 360px">
    <div class="card-header"><h3 class="card-title">Daftar Aspek (<?= count($rows) ?>)</h3></div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Kategori</th><th>Nama</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><span class="badge <?= $r['kategori']==='spiritual'?'badge-info':'badge-primary' ?>"><?= esc(ucfirst($r['kategori'])) ?></span></td>
            <td><strong><?= esc($r['nama']) ?></strong></td>
            <td style="text-align:right">
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
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
