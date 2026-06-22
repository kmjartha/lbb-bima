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

        if ($op === 'save') {
            $id      = int_or_null($_POST['id'] ?? null);
            $niy     = req_str($_POST, 'niy', 20);
            $nama    = req_str($_POST, 'nama', 120);
            $email   = opt_str($_POST, 'email', 120);
            $nip     = opt_str($_POST, 'nip', 30);
            $phone   = opt_str($_POST, 'phone', 30);
            $is_wali = !empty($_POST['is_wali']) ? 1 : 0;

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

            // Subjects mapping is handled separately in Guru Pengampu / rombel pengampu.
            if (isset($_POST['subjects'])) {
                $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = :id")->execute(['id'=>$id]);
                $insS = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (:t,:s)");
                foreach (array_map('intval', $_POST['subjects']) as $sid) {
                    $insS->execute(['t'=>$id,'s'=>$sid]);
                }
            }

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

// Build Search and Pagination Query
$searchCond = "";
$countParams = [
    'y_year' => $sc['year_id'],
    'y_subjects' => $sc['year_id'],
    'y_wali' => $sc['year_id'],
    'y_assignments' => $sc['year_id'],
];

if ($q !== '') {
    $searchCond = " AND (u.niy LIKE :q1 OR u.nama LIKE :q2 OR u.email LIKE :q3) ";
    $countParams['q1'] = '%' . $q . '%';
    $countParams['q2'] = '%' . $q . '%';
    $countParams['q3'] = '%' . $q . '%';
}

$baseWhere = "WHERE u.deleted_at IS NULL AND u.is_active = 1 AND u.role = 'guru'
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
              ) " . $searchCond;

// Count total records for pagination
// We use a subquery for counting to handle the complex joins and EXISTS clauses properly
$countSql = "SELECT COUNT(DISTINCT t.id) FROM teachers t
             JOIN users u ON u.id = t.user_id
             LEFT JOIN teacher_years ty ON ty.teacher_id = t.id AND ty.academic_year_id = :y_year
             $baseWhere";
             
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($countParams);
$totalRows = (int)$stmtCount->fetchColumn();

// Calculate total pages and offset
$totalPages = max(1, (int)ceil($totalRows / $limit));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

// Fetch rows with LIMIT and OFFSET
$rowParams = $countParams;
$rowParams['y_outer'] = $sc['year_id']; // Parameter needed for the final SELECT, not used in COUNT

$rowSql = "SELECT t.id, u.niy, u.nama, u.email, u.is_wali, t.nip, t.phone,
                  GROUP_CONCAT(DISTINCT s.kode ORDER BY s.kode SEPARATOR ', ') AS mapel
           FROM teachers t
           JOIN users u ON u.id = t.user_id
           LEFT JOIN teacher_years ty ON ty.teacher_id = t.id AND ty.academic_year_id = :y_year
           LEFT JOIN teacher_subjects ts ON ts.teacher_id = t.id
           LEFT JOIN subjects s ON s.id = ts.subject_id AND s.deleted_at IS NULL AND s.academic_year_id = :y_outer
           $baseWhere
           GROUP BY t.id, u.niy, u.nama, u.email, u.is_wali, t.nip, t.phone
           ORDER BY u.nama
           LIMIT $limit OFFSET $offset";

$stmtRows = $pdo->prepare($rowSql);
// PDO expects LIMIT and OFFSET to be integers if passed as parameters, 
// so embedding them directly into the string query is safer here given $limit and $offset are already cast to int.
$stmtRows->execute($rowParams);
$rows = $stmtRows->fetchAll();

$edit = null;
if ($editId) {
    $stmt = $pdo->prepare(
        "SELECT t.id, u.niy, u.nama, u.email, u.is_wali, t.nip, t.phone
         FROM teachers t JOIN users u ON u.id = t.user_id
         WHERE t.id = :id AND u.deleted_at IS NULL"
    );
    $stmt->execute(['id'=>$editId]);
    $edit = $stmt->fetch();
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
        <label class="checkbox-row mb-4"><input type="checkbox" name="is_wali" value="1" <?= !empty($edit['is_wali'])?'checked':'' ?>> Tandai sebagai Wali Kelas</label>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <h3 class="card-title">Daftar Guru (<?= $totalRows ?>)</h3>
        <form method="get" style="display:flex; gap:5px;">
            <input type="text" name="q" class="input input-sm" placeholder="Cari Nama/NIY..." value="<?= esc($q) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
            <?php if ($q): ?>
                <a href="teachers.php" class="btn btn-ghost btn-sm" title="Reset Pencarian">✕</a>
            <?php endif; ?>
        </form>
    </div>
    
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
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $page>1 ? '&p='.$page : '' ?>">Edit</a>
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