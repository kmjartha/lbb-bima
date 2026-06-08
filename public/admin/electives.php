<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/elective_helpers.php';

$user = require_view('electives');
$pdo = db();
$sc = active_scope();
$yearId = (int)$sc['year_id'];
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);

// Load current year rombels for assignment
$allRombels = $pdo->prepare(
    "SELECT id, jenjang, tingkat, nama
     FROM rombel
     WHERE academic_year_id = :y AND deleted_at IS NULL
     ORDER BY FIELD(jenjang,'SD','SMP','SMA'), tingkat, nama"
);
$allRombels->execute(['y' => $yearId]);
$allRombels = $allRombels->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        require_edit('electives');
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'save') {
            $id = int_or_null($_POST['id'] ?? null);
            $kode = req_str($_POST, 'kode', 20);
            $nama = req_str($_POST, 'nama', 120);
            $jenjang = req_str($_POST, 'jenjang', 4);
            if (!in_array($jenjang, ['SD','SMP','SMA'], true)) {
                throw new RuntimeException('Jenjang invalid.');
            }

            $rombelIds = array_filter(array_map('intval', (array)($_POST['rombel_ids'] ?? [])));
            if (!$rombelIds) {
                throw new RuntimeException('Pilih minimal 1 rombel untuk mapel pilihan ini.');
            }

            $classNames = $_POST['classes']['name'] ?? [];
            $classCaps  = $_POST['classes']['kapasitas'] ?? [];
            $classIds   = $_POST['classes']['id'] ?? [];
            if (!is_array($classNames) || !is_array($classCaps) || !is_array($classIds)) {
                throw new RuntimeException('Format data opsi pilihan tidak valid.');
            }

            $options = [];
            foreach ($classNames as $index => $name) {
                $name = trim((string)$name);
                if ($name === '') {
                    continue;
                }
                $kapasitas = max(0, intval($classCaps[$index] ?? 0));
                $cid = intval($classIds[$index] ?? 0);
                $options[] = ['id' => $cid, 'nama' => $name, 'kapasitas' => $kapasitas];
            }
            if (!$options) {
                throw new RuntimeException('Masukkan minimal 1 opsi mapel pilihan.');
            }

            // Validate rombel IDs belong to the same year and jenjang.
            $in = implode(',', array_map('intval', $rombelIds));
            $count = (int)$pdo->query(
                "SELECT COUNT(*) FROM rombel WHERE id IN ($in) AND academic_year_id = $yearId AND jenjang = " . $pdo->quote($jenjang) . " AND deleted_at IS NULL"
            )->fetchColumn();
            if ($count !== count($rombelIds)) {
                throw new RuntimeException('Salah satu rombel tidak valid untuk jenjang ini.');
            }

            $pdo->beginTransaction();
            if ($id) {
                $stmt = $pdo->prepare("UPDATE electives SET kode = :k, nama = :n, jenjang = :j WHERE id = :id");
                $stmt->execute(['k' => $kode, 'n' => $nama, 'j' => $jenjang, 'id' => $id]);
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO electives (kode, nama, jenjang, academic_year_id) VALUES (:k, :n, :j, :y)"
                );
                $stmt->execute(['k' => $kode, 'n' => $nama, 'j' => $jenjang, 'y' => $yearId]);
                $id = (int)$pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("DELETE FROM elective_rombels WHERE elective_id = :e");
            $stmt->execute(['e' => $id]);
            $insertRombel = $pdo->prepare("INSERT INTO elective_rombels (elective_id, rombel_id) VALUES (:e, :r)");
            foreach ($rombelIds as $rid) {
                $insertRombel->execute(['e' => $id, 'r' => $rid]);
            }

            $existingClassIds = [];
            $stmt = $pdo->prepare("SELECT id FROM elective_classes WHERE elective_id = :e AND deleted_at IS NULL");
            $stmt->execute(['e' => $id]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $existingId) {
                $existingClassIds[] = (int)$existingId;
            }

            $usedIds = [];
            foreach ($options as $option) {
                if ($option['id'] > 0 && in_array($option['id'], $existingClassIds, true)) {
                    $update = $pdo->prepare(
                        "UPDATE elective_classes SET nama = :n, kapasitas = :k, deleted_at = NULL WHERE id = :id AND elective_id = :e"
                    );
                    $update->execute(['n' => $option['nama'], 'k' => $option['kapasitas'], 'id' => $option['id'], 'e' => $id]);
                    $usedIds[] = $option['id'];
                } else {
                    $insert = $pdo->prepare(
                        "INSERT INTO elective_classes (elective_id, nama, kapasitas) VALUES (:e, :n, :k)"
                    );
                    $insert->execute(['e' => $id, 'n' => $option['nama'], 'k' => $option['kapasitas']]);
                    $usedIds[] = (int)$pdo->lastInsertId();
                }
            }

            if ($existingClassIds) {
                $toDelete = array_diff($existingClassIds, $usedIds);
                if ($toDelete) {
                    $pdo->prepare(
                        "UPDATE elective_classes SET deleted_at = NOW() WHERE id IN (" . implode(',', array_map('intval', $toDelete)) . ")"
                    )->execute();
                }
            }

            $pdo->commit();
            audit('save', 'elective:' . $id);
            flash('success', 'Mapel pilihan disimpan.');
            redirect('admin/electives.php');
        }

        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE electives SET deleted_at = NOW() WHERE id = :id")->execute(['id' => $id]);
            audit('delete', 'elective:' . $id);
            flash('success', 'Mapel pilihan dihapus.');
            redirect('admin/electives.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

$rows = electives_for_year($yearId);
$edit = null;
$editRombels = [];
$editClasses = [];
if ($editId) {
    $edit = elective_by_id($editId);
    if ($edit) {
        $editRombels = elective_rombel_ids($editId);
        $editClasses = elective_classes($editId);
    }
}

$page_title = 'Mapel Pilihan';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="row">
  <div class="card" style="flex: 1 1 320px; min-width: 320px">
    <div class="card-header between" style="align-items:flex-start; gap:.75rem;">
      <div>
        <h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Mapel Pilihan</h3>
        <div class="text-xs text-muted">Buat mapel pilihan dengan opsi sub-kelas dan rombel yang digabung dalam jenjang yang sama.</div>
      </div>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/electives.php')) ?>">Batal</a><?php endif; ?>
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
          <label class="label">Jenjang *</label>
          <select id="elective-jenjang" class="select" name="jenjang" onchange="filterRombels()" required>
            <option value="">— Pilih jenjang —</option>
            <?php foreach (['SD','SMP','SMA'] as $j): ?>
              <option value="<?= $j ?>" <?= ($edit['jenjang'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label">Rombel yang digabung *</label>
          <div id="rombel-options" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:.75rem; max-height:320px; overflow:auto; padding:1rem; border:1px solid var(--border); border-radius:var(--r-md); background:var(--surface);">
            <?php foreach ($allRombels as $r): ?>
              <?php $checked = in_array((int)$r['id'], $editRombels, true); ?>
              <label class="checkbox-row" data-jenjang="<?= esc($r['jenjang']) ?>" style="width:100%; padding:.75rem 1rem; border:1px solid var(--border); border-radius:var(--r-sm); background:var(--surface);">
                <input type="checkbox" name="rombel_ids[]" value="<?= (int)$r['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                <span><?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="text-xs text-muted">Pilih rombel dari jenjang yang sama. Siswa dari rombel terpilih dapat memilih salah satu opsi mapel.</div>
        </div>
        <div class="field">
          <label class="label">Opsi Mapel Pilihan *</label>
          <div id="class-options">
            <?php if ($editClasses): ?>
              <?php foreach ($editClasses as $class): ?>
                <div class="row class-row" style="gap:.5rem; align-items:flex-end; margin-bottom:.5rem">
                  <input type="hidden" name="classes[id][]" value="<?= (int)$class['id'] ?>">
                  <div class="field" style="flex:2"><label class="label">Nama</label><input class="input" name="classes[name][]" required value="<?= esc($class['nama']) ?>"></div>
                  <div class="field" style="flex:1; min-width:120px"><label class="label">Kapasitas</label><input class="input" type="number" min="0" name="classes[kapasitas][]" value="<?= (int)$class['kapasitas'] ?>"></div>
                  <button type="button" class="btn btn-ghost btn-sm" onclick="removeClassRow(this)">Hapus</button>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="row class-row" style="gap:.5rem; align-items:flex-end; margin-bottom:.5rem">
                <input type="hidden" name="classes[id][]" value="0">
                <div class="field" style="flex:2"><label class="label">Nama</label><input class="input" name="classes[name][]" required></div>
                <div class="field" style="flex:1; min-width:120px"><label class="label">Kapasitas</label><input class="input" type="number" min="0" name="classes[kapasitas][]" value="0"></div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="removeClassRow(this)">Hapus</button>
              </div>
            <?php endif; ?>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="addClassRow()">Tambah Opsi</button>
        </div>
        <button class="btn btn-primary" type="submit">Simpan Mapel Pilihan</button>
      </form>
    </div>
  </div>

  <div class="card" style="flex: 2 1 380px; min-width: 380px">
    <div class="card-header">
      <div>
        <h3 class="card-title">Daftar Mapel Pilihan (<?= count($rows) ?>)</h3>
        <div class="text-xs text-muted">Kelola mapel pilihan dan lihat rombel yang sudah digabung.</div>
      </div>
    </div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Kode</th><th>Nama</th><th>Jenjang</th><th>Rombel</th><th>Opsi</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6"><div class="empty">Belum ada data.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <?php $rombelList = elective_rombels_for((int)$r['id']); ?>
          <tr>
            <td><strong><?= esc($r['kode']) ?></strong></td>
            <td><?= esc($r['nama']) ?></td>
            <td><span class="badge badge-primary"><?= esc($r['jenjang']) ?></span></td>
            <td>
              <?php foreach ($rombelList as $rb): ?>
                <div><?= esc($rb['jenjang'].' '.$rb['tingkat'].' · '.$rb['nama']) ?></div>
              <?php endforeach; ?>
            </td>
            <td><?= count(elective_classes((int)$r['id'])) ?> opsi</td>
            <td style="text-align:right; white-space:nowrap">
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Hapus mapel pilihan <?= esc($r['nama']) ?>?">
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

<script>
  function filterRombels() {
    const jenjang = document.getElementById('elective-jenjang').value;
    document.querySelectorAll('#rombel-options [data-jenjang]').forEach(el => {
      el.style.display = jenjang && el.getAttribute('data-jenjang') !== jenjang ? 'none' : 'inline-flex';
    });
  }

  function removeClassRow(button) {
    const row = button.closest('.class-row');
    if (row) row.remove();
  }

  function addClassRow() {
    const container = document.getElementById('class-options');
    const row = document.createElement('div');
    row.className = 'row class-row';
    row.style = 'gap:.5rem; align-items:flex-end; margin-bottom:.5rem';
    row.innerHTML = `
      <input type="hidden" name="classes[id][]" value="0">
      <div class="field" style="flex:2"><label class="label">Nama</label><input class="input" name="classes[name][]" required></div>
      <div class="field" style="flex:1; min-width:120px"><label class="label">Kapasitas</label><input class="input" type="number" min="0" name="classes[kapasitas][]" value="0"></div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="removeClassRow(this)">Hapus</button>
    `;
    container.appendChild(row);
  }

  document.addEventListener('DOMContentLoaded', filterRombels);
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
