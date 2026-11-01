<?php
$pageTitle = $group['name'];
$currentUser = currentUser();
$currentId = (int)$currentUser['id'];
$groupId = (int)$group['id'];
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <?= renderFlash('groups') ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex gap-3 flex-wrap">
                    <img src="<?= groupAvatarUrl($group['avatar'] ?? 'default.png') ?>"
                         class="rounded-circle" width="72" height="72" alt=""
                         onerror="this.src='<?= url('img/default.svg') ?>'">
                    <div>
                        <h1 class="h4 mb-1"><?= e($group['name']) ?></h1>
                        <?php if (!empty($group['description'])): ?>
                        <p class="text-muted mb-2"><?= nl2br(e($group['description'])) ?></p>
                        <?php endif; ?>
                        <div class="d-flex gap-2 flex-wrap small">
                            <span class="badge <?= $group['privacy'] === 'private' ? 'bg-secondary' : 'bg-info text-dark' ?>">
                                <?= $group['privacy'] === 'private' ? 'Privado' : 'Público' ?>
                            </span>
                            <span class="text-muted">
                                <i class="bi bi-people me-1"></i><?= (int)$group['member_count'] ?> miembros
                            </span>
                            <span class="text-muted">
                                por <?= e($group['creator_username']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$canViewPosts): ?>
        <div class="alert alert-info">
            <?= $isPending
                ? 'Tu solicitud está pendiente. Verás las publicaciones cuando un administrador la apruebe.'
                : 'Este grupo es privado. Solicita unirte para ver las publicaciones.' ?>
        </div>
        <?php else: ?>

        <?php if ($isMember): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="<?= url('groups/post') ?>" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <textarea name="content" class="form-control mb-2" rows="2"
                              placeholder="Escribe algo para el grupo…"
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
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
        <p class="text-muted text-center py-4">Aún no hay publicaciones en este grupo.</p>
        <?php else: ?>
            <?php foreach ($posts as $post):
                $postId = (int)$post['id'];
                $isPostOwner = (int)$post['user_id'] === $currentId;
                $canDeletePost = $isPostOwner || $isAdmin;
                $commentCount = (int)($post['comment_count'] ?? 0);
            ?>
            <article class="card post-card mb-3" id="gpost-<?= $postId ?>">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <img src="<?= avatarUrl($post['avatar'] ?? 'default.png') ?>"
                             class="avatar rounded-circle" width="48" height="48" alt=""
                             onerror="this.src='<?= url('img/default.svg') ?>'">
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="<?= url('profile/' . (int)$post['user_id']) ?>"
                                       class="fw-semibold text-decoration-none">
                                        <?= e($post['username']) ?>
                                    </a>
                                    <small class="text-muted d-block"><?= e(timeAgo($post['created_at'])) ?></small>
                                </div>
                                <?php if ($canDeletePost): ?>
                                <form method="POST" action="<?= url('groups/post/delete') ?>" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                            data-confirm="¿Eliminar esta publicación?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <p class="mt-2 mb-2 text-break"><?= nl2br(e($post['content'])) ?></p>
                            <?php if (!empty($post['image'])): ?>
                            <img src="<?= url('uploads/groups/' . $post['image']) ?>"
                                 class="post-image mb-2 rounded" alt="" loading="lazy">
                            <?php endif; ?>
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#gcomments-<?= $postId ?>">
                                <i class="bi bi-chat"></i> <?= $commentCount ?> comentarios
                            </button>
                            <div class="collapse mt-2" id="gcomments-<?= $postId ?>">
                                <div class="comment-list" id="group-comments-<?= $postId ?>">
                                    <?php foreach ($post['comments'] ?? [] as $c): ?>
                                    <div class="comment-item d-flex gap-2">
                                        <img src="<?= avatarUrl($c['avatar'] ?? 'default.png') ?>"
                                             class="rounded-circle" width="28" height="28" alt="">
                                        <div>
                                            <strong><?= e($c['username']) ?></strong>
                                            <span class="text-muted ms-1"><?= e($c['comment']) ?></span>
                                            <small class="text-muted d-block"><?= e(timeAgo($c['created_at'])) ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($isMember): ?>
                                <form class="group-comment-form mt-2" data-group-post-id="<?= $postId ?>">
                                    <?= csrfField() ?>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="comment" class="form-control"
                                               placeholder="Comentar…" maxlength="1000" autocomplete="off">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>

            <?php if (($pag['totalPages'] ?? 1) > 1): ?>
            <nav class="mt-3" aria-label="Paginación de posts del grupo">
                <ul class="pagination justify-content-center">
                    <?php if ($pag['page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('groups/' . $groupId . '?page=' . ($pag['page'] - 1)) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
                    <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('groups/' . $groupId . '?page=' . $i) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($pag['page'] < $pag['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('groups/' . $groupId . '?page=' . ($pag['page'] + 1)) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card sidebar-card">
            <div class="card-body d-grid gap-2">
                <?php if ($isPending): ?>
                <button type="button" class="btn btn-outline-secondary w-100" disabled>
                    <i class="bi bi-hourglass-split me-1"></i> Solicitud pendiente
                </button>
                <?php elseif (!$isMember): ?>
                <form method="POST" action="<?= url('groups/join') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus me-1"></i>
                        <?= $group['privacy'] === 'private' ? 'Solicitar unirse' : 'Unirse al grupo' ?>
                    </button>
                </form>
                <?php else: ?>
                    <?php if ($canLeave): ?>
                    <form method="POST" action="<?= url('groups/leave') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="group_id" value="<?= $groupId ?>">
                        <button type="submit" class="btn btn-outline-secondary w-100"
                                data-confirm="¿Salir de este grupo?">
                            <i class="bi bi-box-arrow-right me-1"></i> Salir del grupo
                        </button>
                    </form>
                    <?php elseif ($isAdmin): ?>
                    <p class="text-muted small mb-0 text-center">
                        Eres el único administrador; no puedes abandonar el grupo.
                    </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin && !empty($pendingRequests)): ?>
        <div class="card sidebar-card mt-3">
            <div class="card-header bg-white fw-semibold">
                Solicitudes pendientes (<?= count($pendingRequests) ?>)
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($pendingRequests as $req): ?>
                <li class="list-group-item d-flex align-items-center gap-2">
                    <img src="<?= avatarUrl($req['avatar'] ?? null) ?>"
                         class="rounded-circle" width="32" height="32" alt=""
                         onerror="this.src='<?= url('img/default.svg') ?>'">
                    <a href="<?= url('profile/' . (int)$req['user_id']) ?>"
                       class="text-decoration-none flex-grow-1">
                        <?= e($req['username']) ?>
                    </a>
                    <form method="POST" action="<?= url('groups/approve') ?>" class="m-0">
                        <?= csrfField() ?>
                        <input type="hidden" name="group_id" value="<?= $groupId ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$req['user_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success" title="Aprobar">
                            <i class="bi bi-check-lg"></i>
                        </button>
                    </form>
                    <form method="POST" action="<?= url('groups/reject') ?>" class="m-0">
                        <?= csrfField() ?>
                        <input type="hidden" name="group_id" value="<?= $groupId ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$req['user_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Rechazar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card sidebar-card mt-3">
            <div class="card-header bg-white fw-semibold">Miembros</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($members as $m): ?>
                <li class="list-group-item d-flex align-items-center gap-2">
                    <img src="<?= avatarUrl($m['avatar'] ?? 'default.png') ?>"
                         class="rounded-circle" width="32" height="32" alt=""
                         onerror="this.src='<?= url('img/default.svg') ?>'">
                    <a href="<?= url('profile/' . (int)$m['user_id']) ?>"
                       class="text-decoration-none flex-grow-1">
                        <?= e($m['username']) ?>
                    </a>
                    <?php if ($m['role'] === 'admin'): ?>
                    <span class="badge bg-primary">Admin</span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<p class="mt-2">
    <a href="<?= url('groups') ?>" class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Volver a grupos
    </a>
</p>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
