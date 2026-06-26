<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
$me = require_view('rombel');
$canEdit = can_edit('rombel', $me);

$pdo = db();
$sc = active_scope();
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);
$manageId = int_or_null($_GET['manage'] ?? null);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
    $stmt->execute(['id'=>$editId,'y'=>$sc['year_id']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        if (!$canEdit) throw new RuntimeException('Anda hanya memiliki akses lihat untuk fitur ini.');
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'save') {
            $id        = int_or_null($_POST['id'] ?? null);
            $jenjang   = req_str($_POST, 'jenjang', 3);
            $tingkat   = (int)($_POST['tingkat'] ?? 0);
            $nama      = req_str($_POST, 'nama', 40);
            $waliId    = int_or_null($_POST['wali_id'] ?? null);
            $kapasitas = max(1, (int)($_POST['kapasitas'] ?? 28));
            if (!in_array($jenjang, ['TK','SD','SMP','SMA'], true)) throw new RuntimeException('Jenjang invalid.');
            if ($jenjang === 'TK') {
                if ($tingkat < 0 || $tingkat > 2) throw new RuntimeException('Tingkat TK 0-2.');
            } else {
                if ($tingkat < 1 || $tingkat > 12) throw new RuntimeException('Tingkat 1-12.');
            }

            if ($waliId !== null && $waliId > 0) {
                $waliCheck = $pdo->prepare(
                    "SELECT u.id
                     FROM users u
                     JOIN teachers t ON t.user_id = u.id
                     JOIN teacher_years ty ON ty.teacher_id = t.id AND ty.academic_year_id = :y
                     WHERE u.id = :uid AND u.role = 'guru' AND u.is_wali = 1 AND u.deleted_at IS NULL"
                );
                $waliCheck->execute(['uid'=>$waliId,'y'=>$sc['year_id']]);
                if (!$waliCheck->fetchColumn()) {
                    throw new RuntimeException('Guru yang dipilih bukan wali kelas yang valid.');
                }

                $duplicateWali = $pdo->prepare(
                    "SELECT id FROM rombel
                     WHERE academic_year_id = :y AND deleted_at IS NULL AND wali_id = :wid AND id <> :rid"
                );
                $duplicateWali->execute(['y'=>$sc['year_id'],'wid'=>$waliId,'rid'=>$id ?: 0]);
                if ($duplicateWali->fetchColumn()) {
                    throw new RuntimeException('Guru ini sudah menjadi wali kelas di rombel lain.');
                }
            }

            if ($id) {
                $pdo->prepare("UPDATE rombel SET jenjang=:j, tingkat=:t, nama=:n, wali_id=:w, kapasitas=:k WHERE id=:id AND academic_year_id=:y")
                    ->execute(['j'=>$jenjang,'t'=>$tingkat,'n'=>$nama,'w'=>$waliId,'k'=>$kapasitas,'id'=>$id,'y'=>$sc['year_id']]);
            } else {
                $pdo->prepare("INSERT INTO rombel (academic_year_id, jenjang, tingkat, nama, wali_id, kapasitas) VALUES (:y,:j,:t,:n,:w,:k)")
                    ->execute(['y'=>$sc['year_id'],'j'=>$jenjang,'t'=>$tingkat,'n'=>$nama,'w'=>$waliId,'k'=>$kapasitas]);
                $id = (int)$pdo->lastInsertId();
            }
            audit('save', 'rombel:' . $id);
            flash('success', 'Rombel disimpan.');
            redirect('admin/rombel.php');
        }

        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE rombel SET deleted_at=NOW() WHERE id=:id AND academic_year_id=:y")
                ->execute(['id'=>$id,'y'=>$sc['year_id']]);
            audit('delete', 'rombel:' . $id);
            flash('success', 'Rombel dihapus.');
            redirect('admin/rombel.php');
        }

        if ($op === 'add_members') {
            $rid = (int)($_POST['rombel_id'] ?? 0);
            $ids = array_map('intval', $_POST['student_ids'] ?? []);
            if (!$rid || !$ids) throw new RuntimeException('Pilih minimal 1 siswa.');
            $stmt = $pdo->prepare("SELECT 1 FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
            $stmt->execute(['id'=>$rid,'y'=>$sc['year_id']]);
            if (!$stmt->fetchColumn()) throw new RuntimeException('Rombel tidak ditemukan di tahun ajaran aktif.');
            $ins = $pdo->prepare("INSERT IGNORE INTO rombel_members (rombel_id, student_id) VALUES (:r,:s)");
            foreach ($ids as $sid) $ins->execute(['r'=>$rid,'s'=>$sid]);
            audit('add_members', 'rombel:' . $rid, ['n' => count($ids)]);
            flash('success', count($ids) . ' siswa ditambahkan.');
            redirect('admin/rombel.php?manage=' . $rid);
        }

        if ($op === 'remove_member') {
            $rid = (int)($_POST['rombel_id'] ?? 0);
            $sid = (int)($_POST['student_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT 1 FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
            $stmt->execute(['id'=>$rid,'y'=>$sc['year_id']]);
            if (!$stmt->fetchColumn()) throw new RuntimeException('Rombel tidak ditemukan di tahun ajaran aktif.');
            $pdo->prepare("DELETE FROM rombel_members WHERE rombel_id=:r AND student_id=:s")->execute(['r'=>$rid,'s'=>$sid]);
            audit('remove_member', 'rombel:' . $rid, ['s' => $sid]);
            flash('success', 'Anggota dikeluarkan.');
            redirect('admin/rombel.php?manage=' . $rid);
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

// Wali kelas options (guru yang belum menjadi wali di rombel lain di TA aktif)
$walis = $pdo->prepare(
    "SELECT u.id, u.niy, u.nama
     FROM users u
     JOIN teachers t ON t.user_id = u.id
     JOIN teacher_years ty ON ty.teacher_id = t.id AND ty.academic_year_id = :y
     WHERE u.role = 'guru' AND u.is_wali = 1 AND u.deleted_at IS NULL
       AND NOT EXISTS (
           SELECT 1 FROM rombel r
           WHERE r.academic_year_id = :y2
             AND r.deleted_at IS NULL
             AND r.wali_id = u.id
             AND r.id <> :edit_id
       )
     ORDER BY u.nama"
);
$walis->execute(['y' => $sc['year_id'], 'y2' => $sc['year_id'], 'edit_id' => $editId ?: 0]);
$walis = $walis->fetchAll();

// List rombel for active TA
$rows = $pdo->prepare(
    "SELECT r.*, u.nama AS wali_nama,
            (SELECT COUNT(*) FROM rombel_members rm WHERE rm.rombel_id = r.id) AS jml_anggota
     FROM rombel r LEFT JOIN users u ON u.id = r.wali_id
     WHERE r.academic_year_id = :y AND r.deleted_at IS NULL
     ORDER BY FIELD(r.jenjang,'TK','SD','SMP','SMA'), r.tingkat, r.nama"
);
$rows->execute(['y'=>$sc['year_id']]);
$rows = $rows->fetchAll();

// Manage members of one rombel
$manage = null; $members = []; $eligible = [];
if ($manageId) {
    $stmt = $pdo->prepare("SELECT r.*, u.nama AS wali_nama FROM rombel r LEFT JOIN users u ON u.id=r.wali_id WHERE r.id=:id AND r.academic_year_id=:y AND r.deleted_at IS NULL");
    $stmt->execute(['id'=>$manageId,'y'=>$sc['year_id']]); $manage = $stmt->fetch();
    if ($manage) {
        $m = $pdo->prepare("SELECT s.* FROM rombel_members rm JOIN students s ON s.id=rm.student_id WHERE rm.rombel_id=:r AND s.deleted_at IS NULL AND s.academic_year_id = :y ORDER BY s.nama");
        $m->execute(['r'=>$manageId,'y'=>$sc['year_id']]); $members = $m->fetchAll();
        // Eligible = same jenjang+tingkat & not yet member of any rombel in this TA
        $e = $pdo->prepare(
            "SELECT s.* FROM students s
             WHERE s.deleted_at IS NULL AND s.is_active=1 AND s.academic_year_id = :y_outer AND s.jenjang=:j AND s.tingkat=:t
               AND s.id NOT IN (
                 SELECT rm.student_id FROM rombel_members rm JOIN rombel r ON r.id=rm.rombel_id
                 WHERE r.academic_year_id=:y_inner AND r.deleted_at IS NULL
               )
             ORDER BY s.nama"
        );
        $e->execute([
            'j' => $manage['jenjang'],
            't' => $manage['tingkat'],
            'y_outer' => $sc['year_id'],
            'y_inner' => $sc['year_id'],
        ]);
        $eligible = $e->fetchAll();
    }
}

$page_title = 'Rombel & Anggota';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="row">
  <?php if ($canEdit): ?><div class="card" style="flex: 1; min-width: 320px">
    <div class="card-header"><h3 class="card-title"><?= $edit?'Edit':'Tambah' ?> Rombel</h3>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/rombel.php')) ?>">Batal</a><?php endif; ?>
    </div>
    <div class="card-body">
      <p class="text-sm text-muted mb-3">Tahun Ajaran aktif: <strong><?= esc($sc['year']) ?></strong></p>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="row">
          <div class="field"><label class="label">Jenjang *</label>
            <select class="select" name="jenjang" id="jenjang_select" required>
              <?php foreach (['TK','SD','SMP','SMA'] as $j): ?>
                <option value="<?= $j ?>" <?= ($edit['jenjang']??'')===$j?'selected':'' ?>><?= $j ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label class="label">Tingkat *</label>
            <input class="input" type="number" name="tingkat" id="tingkat_input" min="1" max="12" required value="<?= esc($edit['tingkat']??'') ?>">
          </div>
        </div>
        <script>
          (function() {
            const jenjangSelect = document.getElementById('jenjang_select');
            const tingkatInput = document.getElementById('tingkat_input');
            
            const constraints = {
              'TK': { min: 0, max: 2 },
              'SD': { min: 1, max: 6 },
              'SMP': { min: 7, max: 9 },
              'SMA': { min: 10, max: 12 }
            };
            
            function updateTingkatConstraints() {
              const jenjang = jenjangSelect.value;
              const constraint = constraints[jenjang];
              if (constraint) {
                tingkatInput.min = constraint.min;
                tingkatInput.max = constraint.max;
              }
            }
            
            jenjangSelect.addEventListener('change', updateTingkatConstraints);
            updateTingkatConstraints();
          })();
        </script>
        <div class="row">
          <div class="field"><label class="label">Nama Rombel *</label><input class="input" name="nama" required placeholder="1A / 7-Bilal" value="<?= esc($edit['nama']??'') ?>"></div>
          <div class="field" style="flex:0 0 130px"><label class="label">Kapasitas</label><input class="input" type="number" name="kapasitas" min="1" max="60" value="<?= esc($edit['kapasitas']??28) ?>"></div>
        </div>
        <div class="field"><label class="label">Wali Kelas</label>
          <select class="select" name="wali_id">
            <option value="">— Belum ditugaskan —</option>
            <?php foreach ($walis as $w): ?>
              <option value="<?= (int)$w['id'] ?>" <?= ($edit['wali_id']??null)==$w['id']?'selected':'' ?>><?= esc($w['niy'].' — '.$w['nama']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="help">Hanya guru yang ditandai "Wali Kelas" di halaman Guru. Guru yang sudah menjadi wali di rombel lain tidak ditampilkan.</div>
        </div>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div><?php endif; ?>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header"><h3 class="card-title">Daftar Rombel (<?= count($rows) ?>)</h3></div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Jenjang</th><th>Tingkat</th><th>Nama</th><th>Wali Kelas</th><th>Anggota</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6"><div class="empty">Belum ada rombel di TA ini.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><span class="badge badge-primary"><?= esc($r['jenjang']) ?></span></td>
            <td><?= (int)$r['tingkat'] ?></td>
            <td><strong><?= esc($r['nama']) ?></strong></td>
            <td><?= esc($r['wali_nama'] ?? '—') ?></td>
            <td><?= (int)$r['jml_anggota'] ?> / <?= (int)$r['kapasitas'] ?></td>
            <td style="text-align:right; white-space:nowrap">
              <a class="btn btn-secondary btn-sm" href="?manage=<?= (int)$r['id'] ?>">Anggota</a>
              <?php if ($canEdit): ?><a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Hapus rombel <?= esc($r['nama']) ?>?">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($manage): ?>
<div class="card mt-4">
  <div class="card-header">
    <h3 class="card-title">Anggota — <?= esc($manage['jenjang'].' '.$manage['tingkat'].' · '.$manage['nama']) ?></h3>
    <a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/rombel.php')) ?>">Tutup</a>
  </div>
  <div class="card-body">
    <div class="row">
      <div style="flex: 1; min-width: 300px">
        <h4 class="mb-2">Anggota Saat Ini (<?= count($members) ?>)</h4>
        <?php if (!$members): ?><div class="empty">Belum ada anggota.</div><?php else: ?>
          <div class="table-wrap"><table class="t">
            <thead><tr><th>NIS</th><th>Nama</th><th>JK</th><th></th></tr></thead><tbody>
            <?php foreach ($members as $s): ?>
              <tr>
                <td><?= esc($s['nis']) ?></td><td><?= esc($s['nama']) ?></td><td><?= esc($s['jk']) ?></td>
                <td style="text-align:right">
                  <?php if ($canEdit): ?><form method="post" style="display:inline" data-confirm="Keluarkan <?= esc($s['nama']) ?>?">
                    <?= csrf_field() ?><input type="hidden" name="op" value="remove_member">
                    <input type="hidden" name="rombel_id" value="<?= (int)$manage['id'] ?>"><input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                    <button class="btn btn-danger btn-sm">Keluarkan</button>
                  </form><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
      </div>
      <div style="flex: 1; min-width: 300px">
        <?php if ($canEdit): ?><h4 class="mb-2">Tambah Anggota (siswa <?= esc($manage['jenjang']) ?> tingkat <?= (int)$manage['tingkat'] ?> belum di rombel manapun)</h4>
        <?php if (!$eligible): ?><div class="empty">Tidak ada siswa eligible.</div><?php else: ?>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="op" value="add_members"><input type="hidden" name="rombel_id" value="<?= (int)$manage['id'] ?>">
            <div style="max-height:260px; overflow:auto; border:1px solid #e6e6e6; padding:8px; border-radius:6px">
              <?php foreach ($eligible as $s): ?>
                <label style="display:block; margin-bottom:6px;">
                  <input type="checkbox" name="student_ids[]" value="<?= (int)$s['id'] ?>"> <?= esc($s['nis'].' — '.$s['nama'].' ('.$s['jk'].')') ?>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="help">Centang siswa yang akan ditambahkan.</div>
            <button class="btn btn-primary mt-2">Tambahkan</button>
          </form>
        <?php endif; ?><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
