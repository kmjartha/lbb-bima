<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/scope.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_admin_any();

$pdo = db();
$sc = active_scope();
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
                $stmt = $pdo->prepare("UPDATE subject_categories SET nama = :n WHERE id = :id AND academic_year_id = :y");
                $stmt->execute(['n' => $nama, 'id' => $id, 'y' => $sc['year_id']]);
                audit('save', 'subject_category:' . $id);
                flash('success', 'Kategori disimpan.');
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO subject_categories (academic_year_id, nama) VALUES (:y, :n)");
                    $stmt->execute(['y' => $sc['year_id'], 'n' => $nama]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Kategori dengan nama tersebut sudah ada di tahun ajaran ini.');
                    }
                    throw $e;
                }
                audit('save', 'subject_category:' . $pdo->lastInsertId());
                flash('success', 'Kategori disimpan.');
            }
            redirect('admin/subject_categories.php');
        }

        if ($op === 'delete') {
            $id = int_or_null($_POST['id'] ?? null);
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM subjects s
                   JOIN rombel_subject_teachers rst ON rst.subject_id = s.id
                   JOIN rombel r ON r.id = rst.rombel_id AND r.academic_year_id = :y AND r.deleted_at IS NULL
                  WHERE s.category_id = :id AND s.academic_year_id = :y AND s.deleted_at IS NULL"
            );
            $stmt->execute(['id' => $id, 'y' => $sc['year_id']]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('Kategori masih digunakan oleh mata pelajaran di Tahun Ajaran aktif.');
            }
            $pdo->prepare("DELETE FROM subject_categories WHERE id = :id AND academic_year_id = :y")
                ->execute(['id' => $id, 'y' => $sc['year_id']]);
            audit('delete', 'subject_category:' . $id);
            flash('success', 'Kategori dihapus.');
            redirect('admin/subject_categories.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

$categories = $pdo->prepare(
    "SELECT sc.*, COALESCE(subject_counts.subject_count, 0) AS subject_count
       FROM subject_categories sc
       LEFT JOIN (
           SELECT category_id, COUNT(*) AS subject_count
             FROM subjects
            WHERE academic_year_id = :y_inner AND deleted_at IS NULL
            GROUP BY category_id
       ) AS subject_counts ON subject_counts.category_id = sc.id
      WHERE sc.academic_year_id = :y_outer
      ORDER BY sc.nama"
);
$categories->execute(['y_inner' => $sc['year_id'], 'y_outer' => $sc['year_id']]);
$categories = $categories->fetchAll();
$editCategory = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM subject_categories WHERE id = :id AND academic_year_id = :y");
    $stmt->execute(['id' => $editId, 'y' => $sc['year_id']]);
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
      <thead><tr><th>Nama</th><th>Jumlah Mapel</th><th></th></tr></thead>
      <tbody>
      <?php if (!$categories): ?><tr><td colspan="3"><div class="empty">Belum ada kategori.</div></td></tr><?php endif; ?>
      <?php foreach ($categories as $category): ?>
        <tr>
          <td><?= esc($category['nama']) ?></td>
          <td><?= (int)$category['subject_count'] ?></td>
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
