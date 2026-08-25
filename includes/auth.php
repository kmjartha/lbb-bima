<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/scope.php';
require_once __DIR__ . '/csrf.php';

/* =====================================================================
 * Staff auth (NIY + password + remember-me selector/validator)
 * ===================================================================== */

function staff_login(string $niy, string $password, bool $remember = false): array
{
    $stmt = db()->prepare("SELECT * FROM users WHERE niy = :n AND deleted_at IS NULL LIMIT 1");
    $stmt->execute(['n' => $niy]);
    $u = $stmt->fetch();
    if (!$u || !$u['is_active']) throw new RuntimeException('Akun tidak ditemukan atau nonaktif.');
    if (!password_verify($password, $u['password_hash'])) throw new RuntimeException('NIY atau password salah.');

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$u['id'], 'niy' => $u['niy'], 'nama' => $u['nama'],
        'role' => $u['role'], 'jenjang' => $u['jenjang'], 'is_wali' => (int)$u['is_wali'],
        'must_change_pw' => (int)$u['must_change_pw'],
    ];
    db()->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id")->execute(['id' => $u['id']]);
    if ($remember) issue_remember_cookie('staff', (int)$u['id']);
    audit('login', 'user:' . $u['id']);
    return $_SESSION['user'];
}

function staff_logout(): void
{
    if (!empty($_SESSION['user'])) audit('logout', 'user:' . $_SESSION['user']['id']);
    clear_remember_cookie('staff');
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function current_user(): ?array
{
    if (!empty($_SESSION['user'])) return $_SESSION['user'];
    return try_remember_login('staff');
}

/* =====================================================================
 * Parent auth (NIS + password + remember-me)
 * ===================================================================== */

function parent_login(string $nis, string $password, bool $remember = false): array
{
    $yearId = active_scope()['year_id'];
    $studentStmt = db()->prepare(
        "SELECT id, nis, nisn, nama, jenjang, tingkat, tgl_lahir
         FROM students
         WHERE nis = :n AND deleted_at IS NULL
         ORDER BY CASE WHEN academic_year_id = :y THEN 0 ELSE 1 END, id LIMIT 1"
    );
    $studentStmt->execute(['n' => $nis, 'y' => $yearId]);
    $student = $studentStmt->fetch();
    if (!$student) throw new RuntimeException('Akun ortu tidak ditemukan.');

    $parentStmt = db()->prepare(
        "SELECT pa.*, s.nama, s.jenjang, s.tingkat
         FROM parents_auth pa JOIN students s ON s.id = pa.student_id
         WHERE pa.student_id = :sid AND pa.is_active = 1 AND s.deleted_at IS NULL LIMIT 1"
    );
    $parentStmt->execute(['sid' => (int)$student['id']]);
    $row = $parentStmt->fetch();

    if (!$row) {
        $defaultPassword = '';
        if (!empty($student['tgl_lahir'])) {
            try {
                $defaultPassword = (new DateTime((string)$student['tgl_lahir']))->format('dmY');
            } catch (Throwable) {
                $defaultPassword = '';
            }
        }
        if ($defaultPassword === '') {
            $defaultPassword = (string)$student['nis'];
        }

        $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        db()->prepare("INSERT INTO parents_auth (student_id, password_hash, must_change_pw) VALUES (:sid, :hash, 0)")
            ->execute(['sid' => (int)$student['id'], 'hash' => $hash]);

        $row = [
            'id' => (int)db()->lastInsertId(),
            'student_id' => (int)$student['id'],
            'password_hash' => $hash,
            'must_change_pw' => 0,
            'nama' => $student['nama'],
            'jenjang' => $student['jenjang'],
            'tingkat' => (int)$student['tingkat'],
        ];
    }

    if (!password_verify($password, $row['password_hash'])) throw new RuntimeException('NIS atau password salah.');

    session_regenerate_id(true);
    $_SESSION['parent'] = [
        'id' => (int)$row['id'], 'student_id' => (int)$row['student_id'],
        'nama' => $row['nama'], 'jenjang' => $row['jenjang'], 'tingkat' => (int)$row['tingkat'],
        'must_change_pw' => (int)$row['must_change_pw'],
    ];
    db()->prepare("UPDATE parents_auth SET last_login_at = NOW() WHERE id = :id")->execute(['id' => $row['id']]);
    if ($remember) issue_remember_cookie('parent', (int)$row['id']);
    audit('parent_login', 'student:' . $row['student_id']);
    return $_SESSION['parent'];
}

function parent_logout(): void
{
    clear_remember_cookie('parent');
    unset($_SESSION['parent']);
    session_regenerate_id(true);
}

function current_parent(): ?array
{
    if (!empty($_SESSION['parent'])) return $_SESSION['parent'];
    return try_remember_login('parent');
}

/* =====================================================================
 * Remember-me (selector/validator pattern, 64-char token)
 * ===================================================================== */

function _remember_cookie_name(string $kind): string { return $kind === 'parent' ? 'sg_premember' : 'sg_remember'; }

function issue_remember_cookie(string $kind, int $id): void
{
    $selector  = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $hash = hash('sha256', $validator);
    $days = (int)cfg()['remember_days'];
    $expires = date('Y-m-d H:i:s', time() + $days * 86400);
    $isProd = (cfg()['env'] ?? 'development') === 'production';

    $table = $kind === 'parent' ? 'parent_remember_tokens' : 'user_remember_tokens';
    $col   = $kind === 'parent' ? 'parent_auth_id' : 'user_id';
    db()->prepare("INSERT INTO $table ($col, selector, validator_hash, expires_at) VALUES (:i,:s,:v,:e)")
        ->execute(['i' => $id, 's' => $selector, 'v' => $hash, 'e' => $expires]);

    setcookie(_remember_cookie_name($kind), $selector . ':' . $validator, [
        'expires'  => time() + $days * 86400,
        'path'     => '/',
        'secure'   => $isProd, // Secure only in production (HTTPS)
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_remember_cookie(string $kind): void
{
    $name = _remember_cookie_name($kind);
    if (!empty($_COOKIE[$name])) {
        $parts = explode(':', $_COOKIE[$name], 2);
        if (count($parts) === 2) {
            $table = $kind === 'parent' ? 'parent_remember_tokens' : 'user_remember_tokens';
            db()->prepare("DELETE FROM $table WHERE selector = :s")->execute(['s' => $parts[0]]);
        }
    }
    setcookie($name, '', ['expires' => time() - 3600, 'path' => '/']);
}

function try_remember_login(string $kind): ?array
{
    $name = _remember_cookie_name($kind);
    if (empty($_COOKIE[$name])) return null;
    $parts = explode(':', $_COOKIE[$name], 2);
    if (count($parts) !== 2) return null;
    [$selector, $validator] = $parts;

    $table = $kind === 'parent' ? 'parent_remember_tokens' : 'user_remember_tokens';
    $col   = $kind === 'parent' ? 'parent_auth_id' : 'user_id';
    $stmt = db()->prepare("SELECT * FROM $table WHERE selector = :s AND expires_at > NOW() LIMIT 1");
    $stmt->execute(['s' => $selector]);
    $row = $stmt->fetch();
    if (!$row) { clear_remember_cookie($kind); return null; }
    if (!hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        clear_remember_cookie($kind);
        return null;
    }

    if ($kind === 'parent') {
        $u = db()->prepare("SELECT pa.*, s.nama, s.jenjang, s.tingkat FROM parents_auth pa JOIN students s ON s.id = pa.student_id WHERE pa.id = :id AND pa.is_active = 1 AND s.deleted_at IS NULL LIMIT 1");
        $u->execute(['id' => $row[$col]]);
        $r = $u->fetch();
        if (!$r) return null;
        $_SESSION['parent'] = [
            'id' => (int)$r['id'], 'student_id' => (int)$r['student_id'],
            'nama' => $r['nama'], 'jenjang' => $r['jenjang'], 'tingkat' => (int)$r['tingkat'],
            'must_change_pw' => (int)$r['must_change_pw'],
        ];
        return $_SESSION['parent'];
    } else {
        $u = db()->prepare("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL AND is_active = 1");
        $u->execute(['id' => $row[$col]]);
        $r = $u->fetch();
        if (!$r) return null;
        $_SESSION['user'] = [
            'id' => (int)$r['id'], 'niy' => $r['niy'], 'nama' => $r['nama'],
            'role' => $r['role'], 'jenjang' => $r['jenjang'], 'is_wali' => (int)$r['is_wali'],
            'must_change_pw' => (int)$r['must_change_pw'],
        ];
        return $_SESSION['user'];
    }
}
