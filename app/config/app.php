<?php

declare(strict_types=1);

// Temporal: suprimir notices/warnings hasta confirmar ext-gmp tras rebuild
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Cargar variables de entorno desde .env si no están definidas
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

// Rutas base
define('BASE_PATH', dirname(__DIR__));

loadEnv(dirname(BASE_PATH) . '/.env');
loadEnv(BASE_PATH . '/.env');

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('VIEWS_PATH', BASE_PATH . '/views');

// URL base (detectada automáticamente)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
define('BASE_URL', "{$protocol}://{$host}");

// Configuración de sesión segura
ini_set('session.use_strict_mode',   '1');
ini_set('session.cookie_httponly',   '1');
ini_set('session.cookie_samesite',   'Lax');
ini_set('session.cookie_path',       '/');
ini_set('session.cookie_domain',     '');
ini_set('session.gc_maxlifetime',    '3600');
ini_set('session.use_only_cookies',  '1');
ini_set('session.use_trans_sid',     '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload de clases
spl_autoload_register(function (string $class): void {
    $paths = [
        BASE_PATH . '/models/',
        BASE_PATH . '/controllers/',
        BASE_PATH . '/config/',
        BASE_PATH . '/helpers/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Helpers globales
require_once BASE_PATH . '/helpers/functions.php';

// Modo debug
$debug = filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?? 'false', FILTER_VALIDATE_BOOLEAN);
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
