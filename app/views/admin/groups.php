<?php
$pageTitle = 'Gestión de grupos';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">
        <i class="bi bi-collection me-2"></i>Gestión de grupos
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
                    <th>Grupo</th>
                    <th>Creador</th>
                    <th>Privacidad</th>
                    <th>Miembros</th>
                    <th>Creado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No hay grupos registrados.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($groups as $g): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= groupAvatarUrl($g['avatar'] ?? 'default.png') ?>"
                                     class="rounded-circle flex-shrink-0"
                                     width="40" height="40" alt=""
                                     onerror="this.onerror=null; this.src='/img/default.svg'">
                                <span class="fw-semibold"><?= e($g['name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <a href="<?= url('profile/' . (int)$g['creator_id']) ?>"
                               class="text-decoration-none">
                                <?= e($g['creator_username']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($g['privacy'] === 'public'): ?>
                            <span class="badge bg-success">Público</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Privado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$g['member_count'] ?></td>
                        <td class="text-muted small">
                            <?= e(timeAgo($g['created_at'])) ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end flex-wrap">
                                <form method="POST"
                                      action="<?= url('admin/group/toggle-privacy') ?>"
                                      class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <?php if ($g['privacy'] === 'public'): ?>
                                        Hacer privado
                                        <?php else: ?>
                                        Hacer público
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= url('admin/group/delete') ?>"
                                      class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            data-confirm="¿Eliminar el grupo <?= e($g['name']) ?>? Se borrarán todos sus datos.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pag['totalPages'] ?? 1) > 1): ?>
<nav class="mt-4" aria-label="Paginación de grupos">
    <ul class="pagination justify-content-center">
        <?php if ($pag['page'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/groups?page=' . ($pag['page'] - 1)) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('admin/groups?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($pag['page'] < $pag['totalPages']): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/groups?page=' . ($pag['page'] + 1)) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
