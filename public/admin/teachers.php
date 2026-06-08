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
            $id      = int_or_null($_POST['id'] ?? null);
            $niy     = req_str($_POST, 'niy', 20);
            $nama    = req_str($_POST, 'nama', 120);
            $email   = opt_str($_POST, 'email', 120);
            $nip     = opt_str($_POST, 'nip', 30);
            $phone   = opt_str($_POST, 'phone', 30);
            $is_wali = !empty($_POST['is_wali']) ? 1 : 0;
            $subjectIds = array_map('intval', $_POST['subjects'] ?? []);

            $pdo->beginTransaction();

            if ($id) {
                // Existing teacher edit
                $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $userId = (int)$stmt->fetchColumn();
                if (!$userId) throw new RuntimeException('Guru tidak ditemukan.');
                if (!niy_unique($niy, $userId)) throw new RuntimeException('NIY sudah digunakan.');
                $pdo->prepare("UPDATE users SET niy=:n, nama=:nm, email=:e, is_wali=:w WHERE id=:id")
                    ->execute(['n'=>$niy,'nm'=>$nama,'e'=>$email,'w'=>$is_wali,'id'=>$userId]);
                $pdo->prepare("UPDATE teachers SET nip=:p, phone=:ph WHERE id=:id")
                    ->execute(['p'=>$nip,'ph'=>$phone,'id'=>$id]);
            } else {
                if (!niy_unique($niy)) throw new RuntimeException('NIY sudah digunakan.');
                $defaultPw = substr($niy, -4);
                if (strlen($defaultPw) < 4) throw new RuntimeException('NIY minimal 4 digit.');
                $pdo->prepare("INSERT INTO users (niy, nama, email, password_hash, role, is_wali, must_change_pw)
                               VALUES (:n,:nm,:e,:h,'guru',:w,1)")
                    ->execute(['n'=>$niy,'nm'=>$nama,'e'=>$email,'h'=>password_hash($defaultPw, PASSWORD_DEFAULT),'w'=>$is_wali]);
                $userId = (int)$pdo->lastInsertId();
                $pdo->prepare("INSERT INTO teachers (user_id, nip, phone) VALUES (:u,:p,:ph)")
                    ->execute(['u'=>$userId,'p'=>$nip,'ph'=>$phone]);
                $id = (int)$pdo->lastInsertId();
            }

            // Subjects mapping
            $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = :id")->execute(['id'=>$id]);
            $insS = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (:t,:s)");
            foreach ($subjectIds as $sid) $insS->execute(['t'=>$id,'s'=>$sid]);

            $pdo->commit();
            audit('save', 'teacher:' . $id);
            flash('success', 'Data guru disimpan.' . (empty($_POST['id']) ? ' Password default = 4 digit terakhir NIY.' : ''));
            redirect('admin/teachers.php');
        }

        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $userId = (int)$pdo->query("SELECT user_id FROM teachers WHERE id = " . (int)$id)->fetchColumn();
            $pdo->prepare("UPDATE users SET deleted_at = NOW(), is_active = 0 WHERE id = :u")->execute(['u'=>$userId]);
            audit('delete', 'teacher:' . $id);
            flash('success', 'Guru dinonaktifkan.');
            redirect('admin/teachers.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}

$subjects = $pdo->query("SELECT id, kode, nama FROM subjects WHERE deleted_at IS NULL ORDER BY kode")->fetchAll();

$rows = $pdo->query(
    "SELECT t.id, u.niy, u.nama, u.email, u.is_wali, t.nip, t.phone,
            (SELECT GROUP_CONCAT(s.kode ORDER BY s.kode SEPARATOR ', ')
             FROM teacher_subjects ts JOIN subjects s ON s.id = ts.subject_id
             WHERE ts.teacher_id = t.id) AS mapel
     FROM teachers t
     JOIN users u ON u.id = t.user_id
     WHERE u.deleted_at IS NULL
     ORDER BY u.nama"
)->fetchAll();

$edit = null; $editSubjects = [];
if ($editId) {
    $stmt = $pdo->prepare(
        "SELECT t.id, u.niy, u.nama, u.email, u.is_wali, t.nip, t.phone
         FROM teachers t JOIN users u ON u.id = t.user_id
         WHERE t.id = :id AND u.deleted_at IS NULL"
    );
    $stmt->execute(['id'=>$editId]);
    $edit = $stmt->fetch();
    if ($edit) {
        $s = $pdo->prepare("SELECT subject_id FROM teacher_subjects WHERE teacher_id = :id");
        $s->execute(['id'=>$editId]);
        $editSubjects = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    }
}

$page_title = 'Guru';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>
<div class="row">
  <div class="card" style="flex: 1; min-width: 340px">
    <div class="card-header"><h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Guru</h3>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/teachers.php')) ?>">Batal</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="row">
          <div class="field"><label class="label">NIY *</label><input class="input" name="niy" required value="<?= esc($edit['niy'] ?? '') ?>"><div class="help">Password default = 4 digit terakhir.</div></div>
          <div class="field"><label class="label">NIP</label><input class="input" name="nip" value="<?= esc($edit['nip'] ?? '') ?>"></div>
        </div>
        <div class="field"><label class="label">Nama *</label><input class="input" name="nama" required value="<?= esc($edit['nama'] ?? '') ?>"></div>
        <div class="row">
          <div class="field"><label class="label">Email</label><input class="input" type="email" name="email" value="<?= esc($edit['email'] ?? '') ?>"></div>
          <div class="field"><label class="label">Telepon</label><input class="input" name="phone" value="<?= esc($edit['phone'] ?? '') ?>"></div>
        </div>
        <div class="field">
  <label class="label">Mata Pelajaran</label>

  <div class="checkbox-scroll">
    <?php foreach ($subjects as $s): ?>
      <label class="checkbox-item">
        <input type="checkbox"
               name="subjects[]"
               value="<?= (int)$s['id'] ?>"
               <?= in_array((int)$s['id'], $editSubjects, true) ? 'checked' : '' ?>>
        <span><?= esc($s['kode'] . ' — ' . $s['nama']) ?></span>
      </label>
    <?php endforeach; ?>
  </div>

</div>
        <label class="checkbox-row mb-4"><input type="checkbox" name="is_wali" value="1" <?= !empty($edit['is_wali'])?'checked':'' ?>> Tandai sebagai Wali Kelas</label>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header"><h3 class="card-title">Daftar Guru (<?= count($rows) ?>)</h3></div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>NIY</th><th>Nama</th><th>NIP</th><th>Mapel</th><th>Wali?</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6"><div class="empty">Belum ada data.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= esc($r['niy']) ?></td>
            <td><strong><?= esc($r['nama']) ?></strong><div class="text-sm text-muted"><?= esc($r['email'] ?? '—') ?></div></td>
            <td><?= esc($r['nip'] ?? '—') ?></td>
            <td class="text-sm"><?= esc($r['mapel'] ?? '—') ?></td>
            <td><?= $r['is_wali'] ? '<span class="badge badge-success">Wali</span>' : '<span class="badge">—</span>' ?></td>
            <td style="text-align:right; white-space:nowrap">
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Nonaktifkan guru <?= esc($r['nama']) ?>?">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Nonaktif</button>
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
