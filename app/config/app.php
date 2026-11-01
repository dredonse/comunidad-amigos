<?php

declare(strict_types=1);

// Cargar variables de entorno desde .env si no están definidas
// (en Docker ya llegan por docker-compose; esto sirve al ejecutar fuera de Docker)
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

// Dependencias de Composer: en la carpeta del proyecto o, en Docker, en la imagen
// (el volumen ./app tapa el vendor/ instalado en /var/www/html durante el build)
foreach ([BASE_PATH . '/vendor/autoload.php', '/opt/app-deps/vendor/autoload.php'] as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('VIEWS_PATH', BASE_PATH . '/views');

// Helpers globales (env(), e(), csrf…)
require_once BASE_PATH . '/helpers/functions.php';

// URL base: fija desde APP_URL. Construirla con la cabecera Host de la petición
// dejaría que el cliente decida a dónde apuntan las redirecciones.
$appUrl = env('APP_URL');
if ($appUrl !== null) {
    define('BASE_URL', rtrim($appUrl, '/'));
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8080'));
}

// Configuración de sesión segura
$https = str_starts_with(BASE_URL, 'https://');
ini_set('session.use_strict_mode',   '1');
ini_set('session.cookie_httponly',   '1');
ini_set('session.cookie_samesite',   'Lax');
ini_set('session.cookie_secure',     $https ? '1' : '0');
ini_set('session.cookie_path',       '/');
ini_set('session.cookie_domain',     '');
ini_set('session.gc_maxlifetime',    env('SESSION_LIFETIME', '3600'));
ini_set('session.use_only_cookies',  '1');
ini_set('session.use_trans_sid',     '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Las páginas con sesión no deben guardarse en ninguna caché (navegador, proxy, Service Worker)
if (isset($_SESSION['user_id'])) {
    header('Cache-Control: no-store, private');
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

// Modo debug (desactivado por defecto)
if (filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
}
