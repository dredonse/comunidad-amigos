<?php
/** @var array $conversations */
/** @var int|null $activeOtherId */
/** @var bool $compact */
$compact = $compact ?? false;
?>
<?php if (empty($conversations)): ?>
<p class="text-muted small mb-0 px-2">Sin conversaciones.</p>
<?php else: ?>
<ul class="list-group list-group-flush <?= $compact ? 'messages-sidebar-list' : '' ?>">
    <?php foreach ($conversations as $conv):
        $otherId = (int)$conv['other_id'];
        $isActive = isset($activeOtherId) && $activeOtherId === $otherId;
        $preview = mb_strlen($conv['last_message']) > 60
            ? mb_substr($conv['last_message'], 0, 60) . '…'
            : $conv['last_message'];
    ?>
    <li class="list-group-item <?= $isActive ? 'active' : '' ?> <?= $compact ? 'py-2' : '' ?>">
        <a href="<?= url('messages/' . $otherId) ?>"
           class="d-flex align-items-center gap-2 text-decoration-none <?= $isActive ? 'text-white' : 'text-body' ?>">
            <img src="<?= avatarUrl($conv['avatar'] ?? 'default.png') ?>"
                 class="rounded-circle flex-shrink-0"
                 width="<?= $compact ? 36 : 48 ?>"
                 height="<?= $compact ? 36 : 48 ?>" alt=""
                 onerror="this.onerror=null; this.src='/img/default.svg'">
            <div class="flex-grow-1 min-width-0">
                <div class="d-flex justify-content-between align-items-center gap-1">
                    <span class="fw-semibold text-truncate"><?= e($conv['username']) ?></span>
                    <?php if ((int)$conv['unread_count'] > 0): ?>
                    <span class="badge <?= $isActive ? 'bg-light text-primary' : 'bg-danger' ?>">
                        <?= (int)$conv['unread_count'] ?>
                    </span>
                    <?php endif; ?>
                </div>
                <small class="<?= $isActive ? 'text-white-50' : 'text-muted' ?> d-block text-truncate">
                    <?= e($preview) ?>
                </small>
                <?php if (!$compact): ?>
                <small class="<?= $isActive ? 'text-white-50' : 'text-muted' ?>">
                    <?= e(timeAgo($conv['last_at'])) ?>
                </small>
                <?php endif; ?>
            </div>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
