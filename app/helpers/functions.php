<?php

declare(strict_types=1);

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
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Token CSRF inválido.');
    }
}

// ── Autenticación ────────────────────────────────────────────

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        die('Acceso denegado.');
    }
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

// ── Redirección y URL ────────────────────────────────────────

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return BASE_URL . '/css/../' . ltrim($path, '/');
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

function avatarUrl(string $avatar = 'default.png'): string
{
    return BASE_URL . '/uploads/avatars/' . e($avatar);
}

function groupAvatarUrl(string $avatar = 'default.png'): string
{
    return BASE_URL . '/uploads/groups/' . e($avatar);
}

function uploadImage(array $file, string $subdir = 'posts'): ?string
{
    $allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize   = 5 * 1024 * 1024; // 5 MB
    $uploadDir = UPLOADS_PATH . '/' . $subdir . '/';

    if ($file['error'] !== UPLOAD_ERR_OK)          return null;
    if ($file['size'] > $maxSize)                  return null;
    if (!in_array($file['type'], $allowed, true))  return null;

    $info = getimagesize($file['tmp_name']);
    if ($info === false) return null;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }

    return null;
}

// ── Notificaciones ───────────────────────────────────────────

function notificationUrl(string $type, ?int $referenceId = null): string
{
    return match ($type) {
        'friend_request' => url('friends'),
        'comment'        => url('feed'),
        'like'           => url('feed'),
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
