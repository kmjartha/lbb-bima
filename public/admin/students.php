<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
require_admin_any();

$sc = active_scope();
$yearId = (int)$sc['year_id'];

$pdo = db();
$err = null;
$editId = int_or_null($_GET['edit'] ?? null);

// jenjang filter
$jf = in_array(($_GET['jenjang'] ?? ''), ['TK','SD','SMP','SMA'], true) ? $_GET['jenjang'] : '';
$q  = trim((string)($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $op = (string)($_POST['op'] ?? '');

        if ($op === 'save') {
            $id       = int_or_null($_POST['id'] ?? null);
            $nisn     = req_str($_POST, 'nisn', 10);
            $nis      = req_str($_POST, 'nis', 7);
            $nama     = req_str($_POST, 'nama', 120);
            $jenjang  = req_str($_POST, 'jenjang', 3);
            $tingkat  = (int)$_POST['tingkat'];
            $jk       = req_str($_POST, 'jk', 1);
            $tempat   = opt_str($_POST, 'tempat_lahir', 80);
            $tgl      = req_str($_POST, 'tgl_lahir', 10);
            $alamat   = opt_str($_POST, 'alamat', 1000);
            $a_ayah   = opt_str($_POST, 'nama_ayah', 120);
            $a_ibu    = opt_str($_POST, 'nama_ibu', 120);
            $p_ayah   = opt_str($_POST, 'pekerjaan_ayah', 80);
            $p_ibu    = opt_str($_POST, 'pekerjaan_ibu', 80);
            $telp     = opt_str($_POST, 'telp_ortu', 30);

            if (!valid_nisn($nisn)) throw new RuntimeException('NISN harus 10 digit angka.');
            if (!valid_nis($nis))   throw new RuntimeException('NIS harus 7 digit angka.');
            if (!in_array($jenjang, ['TK','SD','SMP','SMA'], true)) throw new RuntimeException('Jenjang invalid.');
            $valid = ($jenjang==='TK' && $tingkat>=0 && $tingkat<=2)||($jenjang==='SD' && $tingkat>=1 && $tingkat<=6) || ($jenjang==='SMP' && $tingkat>=7 && $tingkat<=9) || ($jenjang==='SMA' && $tingkat>=10 && $tingkat<=12);
            if (!$valid) throw new RuntimeException('Tingkat tidak sesuai jenjang.');
            if (!in_array($jk, ['L','P'], true)) throw new RuntimeException('JK invalid.');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) throw new RuntimeException('Format tanggal lahir invalid.');

            $foto_path = null;
            if (!empty($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['foto']['size'] > 2 * 1024 * 1024) throw new RuntimeException('Foto maksimal 2 MB.');
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) throw new RuntimeException('Format foto: jpg/png/webp.');
                $name = 'stu_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $destDir = UPLOADS_PATH . DIRECTORY_SEPARATOR . 'students';
                if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
                $dest = $destDir . DIRECTORY_SEPARATOR . $name;
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) throw new RuntimeException('Upload gagal.');
                $foto_path = 'uploads/students/' . $name;
            }

            $pdo->beginTransaction();
            $params = compact('nisn','nis','nama','jenjang','tingkat','jk','tempat','tgl','alamat','a_ayah','a_ibu','p_ayah','p_ibu','telp');
            $params['tempat_lahir']=$tempat; $params['tgl_lahir']=$tgl;
            $params['nama_ayah']=$a_ayah; $params['nama_ibu']=$a_ibu;
            $params['pekerjaan_ayah']=$p_ayah; $params['pekerjaan_ibu']=$p_ibu;
            $params['telp_ortu']=$telp;
            unset($params['tempat'],$params['tgl'],$params['a_ayah'],$params['a_ibu'],$params['p_ayah'],$params['p_ibu'],$params['telp']);

            if ($id) {
                $sql = "UPDATE students SET nisn=:nisn, nis=:nis, nama=:nama, jenjang=:jenjang, tingkat=:tingkat,
                        jk=:jk, tempat_lahir=:tempat_lahir, tgl_lahir=:tgl_lahir, alamat=:alamat,
                        nama_ayah=:nama_ayah, nama_ibu=:nama_ibu, pekerjaan_ayah=:pekerjaan_ayah,
                        pekerjaan_ibu=:pekerjaan_ibu, telp_ortu=:telp_ortu";
                if ($foto_path) $sql .= ", foto_path=:foto_path";
                $sql .= " WHERE id = :id AND academic_year_id = :y";
                $params['id'] = $id;
                $params['y'] = $yearId;
                if ($foto_path) $params['foto_path'] = $foto_path;
                $pdo->prepare($sql)->execute($params);
            } else {
                $params['academic_year_id'] = $yearId;
                if ($foto_path) $params['foto_path'] = $foto_path;
                $cols = implode(',', array_keys($params));
                $ph   = ':' . implode(',:', array_keys($params));
                $pdo->prepare("INSERT INTO students ($cols) VALUES ($ph)")->execute($params);
                $id = (int)$pdo->lastInsertId();
                // Auto-create parent login (default password = ddmmyyyy)
                $dob = new DateTime($tgl);
                $pw  = $dob->format('dmY');
                $pdo->prepare("INSERT INTO parents_auth (student_id, password_hash, must_change_pw)
                               VALUES (:s,:h,1)")
                    ->execute(['s'=>$id,'h'=>password_hash($pw, PASSWORD_DEFAULT)]);
            }
            $pdo->commit();
            audit('save', 'student:' . $id);
            flash('success', 'Data siswa disimpan.');
            redirect('admin/students.php' . ($jf ? '?jenjang=' . $jf : ''));
        }

        if ($op === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE students SET deleted_at = NOW() WHERE id = :id AND academic_year_id = :y")->execute(['id'=>$id, 'y'=>$yearId]);
            audit('delete', 'student:' . $id);
            flash('success', 'Siswa dinonaktifkan.');
            redirect('admin/students.php');
        }

        if ($op === 'batch_promote') {
          $ids = array_map('intval', $_POST['ids'] ?? []);
          $newJenjang = $_POST['new_jenjang'] ?? '';
          $newTingkat = int_or_null($_POST['new_tingkat'] ?? null);
          if (!$ids) throw new RuntimeException('Pilih minimal 1 siswa.');
          $sets = [];
          $params = [];
          if (in_array($newJenjang, ['TK','SD','SMP','SMA'], true)) { $sets[] = "jenjang = :j"; $params['j'] = $newJenjang; }
          if ($newTingkat !== null) { $sets[] = "tingkat = :t"; $params['t'] = $newTingkat; }
          if (!$sets) throw new RuntimeException('Pilih jenjang atau tingkat baru.');
          // Build named placeholders for the IN() list to avoid mixing positional and named params
          $idPlaceholders = [];
          foreach ($ids as $k => $idv) {
            $ph = ':id_' . $k;
            $idPlaceholders[] = $ph;
            $params['id_' . $k] = $idv;
          }
          $in = implode(',', $idPlaceholders);
          $params['y'] = $yearId;
          $sql = "UPDATE students SET " . implode(',', $sets) . " WHERE academic_year_id = :y AND id IN ($in)";
          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);
          audit('batch_promote', 'students:' . count($ids), ['new_jenjang'=>$newJenjang,'new_tingkat'=>$newTingkat]);
          flash('success', count($ids) . ' siswa dipromosikan.');
          redirect('admin/students.php');
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}

// ----- DATA -----
$where = ['s.deleted_at IS NULL', 's.academic_year_id = :y'];
$params = ['y' => $yearId];
if ($jf) { $where[] = 's.jenjang = :j'; $params['j'] = $jf; }
if ($q !== '') {
    $where[] = '(s.nama LIKE :q_nama OR s.nisn LIKE :q_nisn OR s.nis LIKE :q_nis)';
    $params['q_nama'] = '%' . $q . '%';
    $params['q_nisn'] = '%' . $q . '%';
    $params['q_nis'] = '%' . $q . '%';
}
$wsql = 'WHERE ' . implode(' AND ', $where);

$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20;
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT s.id) FROM students s $wsql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT DISTINCT s.* FROM students s $wsql ORDER BY s.jenjang, s.tingkat, s.nama LIMIT $per OFFSET " . (($page-1)*$per));
$stmt->execute($params);
$rows = $stmt->fetchAll();

$edit = null;
if ($editId) {
    $s = $pdo->prepare("SELECT * FROM students WHERE id = :id AND deleted_at IS NULL AND academic_year_id = :y");
    $s->execute(['id'=>$editId,'y'=>$yearId]);
    $edit = $s->fetch();
}

$page_title = 'Siswa';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>
<div class="alert alert-info">Menampilkan siswa yang terdaftar pada Tahun Ajaran aktif: <strong><?= esc($sc['year']) ?></strong>.</div>

<div class="row">
  <!-- Form -->
  <div class="card" style="flex: 1; min-width: 360px">
    <div class="card-header"><h3 class="card-title"><?= $edit ? 'Edit' : 'Tambah' ?> Siswa</h3>
      <?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="<?= esc(url('admin/students.php')) ?>">Batal</a><?php endif; ?>
    </div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="op" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="row">
          <div class="field"><label class="label">NISN * (10 digit)</label><input class="input" name="nisn" maxlength="10" pattern="\d{10}" required value="<?= esc($edit['nisn'] ?? '') ?>"></div>
          <div class="field"><label class="label">NIS * (7 digit)</label><input class="input" name="nis" maxlength="7" pattern="\d{7}" required value="<?= esc($edit['nis'] ?? '') ?>"></div>
        </div>
        <div class="field"><label class="label">Nama *</label><input class="input" name="nama" required value="<?= esc($edit['nama'] ?? '') ?>"></div>
        <div class="row">
          <div class="field"><label class="label">Jenjang *</label>
            <select class="select" name="jenjang" required>
              <?php foreach (['TK','SD','SMP','SMA'] as $j): ?><option value="<?= $j ?>" <?= ($edit['jenjang'] ?? '')===$j?'selected':'' ?>><?= $j ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label class="label">Tingkat *</label><input class="input" type="number" name="tingkat" min="1" max="12" required value="<?= esc($edit['tingkat'] ?? '') ?>"></div>
          <div class="field"><label class="label">JK *</label>
            <select class="select" name="jk" required>
              <option value="L" <?= ($edit['jk'] ?? '')==='L'?'selected':'' ?>>Laki-laki</option>
              <option value="P" <?= ($edit['jk'] ?? '')==='P'?'selected':'' ?>>Perempuan</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="field"><label class="label">Tempat Lahir</label><input class="input" name="tempat_lahir" value="<?= esc($edit['tempat_lahir'] ?? '') ?>"></div>
          <div class="field"><label class="label">Tanggal Lahir *</label><input class="input" type="date" name="tgl_lahir" required value="<?= esc($edit['tgl_lahir'] ?? '') ?>"></div>
        </div>
        <div class="field"><label class="label">Alamat</label><textarea class="textarea" name="alamat"><?= esc($edit['alamat'] ?? '') ?></textarea></div>
        <div class="row">
          <div class="field"><label class="label">Nama Ayah</label><input class="input" name="nama_ayah" value="<?= esc($edit['nama_ayah'] ?? '') ?>"></div>
          <div class="field"><label class="label">Nama Ibu</label><input class="input" name="nama_ibu" value="<?= esc($edit['nama_ibu'] ?? '') ?>"></div>
        </div>
        <div class="row">
          <div class="field"><label class="label">Pekerjaan Ayah</label><input class="input" name="pekerjaan_ayah" value="<?= esc($edit['pekerjaan_ayah'] ?? '') ?>"></div>
          <div class="field"><label class="label">Pekerjaan Ibu</label><input class="input" name="pekerjaan_ibu" value="<?= esc($edit['pekerjaan_ibu'] ?? '') ?>"></div>
          <div class="field"><label class="label">Telp Ortu</label><input class="input" name="telp_ortu" value="<?= esc($edit['telp_ortu'] ?? '') ?>"></div>
        </div>
        <?php if ($edit && !empty($edit['foto_path']) && file_exists(__DIR__ . '/../' . ltrim($edit['foto_path'], '/'))): ?>
          <div class="field">
            <label class="label">Foto Saat Ini</label>
            <div><img src="<?= esc(url(ltrim($edit['foto_path'], '/'))) ?>" alt="Foto siswa" style="max-height:140px; border:1px solid var(--border); border-radius:8px"></div>
          </div>
        <?php endif; ?>
        <div class="field"><label class="label">Foto (jpg/png/webp ≤ 2 MB)</label><input class="input" type="file" name="foto" accept="image/*"></div>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </form>
    </div>
  </div>

  <!-- List -->
  <div class="card" style="flex: 2; min-width: 380px">
    <div class="card-header">
      <h3 class="card-title">Daftar Siswa (<?= $total ?>)</h3>
      <form method="get" class="row" style="gap: var(--sp-2); flex: 0 0 auto; margin: 0">
        <select class="select" name="jenjang" onchange="this.form.submit()">
          <option value="">Semua Jenjang</option>
          <?php foreach (['TK','SD','SMP','SMA'] as $j): ?><option value="<?= $j ?>" <?= $jf===$j?'selected':'' ?>><?= $j ?></option><?php endforeach; ?>
        </select>
        <input class="input" name="q" placeholder="Cari NISN/NIS/Nama" value="<?= esc($q) ?>">
        <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
      </form>
    </div>
    <div class="card-body">
      <form method="post" id="batchForm">
        <?= csrf_field() ?><input type="hidden" name="op" value="batch_promote">
        <div class="row" style="gap: var(--sp-2); align-items:end">
          <div class="field" style="flex: 0 0 160px"><label class="label">Promote Jenjang</label>
            <select class="select" name="new_jenjang"><option value="">— Tetap —</option><?php foreach (['TK','SD','SMP','SMA'] as $j): ?><option><?= $j ?></option><?php endforeach; ?></select>
          </div>
          <div class="field" style="flex: 0 0 140px"><label class="label">Promote Tingkat</label>
            <input class="input" type="number" name="new_tingkat" min="1" max="12" placeholder="—">
          </div>
          <div class="field" style="flex: 0 0 auto"><button class="btn btn-primary btn-sm" type="submit" data-confirm="Promote semua siswa terpilih?">Batch Promote</button></div>
        </div>
      </form>
    </div>
    <div class="table-wrap">
      <table class="t">
        <thead><tr>
          <th><input type="checkbox" onclick="document.querySelectorAll('.row-chk').forEach(c=>{c.checked=this.checked; c.dispatchEvent(new Event('change'))})"></th>
          <th>NISN</th><th>NIS</th><th>Nama</th><th>Jenjang/Tingkat</th><th>JK</th><th></th>
        </tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="7"><div class="empty">Belum ada data.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><input type="checkbox" class="row-chk" form="batchForm" name="ids[]" value="<?= (int)$r['id'] ?>"></td>
            <td><?= esc($r['nisn']) ?></td>
            <td><?= esc($r['nis']) ?></td>
            <td><strong><?= esc($r['nama']) ?></strong></td>
            <td><?= esc($r['jenjang']) ?> · <?= (int)$r['tingkat'] ?></td>
            <td><?= esc($r['jk']) ?></td>
            <td style="text-align:right; white-space:nowrap">
              <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$r['id'] ?><?= $jf ? '&jenjang=' . $jf : '' ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Nonaktifkan siswa <?= esc($r['nama']) ?>?">
                <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Nonaktif</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body"><?= paginator($total, $per, $page, qs([])) ?></div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
