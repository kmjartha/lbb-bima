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
            $id      = int_or_null($_POST['id'] ?? null);
            $niy     = req_str($_POST, 'niy', 20);
            $nama    = req_str($_POST, 'nama', 120);
            $email   = opt_str($_POST, 'email', 120);
            $nip     = opt_str($_POST, 'nip', 30);
            $phone   = opt_str($_POST, 'phone', 30);
            $is_wali = !empty($_POST['is_wali']) ? 1 : 0;
            $subjectIds = array_map('intval', $_POST['subjects'] ?? []);

            // Only allow subjects that are active in the current TA.
            if ($subjectIds) {
                $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
                $validStmt = $pdo->prepare(
                    "SELECT DISTINCT s.id
                     FROM subjects s
                     JOIN rombel_subject_teachers rst ON rst.subject_id = s.id
                     JOIN rombel r ON r.id = rst.rombel_id AND r.academic_year_id = ? AND r.deleted_at IS NULL
                     WHERE s.deleted_at IS NULL AND s.id IN ($placeholders)"
                );
                $validStmt->execute(array_merge([$sc['year_id']], $subjectIds));
                $validSubjectIds = array_map('intval', $validStmt->fetchAll(PDO::FETCH_COLUMN));
                if (count($validSubjectIds) !== count(array_unique($subjectIds))) {
                    throw new RuntimeException('Beberapa mata pelajaran tidak valid untuk Tahun Ajaran aktif.');
                }
                $subjectIds = $validSubjectIds;
            }

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
                $pdo->prepare("INSERT IGNORE INTO teacher_years (teacher_id, academic_year_id) VALUES (:t,:y)")
                    ->execute(['t'=>$id,'y'=>$sc['year_id']]);
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
                $pdo->prepare("INSERT INTO teacher_years (teacher_id, academic_year_id) VALUES (:t,:y)")
                    ->execute(['t'=>$id,'y'=>$sc['year_id']]);
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

$subjects = $pdo->prepare(
    "SELECT DISTINCT s.id, s.kode, s.nama
     FROM subjects s
     JOIN rombel_subject_teachers rst ON rst.subject_id = s.id
     JOIN rombel r ON r.id = rst.rombel_id AND r.academic_year_id = :y_r AND r.deleted_at IS NULL
     WHERE s.deleted_at IS NULL
       AND s.academic_year_id = :y_s
     ORDER BY s.kode"
);
$subjects->execute(['y_r' => $sc['year_id'], 'y_s' => $sc['year_id']]);
$subjects = $subjects->fetchAll();

$rows = $pdo->prepare(
    "SELECT t.id, u.niy, u.nama, u.email, u.is_wali, t.nip, t.phone,
            GROUP_CONCAT(DISTINCT s.kode ORDER BY s.kode SEPARATOR ', ') AS mapel
     FROM teachers t
     JOIN users u ON u.id = t.user_id
     LEFT JOIN teacher_years ty ON ty.teacher_id = t.id AND ty.academic_year_id = :y_year
     LEFT JOIN teacher_subjects ts ON ts.teacher_id = t.id
     LEFT JOIN subjects s ON s.id = ts.subject_id AND s.deleted_at IS NULL AND s.academic_year_id = :y_outer
     WHERE u.deleted_at IS NULL AND u.is_active = 1 AND u.role = 'guru'
       AND (
           ty.teacher_id IS NOT NULL
           OR EXISTS (
               SELECT 1 FROM teacher_subjects ts2
               JOIN subjects s2 ON s2.id = ts2.subject_id AND s2.deleted_at IS NULL AND s2.academic_year_id = :y_subjects
               WHERE ts2.teacher_id = t.id
           )
           OR EXISTS (
               SELECT 1 FROM rombel r2 WHERE r2.wali_id = t.id AND r2.academic_year_id = :y_wali AND r2.deleted_at IS NULL
           )
           OR EXISTS (
               SELECT 1 FROM rombel_subject_teachers rst2
               JOIN rombel r3 ON r3.id = rst2.rombel_id AND r3.academic_year_id = :y_assignments AND r3.deleted_at IS NULL
               WHERE rst2.teacher_id = t.id
           )
       )
     GROUP BY t.id, u.niy, u.nama, u.email, u.is_wali, t.nip, t.phone
     ORDER BY u.nama"
);
$rows->execute([
    'y_year' => $sc['year_id'],
    'y_outer' => $sc['year_id'],
    'y_subjects' => $sc['year_id'],
    'y_wali' => $sc['year_id'],
    'y_assignments' => $sc['year_id'],
]);
$rows = $rows->fetchAll();

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
        $s = $pdo->prepare(
            "SELECT DISTINCT ts.subject_id
             FROM teacher_subjects ts
             JOIN rombel_subject_teachers rst ON rst.subject_id = ts.subject_id AND rst.teacher_id = ts.teacher_id
             JOIN rombel r ON r.id = rst.rombel_id AND r.academic_year_id = :y AND r.deleted_at IS NULL
             WHERE ts.teacher_id = :id"
        );
        $s->execute(['id'=>$editId, 'y' => $sc['year_id']]);
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
