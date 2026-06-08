<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); parent_logout(); }
redirect('parent/login.php');
