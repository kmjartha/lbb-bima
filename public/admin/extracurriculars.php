<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/guard.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/scope.php';
require_administrator();

$page_title = 'Halaman Tidak Tersedia';
require __DIR__ . '/../../includes/header.php';
?>

<div class="card">
  <div class="card-body">
    <div class="empty">Fitur ini telah dihapus dari aplikasi.</div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
