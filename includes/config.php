<?php
/**
 * App configuration.
 * Reads from environment variables when available; falls back to defaults.
 * Set these as env vars in production (Apache SetEnv, .htaccess, or server config).
 */
declare(strict_types=1);

$_env = function(string $key, string $default): string {
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
};

return [
    'app_name'    => $_env('APP_NAME', 'Sekolah Grading'),
    'base_url' => $_env('APP_BASE_URL', '/public/')
    'env'         => $_env('APP_ENV', 'development'), // 'production' | 'development'
    'db' => [
        'host'    => $_env('DB_HOST', '127.0.0.1'),
        'port'    => (int)$_env('DB_PORT', '3306'),
        'name'    => $_env('DB_NAME', 'sekolah_grading'),
        'user'    => $_env('DB_USER', 'root'),
        'pass'    => $_env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'session_name'  => 'SGSID',
    'remember_days' => 30,
    'timezone'      => $_env('APP_TZ', 'Asia/Jakarta'),
    // Trusted proxy IPs for X-Forwarded-For (comma-separated)
    'trusted_proxies' => $_env('TRUSTED_PROXIES', ''),
];
