<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../includes/scope.php';
require_staff();
csrf_check();
set_scope(
    isset($_POST['year_id']) ? (int)$_POST['year_id'] : null,
    (string)($_POST['semester'] ?? ''),
    (string)($_POST['period'] ?? '')
);
$back = $_SERVER['HTTP_REFERER'] ?? url('dashboard.php');
header('Location: ' . $back); exit;
