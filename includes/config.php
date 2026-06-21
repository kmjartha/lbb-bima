<?php
declare(strict_types=1);

// Simple .env loader
$envFile = dirname(__DIR__) . '/.env';

if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        $value = trim($value, "\"'");

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

/**
 * App configuration.
 * Reads from environment variables when available; falls back to defaults.
 */
$_env = function(string $key, string $default): string {
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
};

return [
    'app_name'    => $_env('APP_NAME', 'LBB Bima'),
    'base_url'    => $_env('APP_BASE_URL', '/public/'),
    'env'         => $_env('APP_ENV', 'development'),

    'db' => [
        'host'    => $_env('DB_HOST', ''),
        'port'    => (int)$_env('DB_PORT', '3306'),
        'name'    => $_env('DB_NAME', ''),
        'user'    => $_env('DB_USER', ''),
        'pass'    => $_env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    'session_name'  => 'SGSID',
    'remember_days' => 30,
    'timezone'      => $_env('APP_TZ', 'Asia/Jakarta'),
    'trusted_proxies' => $_env('TRUSTED_PROXIES', ''),
];