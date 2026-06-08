<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';

/** Require a logged-in staff user with one of the given roles. */
function require_role(array $roles): array
{
    $u = current_user();
    if (!$u) redirect('login.php');
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die('403 — Anda tidak memiliki akses ke halaman ini.');
    }
    return $u;
}

/** Administrator only. */
function require_administrator(): array { return require_role(['administrator']); }

/** Administrator OR Admin (master-data CRUD pages). */
function require_admin_any(): array { return require_role(['administrator','admin']); }

/** Logged-in parent (for /parent/* pages). */
function require_parent(): array
{
    $p = current_parent();
    if (!$p) redirect('parent/login.php');
    return $p;
}

/** Any authenticated staff (for shell pages like dashboard). */
function require_staff(): array
{
    $u = current_user();
    if (!$u) redirect('login.php');
    return $u;
}
