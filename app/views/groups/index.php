<?php
$pageTitle = 'Grupos';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>Grupos</h1>
    <a href="<?= url('groups/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Crear grupo
    </a>
</div>

<?= renderFlash('groups') ?>

<?php if (empty($groups)): ?>
<div class="card text-center py-5">
    <div class="card-body">
        <i class="bi bi-people text-muted" style="font-size:3rem"></i>
        <p class="text-muted mt-3 mb-3">Aún no hay grupos. Crea el primero para tu comunidad.</p>
        <a href="<?= url('groups/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Crear grupo
        </a>
    </div>
</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($groups as $g):
        $desc = $g['description'] ?? '';
        $snippet = mb_strlen($desc) > 80 ? mb_substr($desc, 0, 80) . '…' : $desc;
    ?>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
                <div class="d-flex gap-3 mb-2">
                    <img src="<?= groupAvatarUrl($g['avatar'] ?? 'default.png') ?>"
                         class="rounded-circle flex-shrink-0" width="56" height="56" alt=""
                         onerror="this.src='<?= url('img/default.svg') ?>'">
                    <div class="min-width-0">
                        <h2 class="h6 mb-1">
                            <a href="<?= url('groups/' . (int)$g['id']) ?>"
                               class="text-decoration-none stretched-link">
                                <?= e($g['name']) ?>
                            </a>
                        </h2>
                        <small class="text-muted">
                            <i class="bi bi-person me-1"></i><?= (int)$g['member_count'] ?> miembros
                        </small>
                    </div>
                </div>
                <?php if ($snippet !== ''): ?>
                <p class="text-muted small flex-grow-1"><?= e($snippet) ?></p>
                <?php endif; ?>
                <div class="d-flex gap-2 flex-wrap position-relative" style="z-index:1">
                    <span class="badge <?= $g['privacy'] === 'private' ? 'bg-secondary' : 'bg-info text-dark' ?>">
                        <?= $g['privacy'] === 'private' ? 'Privado' : 'Público' ?>
                    </span>
                    <?php if ($g['is_member']): ?>
                    <span class="badge bg-success">Miembro</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (($pag['totalPages'] ?? 1) > 1): ?>
<nav class="mt-4" aria-label="Paginación de grupos">
    <ul class="pagination justify-content-center">
        <?php if ($pag['page'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('groups?page=' . ($pag['page'] - 1)) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('groups?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($pag['page'] < $pag['totalPages']): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('groups?page=' . ($pag['page'] + 1)) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
