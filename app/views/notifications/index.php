<?php
$pageTitle = 'Notificaciones';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">
        <i class="bi bi-bell me-2"></i>Notificaciones
        <?php if ($unreadCount > 0): ?>
        <span class="badge bg-danger"><?= (int)$unreadCount ?> sin leer</span>
        <?php endif; ?>
    </h1>

    <?php if ($unreadCount > 0): ?>
    <form method="POST" action="<?= url('notifications/read') ?>" class="d-inline">
        <?= csrfField() ?>
        <input type="hidden" name="mark_all" value="1">
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-check2-all me-1"></i> Marcar todas como leídas
        </button>
    </form>
    <?php endif; ?>
</div>

<?= renderFlash('notifications') ?>

<?php if (empty($notifications)): ?>
<div class="card text-center py-5">
    <div class="card-body text-muted">
        <i class="bi bi-bell-slash" style="font-size:3rem"></i>
        <p class="mt-3 mb-0">No tienes notificaciones.</p>
    </div>
</div>
<?php else: ?>
<div class="list-group shadow-sm">
    <?php foreach ($notifications as $n): ?>
    <div class="list-group-item list-group-item-action p-0 <?= !$n['is_read'] ? 'border-start border-primary border-3 bg-white' : '' ?>">
        <div class="d-flex align-items-stretch">
            <a href="<?= e(notificationUrl($n['type'], $n['reference_id'] ? (int)$n['reference_id'] : null)) ?>"
               class="d-flex flex-grow-1 gap-3 p-3 text-decoration-none text-body align-items-start">
                <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                      style="width:44px;height:44px">
                    <i class="bi <?= notificationIcon($n['type']) ?>"></i>
                </span>
                <div class="min-width-0 flex-grow-1">
                    <p class="mb-1 <?= !$n['is_read'] ? 'fw-semibold' : '' ?>">
                        <?= e($n['message']) ?>
                    </p>
                    <small class="text-muted"><?= e(timeAgo($n['created_at'])) ?></small>
                </div>
            </a>
            <?php if (!$n['is_read']): ?>
            <form method="POST" action="<?= url('notifications/read') ?>"
                  class="d-flex align-items-center pe-3">
                <?= csrfField() ?>
                <input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Marcar como leída">
                    <i class="bi bi-check2"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (($pag['totalPages'] ?? 1) > 1): ?>
<nav class="mt-4" aria-label="Paginación de notificaciones">
    <ul class="pagination justify-content-center">
        <?php if ($pag['page'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('notifications?page=' . ($pag['page'] - 1)) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('notifications?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($pag['page'] < $pag['totalPages']): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('notifications?page=' . ($pag['page'] + 1)) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
