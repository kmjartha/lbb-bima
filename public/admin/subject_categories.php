<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_admin_any();

$pdo = db();
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'save') {
            $id = int_or_null($_POST['id'] ?? null);
            $nama = req_str($_POST, 'nama', 80);

            if ($id) {
                $pdo->prepare("UPDATE subject_categories SET nama = :n WHERE id = :id")
                    ->execute(['n' => $nama, 'id' => $id]);
                audit('save', 'subject_category:' . $id);
                flash('success', 'Kategori disimpan.');
            } else {
                $pdo->prepare("INSERT INTO subject_categories (nama) VALUES (:n)")
                    ->execute(['n' => $nama]);
                audit('save', 'subject_category:' . $pdo->lastInsertId());
                flash('success', 'Kategori disimpan.');
            }
            redirect('admin/subject_categories.php');
        }

        if ($op === 'delete') {
            $id = int_or_null($_POST['id'] ?? null);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE category_id = :id AND deleted_at IS NULL");
            $stmt->execute(['id' => $id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('Kategori masih digunakan oleh mata pelajaran.');
            }
            $pdo->prepare("DELETE FROM subject_categories WHERE id = :id")->execute(['id' => $id]);
            audit('delete', 'subject_category:' . $id);
            flash('success', 'Kategori dihapus.');
            redirect('admin/subject_categories.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM subject_categories ORDER BY nama")->fetchAll();
$editCategory = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM subject_categories WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $editCategory = $stmt->fetch();
}

$page_title = 'Kategori Mata Pelajaran';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>
<div class="card" style="max-width:640px">
  <div class="card-header"><h3 class="card-title"><?= $editCategory ? 'Edit' : 'Tambah' ?> Kategori</h3>
    <?php if ($editCategory): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/subject_categories.php')) ?>">Batal</a><?php endif; ?>
  </div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="op" value="save">
      <?php if ($editCategory): ?><input type="hidden" name="id" value="<?= (int)$editCategory['id'] ?>"><?php endif; ?>
      <div class="field">
        <label class="label">Nama Kategori *</label>
        <input class="input" name="nama" required value="<?= esc($editCategory['nama'] ?? '') ?>">
      </div>
      <button class="btn btn-primary" type="submit">Simpan</button>
    </form>
  </div>
</div>
<div class="card" style="margin-top: var(--sp-4)">
  <div class="card-header"><h3 class="card-title">Daftar Kategori</h3></div>
  <div class="table-wrap">
    <table class="t">
      <thead><tr><th>Nama</th><th></th></tr></thead>
      <tbody>
      <?php if (!$categories): ?><tr><td colspan="2"><div class="empty">Belum ada kategori.</div></td></tr><?php endif; ?>
      <?php foreach ($categories as $category): ?>
        <tr>
          <td><?= esc($category['nama']) ?></td>
          <td style="text-align:right; white-space:nowrap">
            <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$category['id'] ?>">Edit</a>
            <form method="post" style="display:inline" data-confirm="Hapus kategori <?= esc($category['nama']) ?>?">
              <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
