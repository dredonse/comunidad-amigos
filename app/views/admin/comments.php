<?php
$pageTitle = 'Gestión de comentarios';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">
        <i class="bi bi-chat-left-text me-2"></i>Gestión de comentarios
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
                    <th>Comentario</th>
                    <th>Publicación</th>
                    <th>Fecha</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        No hay comentarios.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($comments as $p):
                        $snippet = mb_strlen($p['comment']) > 80
                            ? mb_substr($p['comment'], 0, 80) . '…'
                            : $p['comment'];
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= avatarUrl($p['avatar'] ?? 'default.png') ?>"
                                     class="rounded-circle flex-shrink-0"
                                     width="36" height="36" alt=""
                                     onerror="this.onerror=null; this.src='/img/default.svg'">
                                <a href="<?= url('profile/' . (int)$p['user_id']) ?>"
                                   class="fw-semibold text-decoration-none">
                                    <?= e($p['username']) ?>
                                </a>
                            </div>
                        </td>
                        <td class="small text-muted"><?= e($snippet) ?></td>
                        <td class="small">#<?= (int)$p['post_id'] ?></td>
                        <td class="text-muted small">
                            <?= e(timeAgo($p['created_at'])) ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="<?= url('admin/comment/delete') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="comment_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        data-confirm="¿Eliminar este comentario?">
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
<nav class="mt-4" aria-label="Paginación de comentarios">
    <ul class="pagination justify-content-center">
        <?php if ($pag['page'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/comments?page=' . ($pag['page'] - 1)) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('admin/comments?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($pag['page'] < $pag['totalPages']): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/comments?page=' . ($pag['page'] + 1)) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
