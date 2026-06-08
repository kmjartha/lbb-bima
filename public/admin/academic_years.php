<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
$u = require_view('academic_years'); // Administrator only per spec

$pdo = db();
$action = $_GET['action'] ?? 'list';
$err = null;

// ---------- POST handlers ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'create') {
            // Administrator-only: control academic year mode/lock states
            require_administrator();
            $label = req_str($_POST, 'label', 9);
            if (!preg_match('#^\d{4}/\d{4}$#', $label)) throw new RuntimeException('Format tahun ajaran: YYYY/YYYY (contoh 2025/2026).');
            $copy_from = int_or_null($_POST['copy_from'] ?? null);
            $set_active = !empty($_POST['set_active']);

            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO academic_years (label, is_active) VALUES (:l, 0)")->execute(['l' => $label]);
            $newId = (int)$pdo->lastInsertId();
            // Default state rows
            $pdo->prepare("INSERT INTO semesters_state (academic_year_id, semester) VALUES (:y1,'ganjil'),(:y2,'genap')")
                ->execute(['y1' => $newId, 'y2' => $newId]);

            if ($copy_from) {
                // Copy rombel + members + teacher mappings + KKM + templates already are global; ekskul too.
                $rombels = $pdo->prepare("SELECT * FROM rombel WHERE academic_year_id = :y AND deleted_at IS NULL");
                $rombels->execute(['y' => $copy_from]);
                foreach ($rombels->fetchAll() as $r) {
                    $pdo->prepare("INSERT INTO rombel (academic_year_id, jenjang, tingkat, nama, wali_id) VALUES (:y,:j,:t,:n,:w)")
                        ->execute(['y'=>$newId,'j'=>$r['jenjang'],'t'=>$r['tingkat'],'n'=>$r['nama'],'w'=>$r['wali_id']]);
                    $newRombelId = (int)$pdo->lastInsertId();
                    // members
                    $m = $pdo->prepare("SELECT student_id FROM rombel_members WHERE rombel_id = :r");
                    $m->execute(['r' => $r['id']]);
                    $ins = $pdo->prepare("INSERT IGNORE INTO rombel_members (rombel_id, student_id) VALUES (:r,:s)");
                    foreach ($m->fetchAll(PDO::FETCH_COLUMN) as $sid) {
                        $ins->execute(['r' => $newRombelId, 's' => $sid]);
                    }
                    // teacher mappings
                    $t = $pdo->prepare("SELECT subject_id, teacher_id FROM rombel_subject_teachers WHERE rombel_id = :r");
                    $t->execute(['r' => $r['id']]);
                    $insT = $pdo->prepare("INSERT IGNORE INTO rombel_subject_teachers (rombel_id, subject_id, teacher_id) VALUES (:r,:s,:t)");
                    foreach ($t->fetchAll() as $row) {
                        $insT->execute(['r' => $newRombelId, 's' => $row['subject_id'], 't' => $row['teacher_id']]);
                    }
                }
            }

            if ($set_active) {
                $pdo->exec("UPDATE academic_years SET is_active = 0");
                $pdo->prepare("UPDATE academic_years SET is_active = 1 WHERE id = :id")->execute(['id' => $newId]);
            }
            $pdo->commit();
            audit('create', 'academic_year:' . $label, ['copy_from' => $copy_from]);
            flash('success', "Tahun ajaran $label dibuat.");
            redirect('admin/academic_years.php');
        }

        if ($op === 'set_active') {
            require_administrator();
            $id = (int)($_POST['id'] ?? 0);
            $pdo->beginTransaction();
            $pdo->exec("UPDATE academic_years SET is_active = 0");
            $pdo->prepare("UPDATE academic_years SET is_active = 1 WHERE id = :id")->execute(['id' => $id]);
            $pdo->commit();
            audit('set_active', 'academic_year:' . $id);
            flash('success', 'Tahun ajaran aktif diperbarui.');
            redirect('admin/academic_years.php');
        }

        if ($op === 'toggle_lock') {
            require_administrator();
            $yearId   = (int)($_POST['year_id'] ?? 0);
            $semester = (string)($_POST['semester'] ?? '');
            if (!in_array($semester, ['ganjil','genap'], true)) throw new RuntimeException('Semester invalid.');
            // Ensure a row exists, then flip the per-semester lock.
            $pdo->prepare("INSERT IGNORE INTO semesters_state (academic_year_id, semester) VALUES (:y, :s)")
                ->execute(['y' => $yearId, 's' => $semester]);
            $pdo->prepare("UPDATE semesters_state SET semester_locked = 1 - semester_locked WHERE academic_year_id = :y AND semester = :s")
                ->execute(['y' => $yearId, 's' => $semester]);
            audit('toggle_semester_lock', "year:$yearId/$semester");
            flash('success', 'Status kunci semester diperbarui.');
            redirect('admin/academic_years.php');
        }

        if ($op === 'delete') {
            require_administrator();
            $id = (int)($_POST['id'] ?? 0);
            // Refuse to delete the active year
            $isActive = (int)$pdo->prepare("SELECT is_active FROM academic_years WHERE id = :id");
            $stmt = $pdo->prepare("SELECT is_active FROM academic_years WHERE id = :id");
            $stmt->execute(['id' => $id]);
            if ((int)$stmt->fetchColumn() === 1) throw new RuntimeException('Tidak bisa menghapus tahun ajaran yang aktif.');
            $pdo->prepare("DELETE FROM academic_years WHERE id = :id")->execute(['id' => $id]);
            audit('delete', 'academic_year:' . $id);
            flash('success', 'Tahun ajaran dihapus.');
            redirect('admin/academic_years.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}

// ---------- DATA ----------
$years = $pdo->query(
    "SELECT y.*,
        COALESCE((SELECT semester_locked FROM semesters_state WHERE academic_year_id=y.id AND semester='ganjil'),0) AS gj_lock,
        COALESCE((SELECT semester_locked FROM semesters_state WHERE academic_year_id=y.id AND semester='genap'),0)  AS gn_lock
     FROM academic_years y ORDER BY label DESC"
)->fetchAll();

$page_title = 'Tahun Ajaran';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<?php if ($u['role'] === 'administrator'): ?>
<div class="card mb-4">
  <div class="card-header"><h3 class="card-title">Tambah Tahun Ajaran</h3></div>
  <div class="card-body">
    <form method="post" class="row" style="align-items: end">
      <?= csrf_field() ?><input type="hidden" name="op" value="create">
      <div class="field" style="flex: 1"><label class="label">Label *</label><input class="input" name="label" placeholder="2025/2026" pattern="\d{4}/\d{4}" required></div>
      <div class="field" style="flex: 1">
        <label class="label">Salin dari</label>
        <select class="select" name="copy_from">
          <option value="">— Mulai kosong —</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= (int)$y['id'] ?>"><?= esc($y['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="flex: 0 0 auto">
        <label class="checkbox-row"><input type="checkbox" name="set_active" value="1"> Jadikan aktif</label>
      </div>
      <div class="field" style="flex: 0 0 auto"><button class="btn btn-primary" type="submit">Buat</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h3 class="card-title">Daftar Tahun Ajaran</h3></div>
  <div class="table-wrap">
    <table class="t">
      <thead><tr>
        <th>Label</th><th>Status</th>
        <th>Semester Ganjil</th>
        <th>Semester Genap</th>
        <th style="text-align:right">Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach ($years as $y): ?>
        <tr>
          <td><strong><?= esc($y['label']) ?></strong></td>
          <td><?= $y['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge">Nonaktif</span>' ?></td>
          <?php foreach ([['ganjil', (int)$y['gj_lock']], ['genap', (int)$y['gn_lock']]] as [$sem, $val]): ?>
            <td>
              <?php if ($u['role'] === 'administrator'): ?>
                <form method="post" style="display:inline-flex; align-items:center; gap:.5rem; margin:0">
                  <?= csrf_field() ?>
                  <input type="hidden" name="op" value="toggle_lock">
                  <input type="hidden" name="year_id" value="<?= (int)$y['id'] ?>">
                  <input type="hidden" name="semester" value="<?= esc($sem) ?>">
                  <label class="switch" title="<?= $val ? 'Klik untuk membuka kunci' : 'Klik untuk mengunci' ?> Semester <?= esc(ucfirst($sem)) ?>">
                    <input type="checkbox" <?= $val ? 'checked' : '' ?> onchange="this.form.submit()">
                    <span class="slider"></span>
                  </label>
                  <span class="badge <?= $val ? 'badge-danger' : 'badge-success' ?>"><?= $val ? '🔒 Terkunci' : '🔓 Terbuka' ?></span>
                </form>
              <?php else: ?>
                <span class="badge <?= $val ? 'badge-danger' : 'badge-success' ?>"><?= $val ? '🔒 Terkunci' : '🔓 Terbuka' ?></span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
          <td style="text-align:right">
            <?php if ($u['role'] === 'administrator'): ?>
              <?php if (!$y['is_active']): ?>
              <form method="post" style="display:inline">
                <?= csrf_field() ?><input type="hidden" name="op" value="set_active"><input type="hidden" name="id" value="<?= (int)$y['id'] ?>">
                <button class="btn btn-secondary btn-sm" type="submit">Aktifkan</button>
              </form>
              <form method="post" style="display:inline" data-confirm="Hapus tahun ajaran <?= esc($y['label']) ?>? Semua rombel & nilai akan ikut terhapus.">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$y['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
