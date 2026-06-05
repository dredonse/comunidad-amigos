<?php
$pageTitle   = 'Inicio';
$currentUser = currentUser();
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <?= renderFlash('feed') ?>

        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="<?= url('post') ?>" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="d-flex gap-3">
                        <img src="<?= avatarUrl($profile['avatar'] ?? 'default.png') ?>"
                             class="avatar rounded-circle flex-shrink-0"
                             width="48" height="48" alt="Tu avatar"
                             onerror="this.onerror=null; this.src='/img/default.png'">
                        <div class="flex-grow-1">
                            <textarea id="postContent" name="content" class="form-control mb-2"
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

        <div id="feed-container">
        <?php if (empty($posts)): ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="bi bi-newspaper text-muted" style="font-size:3rem"></i>
                <h2 class="h5 mt-3">Tu feed está vacío</h2>
                <p class="text-muted mb-3">
                    Publica algo para empezar o agrega amigos para ver sus publicaciones.
                </p>
                <a href="<?= url('friends') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-person-plus me-1"></i> Buscar amigos
                </a>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php require VIEWS_PATH . '/posts/_post.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card sidebar-card">
            <div class="card-body text-center">
                <img src="<?= avatarUrl($profile['avatar'] ?? 'default.png') ?>"
                     class="avatar rounded-circle mb-2"
                     width="72" height="72" alt="<?= e($profile['username']) ?>"
                     onerror="this.onerror=null; this.src='/img/default.png'">
                <h2 class="h6 mb-1"><?= e($profile['username']) ?></h2>
                <p class="text-muted small mb-3">
                    <?= !empty($profile['bio'])
                        ? nl2br(e($profile['bio']))
                        : '<em class="text-muted">Sin biografía aún.</em>' ?>
                </p>
                <a href="<?= url('profile') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-person me-1"></i> Ver perfil
                </a>
            </div>
        </div>

        <?php if (($pagination['totalPages'] ?? 1) > 1): ?>
        <nav class="card mt-3 sidebar-card" aria-label="Paginación del feed">
            <div class="card-body py-3">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php if ($pagination['page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('feed?page=' . ($pagination['page'] - 1)) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('feed?page=' . $i) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('feed?page=' . ($pagination['page'] + 1)) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <p class="text-muted text-center small mb-0 mt-2">
                    Página <?= (int)$pagination['page'] ?> de <?= (int)$pagination['totalPages'] ?>
                </p>
            </div>
        </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
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

<script>
document.getElementById('imageModal')?.addEventListener('show.bs.modal', function(e) {
    const trigger = e.relatedTarget;
    const src = trigger?.dataset?.fullSrc || trigger?.src;
    if (src) document.getElementById('modalImage').src = src;
});
</script>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
