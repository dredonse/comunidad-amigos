<?php
$pageTitle = 'Chat con ' . $otherUser['username'];
$currentUser = currentUser();
$currentId = (int)$currentUser['id'];
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card sidebar-card">
            <div class="card-header bg-white py-2">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <a href="<?= url('messages') ?>" class="text-decoration-none small">
                        <i class="bi bi-arrow-left me-1"></i> Todas
                    </a>
                </div>
                <a href="<?= url('messages?new=1') ?>" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-pencil-square me-1"></i> Nuevo mensaje
                </a>
            </div>
            <div class="card-body p-0" style="max-height:70vh;overflow-y:auto">
                <?php
                $compact = true;
                require VIEWS_PATH . '/messages/_conversations.php';
                ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100 d-flex flex-column" style="min-height:70vh">
            <div class="card-header bg-white d-flex align-items-center gap-3">
                <img src="<?= avatarUrl($otherUser['avatar'] ?? 'default.png') ?>"
                     class="rounded-circle" width="40" height="40" alt=""
                     onerror="this.onerror=null; this.src='/img/default.svg'">
                <div>
                    <a href="<?= url('profile/' . (int)$otherUser['id']) ?>"
                       class="fw-semibold text-decoration-none">
                        <?= e($otherUser['username']) ?>
                    </a>
                </div>
            </div>

            <?= renderFlash('messages') ?>

            <div class="card-body messages-area flex-grow-1 overflow-auto d-flex flex-column gap-2"
                 id="messagesArea">
                <?php if (empty($messages)): ?>
                <p class="text-muted text-center small my-auto">
                    No hay mensajes aún. ¡Envía el primero!
                </p>
                <?php else: ?>
                    <?php foreach ($messages as $msg):
                        $isMine = (int)$msg['sender_id'] === $currentId;
                    ?>
                    <div class="d-flex <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div class="msg-bubble <?= $isMine ? 'msg-mine' : 'msg-other' ?>">
                            <div><?= nl2br(e($msg['content'])) ?></div>
                            <div class="msg-time"><?= e(timeAgo($msg['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (($pag['totalPages'] ?? 1) > 1): ?>
            <div class="px-3 py-2 border-top bg-light">
                <nav aria-label="Paginación del chat">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php if ($pag['page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link"
                               href="<?= url('messages/' . (int)$otherUser['id'] . '?page=' . ($pag['page'] - 1)) ?>">
                                Anteriores
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="page-item disabled">
                            <span class="page-link">
                                <?= (int)$pag['page'] ?> / <?= (int)$pag['totalPages'] ?>
                            </span>
                        </li>
                        <?php if ($pag['page'] < $pag['totalPages']): ?>
                        <li class="page-item">
                            <a class="page-link"
                               href="<?= url('messages/' . (int)$otherUser['id'] . '?page=' . ($pag['page'] + 1)) ?>">
                                Siguientes
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

            <div class="card-footer bg-white">
                <form method="POST" action="<?= url('messages/send') ?>"
                      class="message-form d-flex gap-2"
                      data-receiver-id="<?= (int)$otherUser['id'] ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="receiver_id" value="<?= (int)$otherUser['id'] ?>">
                    <input type="text" name="content" class="form-control"
                           placeholder="Escribe un mensaje…" maxlength="1000"
                           autocomplete="off" required>
                    <button type="submit" class="btn btn-primary flex-shrink-0">
                        <i class="bi bi-send"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const RECEIVER_ID = <?= (int)$otherUser['id'] ?>;
    const WS_URL      = <?= json_encode($wsUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const WS_TOKEN    = <?= json_encode($wsToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<script src="<?= BASE_URL ?>/js/chat.js"></script>
<script>
(function() {
    const area = document.querySelector('.messages-area');
    if (area) area.scrollTop = area.scrollHeight;
})();
</script>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
