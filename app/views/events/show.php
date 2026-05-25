<?php
$pageTitle = $event['title'];
$eventId   = (int)$event['id'];
$userStatus = $event['user_status'] ?? null;
$canDelete = $isCreator || $isAdmin;
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <?= renderFlash('events') ?>

        <?php if (!empty($event['image'])): ?>
        <img src="<?= url('uploads/events/' . $event['image']) ?>"
             class="img-fluid rounded shadow-sm mb-4 w-100"
             alt="<?= e($event['title']) ?>"
             style="max-height:400px;object-fit:cover">
        <?php endif; ?>

        <h1 class="h3 mb-2"><?= e($event['title']) ?></h1>

        <?php if (!empty($event['description'])): ?>
        <p class="text-muted mb-3"><?= nl2br(e($event['description'])) ?></p>
        <?php endif; ?>

        <ul class="list-unstyled mb-4">
            <li class="mb-2">
                <i class="bi bi-calendar3 me-2 text-primary"></i>
                <strong>Fecha:</strong>
                <?= e(date('d/m/Y H:i', strtotime($event['event_date']))) ?>
            </li>
            <?php if (!empty($event['location'])): ?>
            <li class="mb-2">
                <i class="bi bi-geo-alt me-2 text-primary"></i>
                <strong>Ubicación:</strong> <?= e($event['location']) ?>
            </li>
            <?php endif; ?>
            <li>
                <i class="bi bi-person me-2 text-primary"></i>
                <strong>Creado por:</strong>
                <a href="<?= url('profile/' . (int)$event['creator_id']) ?>">
                    <?= e($event['creator_username']) ?>
                </a>
            </li>
        </ul>

        <section class="mb-4">
            <h2 class="h6 mb-3">
                <i class="bi bi-people me-1"></i>
                Asistentes confirmados (<?= (int)$event['going_count'] ?>)
            </h2>
            <?php if (empty($goingAttendees)): ?>
            <p class="text-muted small">Nadie ha confirmado asistencia todavía.</p>
            <?php else: ?>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <?php foreach (array_slice($goingAttendees, 0, 12) as $att): ?>
                <a href="<?= url('profile/' . (int)$att['user_id']) ?>" title="<?= e($att['username']) ?>">
                    <img src="<?= avatarUrl($att['avatar'] ?? 'default.png') ?>"
                         class="rounded-circle border" width="40" height="40" alt=""
                         onerror="this.src='<?= url('img/default.svg') ?>'">
                </a>
                <?php endforeach; ?>
                <?php if (count($goingAttendees) > 12): ?>
                <span class="badge bg-secondary align-self-center">
                    +<?= count($goingAttendees) - 12 ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>

        <?php if ($canDelete): ?>
        <form method="POST" action="<?= url('events/delete') ?>" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm"
                    data-confirm="¿Eliminar este evento? Esta acción no se puede deshacer.">
                <i class="bi bi-trash me-1"></i> Eliminar evento
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card sidebar-card mb-4">
            <div class="card-header bg-white fw-semibold">¿Vas a ir?</div>
            <div class="card-body d-grid gap-2">
                <?php
                $statuses = [
                    'going'     => ['label' => 'Voy', 'class' => 'btn-success'],
                    'maybe'     => ['label' => 'Quizás', 'class' => 'btn-warning'],
                    'not_going' => ['label' => 'No voy', 'class' => 'btn-danger'],
                ];
                foreach ($statuses as $status => $meta):
                    $active = $userStatus === $status;
                    $btnClass = $active
                        ? $meta['class']
                        : 'btn-outline-secondary';
                ?>
                <form method="POST" action="<?= url('events/attend') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <input type="hidden" name="status" value="<?= e($status) ?>">
                    <button type="submit" class="btn <?= $btnClass ?> w-100">
                        <?= e($meta['label']) ?>
                    </button>
                </form>
                <?php endforeach; ?>

                <?php if ($userStatus !== null): ?>
                <form method="POST" action="<?= url('events/cancel') ?>" class="mt-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <button type="submit" class="btn btn-link btn-sm text-muted w-100">
                        Cancelar asistencia
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card sidebar-card mb-4">
            <div class="card-header bg-white fw-semibold">
                Confirmados (<?= count($goingAttendees) ?>)
            </div>
            <ul class="list-group list-group-flush">
                <?php if (empty($goingAttendees)): ?>
                <li class="list-group-item text-muted small">Sin asistentes.</li>
                <?php else: ?>
                    <?php foreach ($goingAttendees as $att): ?>
                    <li class="list-group-item d-flex align-items-center gap-2">
                        <img src="<?= avatarUrl($att['avatar'] ?? 'default.png') ?>"
                             class="rounded-circle" width="32" height="32" alt=""
                             onerror="this.src='<?= url('img/default.svg') ?>'">
                        <a href="<?= url('profile/' . (int)$att['user_id']) ?>"
                           class="text-decoration-none">
                            <?= e($att['username']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <?php if (!empty($maybeAttendees)): ?>
        <div class="card sidebar-card">
            <div class="card-header bg-white fw-semibold">
                Quizás (<?= count($maybeAttendees) ?>)
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($maybeAttendees as $att): ?>
                <li class="list-group-item d-flex align-items-center gap-2">
                    <img src="<?= avatarUrl($att['avatar'] ?? 'default.png') ?>"
                         class="rounded-circle" width="32" height="32" alt=""
                         onerror="this.src='<?= url('img/default.svg') ?>'">
                    <a href="<?= url('profile/' . (int)$att['user_id']) ?>"
                       class="text-decoration-none">
                        <?= e($att['username']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<p class="mt-3">
    <a href="<?= url('events') ?>" class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Volver a eventos
    </a>
</p>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
