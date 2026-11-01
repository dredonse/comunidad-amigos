<?php
$pageTitle = 'Administración';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">
        <i class="bi bi-shield-check me-2"></i>Panel de administración
        <span class="badge bg-danger ms-1">Admin</span>
    </h1>
    <a href="<?= url('admin/users') ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-people me-1"></i> Ver todos los usuarios
    </a>
</div>

<?= renderFlash('admin') ?>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">
    <div class="col">
        <a href="<?= url('admin/users') ?>" class="text-decoration-none text-dark admin-stat-link">
            <div class="card h-100 border-0 shadow-sm admin-stat-card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-people-fill text-primary fs-2"></i>
                    <div class="display-6 fw-bold mt-2"><?= (int)$stats['total_users'] ?></div>
                    <div class="text-muted small">Usuarios</div>
                    <div class="text-success small mt-1">
                        +<?= (int)$stats['new_users_week'] ?> esta semana
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="<?= url('admin/posts') ?>" class="text-decoration-none text-dark admin-stat-link">
            <div class="card h-100 border-0 shadow-sm admin-stat-card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-file-post text-primary fs-2"></i>
                    <div class="display-6 fw-bold mt-2"><?= (int)$stats['total_posts'] ?></div>
                    <div class="text-muted small">Publicaciones</div>
                    <div class="text-muted small mt-1">
                        +<?= (int)$stats['new_posts_week'] ?> esta semana
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="<?= url('admin/events') ?>" class="text-decoration-none text-dark admin-stat-link">
            <div class="card h-100 border-0 shadow-sm admin-stat-card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-calendar-event text-primary fs-2"></i>
                    <div class="display-6 fw-bold mt-2"><?= (int)$stats['total_events'] ?></div>
                    <div class="text-muted small">Eventos</div>
                    <div class="text-muted small mt-1">
                        <?= (int)$stats['upcoming_events'] ?> próximos
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <i class="bi bi-chat-dots text-primary fs-2"></i>
                <div class="display-6 fw-bold mt-2"><?= (int)$stats['total_messages'] ?></div>
                <div class="text-muted small">Mensajes</div>
                <div class="text-muted small mt-1">mensajes enviados</div>
            </div>
        </div>
    </div>
    <div class="col">
        <a href="<?= url('admin/groups') ?>" class="text-decoration-none text-dark admin-stat-link">
            <div class="card h-100 border-0 shadow-sm admin-stat-card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-collection text-primary fs-2"></i>
                    <div class="display-6 fw-bold mt-2"><?= (int)$stats['total_groups'] ?></div>
                    <div class="text-muted small">Grupos</div>
                    <div class="text-muted small mt-1">
                        <?= (int)$stats['public_groups'] ?> públicos ·
                        <?= (int)$stats['private_groups'] ?> privados
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                Últimos usuarios registrados
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= avatarUrl($u['avatar'] ?? 'default.png') ?>"
                                         class="rounded-circle" width="32" height="32" alt=""
                                         onerror="this.onerror=null; this.src='/img/default.svg'">
                                    <div>
                                        <a href="<?= url('profile/' . (int)$u['id']) ?>"
                                           class="fw-semibold text-decoration-none">
                                            <?= e($u['username']) ?>
                                        </a>
                                        <small class="text-muted d-block"><?= e($u['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($u['active']): ?>
                                <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?= e(date('d/m/Y', strtotime($u['created_at']))) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                Últimas publicaciones
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Autor</th>
                            <th>Contenido</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPosts as $p):
                            $snippet = mb_strlen($p['content']) > 60
                                ? mb_substr($p['content'], 0, 60) . '…'
                                : $p['content'];
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= avatarUrl($p['avatar'] ?? 'default.png') ?>"
                                         class="rounded-circle" width="32" height="32" alt=""
                                         onerror="this.onerror=null; this.src='/img/default.svg'">
                                    <span class="fw-semibold"><?= e($p['username']) ?></span>
                                </div>
                                <small class="text-muted"><?= e(timeAgo($p['created_at'])) ?></small>
                            </td>
                            <td class="small text-muted"><?= e($snippet) ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?= url('admin/post/delete') ?>" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            data-confirm="¿Eliminar esta publicación?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
