<?php
$pageTitle = 'Eventos';
$monthsShort = [
    1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
    7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
];
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-calendar-event me-2"></i>Eventos</h1>
    <a href="<?= url('events/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Crear evento
    </a>
</div>

<?= renderFlash('events') ?>

<?php if (empty($events)): ?>
<div class="card text-center py-5">
    <div class="card-body">
        <i class="bi bi-calendar-x text-muted" style="font-size:3rem"></i>
        <p class="text-muted mt-3 mb-3">Aún no hay eventos programados.</p>
        <a href="<?= url('events/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Crear el primero
        </a>
    </div>
</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($events as $event):
        $ts = strtotime($event['event_date']);
        $day = date('j', $ts);
        $month = $monthsShort[(int)date('n', $ts)] ?? date('M', $ts);
        $year = date('Y', $ts);
        $time = date('H:i', $ts);
        $status = $event['user_status'] ?? null;
    ?>
    <div class="col">
        <div class="card event-card h-100">
            <?php if (!empty($event['image'])): ?>
            <img src="<?= url('uploads/events/' . $event['image']) ?>"
                 class="card-img-top" alt="<?= e($event['title']) ?>"
                 style="height:160px;object-fit:cover">
            <?php else: ?>
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center text-muted"
                 style="height:160px">
                <i class="bi bi-calendar-event" style="font-size:3rem"></i>
            </div>
            <?php endif; ?>

            <div class="card-body">
                <div class="d-flex gap-3 mb-2">
                    <div class="event-date flex-shrink-0">
                        <div class="day"><?= (int)$day ?></div>
                        <div class="month"><?= e($month) ?></div>
                    </div>
                    <div class="min-width-0">
                        <small class="text-muted"><?= e($time) ?> · <?= e($year) ?></small>
                        <h2 class="h6 mb-1">
                            <a href="<?= url('events/' . (int)$event['id']) ?>"
                               class="text-decoration-none text-dark stretched-link">
                                <?= e($event['title']) ?>
                            </a>
                        </h2>
                        <?php if (!empty($event['location'])): ?>
                        <small class="text-muted d-block">
                            <i class="bi bi-geo-alt"></i> <?= e($event['location']) ?>
                        </small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 position-relative"
                     style="z-index:1">
                    <small class="text-muted">
                        <i class="bi bi-people me-1"></i>
                        <?= (int)$event['going_count'] ?> confirmados
                    </small>
                    <?php if ($status): ?>
                    <?php
                    $badgeClass = match ($status) {
                        'going'      => 'bg-success',
                        'maybe'      => 'bg-warning text-dark',
                        'not_going'  => 'bg-danger',
                        default      => 'bg-secondary',
                    };
                    $badgeLabel = match ($status) {
                        'going'      => 'Voy',
                        'maybe'      => 'Quizás',
                        'not_going'  => 'No voy',
                        default      => $status,
                    };
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= e($badgeLabel) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (($pag['totalPages'] ?? 1) > 1): ?>
<nav class="mt-4" aria-label="Paginación de eventos">
    <ul class="pagination justify-content-center">
        <?php if ($pag['page'] > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('events?page=' . ($pag['page'] - 1)) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $pag['totalPages']; $i++): ?>
        <li class="page-item <?= $i === $pag['page'] ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('events?page=' . $i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($pag['page'] < $pag['totalPages']): ?>
        <li class="page-item">
            <a class="page-link" href="<?= url('events?page=' . ($pag['page'] + 1)) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
