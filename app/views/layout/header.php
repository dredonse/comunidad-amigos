<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d6efd">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <title><?= e($pageTitle ?? 'Comunidad') ?> · Comunidad</title>
    <?php if (isLoggedIn()): ?>
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
</head>
<body class="bg-light">

<?php if (isLoggedIn()):
    $pendingCount   = (new FriendModel())->countPending((int)$_SESSION['user_id']);
    $unreadNotifs   = (new NotificationModel())->countUnread((int)$_SESSION['user_id']);
    $unreadMessages = (new MessageModel())->countUnread((int)$_SESSION['user_id']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('feed') ?>">
            <i class="bi bi-people-fill me-1"></i> Comunidad
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('feed') ?>"><i class="bi bi-house-door"></i> Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= url('friends') ?>">
                        <i class="bi bi-person-check"></i> Amigos
                        <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-danger notif-badge"><?= (int)$pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('events') ?>"><i class="bi bi-calendar-event"></i> Eventos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('groups') ?>"><i class="bi bi-people"></i> Grupos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= url('messages') ?>">
                        <i class="bi bi-chat-dots"></i> Mensajes
                        <?php if ($unreadMessages > 0): ?>
                        <span class="badge bg-danger notif-badge"><?= (int)$unreadMessages ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-2">
                    <a class="nav-link position-relative" href="<?= url('notifications') ?>"
                       title="Notificaciones">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadNotifs > 0): ?>
                        <span class="badge bg-danger notif-badge"><?= (int)$unreadNotifs ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
                       data-bs-toggle="dropdown">
                        <img src="<?= avatarUrl($_SESSION['avatar'] ?? 'default.png') ?>"
                             class="rounded-circle" width="32" height="32" alt="Avatar"
                             onerror="this.src='<?= url('img/default.svg') ?>'">
                        <span><?= e($_SESSION['username'] ?? '') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= url('profile') ?>">
                            <i class="bi bi-person me-1"></i> Mi perfil</a></li>
                        <li>
                            <button type="button" class="dropdown-item" id="push-toggle-btn">
                                <i class="bi bi-bell me-1"></i> Activar notificaciones push
                            </button>
                        </li>
                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <li><a class="dropdown-item" href="<?= url('admin') ?>">
                            <i class="bi bi-shield-check me-1"></i> Administración</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="<?= url('logout') ?>" id="logout-form" class="m-0">
                                <?= csrfField() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="py-4">
    <div class="container">
