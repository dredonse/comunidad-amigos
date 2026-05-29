<?php
$pageTitle = 'Gestión de publicaciones';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">
        <i class="bi bi-file-post me-2"></i>Gestión de publicaciones
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
                    <th>Autor</th>
                    <th>Contenido</th>
                    <th>Imagen</th>
                    <th>Fecha</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        No hay publicaciones registradas.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($posts as $p):
                        $snippet = mb_strlen($p['content']) > 80
                            ? mb_substr($p['content'], 0, 80) . '…'
                            : $p['content'];
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= avatarUrl($p['avatar'] ?? 'default.png') ?>"
                                     class="rounded-circle flex-shrink-0"
                                     width="36" height="36" alt=""
                                     onerror="this.onerror=null; this.src='/img/default.png'">
                                <a href="<?= url('profile/' . (int)$p['user_id']) ?>"
                                   class="fw-semibold text-decoration-none">
                                    <?= e($p['username']) ?>
                                </a>
                            </div>
                        </td>
                        <td class="small text-muted"><?= e($snippet) ?></td>
                        <td>
                            <?php if (!empty($p['image'])): ?>
                            <img src="<?= url('uploads/posts/' . $p['image']) ?>"
                                 class="rounded" width="40" height="40" alt=""
                                 style="object-fit:cover">
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= e(timeAgo($p['created_at'])) ?>
                        </td>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pag['totalPages'] ?? 1) > 1): ?>
<nav class="mt-4" aria-label="Paginación de publicaciones">
    <ul class="pagination justify-content-center">
        <?php if ($pag['page'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/posts?page=' . ($pag['page'] - 1)) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('admin/posts?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($pag['page'] < $pag['totalPages']): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/posts?page=' . ($pag['page'] + 1)) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
