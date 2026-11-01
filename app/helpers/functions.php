<?php

declare(strict_types=1);

// ── Entorno ──────────────────────────────────────────────────

/**
 * Lee una variable de entorno. getenv() devuelve false (no null) cuando no existe,
 * así que el operador ?? no sirve para dar un valor por defecto.
 */
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? null;
    }

    return ($value === null || $value === '') ? $default : (string)$value;
}

// ── Seguridad ────────────────────────────────────────────────

/** Escapa salida HTML para prevenir XSS */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Genera y verifica tokens CSRF */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Token CSRF inválido.');
    }
}

/**
 * Token para identificarse ante el servidor WebSocket.
 * Formato: user_id.caducidad.firma, con la firma HMAC-SHA256 calculada con APP_SECRET.
 * El servidor WebSocket comparte el secreto, verifica la firma y usa el user_id del token:
 * nunca un id que el navegador pueda inventar.
 */
function wsToken(int $userId, int $ttl = 7200): string
{
    $secret = env('APP_SECRET', '');
    if ($secret === '') {
        return '';
    }

    $payload = $userId . '.' . (time() + $ttl);

    return $payload . '.' . hash_hmac('sha256', 'ws|' . $payload, $secret);
}

// ── Autenticación ────────────────────────────────────────────

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Exige sesión y la contrasta con la base de datos en cada petición:
 * si un administrador desactiva o borra la cuenta, el usuario sale en su siguiente clic,
 * y un cambio de rol se aplica al momento (antes se confiaba en lo guardado en la sesión).
 */
function requireLogin(): void
{
    static $checked = false;

    if (!isLoggedIn()) {
        redirect('/login');
    }
    if ($checked) {
        return;
    }

    $stmt = Database::getConnection()->prepare(
        'SELECT username, avatar, role, active FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !(int)$user['active']) {
        endSession();
        session_start();
        flash('auth', 'Tu sesión ha terminado. Si crees que es un error, contacta con el administrador.', 'warning');
        redirect('/login');
    }

    $_SESSION['username']  = $user['username'];
    $_SESSION['avatar']    = $user['avatar'];
    $_SESSION['user_role'] = $user['role'];
    $checked = true;
}

function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        die('Acceso denegado.');
    }
}

/** Vacía la sesión, borra la cookie y destruye la sesión del servidor. */
function endSession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'avatar'   => $_SESSION['avatar'],
        'role'     => $_SESSION['user_role'],
    ];
}

// ── Respuestas ───────────────────────────────────────────────

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function isAjax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// ── Flash messages ───────────────────────────────────────────

function flash(string $key, string $message, string $type = 'success'): void
{
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

function getFlash(string $key): ?array
{
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function renderFlash(string $key): string
{
    $flash = getFlash($key);
    if (!$flash) return '';

    $type    = e($flash['type']);
    $message = e($flash['message']);
    return <<<HTML
    <div class="alert alert-{$type} alert-dismissible fade show" role="alert">
        {$message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    HTML;
}

// ── Fechas ───────────────────────────────────────────────────

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);

    return match (true) {
        $diff < 60     => 'hace un momento',
        $diff < 3600   => 'hace ' . floor($diff / 60) . ' min',
        $diff < 86400  => 'hace ' . floor($diff / 3600) . ' h',
        $diff < 604800 => 'hace ' . floor($diff / 86400) . ' días',
        default        => date('d/m/Y', strtotime($datetime)),
    };
}

// ── Imágenes ─────────────────────────────────────────────────

function avatarUrl(?string $avatar = null): string
{
    if ($avatar === null || $avatar === '' || $avatar === 'default.png') {
        return BASE_URL . '/img/default.svg';
    }
    return BASE_URL . '/uploads/avatars/' . e($avatar);
}

function groupAvatarUrl(?string $avatar = null): string
{
    if ($avatar === null || $avatar === '' || $avatar === 'default.png') {
        return BASE_URL . '/img/default.svg';
    }
    return BASE_URL . '/uploads/groups/' . e($avatar);
}

/**
 * Valida y guarda una imagen subida. Devuelve el nombre del fichero o null si no es válida.
 *
 * Nada de lo que decide el resultado viene del cliente:
 *  1. El tipo se detecta por el CONTENIDO (finfo), no por $file['type'] (lo manda el navegador).
 *  2. La imagen se RE-CODIFICA con GD: se descarta cualquier código incrustado (ficheros
 *     "políglotas" GIF+PHP) y los metadatos EXIF, que pueden incluir coordenadas GPS.
 *  3. La extensión la pone el servidor según el tipo real, nunca el nombre original.
 * Además, en public/uploads/ PHP no se ejecuta (vhost de Apache y uploads/.htaccess).
 */
function uploadImage(array $file, string $subdir = 'posts'): ?string
{
    $types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $maxBytes  = 5 * 1024 * 1024; // 5 MB
    $maxPixels = 6000;            // por lado: evita "bombas de descompresión"

    if (!in_array($subdir, ['avatars', 'posts', 'events', 'groups'], true)) return null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)           return null;
    if (($file['size'] ?? 0) > $maxBytes)                                   return null;
    if (!is_uploaded_file($file['tmp_name'] ?? ''))                         return null;

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!is_string($mime) || !isset($types[$mime])) return null;

    $info = getimagesize($file['tmp_name']);
    if ($info === false
        || $info[0] < 1 || $info[1] < 1
        || $info[0] > $maxPixels || $info[1] > $maxPixels) {
        return null;
    }

    $image = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
    if ($image === false) return null;

    if ($mime === 'image/jpeg') {
        $image = applyExifOrientation($image, $file['tmp_name']);
    }

    $dir = UPLOADS_PATH . '/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($image);
        return null;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $types[$mime];
    $path     = $dir . '/' . $filename;

    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    $saved = match ($mime) {
        'image/jpeg' => imagejpeg($image, $path, 85),
        'image/png'  => imagepng($image, $path, 6),
        'image/webp' => imagewebp($image, $path, 85),
        'image/gif'  => imagegif($image, $path),
    };
    imagedestroy($image);

    return $saved ? $filename : null;
}

/** Al re-codificar se pierde el EXIF, así que se aplica antes la orientación de la foto. */
function applyExifOrientation(GdImage $image, string $path): GdImage
{
    if (!function_exists('exif_read_data')) {
        return $image;
    }

    $exif  = @exif_read_data($path);
    $angle = match ((int)($exif['Orientation'] ?? 1)) {
        3       => 180,
        6       => -90,
        8       => 90,
        default => 0,
    };
    if ($angle === 0) {
        return $image;
    }

    $rotated = imagerotate($image, $angle, 0);
    if ($rotated === false) {
        return $image;
    }
    imagedestroy($image);

    return $rotated;
}

/** Borra un fichero subido. basename() impide salir de la carpeta. */
function deleteUpload(string $subdir, ?string $filename): void
{
    if ($filename === null || $filename === '' || $filename === 'default.png') {
        return;
    }
    $path = UPLOADS_PATH . '/' . $subdir . '/' . basename($filename);
    if (is_file($path)) {
        unlink($path);
    }
}

// ── Notificaciones ───────────────────────────────────────────

function notificationUrl(string $type, ?int $referenceId = null): string
{
    return match ($type) {
        'friend_request' => url('friends'),
        'comment'        => $referenceId ? url('feed#post-' . $referenceId) : url('feed'),
        'like'           => $referenceId ? url('feed#post-' . $referenceId) : url('feed'),
        'event'          => $referenceId ? url('events/' . $referenceId) : url('events'),
        'message'        => $referenceId ? url('messages/' . $referenceId) : url('messages'),
        'group'          => $referenceId ? url('groups/' . $referenceId) : url('groups'),
        default          => url('notifications'),
    };
}

function notificationIcon(string $type): string
{
    return match ($type) {
        'friend_request' => 'bi-person-plus-fill',
        'comment'        => 'bi-chat-dots-fill',
        'like'           => 'bi-heart-fill',
        'event'          => 'bi-calendar-event-fill',
        'message'        => 'bi-envelope-fill',
        'group'          => 'bi-people-fill',
        default          => 'bi-bell-fill',
    };
}

// ── Paginación ───────────────────────────────────────────────

function paginate(int $total, int $perPage, int $page): array
{
    $totalPages = (int)ceil($total / $perPage);
    $page       = max(1, min($page, $totalPages ?: 1));
    $offset     = ($page - 1) * $perPage;

    return compact('total', 'perPage', 'page', 'totalPages', 'offset');
}
