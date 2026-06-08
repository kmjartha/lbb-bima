<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_admin_any();

$pdo = db();
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $data = [
            'nama'          => req_str($_POST, 'nama', 150),
            'npsn'          => opt_str($_POST, 'npsn', 20),
            'alamat'        => opt_str($_POST, 'alamat', 1000),
            'telp'       => opt_str($_POST, 'telp', 30),
            'email'         => opt_str($_POST, 'email', 120),
            'website'       => opt_str($_POST, 'website', 120),
        ];
        $stmt = $pdo->prepare(
            "UPDATE school_profile SET nama=:nama, npsn=:npsn, alamat=:alamat, telp=:telp,
             email=:email, website=:website
             WHERE id = 1"
        );
        $stmt->execute($data);
        if ($stmt->rowCount() === 0 && (int)$pdo->query("SELECT COUNT(*) FROM school_profile")->fetchColumn() === 0) {
            $data['id'] = 1;
            $pdo->prepare("INSERT INTO school_profile (id, nama, npsn, alamat, telp, email, website)
                VALUES (:id,:nama,:npsn,:alamat,:telp,:email,:website)")->execute($data);
        }
        audit('update', 'school_profile');
        flash('success', 'Profil sekolah disimpan.');
        redirect('admin/school_profile.php');
    } catch (Throwable $e) { $err = $e->getMessage(); }
}

$row = $pdo->query("SELECT * FROM school_profile WHERE id = 1")->fetch() ?: [];
$page_title = 'Profil Sekolah';
require __DIR__ . '/../../includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-error"><?= esc($err) ?></div><?php endif; ?>

<div class="card" style="max-width: 760px">
  <div class="card-header"><h3 class="card-title">Profil Sekolah</h3></div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="row">
        <div class="field"><label class="label">Nama Sekolah *</label><input class="input" name="nama" required value="<?= esc($row['nama'] ?? '') ?>"></div>
        <div class="field"><label class="label">NPSN</label><input class="input" name="npsn" value="<?= esc($row['npsn'] ?? '') ?>"></div>
      </div>
      <div class="field"><label class="label">Alamat</label><textarea class="textarea" name="alamat"><?= esc($row['alamat'] ?? '') ?></textarea></div>
      <div class="row">
        <div class="field"><label class="label">Telepon</label><input class="input" name="telp" value="<?= esc($row['telp'] ?? '') ?>"></div>
        <div class="field"><label class="label">Email</label><input class="input" type="email" name="email" value="<?= esc($row['email'] ?? '') ?>"></div>
        <div class="field"><label class="label">Website</label><input class="input" name="website" value="<?= esc($row['website'] ?? '') ?>"></div>
      </div>
      <button class="btn btn-primary" type="submit">Simpan</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
