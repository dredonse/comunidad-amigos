<?php
$pageTitle = 'Amigos';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <?= renderFlash('friends') ?>

        <?php if (!empty($pendingReceived)): ?>
        <section class="mb-4">
            <h2 class="h5 mb-3">
                <i class="bi bi-inbox me-1"></i> Solicitudes recibidas
            </h2>
            <?php foreach ($pendingReceived as $request): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 flex-wrap">
                        <img src="<?= avatarUrl($request['avatar'] ?? 'default.png') ?>"
                             class="avatar rounded-circle flex-shrink-0"
                             width="56" height="56" alt="<?= e($request['username']) ?>"
                             onerror="this.onerror=null; this.src='/img/default.png'">
                        <div class="flex-grow-1 min-width-0">
                            <h3 class="h6 mb-1"><?= e($request['username']) ?></h3>
                            <p class="text-muted small mb-2">
                                <?= !empty($request['bio'])
                                    ? e($request['bio'])
                                    : 'Sin biografía' ?>
                            </p>
                            <small class="text-muted">
                                <?= e(timeAgo($request['created_at'])) ?>
                            </small>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <form method="POST" action="<?= url('friends/accept') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="sender_id"
                                       value="<?= (int)$request['sender_id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-lg me-1"></i> Aceptar
                                </button>
                            </form>
                            <form method="POST" action="<?= url('friends/reject') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="sender_id"
                                       value="<?= (int)$request['sender_id'] ?>">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-lg me-1"></i> Rechazar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <section>
            <h2 class="h5 mb-3">
                <i class="bi bi-people me-1"></i> Mis amigos
            </h2>

            <?php if (empty($friends)): ?>
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="bi bi-person-heart text-muted" style="font-size:3rem"></i>
                    <p class="text-muted mt-3 mb-0">
                        Aún no tienes amigos. Explora las sugerencias y envía tu primera solicitud.
                    </p>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($friends as $friend): ?>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex gap-3">
                            <img src="<?= avatarUrl($friend['avatar'] ?? 'default.png') ?>"
                                 class="avatar rounded-circle flex-shrink-0"
                                 width="48" height="48" alt="<?= e($friend['username']) ?>"
                                 onerror="this.onerror=null; this.src='/img/default.png'">
                            <div class="min-width-0">
                                <a href="<?= url('profile/' . (int)$friend['id']) ?>"
                                   class="fw-semibold text-decoration-none">
                                    <?= e($friend['username']) ?>
                                </a>
                                <p class="text-muted small mb-1 text-truncate">
                                    <?= !empty($friend['bio'])
                                        ? e($friend['bio'])
                                        : 'Sin biografía' ?>
                                </p>
                                <small class="text-muted">
                                    Amigos desde <?= e(timeAgo($friend['friends_since'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-4">
        <div class="card sidebar-card mb-4">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-send me-1"></i> Solicitudes enviadas
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($pendingSent)): ?>
                <li class="list-group-item text-muted small">
                    No tienes solicitudes pendientes enviadas.
                </li>
                <?php else: ?>
                    <?php foreach ($pendingSent as $sent): ?>
                    <li class="list-group-item d-flex align-items-center gap-2">
                        <img src="<?= avatarUrl($sent['avatar'] ?? 'default.png') ?>"
                             class="rounded-circle flex-shrink-0"
                             width="36" height="36" alt=""
                             onerror="this.onerror=null; this.src='/img/default.png'">
                        <span class="flex-grow-1 text-truncate"><?= e($sent['username']) ?></span>
                        <span class="badge bg-warning text-dark">Pendiente</span>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card sidebar-card">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-lightbulb me-1"></i> Sugerencias
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($suggestions)): ?>
                <li class="list-group-item text-muted small">
                    No hay sugerencias disponibles por ahora.
                </li>
                <?php else: ?>
                    <?php foreach ($suggestions as $user): ?>
                    <li class="list-group-item">
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= avatarUrl($user['avatar'] ?? 'default.png') ?>"
                                 class="rounded-circle flex-shrink-0"
                                 width="36" height="36" alt=""
                                 onerror="this.onerror=null; this.src='/img/default.png'">
                            <span class="flex-grow-1 text-truncate fw-semibold">
                                <?= e($user['username']) ?>
                            </span>
                            <form method="POST" action="<?= url('friends/request') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="receiver_id"
                                       value="<?= (int)$user['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-person-plus"></i> Añadir
                                </button>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
