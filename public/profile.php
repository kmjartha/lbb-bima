<?php
/**
 * Staff profile page with teacher details, wali-class info, TTD upload, and password reset.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/report_helpers.php';
require_once __DIR__ . '/../includes/scope.php';

$user = require_view('profile');
$pdo = db();

$meStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
$meStmt->execute(['id' => $user['id']]);
$me = $meStmt->fetch();
if (!$me) {
    http_response_code(404);
    die('Akun tidak ditemukan.');
}

$rombel = null;
if (!empty($me['is_wali'])) {
    $rombelStmt = $pdo->prepare(
        "SELECT * FROM rombel WHERE wali_id = :id AND academic_year_id = :y AND deleted_at IS NULL LIMIT 1"
    );
    $rombelStmt->execute(['id' => $me['id'], 'y' => active_scope()['year_id']]);
    $rombel = $rombelStmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();

        $action = $_POST['action'] ?? '';
        if ($action === 'update_password') {
            $current = trim((string)($_POST['current_password'] ?? ''));
            $new = trim((string)($_POST['new_password'] ?? ''));
            $confirm = trim((string)($_POST['confirm_password'] ?? ''));

            if ($current === '' || $new === '' || $confirm === '') {
                throw new RuntimeException('Lengkapi semua kolom password.');
            }
            if (!password_verify($current, $me['password_hash'])) {
                throw new RuntimeException('Password saat ini salah.');
            }
            if ($new !== $confirm) {
                throw new RuntimeException('Konfirmasi password tidak cocok.');
            }
            if (strlen($new) < 6) {
                throw new RuntimeException('Password baru minimal 6 karakter.');
            }

            $pdo->prepare(
                "UPDATE users SET password_hash = :hash, must_change_pw = 0, updated_at = NOW() WHERE id = :id"
            )->execute([
                'hash' => password_hash($new, PASSWORD_DEFAULT),
                'id' => $me['id'],
            ]);
            audit('user_password_change', 'user:' . $me['id']);
            flash('success', 'Password berhasil diperbarui.');
        } elseif ($action === 'upload_ttd') {
            if (empty($_FILES['ttd_image']) || ($_FILES['ttd_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('Pilih file tanda tangan terlebih dahulu.');
            }
            $relPath = save_image_upload($_FILES['ttd_image'], 'signatures', 'ttd_' . $me['id']);
            $pdo->prepare("UPDATE users SET ttd_path = :path, updated_at = NOW() WHERE id = :id")
                ->execute(['path' => $relPath, 'id' => $me['id']]);
            audit('user_ttd_upload', 'user:' . $me['id']);
            flash('success', 'Tanda tangan berhasil diunggah.');
        } else {
            throw new RuntimeException('Aksi tidak dikenal.');
        }
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
    }

    redirect('profile.php');
}

$page_title = 'Profil Saya';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Profil Saya</h3>
    <span class="text-sm text-muted">Informasi akun staf dan tanda tangan wali kelas</span>
  </div>
  <div class="card-body">
    <div class="field-row" style="gap:16px; flex-wrap:wrap;">
      <div class="field" style="flex:1; min-width:220px;">
        <label class="label">NIY</label>
        <div class="readonly-value"><?= esc($me['niy']) ?></div>
      </div>
      <div class="field" style="flex:2; min-width:220px;">
        <label class="label">Nama</label>
        <div class="readonly-value"><?= esc($me['nama']) ?></div>
      </div>
      <div class="field" style="flex:1; min-width:220px;">
        <label class="label">Role</label>
        <div class="readonly-value"><?= esc(ucfirst($me['role'])) ?></div>
      </div>
      <div class="field" style="flex:1; min-width:220px;">
        <label class="label">Jenjang</label>
        <div class="readonly-value"><?= esc($me['jenjang'] ?? '—') ?></div>
      </div>
      <div class="field" style="flex:1; min-width:220px;">
        <label class="label">Status Wali Kelas</label>
        <div class="readonly-value"><?= $me['is_wali'] ? 'Ya' : 'Tidak' ?></div>
      </div>
      <?php if ($rombel): ?>
      <div class="field" style="flex:1; min-width:220px;">
        <label class="label">Rombel Wali</label>
        <div class="readonly-value"><?= esc($rombel['jenjang'] . ' ' . $rombel['nama']) ?></div>
      </div>
      <?php endif; ?>
      <div class="field" style="flex:1; min-width:220px;">
        <label class="label">Email</label>
        <div class="readonly-value"><?= esc($me['email'] ?? '—') ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Unggah TTD Wali Kelas</h3>
    <span class="text-sm text-muted">TTD akan tampil di rapor siswa sesuai wali kelas.</span>
  </div>
  <div class="card-body">
    <?php if (!empty($me['ttd_path'])): ?>
      <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
        <div style="max-width:240px; border:1px solid #e5e7eb; padding:12px; border-radius:8px; background:#fff;">
          <img src="<?= esc(uploads_url($me['ttd_path'])) ?>" alt="TTD" style="max-width:100%; display:block;">
        </div>
        <div>
          <div class="text-sm text-muted">Tanda tangan saat ini</div>
          <div class="text-sm"><?= esc(basename($me['ttd_path'])) ?></div>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-info">Belum ada file tanda tangan tersimpan.</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="upload_ttd">
      <div class="field">
        <label class="label">File TTD</label>
        <input type="file" name="ttd_image" accept="image/png,image/jpeg,image/gif,image/webp" class="input">
      </div>
      <div class="field" style="margin-top:12px;">
        <button type="submit" class="btn btn-primary">Unggah TTD</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Reset Password</h3>
    <span class="text-sm text-muted">Ubah password akun Anda secara langsung.</span>
  </div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_password">
      <div class="field">
        <label class="label">Password Saat Ini</label>
        <input type="password" name="current_password" class="input" required>
      </div>
      <div class="field">
        <label class="label">Password Baru</label>
        <input type="password" name="new_password" class="input" required>
      </div>
      <div class="field">
        <label class="label">Konfirmasi Password Baru</label>
        <input type="password" name="confirm_password" class="input" required>
      </div>
      <div class="field" style="margin-top:12px;">
        <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
