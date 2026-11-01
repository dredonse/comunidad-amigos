<?php
$pageTitle = 'Gestión de eventos';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">
        <i class="bi bi-calendar-event me-2"></i>Gestión de eventos
    </h1>
    <a href="<?= url('admin') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver al panel
    </a>
</div>

<?= renderFlash('admin') ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Título</th>
                    <th>Creador</th>
                    <th>Fecha del evento</th>
                    <th>Ubicación</th>
                    <th>Creado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No hay eventos registrados.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($events as $ev): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($ev['title']) ?></td>
                        <td>
                            <a href="<?= url('profile/' . (int)$ev['creator_id']) ?>"
                               class="text-decoration-none">
                                <?= e($ev['creator_username']) ?>
                            </a>
                        </td>
                        <td class="small text-nowrap">
                            <?= e(date('d/m/Y H:i', strtotime($ev['event_date']))) ?>
                        </td>
                        <td class="small text-muted">
                            <?= !empty($ev['location']) ? e($ev['location']) : '—' ?>
                        </td>
                        <td class="text-muted small">
                            <?= e(timeAgo($ev['created_at'])) ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="<?= url('admin/event/delete') ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        data-confirm="¿Eliminar este evento?">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pag['totalPages'] ?? 1) > 1): ?>
<nav class="mt-4" aria-label="Paginación de eventos">
    <ul class="pagination justify-content-center">
        <?php if ($pag['page'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/events?page=' . ($pag['page'] - 1)) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('admin/events?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($pag['page'] < $pag['totalPages']): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('admin/events?page=' . ($pag['page'] + 1)) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
