<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
require_once __DIR__ . '/../../includes/grading_helpers.php';
require_once __DIR__ . '/../../includes/permissions.php';
$me = require_view('subject_topics');

$pdo = db();
$sc = active_scope();
$err = null;

$rombelId  = int_or_null($_GET['rombel_id']  ?? null);
$subjectId = int_or_null($_GET['subject_id'] ?? null);
$editId    = int_or_null($_GET['edit_id']    ?? null);
$editTopic = null;

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM subject_topics WHERE id=:id AND deleted_at IS NULL");
    $stmt->execute(['id'=>$editId]);
    $editTopic = $stmt->fetch();
    if ($editTopic && !$rombelId) {
        $rombelId = (int)$editTopic['rombel_id'];
        $subjectId = (int)$editTopic['subject_id'];
    } elseif ($editTopic && ((int)$editTopic['rombel_id'] !== $rombelId || (int)$editTopic['subject_id'] !== $subjectId)) {
        $editTopic = null; // mismatch, don't allow edit
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        require_edit('subject_topics');
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'save') {
            $id        = int_or_null($_POST['id'] ?? null);
            $rid       = (int)($_POST['rombel_id'] ?? 0);
            $sid       = (int)($_POST['subject_id'] ?? 0);
            $semester  = req_str($_POST, 'semester', 6);
            $kode      = opt_str($_POST, 'kode', 20);
            $judul     = req_str($_POST, 'judul', 160);
            
            // SPK: ranah_list selalu mencakup semua 3 ranah (Sikap, Pengetahuan, Keterampilan)
            $ranahList     = ['sikap', 'pengetahuan', 'keterampilan'];
            $ranahListJson = json_encode($ranahList);
            $ranah         = 'sikap'; // legacy single-column compat
            
            $kategori  = req_str($_POST, 'kategori', 20);
            $bobot     = (float)($_POST['bobot'] ?? 1);
            $deskripsi = opt_str($_POST, 'deskripsi', 1000);
            if (!in_array($semester, ['ganjil','genap'], true)) throw new RuntimeException('Semester invalid.');
            if (!in_array($kategori, ['tugas','ulangan','proyek','praktek','portofolio','produk','lainnya'], true)) throw new RuntimeException('Kategori invalid.');
            if (!$rid || !$sid) throw new RuntimeException('Rombel & mapel wajib.');

                // Guru hanya boleh menambah/edit subjek penilaian untuk mapel yang diaampu di rombel ini.
            if ($me['role'] === 'guru') {
                $stmt = $pdo->prepare(
                    "SELECT 1 FROM rombel_subject_teachers rst
                     JOIN teachers t ON t.id = rst.teacher_id
                     WHERE rst.rombel_id = :r
                       AND rst.subject_id = :s
                       AND t.user_id = :u
                       AND (rst.semester IS NULL OR rst.semester = :sem)"
                );
                $stmt->execute(['r'=>$rid,'s'=>$sid,'u'=>$me['id'],'sem'=>$semester]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Anda tidak memiliki akses untuk membuat/mengedit subjek penilaian untuk mapel ini.');
                }
            }

            if ($id) {
                $pdo->prepare("UPDATE subject_topics SET kode=:k, judul=:j, ranah=:rn, ranah_list=:rl, kategori=:kat, bobot=:b, deskripsi=:d, semester=:sem WHERE id=:id")
                    ->execute(['k'=>$kode,'j'=>$judul,'rn'=>$ranah,'rl'=>$ranahListJson,'kat'=>$kategori,'b'=>$bobot,'d'=>$deskripsi,'sem'=>$semester,'id'=>$id]);
            } else {
                $pdo->prepare("INSERT INTO subject_topics (rombel_id, subject_id, semester, kode, judul, ranah, ranah_list, kategori, bobot, deskripsi, created_by) VALUES (:r,:s,:sem,:k,:j,:rn,:rl,:kat,:b,:d,:u)")
                    ->execute(['r'=>$rid,'s'=>$sid,'sem'=>$semester,'k'=>$kode,'j'=>$judul,'rn'=>$ranah,'rl'=>$ranahListJson,'kat'=>$kategori,'b'=>$bobot,'d'=>$deskripsi,'u'=>$me['id']]);
                $id = (int)$pdo->lastInsertId();
            }
            audit('save', 'topic:' . $id);
            flash('success', 'Subjek penilaian disimpan.');
            redirect('admin/subject_topics.php?rombel_id=' . $rid . '&subject_id=' . $sid);
        }

        if ($op === 'delete') {
            $id  = (int)($_POST['id'] ?? 0);
            $rid = (int)($_POST['rombel_id'] ?? 0);
            $sid = (int)($_POST['subject_id'] ?? 0);
            if ($me['role'] === 'guru') {
                $stmt = $pdo->prepare(
                    "SELECT 1 FROM rombel_subject_teachers rst
                     JOIN teachers t ON t.id = rst.teacher_id
                     WHERE rst.rombel_id = :r
                       AND rst.subject_id = :s
                       AND t.user_id = :u
                       AND (rst.semester IS NULL OR rst.semester = :sem)"
                );
                $stmt->execute(['r'=>$rid,'s'=>$sid,'u'=>$me['id'],'sem'=>$sc['semester']]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Anda tidak memiliki akses untuk menghapus topik di mapel ini.');
                }
            }
            $pdo->prepare("UPDATE subject_topics SET deleted_at=NOW() WHERE id=:id")->execute(['id'=>$id]);
            audit('delete', 'topic:' . $id);
            flash('success', 'Topik dihapus.');
            redirect('admin/subject_topics.php?rombel_id=' . $rid . '&subject_id=' . $sid);
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$rombels = $pdo->prepare(
    "SELECT id, jenjang, tingkat, nama FROM rombel
     WHERE academic_year_id=:y AND deleted_at IS NULL
     ORDER BY FIELD(jenjang,'SD','SMP','SMA'), tingkat, nama"
);
$rombels->execute(['y'=>$sc['year_id']]);
$rombels = $rombels->fetchAll();

$subjects = []; $current = null; $topics = [];
if ($rombelId) {
    $stmt = $pdo->prepare("SELECT * FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
    $stmt->execute(['id'=>$rombelId,'y'=>$sc['year_id']]); $current = $stmt->fetch();
    if ($current) {
            if ($me['role'] === 'guru') {
                $s = $pdo->prepare(
                    "SELECT DISTINCT s.id, s.kode, s.nama
                     FROM subjects s
                     JOIN rombel_subject_teachers rst ON rst.subject_id = s.id
                     JOIN teachers t ON t.id = rst.teacher_id
                     WHERE rst.rombel_id = :r
                       AND t.user_id = :u
                       AND (rst.semester IS NULL OR rst.semester = :sem)
                       AND s.deleted_at IS NULL
                       AND s.academic_year_id = :y
                     ORDER BY s.kode"
                );
                $s->execute(['r'=>$rombelId,'u'=>$me['id'],'sem'=>$sc['semester'],'y'=>$sc['year_id']]);
                $subjects = $s->fetchAll();
            } else {
                $s = $pdo->prepare(
                    "SELECT DISTINCT s.id, s.kode, s.nama FROM subjects s
                     JOIN subject_jenjang_map jm ON jm.subject_id=s.id
                     WHERE jm.jenjang=:j AND s.deleted_at IS NULL AND s.academic_year_id = :y
                     ORDER BY s.kode"
                );
                $s->execute(['j'=>$current['jenjang'], 'y'=>$sc['year_id']]);
                $subjects = $s->fetchAll();
            }
        if ($subjectId) {
            $t = $pdo->prepare(
                "SELECT * FROM subject_topics
                 WHERE rombel_id=:r AND subject_id=:s AND semester=:sem AND deleted_at IS NULL
                 ORDER BY ranah, kategori, kode, id"
            );
            $t->execute(['r'=>$rombelId,'s'=>$subjectId,'sem'=>$sc['semester']]);
            $topics = $t->fetchAll();
        }
    }
}

$page_title = 'Subjek Penilaian';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Pilih Rombel &amp; Mapel</h3>
    <span class="text-sm text-muted">TA <?= esc($sc['year']) ?> · Semester <?= esc(ucfirst($sc['semester'])) ?></span>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="align-items:end">
      <div class="field" style="flex:1; min-width:240px"><label class="label">Rombel</label>
        <select class="select" name="rombel_id" onchange="this.form.submit()">
          <option value="">— Pilih rombel —</option>
          <?php foreach ($rombels as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $rombelId===(int)$r['id']?'selected':'' ?>><?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($current): ?>
      <div class="field" style="flex:1; min-width:240px"><label class="label">Mapel</label>
        <select class="select" name="subject_id" onchange="this.form.submit()">
          <option value="">— Pilih mapel —</option>
          <?php foreach ($subjects as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $subjectId===(int)$s['id']?'selected':'' ?>><?= esc($s['kode'].' — '.$s['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($current && $subjectId): ?>
<div class="row">
  <div class="card" style="flex: 1; min-width: 320px">
    <div class="card-header"><h3 class="card-title"><?= $editTopic ? 'Edit Subjek Penilaian' : 'Tambah Subjek Penilaian' ?></h3></div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($editTopic): ?>
          <input type="hidden" name="id" value="<?= (int)$editTopic['id'] ?>">
        <?php endif; ?>
        <input type="hidden" name="rombel_id" value="<?= (int)$rombelId ?>">
        <input type="hidden" name="subject_id" value="<?= (int)$subjectId ?>">
        <input type="hidden" name="semester" value="<?= esc($sc['semester']) ?>">
        <div class="row">
          <div class="field" style="flex:0 0 110px"><label class="label">Kode</label><input class="input" name="kode" placeholder="T1, U2" value="<?= $editTopic ? esc($editTopic['kode'] ?? '') : '' ?>"></div>
          <div class="field"><label class="label">Judul *</label><input class="input" name="judul" required placeholder="Bab 1 — Bilangan Bulat" value="<?= $editTopic ? esc($editTopic['judul']) : '' ?>"></div>
        </div>
        <!-- Ranah selector dihapus: setiap subjek penilaian otomatis mencakup Sikap, Pengetahuan, dan Keterampilan (SPK). -->
        <div class="row">
          <div class="field"><label class="label">Kategori *</label>
            <select class="select" name="kategori" required>
              <?php foreach (['tugas','ulangan','proyek','praktek','portofolio','produk','lainnya'] as $k): ?>
                <option value="<?= $k ?>" <?= $editTopic && $editTopic['kategori'] === $k ? 'selected' : '' ?>><?= ucfirst($k) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field" style="flex:0 0 100px"><label class="label">Bobot</label><input class="input" type="number" step="0.01" name="bobot" value="<?= $editTopic ? esc((string)(float)$editTopic['bobot']) : '1.00' ?>" min="0" required></div>
        </div>
        <div class="field"><label class="label">Deskripsi</label><textarea class="textarea" name="deskripsi"><?= $editTopic ? esc($editTopic['deskripsi'] ?? '') : '' ?></textarea></div>
        <div class="between mt-2">
          <div>
            <?php if ($editTopic): ?>
              <a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/subject_topics.php?rombel_id='.$rombelId.'&subject_id='.$subjectId)) ?>">Batal Edit</a>
            <?php endif; ?>
          </div>
          <button class="btn btn-primary" type="submit"><?= $editTopic ? 'Simpan Perubahan' : 'Tambah' ?></button>
        </div>
      </form>
    </div>
  </div>

  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header"><h3 class="card-title">Daftar Subjek (<?= count($topics) ?>) — Semester <?= esc(ucfirst($sc['semester'])) ?><?= $editTopic ? ' <span class="badge badge-warning" style="margin-left:.5rem">Edit Mode: ' . esc($editTopic['judul']) . '</span>' : '' ?></h3></div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr><th>Kode</th><th>Judul</th><th>Kategori</th><th>Bobot</th><th></th></tr></thead>
        <tbody>
        <?php if (!$topics): ?><tr><td colspan="5"><div class="empty">Belum ada topik untuk semester ini.</div></td></tr><?php endif; ?>
        <?php foreach ($topics as $t): ?>
          <tr>
            <td><strong><?= esc($t['kode'] ?? '—') ?></strong></td>
            <td><?= esc($t['judul']) ?><?php if ($t['deskripsi']): ?><div class="text-sm text-muted"><?= esc(mb_strimwidth($t['deskripsi'],0,80,'…')) ?></div><?php endif; ?></td>
            <td><span class="badge"><?= esc($t['kategori']) ?></span></td>
            <td><?= esc(number_format((float)$t['bobot'],2)) ?></td>
            <td style="text-align:right">
              <a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/subject_topics.php?rombel_id='.$rombelId.'&subject_id='.$subjectId.'&edit_id='.(int)$t['id'])) ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Hapus topik <?= esc($t['judul']) ?>?">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <input type="hidden" name="rombel_id" value="<?= (int)$rombelId ?>"><input type="hidden" name="subject_id" value="<?= (int)$subjectId ?>">
                <button class="btn btn-danger btn-sm">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
