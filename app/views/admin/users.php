<?php
$pageTitle = 'Gestión de usuarios';
$adminId = (int)currentUser()['id'];
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">
        <i class="bi bi-people me-2"></i>Gestión de usuarios
    </h1>
    <a href="<?= url('admin') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver al panel
    </a>
</div>

<?= renderFlash('admin') ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $isSelf = (int)$u['id'] === $adminId;
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= avatarUrl($u['avatar'] ?? 'default.png') ?>"
                                 class="rounded-circle" width="36" height="36" alt=""
                                 onerror="this.src='<?= url('img/default.svg') ?>'">
                            <span class="fw-semibold"><?= e($u['username']) ?></span>
                        </div>
                    </td>
                    <td class="small"><?= e($u['email']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge bg-danger">Admin</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Usuario</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['active']): ?>
                        <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                        <span class="badge bg-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small">
                        <?= e(date('d/m/Y H:i', strtotime($u['created_at']))) ?>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                            <a href="<?= url('profile/' . (int)$u['id']) ?>"
                               class="btn btn-sm btn-outline-primary" title="Ver perfil">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (!$isSelf): ?>
                            <form method="POST" action="<?= url('admin/user/toggle') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit"
                                        class="btn btn-sm btn-outline-<?= $u['active'] ? 'warning' : 'success' ?>"
                                        title="<?= $u['active'] ? 'Desactivar' : 'Activar' ?>">
                                    <i class="bi bi-<?= $u['active'] ? 'pause' : 'play' ?>-fill"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= url('admin/user/delete') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        data-confirm="¿Eliminar al usuario <?= e($u['username']) ?>? Se borrarán todos sus datos.">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pag['totalPages'] ?? 1) > 1): ?>
<nav class="mt-4" aria-label="Paginación de usuarios">
    <ul class="pagination justify-content-center">
        <?php if ($pag['page'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/users?page=' . ($pag['page'] - 1)) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('admin/users?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($pag['page'] < $pag['totalPages']): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/users?page=' . ($pag['page'] + 1)) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
