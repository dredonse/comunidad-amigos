<?php
/** @var array $post */
/** @var array $currentUser */
$postId      = (int)$post['id'];
$isOwner     = (int)$post['user_id'] === (int)$currentUser['id'];
$isAdmin     = ($currentUser['role'] ?? '') === 'admin';
$canDelete   = $isOwner || $isAdmin;
$commentCount = (int)($post['comment_count'] ?? 0);
$likeCount   = (int)($post['like_count'] ?? 0);
$userLiked   = !empty($post['user_liked']);
?>
<article class="card post-card" id="post-<?= $postId ?>" data-post-id="<?= $postId ?>">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <img src="<?= avatarUrl($post['avatar'] ?? 'default.png') ?>"
                 class="avatar rounded-circle flex-shrink-0"
                 width="48" height="48" alt="<?= e($post['username']) ?>"
                 onerror="this.onerror=null; this.src='/img/default.svg'">

            <div class="flex-grow-1 min-width-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong class="d-block"><?= e($post['username']) ?></strong>
                        <small class="text-muted"><?= e(timeAgo($post['created_at'])) ?></small>
                    </div>

                    <?php if ($canDelete): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link text-muted p-0" type="button"
                                data-bs-toggle="dropdown" aria-label="Opciones">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form method="POST" action="<?= url('post/delete') ?>" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                    <button type="submit" class="dropdown-item text-danger"
                                            data-confirm="¿Eliminar esta publicación?">
                                        <i class="bi bi-trash me-1"></i> Eliminar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

                <p class="mt-2 mb-2 text-break"><?= nl2br(e($post['content'])) ?></p>

                <?php if (!empty($post['image'])): ?>
                <img src="<?= url('uploads/posts/' . $post['image']) ?>"
                     class="post-image mb-2"
                     alt="Imagen de la publicación"
                     loading="lazy"
                     data-bs-toggle="modal"
                     data-bs-target="#imageModal"
                     data-full-src="<?= e(url('uploads/posts/' . $post['image'])) ?>">
                <?php endif; ?>

                <div class="post-actions d-flex gap-2 border-top pt-2 mt-2">
                    <?php if ($isOwner): ?>
                    <span class="btn btn-sm text-muted pe-none">
                        <i class="bi bi-heart"></i>
                        <span><?= $likeCount ?></span>
                        Me gusta
                    </span>
                    <?php else: ?>
                    <button type="button"
                            class="btn btn-sm btn-like <?= $userLiked ? 'liked' : '' ?>"
                            data-post-id="<?= $postId ?>">
                        <i class="bi <?= $userLiked ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                        <span class="like-count"><?= $likeCount ?></span>
                        Me gusta
                    </button>
                    <?php endif; ?>
                    <button type="button"
                            class="btn btn-link btn-sm text-decoration-none"
                            data-bs-toggle="collapse"
                            data-bs-target="#comments-panel-<?= $postId ?>"
                            aria-expanded="false"
                            aria-controls="comments-panel-<?= $postId ?>">
                        <i class="bi bi-chat"></i>
                        <span class="comment-count"><?= $commentCount ?></span>
                        Comentar
                    </button>
                </div>

                <div class="collapse" id="comments-panel-<?= $postId ?>">
                    <div class="comment-list mt-2" id="comments-<?= $postId ?>">
                        <?php foreach ($post['comments'] ?? [] as $c): ?>
                        <div class="comment-item d-flex gap-2">
                            <img src="<?= avatarUrl($c['avatar'] ?? 'default.png') ?>"
                                 class="rounded-circle flex-shrink-0"
                                 width="28" height="28" alt=""
                                 onerror="this.onerror=null; this.src='/img/default.svg'">
                            <div>
                                <strong><?= e($c['username']) ?></strong>
                                <span class="text-muted ms-1"><?= e($c['comment']) ?></span>
                                <span class="text-muted d-block" style="font-size:.75rem">
                                    <?= e(timeAgo($c['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($commentCount > 3): ?>
                    <p class="mb-2 mt-1">
                        <a href="#" class="small text-muted text-decoration-none"
                           onclick="return false;">
                            Ver más comentarios (<?= $commentCount ?>)
                        </a>
                    </p>
                    <?php endif; ?>

                    <?php if (!$isOwner): ?>
                    <form class="comment-form mt-2" data-post-id="<?= $postId ?>">
                        <?= csrfField() ?>
                        <div class="input-group input-group-sm">
                            <input type="text" name="comment" class="form-control"
                                   placeholder="Escribe un comentario..." maxlength="1000"
                                   autocomplete="off">
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
