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
                // Check if it's a regular subject or shadow subject from elective
                $subjChk = $pdo->prepare("SELECT elective_class_id FROM subjects WHERE id=:s AND deleted_at IS NULL");
                $subjChk->execute(['s'=>$sid]);
                $electiveClassId = $subjChk->fetchColumn();
                
                if ($electiveClassId === null) {
                    // Regular subject - check rombel_subject_teachers
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
                } else {
                    // Shadow subject from elective - check if rombel is part of this elective
                    $stmt = $pdo->prepare(
                        "SELECT 1 FROM elective_classes ec
                         JOIN elective_rombels er ON er.elective_id = ec.elective_id
                         WHERE ec.id = :ec AND er.rombel_id = :r"
                    );
                    $stmt->execute(['ec'=>$electiveClassId, 'r'=>$rid]);
                    if (!$stmt->fetchColumn()) {
                        throw new RuntimeException('Anda tidak memiliki akses untuk membuat/mengedit subjek penilaian untuk mapel pilihan ini.');
                    }
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
                // Check if it's a regular subject or shadow subject from elective
                $subjChk = $pdo->prepare("SELECT elective_class_id FROM subjects WHERE id=:s AND deleted_at IS NULL");
                $subjChk->execute(['s'=>$sid]);
                $electiveClassId = $subjChk->fetchColumn();
                
                if ($electiveClassId === null) {
                    // Regular subject - check rombel_subject_teachers
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
                } else {
                    // Shadow subject from elective
                    $stmt = $pdo->prepare(
                        "SELECT 1 FROM elective_classes ec
                         JOIN elective_rombels er ON er.elective_id = ec.elective_id
                         WHERE ec.id = :ec AND er.rombel_id = :r"
                    );
                    $stmt->execute(['ec'=>$electiveClassId, 'r'=>$rid]);
                    if (!$stmt->fetchColumn()) {
                        throw new RuntimeException('Anda tidak memiliki akses untuk menghapus topik di mapel pilihan ini.');
                    }
                }
            }
            $pdo->prepare("UPDATE subject_topics SET deleted_at=NOW() WHERE id=:id")->execute(['id'=>$id]);
            audit('delete', 'topic:' . $id);
            flash('success', 'Topik dihapus.');
            redirect('admin/subject_topics.php?rombel_id=' . $rid . '&subject_id=' . $sid);
        }

        if ($op === 'copy_all') {
            $sourceRid       = (int)($_POST['rombel_id'] ?? 0);
            $sourceSid       = (int)($_POST['subject_id'] ?? 0);
            $targetRid       = (int)($_POST['target_rombel_id'] ?? 0);
            $targetSid       = (int)($_POST['target_subject_id'] ?? 0);
            
            if (!$sourceRid || !$sourceSid || !$targetRid || !$targetSid) {
                throw new RuntimeException('Data yang diperlukan tidak lengkap.');
            }
            
            // Permission check
            if ($me['role'] === 'guru') {
                // Check source access
                $stmt = $pdo->prepare(
                    "SELECT 1 FROM rombel_subject_teachers rst
                     JOIN teachers t ON t.id = rst.teacher_id
                     WHERE rst.rombel_id = :r
                       AND rst.subject_id = :s
                       AND t.user_id = :u
                       AND (rst.semester IS NULL OR rst.semester = :sem)"
                );
                $stmt->execute(['r'=>$sourceRid,'s'=>$sourceSid,'u'=>$me['id'],'sem'=>$sc['semester']]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Anda tidak memiliki akses untuk copy topik dari mapel ini.');
                }
                
                // Check target access
                $stmt->execute(['r'=>$targetRid,'s'=>$targetSid,'u'=>$me['id'],'sem'=>$sc['semester']]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Anda tidak memiliki akses untuk copy topik ke mapel target.');
                }
            }
            
            // Fetch all source topics for this rombel & subject & semester
            $stmtSrc = $pdo->prepare(
                "SELECT * FROM subject_topics 
                 WHERE rombel_id=:r AND subject_id=:s AND semester=:sem AND deleted_at IS NULL
                 ORDER BY kode, id"
            );
            $stmtSrc->execute(['r'=>$sourceRid, 's'=>$sourceSid, 'sem'=>$sc['semester']]);
            $sourceTopics = $stmtSrc->fetchAll();
            
            if (!$sourceTopics) {
                throw new RuntimeException('Tidak ada subjek penilaian untuk dicopy.');
            }
            
            // Prepare insert statement
            $insertStmt = $pdo->prepare(
                "INSERT INTO subject_topics 
                 (rombel_id, subject_id, semester, kode, judul, ranah, ranah_list, kategori, bobot, deskripsi, created_by) 
                 VALUES (:r, :s, :sem, :k, :j, :rn, :rl, :kat, :b, :d, :u)"
            );
            
            $copiedCount = 0;
            $skippedCount = 0;
            
            foreach ($sourceTopics as $topic) {
                // Check if topic with same kode already exists in target
                $chkExists = $pdo->prepare(
                    "SELECT id FROM subject_topics 
                     WHERE rombel_id=:r AND subject_id=:s AND kode=:k AND semester=:sem AND deleted_at IS NULL"
                );
                $chkExists->execute(['r'=>$targetRid,'s'=>$targetSid,'k'=>$topic['kode'],'sem'=>$topic['semester']]);
                
                if ($chkExists->fetchColumn()) {
                    $skippedCount++;
                    continue; // Skip this topic if already exists
                }
                
                // Insert new topic as copy
                $insertStmt->execute([
                    'r'   => $targetRid,
                    's'   => $targetSid,
                    'sem' => $topic['semester'],
                    'k'   => $topic['kode'],
                    'j'   => $topic['judul'],
                    'rn'  => $topic['ranah'],
                    'rl'  => $topic['ranah_list'],
                    'kat' => $topic['kategori'],
                    'b'   => $topic['bobot'],
                    'd'   => $topic['deskripsi'],
                    'u'   => $me['id']
                ]);
                
                $copiedCount++;
            }
            
            if ($copiedCount === 0) {
                throw new RuntimeException('Tidak ada subjek yang berhasil dicopy (semua sudah ada di target).');
            }
            
            audit('copy_all', 'topics: ' . $copiedCount . ' copied, ' . $skippedCount . ' skipped');
            
            $msg = 'Berhasil dicopy: ' . $copiedCount . ' subjek penilaian';
            if ($skippedCount > 0) {
                $msg .= ' (' . $skippedCount . ' subjek dilewati karena sudah ada)';
            }
            flash('success', $msg);
            redirect('admin/subject_topics.php?rombel_id=' . $sourceRid . '&subject_id=' . $sourceSid);
        }
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

if ($me['role'] === 'guru') {
    // Hanya ambil rombel di mana guru ini ditugaskan mengajar minimal 1 mapel
    $rombels = $pdo->prepare(
        "SELECT DISTINCT r.id, r.jenjang, r.tingkat, r.nama 
         FROM rombel r
         JOIN rombel_subject_teachers rst ON rst.rombel_id = r.id
         JOIN teachers t ON t.id = rst.teacher_id
         WHERE r.academic_year_id = :y 
           AND r.deleted_at IS NULL 
           AND t.user_id = :u
           AND (rst.semester IS NULL OR rst.semester = :sem)
         ORDER BY FIELD(r.jenjang,'SD','SMP','SMA'), r.tingkat, r.nama"
    );
    $rombels->execute([
        'y'   => $sc['year_id'],
        'u'   => $me['id'],
        'sem' => $sc['semester']
    ]);
} else {
    // Admin / peran lain tetap bisa melihat semua rombel
    $rombels = $pdo->prepare(
        "SELECT id, jenjang, tingkat, nama FROM rombel
         WHERE academic_year_id=:y AND deleted_at IS NULL
         ORDER BY FIELD(jenjang,'SD','SMP','SMA'), tingkat, nama"
    );
    $rombels->execute(['y'=>$sc['year_id']]);
}
$rombels = $rombels->fetchAll();

$subjects = []; $current = null; $topics = [];
if ($rombelId) {
    $stmt = $pdo->prepare("SELECT * FROM rombel WHERE id=:id AND academic_year_id=:y AND deleted_at IS NULL");
    $stmt->execute(['id'=>$rombelId,'y'=>$sc['year_id']]); $current = $stmt->fetch();
    if ($current) {
            if ($me['role'] === 'guru') {
                // Get teacher_id for the logged-in user
                $tStmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id=:u");
                $tStmt->execute(['u'=>$me['id']]);
                $tid = (int)($tStmt->fetchColumn() ?: 0);
                
                $s = $pdo->prepare(
                    "SELECT DISTINCT s.id, s.kode, s.nama, e.kode AS elective_kode
                     FROM subjects s
                     JOIN rombel_subject_teachers rst ON rst.subject_id = s.id
                     JOIN teachers t ON t.id = rst.teacher_id
                     LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
                     LEFT JOIN electives e ON e.id = ec.elective_id
                     WHERE rst.rombel_id = :r
                       AND t.user_id = :u
                       AND (rst.semester IS NULL OR rst.semester = :sem)
                       AND s.deleted_at IS NULL
                       AND s.academic_year_id = :y
                     UNION
                     SELECT DISTINCT s.id, s.kode, s.nama, e.kode AS elective_kode
                     FROM elective_rombels er
                     JOIN elective_classes ec ON ec.elective_id = er.elective_id
                     JOIN subjects s ON s.id = ec.subject_id
                     JOIN electives e ON e.id = ec.elective_id
                     WHERE er.rombel_id = :r
                       AND s.deleted_at IS NULL
                       AND s.academic_year_id = :y
                       AND ec.deleted_at IS NULL
                     ORDER BY kode"
                );
                $s->execute(['r'=>$rombelId,'u'=>$me['id'],'sem'=>$sc['semester'],'y'=>$sc['year_id']]);
                $subjects = $s->fetchAll();
            } else {
                $s = $pdo->prepare(
                    "SELECT DISTINCT s.id, s.kode, s.nama, e.kode AS elective_kode FROM subjects s
                     JOIN subject_jenjang_map jm ON jm.subject_id=s.id
                     LEFT JOIN elective_classes ec ON ec.id = s.elective_class_id
                     LEFT JOIN electives e ON e.id = ec.elective_id
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
            <option value="<?= (int)$s['id'] ?>" <?= $subjectId===(int)$s['id']?'selected':'' ?>><?= esc($s['kode'].' — '.elective_subject_label($s['nama'], $s['elective_kode'] ?? null)) ?></option>
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
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
      <h3 class="card-title" style="margin:0">Daftar Subjek (<?= count($topics) ?>) — Semester <?= esc(ucfirst($sc['semester'])) ?><?= $editTopic ? ' <span class="badge badge-warning" style="margin-left:.5rem">Edit Mode: ' . esc($editTopic['judul']) . '</span>' : '' ?></h3>
      <?php if ($topics): ?>
        <button class="btn btn-secondary btn-sm" type="button" onclick="openCopyAllModal(<?= (int)$rombelId ?>, <?= (int)$subjectId ?>, <?= count($topics) ?>)">Copy Subject Ke ...</button>
      <?php endif; ?>
    </div>
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

<div id="copyAllModal" class="modal" style="display:none;">
  <div class="modal-backdrop" onclick="closeCopyAllModal()"></div>
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title">Copy Semua Subjek Penilaian</h3>
      <button class="modal-close" type="button" onclick="closeCopyAllModal()">×</button>
    </div>
    <div class="modal-body">
      <p id="copyAllInfo" style="color:#666; margin-bottom:1.5rem;"></p>
      <form id="copyAllForm" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="op" value="copy_all">
        <input type="hidden" name="rombel_id" id="copyAllSourceRombel">
        <input type="hidden" name="subject_id" id="copyAllSourceSubject">
        
        <div class="field">
          <label class="label">Target Rombel *</label>
          <select class="select" id="copyAllTargetRombel" name="target_rombel_id" onchange="loadCopyAllTargetSubjects()" required>
            <option value="">— Pilih rombel target —</option>
            <?php foreach ($rombels as $r): ?>
              <option value="<?= (int)$r['id'] ?>"><?= esc($r['jenjang'].' '.$r['tingkat'].' · '.$r['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="field">
          <label class="label">Target Mapel *</label>
          <select class="select" id="copyAllTargetSubject" name="target_subject_id" required>
            <option value="">— Pilih mapel target —</option>
          </select>
        </div>
        
        <div style="margin-top:1.5rem; display:flex; gap:10px; justify-content:flex-end;">
          <button class="btn btn-ghost" type="button" onclick="closeCopyAllModal()">Batal</button>
          <button class="btn btn-primary" type="submit">Copy Semua</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-backdrop { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
.modal-content { position: relative; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 90%; max-width: 500px; max-height: 90vh; overflow: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid #e0e0e0; }
.modal-title { margin: 0; font-size: 1.25rem; font-weight: 600; }
.modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
.modal-close:hover { background: #f5f5f5; border-radius: 4px; }
.modal-body { padding: 1.5rem; }
</style>

<script>
function openCopyAllModal(sourceRombel, sourceSubject, topicCount) {
  document.getElementById('copyAllSourceRombel').value = sourceRombel;
  document.getElementById('copyAllSourceSubject').value = sourceSubject;
  document.getElementById('copyAllInfo').innerHTML = '📌 Akan copy <strong>' + topicCount + ' subjek penilaian</strong> ke rombel & mapel pilihan Anda.';
  document.getElementById('copyAllTargetRombel').value = '';
  document.getElementById('copyAllTargetSubject').innerHTML = '<option value="">— Pilih mapel target —</option>';
  document.getElementById('copyAllModal').style.display = 'flex';
}

function closeCopyAllModal() {
  document.getElementById('copyAllModal').style.display = 'none';
}

function loadCopyAllTargetSubjects() {
  const rombel_id = document.getElementById('copyAllTargetRombel').value;
  if (!rombel_id) {
    document.getElementById('copyAllTargetSubject').innerHTML = '<option value="">— Pilih mapel target —</option>';
    return;
  }
  
  // Fetch subjects for selected rombel via AJAX
  fetch('<?= esc(url('admin/subject_topics_api.php')) ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'get_subjects', rombel_id: parseInt(rombel_id) })
  })
  .then(r => r.json())
  .then(data => {
    const select = document.getElementById('copyAllTargetSubject');
    if (data.success && Array.isArray(data.subjects)) {
      select.innerHTML = '<option value="">— Pilih mapel target —</option>' +
        data.subjects.map(s => `<option value="${s.id}">${s.label}</option>`).join('');
    } else {
      select.innerHTML = '<option value="">Error loading subjects</option>';
    }
  })
  .catch(err => {
    console.error('Error:', err);
    document.getElementById('copyAllTargetSubject').innerHTML = '<option value="">Error</option>';
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const copyAllForm = document.getElementById('copyAllForm');
  if (copyAllForm) {
    copyAllForm.addEventListener('submit', (e) => {
      if (!document.getElementById('copyAllTargetSubject').value) {
        e.preventDefault();
        alert('Pilih target mapel terlebih dahulu');
      }
    });
  }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeCopyAllModal();
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>