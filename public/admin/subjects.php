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

// Setup Search & Pagination
$q      = trim((string)($_GET['q'] ?? ''));
$limit  = 15;
$page   = max(1, (int)($_GET['p'] ?? 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'save_category') {
            $id = int_or_null($_POST['id'] ?? null);
            $nama = req_str($_POST, 'nama', 80);
            if ($id) {
                $pdo->prepare("UPDATE subject_categories SET nama=:n WHERE id=:id AND academic_year_id = :y")
                    ->execute(['n'=>$nama,'id'=>$id,'y'=>$sc['year_id']]);
                audit('save', 'subject_category:' . $id);
                flash('success', 'Kategori disimpan.');
            } else {
                try {
                    $pdo->prepare("INSERT INTO subject_categories (academic_year_id, nama) VALUES (:y, :n)")
                        ->execute(['y'=>$sc['year_id'],'n'=>$nama]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Kategori dengan nama tersebut sudah ada di tahun ajaran ini.');
                    }
                    throw $e;
                }
                audit('save', 'subject_category:' . $pdo->lastInsertId());
                flash('success', 'Kategori disimpan.');
            }
            redirect('admin/subjects.php');
        }

        if ($op === 'delete_category') {
            $id = int_or_null($_POST['id'] ?? null);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE category_id = :id AND academic_year_id = :y AND deleted_at IS NULL");
            $stmt->execute(['id'=>$id, 'y'=>$sc['year_id']]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new RuntimeException('Kategori masih digunakan oleh mata pelajaran di tahun ajaran aktif.');
            }
            $pdo->prepare("DELETE FROM subject_categories WHERE id = :id AND academic_year_id = :y")
                ->execute(['id'=>$id, 'y'=>$sc['year_id']]);
            audit('delete', 'subject_category:' . $id);
            flash('success', 'Kategori dihapus.');
            redirect('admin/subjects.php');
        }

        if ($op === 'save') {
            $id   = int_or_null($_POST['id'] ?? null);
            $kode = req_str($_POST, 'kode', 20);
            $nama = req_str($_POST, 'nama', 120);
            $catId = int_or_null($_POST['category_id'] ?? null);
            $newCat = trim((string)($_POST['new_category'] ?? ''));
            $jenjangs = $_POST['jenjang'] ?? [];
            if (!is_array($jenjangs) || !$jenjangs) throw new RuntimeException('Pilih minimal 1 jenjang.');
            foreach ($jenjangs as $j) if (!in_array($j, ['TK','SD','SMP','SMA'], true)) throw new RuntimeException('Jenjang invalid.');

            $pdo->beginTransaction();
            // Inline category create
            if ($newCat !== '') {
                $stmt = $pdo->prepare("SELECT id FROM subject_categories WHERE nama = :n AND academic_year_id = :y");
                $stmt->execute(['n' => $newCat, 'y' => $sc['year_id']]);
                $catId = (int)($stmt->fetchColumn() ?: 0);
                if (!$catId) {
                    $pdo->prepare("INSERT INTO subject_categories (academic_year_id, nama) VALUES (:y, :n)")
                        ->execute(['y' => $sc['year_id'], 'n' => $newCat]);
                    $catId = (int)$pdo->lastInsertId();
                }
            }

            if ($id) {
                $pdo->prepare("UPDATE subjects SET kode=:k, nama=:n, category_id=:c WHERE id=:id AND academic_year_id=:y")
                    ->execute(['k'=>$kode,'n'=>$nama,'c'=>$catId,'id'=>$id,'y'=>$sc['year_id']]);
            } else {
                $pdo->prepare("INSERT INTO subjects (academic_year_id, kode, nama, category_id) VALUES (:y,:k,:n,:c)")
                    ->execute(['y'=>$sc['year_id'],'k'=>$kode,'n'=>$nama,'c'=>$catId]);
                $id = (int)$pdo->lastInsertId();
            }
            // Reset jenjang map
            $pdo->prepare("DELETE FROM subject_jenjang_map WHERE subject_id = :id")->execute(['id'=>$id]);
            $insJ = $pdo->prepare("INSERT INTO subject_jenjang_map (subject_id, jenjang) VALUES (:s,:j)");
            foreach ($jenjangs as $j) $insJ->execute(['s'=>$id,'j'=>$j]);

            $pdo->commit();
            audit('save', 'subject:' . $id);
            flash('success', 'Mata pelajaran disimpan.');
            redirect('admin/subjects.php');
        }

        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE subjects SET deleted_at = NOW() WHERE id = :id AND academic_year_id = :y")
                ->execute(['id'=>$id, 'y'=>$sc['year_id']]);
            audit('delete', 'subject:' . $id);
            flash('success', 'Mata pelajaran dihapus.');
            redirect('admin/subjects.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}

$cats = $pdo->prepare(
    "SELECT id, nama FROM subject_categories WHERE academic_year_id = :y ORDER BY nama"
);
$cats->execute(['y' => $sc['year_id']]);
$cats = $cats->fetchAll();

// Build Search and Pagination Query
$conds = ["s.deleted_at IS NULL", "s.academic_year_id = :y"];
$params = ['y' => $sc['year_id']];

if ($q !== '') {
    $conds[] = "(s.kode LIKE :q1 OR s.nama LIKE :q2)";
    $params['q1'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
}

$whereSql = "WHERE " . implode(" AND ", $conds);

// Count total records
$countSql = "SELECT COUNT(*) FROM subjects s $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();

// Pagination offsets
$totalPages = max(1, (int)ceil($totalRows / $limit));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

// Fetch rows
$rowSql = "SELECT s.*, c.nama AS cat_nama,
                  GROUP_CONCAT(jm.jenjang ORDER BY FIELD(jm.jenjang,'TK','SD','SMP','SMA') SEPARATOR ',') AS jenjangs
           FROM subjects s
           LEFT JOIN subject_categories c ON c.id = s.category_id
           LEFT JOIN subject_jenjang_map jm ON jm.subject_id = s.id
           $whereSql
           GROUP BY s.id ORDER BY s.kode
           LIMIT $limit OFFSET $offset";

$stmtRows = $pdo->prepare($rowSql);
$stmtRows->execute($params);
$rows = $stmtRows->fetchAll();

$edit = null; $editJ = [];
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = :id AND deleted_at IS NULL AND academic_year_id = :y");
    $stmt->execute(['id'=>$editId,'y'=>$sc['year_id']]);
    $edit = $stmt->fetch();
    if ($edit) {
        $j = $pdo->prepare("SELECT jenjang FROM subject_jenjang_map WHERE subject_id = :id");
        $j->execute(['id'=>$editId]);
        $editJ = $j->fetchAll(PDO::FETCH_COLUMN);
    }
}

$page_title = 'Mata Pelajaran';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="row">
  <div class="card" style="flex: 1; min-width: 320px">
    <div class="card-header"><h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Mapel</h3>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/subjects.php')) ?>">Batal</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="row">
          <div class="field"><label class="label">Kode *</label><input class="input" name="kode" required value="<?= esc($edit['kode'] ?? '') ?>"></div>
          <div class="field" style="flex: 2"><label class="label">Nama *</label><input class="input" name="nama" required value="<?= esc($edit['nama'] ?? '') ?>"></div>
        </div>
        <div class="field">
          <label class="label">Kategori</label>
          <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap">
            <select class="select" name="category_id" style="flex:1; min-width:220px">
              <option value="">— Pilih kategori —</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= ($edit['category_id'] ?? null)==$c['id']?'selected':'' ?>><?= esc($c['nama']) ?></option>
              <?php endforeach; ?>
            </select>
            <a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/subject_categories.php')) ?>">Kelola kategori</a>
          </div>
        </div>
        <div class="field">
          <label class="label">Jenjang *</label>
          <div style="display:flex; gap: var(--sp-4)">
            <?php foreach (['TK','SD','SMP','SMA'] as $j): ?>
              <label class="checkbox-row"><input type="checkbox" name="jenjang[]" value="<?= $j ?>" <?= in_array($j, $editJ, true) ? 'checked' : '' ?>> <?= $j ?></label>
            <?php endforeach; ?>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <h3 class="card-title">Daftar Mapel (<?= $totalRows ?>)</h3>
        <form method="get" style="display:flex; gap:5px;">
            <input type="text" name="q" class="input input-sm" placeholder="Cari Kode/Nama..." value="<?= esc($q) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
            <?php if ($q): ?>
                <a href="subjects.php" class="btn btn-ghost btn-sm" title="Reset Pencarian">✕</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Jenjang</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="5"><div class="empty">Belum ada data.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= esc($r['kode']) ?></strong></td>
            <td><?= esc($r['nama']) ?></td>
            <td><?= esc($r['cat_nama'] ?? '—') ?></td>
            <td><?php foreach (explode(',', (string)$r['jenjangs']) as $j) if ($j) echo '<span class="badge badge-primary" style="margin-right:4px">' . esc($j) . '</span>'; ?></td>
            <td style="text-align:right; white-space:nowrap">
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $page>1 ? '&p='.$page : '' ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Hapus mapel <?= esc($r['nama']) ?>?">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-body" style="border-top:1px solid var(--c-border); display:flex; justify-content:space-between; align-items:center;">
        <span class="text-sm text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></span>
        <div style="display:flex; gap:5px;">
            <?php if ($page > 1): ?>
                <a href="?p=<?= $page - 1 ?><?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-secondary btn-sm">← Prev</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?p=<?= $page + 1 ?><?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-secondary btn-sm">Next →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>