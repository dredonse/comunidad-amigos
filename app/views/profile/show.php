<?php
$pageTitle   = $isOwner ? 'Mi perfil' : 'Perfil de ' . $profileUser['username'];
$currentUser = currentUser();
$profileUrl  = $isOwner ? url('profile') : url('profile/' . (int)$profileUser['id']);
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <?= renderFlash('profile') ?>

        <div class="card sidebar-card">
            <div class="card-body text-center">
                <?php if ($isOwner): ?>
                <img src="<?= avatarUrl($profileUser['avatar'] ?? 'default.png') ?>"
                     id="profileAvatar"
                     class="avatar rounded-circle mb-3 cursor-pointer"
                     width="120" height="120" alt="Tu avatar"
                     role="button"
                     data-bs-toggle="collapse"
                     data-bs-target="#editProfile"
                     aria-expanded="false"
                     aria-controls="editProfile"
                     onerror="this.onerror=null; this.src='/img/default.png'">
                <?php else: ?>
                <img src="<?= avatarUrl($profileUser['avatar'] ?? 'default.png') ?>"
                     class="avatar rounded-circle mb-3"
                     width="120" height="120" alt="<?= e($profileUser['username']) ?>"
                     onerror="this.onerror=null; this.src='/img/default.png'">
                <?php endif; ?>

                <h1 class="h4 mb-1"><?= e($profileUser['username']) ?></h1>
                <p class="text-muted small mb-3">
                    <?= !empty($profileUser['bio'])
                        ? nl2br(e($profileUser['bio']))
                        : '<em>Sin biografía</em>' ?>
                </p>

                <div class="d-flex justify-content-center gap-4 mb-3 small">
                    <div>
                        <strong class="d-block"><?= (int)$friendCount ?></strong>
                        <span class="text-muted">Amigos</span>
                    </div>
                    <div>
                        <strong class="d-block"><?= (int)$postCount ?></strong>
                        <span class="text-muted">Publicaciones</span>
                    </div>
                </div>

                <p class="text-muted small mb-3">
                    Miembro desde <?= e(date('d/m/Y', strtotime($profileUser['created_at']))) ?>
                </p>

                <?php if ($isOwner): ?>
                <button class="btn btn-outline-primary btn-sm" type="button"
                        data-bs-toggle="collapse" data-bs-target="#editProfile"
                        aria-expanded="false" aria-controls="editProfile">
                    <i class="bi bi-pencil me-1"></i> Editar perfil
                </button>

                <div class="collapse mt-3 text-start" id="editProfile">
                    <form method="POST" action="<?= url('profile/update') ?>"
                          enctype="multipart/form-data" class="border-top pt-3">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="bio">Biografía</label>
                            <textarea id="bio" name="bio" class="form-control" rows="3"
                                      maxlength="300"><?= e($profileUser['bio'] ?? '') ?></textarea>
                            <div class="form-text text-end">
                                <span id="bioCount">0</span>/300
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="avatarInput">Avatar</label>
                            <input type="file" id="avatarInput" name="avatar" class="form-control"
                                   accept="image/jpeg,image/png,image/webp,image/gif">
                            <img id="avatarPreview" src="<?= avatarUrl($profileUser['avatar'] ?? 'default.png') ?>"
                                 class="rounded-circle mt-2 d-none" width="80" height="80" alt="Vista previa">
                        </div>

                        <fieldset class="mb-3">
                            <legend class="form-label fw-semibold fs-6">Cambiar contraseña</legend>
                            <p class="text-muted small">Déjalo en blanco si no quieres cambiarla.</p>
                            <div class="mb-2">
                                <input type="password" name="current_password" class="form-control form-control-sm"
                                       placeholder="Contraseña actual" autocomplete="current-password">
                            </div>
                            <div class="mb-2">
                                <input type="password" name="new_password" class="form-control form-control-sm"
                                       placeholder="Nueva contraseña" autocomplete="new-password">
                            </div>
                            <div>
                                <input type="password" name="confirm_password" class="form-control form-control-sm"
                                       placeholder="Confirmar nueva contraseña" autocomplete="new-password">
                            </div>
                        </fieldset>

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check-lg me-1"></i> Guardar cambios
                        </button>
                    </form>
                </div>

                <?php else: ?>
                <div class="mt-2">
                    <?php if ($friendStatus === null): ?>
                    <form method="POST" action="<?= url('friends/request') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="receiver_id" value="<?= (int)$profileUser['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-person-plus me-1"></i> Añadir amigo
                        </button>
                    </form>

                    <?php elseif ($friendStatus['status'] === 'pending'
                        && (int)$friendStatus['sender_id'] === (int)$currentUser['id']): ?>
                    <span class="badge bg-secondary w-100 py-2">
                        <i class="bi bi-hourglass me-1"></i> Solicitud enviada
                    </span>

                    <?php elseif ($friendStatus['status'] === 'pending'): ?>
                    <div class="d-flex gap-2">
                        <form method="POST" action="<?= url('friends/accept') ?>" class="flex-grow-1">
                            <?= csrfField() ?>
                            <input type="hidden" name="sender_id" value="<?= (int)$profileUser['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm w-100">Aceptar</button>
                        </form>
                        <form method="POST" action="<?= url('friends/reject') ?>" class="flex-grow-1">
                            <?= csrfField() ?>
                            <input type="hidden" name="sender_id" value="<?= (int)$profileUser['id'] ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Rechazar</button>
                        </form>
                    </div>

                    <?php elseif ($friendStatus['status'] === 'accepted'): ?>
                    <div class="dropdown w-100">
                        <button class="btn btn-success btn-sm dropdown-toggle w-100" type="button"
                                data-bs-toggle="dropdown">
                            <i class="bi bi-person-check me-1"></i> Amigos
                        </button>
                        <ul class="dropdown-menu w-100">
                            <li>
                                <form method="POST" action="<?= url('friends/unfriend') ?>">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="other_user_id"
                                           value="<?= (int)$profileUser['id'] ?>">
                                    <button type="submit" class="dropdown-item text-danger"
                                            data-confirm="¿Eliminar a este amigo?">
                                        <i class="bi bi-person-x me-1"></i> Eliminar amigo
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <h2 class="h5 mb-3">
            Publicaciones de @<?= e($profileUser['username']) ?>
        </h2>

        <?php if ($isOwner): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="<?= url('post') ?>" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="d-flex gap-3">
                        <img src="<?= avatarUrl($profileUser['avatar'] ?? 'default.png') ?>"
                             class="avatar rounded-circle flex-shrink-0"
                             width="48" height="48" alt="Tu avatar"
                             onerror="this.onerror=null; this.src='/img/default.png'">
                        <div class="flex-grow-1">
                            <textarea name="content" class="form-control mb-2"
                                      rows="2" placeholder="¿Qué quieres compartir?"
                                      maxlength="2000" data-autoresize required></textarea>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <label class="btn btn-sm btn-outline-secondary mb-0">
                                    <i class="bi bi-image me-1"></i> Foto
                                    <input type="file" name="image" class="d-none"
                                           accept="image/jpeg,image/png,image/webp,image/gif">
                                </label>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-send me-1"></i> Publicar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
        <div class="card text-center py-5">
            <div class="card-body text-muted">
                <i class="bi bi-journal-text" style="font-size:2.5rem"></i>
                <p class="mt-3 mb-0">Aún no hay publicaciones.</p>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php require VIEWS_PATH . '/posts/_post.php'; ?>
            <?php endforeach; ?>

            <?php if (($pag['totalPages'] ?? 1) > 1): ?>
            <nav class="mt-3" aria-label="Paginación del perfil">
                <ul class="pagination justify-content-center">
                    <?php if ($pag['page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= e($profileUrl . '?page=' . ($pag['page'] - 1)) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
                    <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e($profileUrl . '?page=' . $i) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($pag['page'] < $pag['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= e($profileUrl . '?page=' . ($pag['page'] + 1)) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
                        data-bs-dismiss="modal" aria-label="Cerrar"></button>
                <img src="" id="modalImage" class="img-fluid rounded shadow" alt="Vista ampliada">
            </div>
        </div>
    </div>
</div>

<?php if ($isOwner): ?>
<script>
(function() {
    const bio = document.getElementById('bio');
    const bioCount = document.getElementById('bioCount');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');

    function updateBioCount() {
        if (!bio || !bioCount) return;
        bioCount.textContent = bio.value.length;
    }
    bio?.addEventListener('input', updateBioCount);
    updateBioCount();

    avatarInput?.addEventListener('change', function() {
        const file = this.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            avatarPreview.src = e.target?.result ?? '';
            avatarPreview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
})();
</script>
<?php endif; ?>

<script>
document.getElementById('imageModal')?.addEventListener('show.bs.modal', function(e) {
    const trigger = e.relatedTarget;
    const src = trigger?.dataset?.fullSrc || trigger?.src;
    if (src) document.getElementById('modalImage').src = src;
});
</script>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
