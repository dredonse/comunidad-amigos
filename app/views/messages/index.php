<?php
$pageTitle = 'Mensajes';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-chat-dots me-2"></i>Mensajes</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
            data-bs-target="#newMessageModal">
        <i class="bi bi-pencil-square me-1"></i> Nuevo mensaje
    </button>
</div>

<?= renderFlash('messages') ?>

<?php if (empty($conversations)): ?>
<div class="card text-center py-5">
    <div class="card-body">
        <i class="bi bi-chat-left-text text-muted" style="font-size:3rem"></i>
        <p class="text-muted mt-3 mb-3">
            Aún no tienes conversaciones. Pulsa <strong>Nuevo mensaje</strong> para escribir
            a un amigo o visita <a href="<?= url('friends') ?>">Amigos</a>.
        </p>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                data-bs-target="#newMessageModal">
            <i class="bi bi-pencil-square me-1"></i> Nuevo mensaje
        </button>
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
                     onerror="this.onerror=null; this.src='/img/default.png'">
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

<div class="modal fade" id="newMessageModal" tabindex="-1"
     aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="newMessageModalLabel">Nueva conversación</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($friends)): ?>
                <p class="text-muted mb-0">
                    Aún no tienes amigos.
                    Ve a la sección <a href="<?= url('friends') ?>">Amigos</a> para añadir.
                </p>
                <?php else: ?>
                <input type="search" id="friendSearch" class="form-control mb-3"
                       placeholder="Buscar por nombre de usuario…" autocomplete="off">
                <div class="list-group list-group-flush" id="friendPickList">
                    <?php foreach ($friends as $friend): ?>
                    <div class="list-group-item friend-pick-row px-0 border-0 border-bottom"
                         data-username="<?= e(mb_strtolower($friend['username'])) ?>">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= avatarUrl($friend['avatar'] ?? 'default.png') ?>"
                                 class="rounded-circle flex-shrink-0"
                                 width="40" height="40" alt=""
                                 onerror="this.onerror=null; this.src='/img/default.png'">
                            <div class="flex-grow-1 min-width-0">
                                <strong><?= e($friend['username']) ?></strong>
                            </div>
                            <a href="<?= url('messages/' . (int)$friend['id']) ?>"
                               class="btn btn-sm btn-outline-primary flex-shrink-0">
                                Escribir
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p id="friendSearchEmpty" class="text-muted small mb-0 mt-2 d-none">
                    Ningún amigo coincide con la búsqueda.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const search = document.getElementById('friendSearch');
    if (!search) return;

    const rows = document.querySelectorAll('.friend-pick-row');
    const emptyHint = document.getElementById('friendSearchEmpty');

    search.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const name = row.dataset.username || '';
            const show = q === '' || name.includes(q);
            row.classList.toggle('d-none', !show);
            if (show) visible++;
        });

        if (emptyHint) {
            emptyHint.classList.toggle('d-none', visible > 0 || rows.length === 0);
        }
    });
})();
</script>

<?php if (($_GET['new'] ?? '') === '1'): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('newMessageModal');
    if (el) {
        new bootstrap.Modal(el).show();
    }
});
</script>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
