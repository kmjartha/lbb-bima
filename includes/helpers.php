<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scope.php';

define('UPLOADS_PATH', realpath(__DIR__ . '/../public/uploads') ?: (__DIR__ . '/../public/uploads'));

/* ---------- Bootstrapping (session, timezone, errors) ---------- */
(function () {
    $c = cfg();
    $isProd = ($c['env'] ?? 'development') === 'production';

    // In production: log errors, never display them.
    // In development: display errors so stack traces are visible locally.
    if ($isProd) {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }

    date_default_timezone_set($c['timezone']);
    if (session_status() === PHP_SESSION_NONE) {
        session_name($c['session_name']);
        $secureCookie = $isProd; // only mark Secure in production (HTTPS)
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
})();

/* ---------- Output / URL ---------- */

function esc($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim(cfg()['base_url'], '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function take_flashes(): array
{
    $out = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $out;
}

/* ---------- Input helpers ---------- */

function req_str(array $src, string $key, int $maxLen = 255): string
{
    if (!isset($src[$key])) throw new RuntimeException("Field '$key' wajib diisi.");
    $v = trim((string)$src[$key]);
    if ($v === '') throw new RuntimeException("Field '$key' wajib diisi.");
    if (mb_strlen($v) > $maxLen) throw new RuntimeException("Field '$key' melebihi $maxLen karakter.");
    return $v;
}

function opt_str(array $src, string $key, int $maxLen = 255): ?string
{
    if (!isset($src[$key])) return null;
    $v = trim((string)$src[$key]);
    if ($v === '') return null;
    if (mb_strlen($v) > $maxLen) throw new RuntimeException("Field '$key' melebihi $maxLen karakter.");
    return $v;
}

/* ---------- Validation helpers ---------- */

function valid_nisn(string $nisn): bool { return preg_match('/^\d{10}$/', $nisn) === 1; }
function valid_nis(string $nis): bool   { return preg_match('/^\d{7}$/', $nis)   === 1; }

/* ---------- IP resolution (proxy-aware) ---------- */

function client_ip(): string
{
    $trusted = array_filter(array_map('trim', explode(',', cfg()['trusted_proxies'] ?? '')));
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($trusted && in_array($remoteAddr, $trusted, true)) {
        // Trust X-Forwarded-For only when the request comes from a known proxy
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            $ips = array_map('trim', explode(',', $xff));
            // Rightmost non-trusted IP is the real client
            foreach (array_reverse($ips) as $ip) {
                if (!in_array($ip, $trusted, true)) return $ip;
            }
        }
    }
    return $remoteAddr;
}

/* ---------- Audit ---------- */

function audit(string $action, ?string $target = null, array $meta = []): void
{
    try {
        $u = $_SESSION['user'] ?? null;
        $stmt = db()->prepare(
            "INSERT INTO audit_log (user_id, user_label, action, target, meta_json, ip)
             VALUES (:uid, :ul, :a, :t, :m, :ip)"
        );
        $stmt->execute([
            'uid' => $u['id'] ?? null,
            'ul'  => $u['nama'] ?? ($_SESSION['parent']['nama'] ?? null),
            'a'   => $action,
            't'   => $target,
            'm'   => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'ip'  => client_ip(),
        ]);
    } catch (Throwable $e) {
        error_log('audit failed: ' . $e->getMessage());
    }
}

/* ---------- KKM lookup ---------- */

function kkm_predikat(string $jenjang, ?float $nilai): array
{
    if ($nilai === null) return ['grade' => '—', 'predikat' => '—'];
    $yearId = active_scope()['year_id'];
    $stmt = db()->prepare(
        "SELECT grade, predikat FROM kkm_settings
         WHERE academic_year_id = :y AND jenjang = :j AND :n BETWEEN min_val AND max_val
         ORDER BY min_val DESC LIMIT 1"
    );
    $stmt->execute(['y' => $yearId, 'j' => $jenjang, 'n' => $nilai]);
    return $stmt->fetch() ?: ['grade' => '—', 'predikat' => '—'];
}

/* ---------- Elective-derived ("shadow") subject labeling ---------- */

/**
 * Disambiguating label for a subject row that may be one of:
 *  - a regular subject               -> just its own nama
 *  - an elective option (opsi)       -> "Opsi (KodeMapelPilihan)", e.g. "Coding (CGV)"
 *
 * $electiveKode should be null/empty for regular subjects, and the parent
 * elective's kode (e.g. "CGV") for shadow subjects synced from an elective
 * option. Pass it from a query that LEFT JOINs elective_classes/electives.
 *
 * Used on staff-facing listings (Guru Pengampu, Subjek Penilaian, Nilai
 * Akhir, Leger) where the same option name could collide with an unrelated
 * regular subject name. NOT used on Rapor, which already groups subjects by
 * category and shows the pure option name with no extra disambiguation.
 */
function elective_subject_label(string $nama, ?string $electiveKode): string
{
    if ($electiveKode === null || $electiveKode === '') {
        return $nama;
    }
    return $nama . ' (' . $electiveKode . ')';
}
