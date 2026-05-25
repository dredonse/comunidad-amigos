<?php
$pageTitle = 'Mensajes';
require_once VIEWS_PATH . '/layout/header.php';
?>

<h1 class="h3 mb-4"><i class="bi bi-chat-dots me-2"></i>Mensajes</h1>

<?= renderFlash('messages') ?>

<?php if (empty($conversations)): ?>
<div class="card text-center py-5">
    <div class="card-body">
        <i class="bi bi-chat-left-text text-muted" style="font-size:3rem"></i>
        <p class="text-muted mt-3 mb-3">
            Aún no tienes conversaciones. Escribe a un amigo desde su perfil o desde
            <a href="<?= url('friends') ?>">Amigos</a>.
        </p>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        <?php foreach ($conversations as $conv):
            $otherId = (int)$conv['other_id'];
            $preview = mb_strlen($conv['last_message']) > 60
                ? mb_substr($conv['last_message'], 0, 60) . '…'
                : $conv['last_message'];
        ?>
        <a href="<?= url('messages/' . $otherId) ?>"
           class="list-group-item list-group-item-action">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= avatarUrl($conv['avatar'] ?? 'default.png') ?>"
                     class="avatar rounded-circle flex-shrink-0"
                     width="48" height="48" alt=""
                     onerror="this.src='<?= url('img/default.svg') ?>'">
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><?= e($conv['username']) ?></strong>
                        <small class="text-muted"><?= e(timeAgo($conv['last_at'])) ?></small>
                    </div>
                    <p class="text-muted small mb-0 text-truncate"><?= e($preview) ?></p>
                </div>
                <?php if ((int)$conv['unread_count'] > 0): ?>
                <span class="badge bg-danger rounded-pill"><?= (int)$conv['unread_count'] ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
