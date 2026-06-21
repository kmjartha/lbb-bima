<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
$me = require_view('users'); // administrator + admin (admin cannot manage administrator accounts)
$isAdministrator = ($me['role'] === 'administrator');

$pdo = db();
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');

        // Helper: load target user (for any op that targets an existing id) and refuse
        // when a non-administrator tries to touch an administrator account.
        $assertCanTouch = function (?int $id, ?string $newRole = null) use ($pdo, $isAdministrator) {
            if (!$isAdministrator) {
                if ($newRole === 'administrator') {
                    throw new RuntimeException('Hanya Administrator yang dapat membuat/mengubah akun Administrator.');
                }
                if ($id) {
                    $stmt = $pdo->prepare("SELECT role FROM users WHERE id=:id");
                    $stmt->execute(['id' => $id]);
                    $r = (string)$stmt->fetchColumn();
                    if ($r === 'administrator') {
                        throw new RuntimeException('Anda tidak memiliki akses ke akun Administrator.');
                    }
                }
            }
        };

        if ($op === 'save') {
            $id     = int_or_null($_POST['id'] ?? null);
            $niy    = req_str($_POST, 'niy', 20);
            $nama   = req_str($_POST, 'nama', 120);
            $email  = opt_str($_POST, 'email', 120);
            $role   = req_str($_POST, 'role', 20);
            $jenj   = ($role === 'kepsek') ? req_str($_POST, 'jenjang', 3) : null;
            $active = !empty($_POST['is_active']) ? 1 : 0;

            if (!in_array($role, ['administrator','admin','kepsek','guru'], true)) throw new RuntimeException('Role invalid.');
            if ($jenj && !in_array($jenj, ['TK','SD','SMP','SMA'], true)) throw new RuntimeException('Jenjang invalid.');
            if (!niy_unique($niy, $id)) throw new RuntimeException('NIY sudah digunakan.');
            $assertCanTouch($id, $role);

            // Kepsek per jenjang must be unique
            if ($role === 'kepsek') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE role='kepsek' AND jenjang=:j AND deleted_at IS NULL"
                    . ($id ? " AND id <> :id" : ""));
                $stmt->bindValue(':j', $jenj);
                if ($id) $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn()) throw new RuntimeException("Sudah ada Kepsek aktif untuk jenjang $jenj. Nonaktifkan dulu.");
            }

            if ($id) {
                $pdo->prepare("UPDATE users SET niy=:n, nama=:nm, email=:e, role=:r, jenjang=:j, is_active=:a WHERE id=:id")
                    ->execute(['n'=>$niy,'nm'=>$nama,'e'=>$email,'r'=>$role,'j'=>$jenj,'a'=>$active,'id'=>$id]);
            } else {
                $pw = substr($niy, -4);
                $pdo->prepare("INSERT INTO users (niy, nama, email, password_hash, role, jenjang, is_active, must_change_pw)
                               VALUES (:n,:nm,:e,:h,:r,:j,:a,1)")
                    ->execute(['n'=>$niy,'nm'=>$nama,'e'=>$email,'h'=>password_hash($pw, PASSWORD_DEFAULT),'r'=>$role,'j'=>$jenj,'a'=>$active]);
                $id = (int)$pdo->lastInsertId();
                if ($role === 'guru') {
                    $pdo->prepare("INSERT INTO teachers (user_id) VALUES (:u)")->execute(['u'=>$id]);
                }
            }
            audit('save', 'user:' . $id);
            flash('success', 'Akun pegawai disimpan.');
            redirect('admin/users.php');
        }

        if ($op === 'reset_pw') {
            $id  = (int)($_POST['id'] ?? 0);
            $assertCanTouch($id);
            $stmt = $pdo->prepare("SELECT niy FROM users WHERE id = :id");
            $stmt->execute(['id'=>$id]);
            $niy = (string)$stmt->fetchColumn();
            if (!$niy) throw new RuntimeException('User tidak ditemukan.');
            $pw  = substr($niy, -4);
            $pdo->prepare("UPDATE users SET password_hash=:h, must_change_pw=1 WHERE id=:id")
                ->execute(['h'=>password_hash($pw, PASSWORD_DEFAULT),'id'=>$id]);
            audit('reset_pw', 'user:' . $id);
            flash('success', 'Password direset ke 4 digit terakhir NIY.');
            redirect('admin/users.php');
        }

        if ($op === 'toggle_active') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)$me['id']) throw new RuntimeException('Tidak dapat menonaktifkan akun sendiri.');
            $assertCanTouch($id);
            $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = :id")->execute(['id'=>$id]);
            audit('toggle_active', 'user:' . $id);
            redirect('admin/users.php');
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$rowSql = "SELECT id, niy, nama, email, role, jenjang, is_active, last_login_at, must_change_pw
           FROM users WHERE deleted_at IS NULL"
        . ($isAdministrator ? '' : " AND role <> 'administrator'")
        . " ORDER BY role, nama";
$rows = $pdo->query($rowSql)->fetchAll();

$edit = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute(['id'=>$editId]);
    $edit = $stmt->fetch();
    if ($edit && !$isAdministrator && $edit['role'] === 'administrator') {
        $edit = null; // hide administrator credentials from admin
        $err = $err ?: 'Anda tidak memiliki akses ke akun Administrator.';
    }
}

$page_title = 'Login Pegawai';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="row">
  <div class="card" style="flex: 1; min-width: 320px">
    <div class="card-header"><h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Akun</h3>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/users.php')) ?>">Batal</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="field"><label class="label">NIY *</label><input class="input" name="niy" required value="<?= esc($edit['niy'] ?? '') ?>"><div class="help">Password default = 4 digit terakhir.</div></div>
        <div class="field"><label class="label">Nama *</label><input class="input" name="nama" required value="<?= esc($edit['nama'] ?? '') ?>"></div>
        <div class="field"><label class="label">Email</label><input class="input" type="email" name="email" value="<?= esc($edit['email'] ?? '') ?>"></div>
        <div class="row">
          <div class="field"><label class="label">Role *</label>
            <select class="select" name="role" id="roleSel" required>
              <?php $__roleOpts = $isAdministrator ? ['administrator','admin','kepsek','guru'] : ['admin','kepsek','guru']; ?>
              <?php foreach ($__roleOpts as $r): ?>
                <option value="<?= $r ?>" <?= ($edit['role'] ?? '')===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label class="label">Jenjang (Kepsek)</label>
            <select class="select" name="jenjang">
              <option value="">—</option>
              <?php foreach (['TK','SD','SMP','SMA'] as $j): ?><option value="<?= $j ?>" <?= ($edit['jenjang'] ?? '')===$j?'selected':'' ?>><?= $j ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <label class="checkbox-row mb-4"><input type="checkbox" name="is_active" value="1" <?= !$edit || !empty($edit['is_active']) ? 'checked' : '' ?>> Aktif</label>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header"><h3 class="card-title">Daftar Akun (<?= count($rows) ?>)</h3></div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>NIY</th><th>Nama</th><th>Role</th><th>Jenjang</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= esc($r['niy']) ?></td>
            <td><strong><?= esc($r['nama']) ?></strong>
              <?php if ($r['must_change_pw']): ?><span class="badge badge-warning">Pwd default</span><?php endif; ?>
              <div class="text-sm text-muted"><?= esc($r['email'] ?? '—') ?></div>
            </td>
            <td><?= esc(ucfirst($r['role'])) ?></td>
            <td><?= esc($r['jenjang'] ?? '—') ?></td>
            <td><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Nonaktif</span>' ?></td>
            <td class="text-sm text-muted"><?= esc($r['last_login_at'] ?? '—') ?></td>
            <td style="text-align:right; white-space:nowrap">
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Reset password ke default?">
                <?= csrf_field() ?><input type="hidden" name="op" value="reset_pw"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-secondary btn-sm" type="submit">Reset PW</button>
              </form>
              <form method="post" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="op" value="toggle_active"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn <?= $r['is_active'] ? 'btn-danger' : 'btn-primary' ?> btn-sm" type="submit"><?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
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