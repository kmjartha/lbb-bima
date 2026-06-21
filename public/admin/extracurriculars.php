<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
require_administrator();

$pdo = db();
$sc = active_scope();
$yearId = (int)$sc['year_id'];
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');
        if ($op === 'save') {
            $id     = int_or_null($_POST['id'] ?? null);
            $nama   = req_str($_POST, 'nama', 120);
            $koord  = int_or_null($_POST['koordinator_id'] ?? null);
            if ($id) {
                $pdo->prepare("UPDATE extracurriculars SET nama=:n, koordinator_id=:k WHERE id=:id AND academic_year_id = :y")
                    ->execute(['n'=>$nama,'k'=>$koord,'id'=>$id,'y'=>$yearId]);
            } else {
                $pdo->prepare("INSERT INTO extracurriculars (academic_year_id, nama, koordinator_id) VALUES (:y,:n,:k)")
                    ->execute(['y'=>$yearId,'n'=>$nama,'k'=>$koord]);
                $id = (int)$pdo->lastInsertId();
            }
            audit('save', 'ekskul:' . $id);
            flash('success', 'Ekskul disimpan.');
            redirect('admin/extracurriculars.php');
        }
        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE extracurriculars SET deleted_at = NOW() WHERE id = :id AND academic_year_id = :y")
                ->execute(['id'=>$id,'y'=>$yearId]);
            audit('delete', 'ekskul:' . $id);
            flash('success', 'Ekskul dihapus.');
            redirect('admin/extracurriculars.php');
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$teachers = $pdo->query("SELECT t.id, u.nama FROM teachers t JOIN users u ON u.id = t.user_id ORDER BY u.nama")->fetchAll();
$rows = $pdo->prepare(
    "SELECT e.*, u.nama AS koord_nama
     FROM extracurriculars e
     LEFT JOIN teachers t ON t.id = e.koordinator_id
     LEFT JOIN users u ON u.id = t.user_id
     WHERE e.academic_year_id = :y AND e.deleted_at IS NULL ORDER BY e.nama"
);
$rows->execute(['y' => $yearId]);
$rows = $rows->fetchAll();

$edit = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM extracurriculars WHERE id = :id AND academic_year_id = :y AND deleted_at IS NULL");
    $stmt->execute(['id'=>$editId,'y'=>$yearId]);
    $edit = $stmt->fetch();
}

$page_title = 'Ekstrakurikuler';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>
<div class="row">
  <div class="card" style="flex: 1; min-width: 320px">
    <div class="card-header"><h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Ekskul</h3>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/extracurriculars.php')) ?>">Batal</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="field"><label class="label">Nama *</label><input class="input" name="nama" required value="<?= esc($edit['nama'] ?? '') ?>"></div>
        <div class="field"><label class="label">Koordinator</label>
          <select class="select" name="koordinator_id">
            <option value="">— Tidak ada —</option>
            <?php foreach ($teachers as $t): ?>
              <option value="<?= (int)$t['id'] ?>" <?= ($edit['koordinator_id'] ?? null)==$t['id']?'selected':'' ?>><?= esc($t['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>
  <div class="card" style="flex: 2; min-width: 360px">
    <div class="card-header"><h3 class="card-title">Daftar Ekskul (<?= count($rows) ?>)</h3></div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Nama</th><th>Koordinator</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="3"><div class="empty">Belum ada data.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= esc($r['nama']) ?></strong></td>
            <td><?= esc($r['koord_nama'] ?? '—') ?></td>
            <td style="text-align:right; white-space:nowrap">
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Hapus ekskul <?= esc($r['nama']) ?>?">
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
