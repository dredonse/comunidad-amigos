<?php
$pageTitle = 'Crear evento';
$minDateTime = date('Y-m-d\TH:i');
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 mb-4">
            <i class="bi bi-calendar-plus me-2"></i> Crear evento
        </h1>

        <?= renderFlash('events') ?>

        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="<?= url('events/store') ?>"
                      enctype="multipart/form-data" novalidate>
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="title">Título *</label>
                        <input type="text" id="title" name="title" class="form-control"
                               minlength="3" maxlength="200" required
                               value="<?= e($_POST['title'] ?? '') ?>"
                               placeholder="Nombre del evento">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="description">Descripción</label>
                        <textarea id="description" name="description" class="form-control"
                                  rows="4" maxlength="2000"
                                  placeholder="Detalles del evento (opcional)"><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="event_date">Fecha y hora *</label>
                        <input type="datetime-local" id="event_date" name="event_date"
                               class="form-control" required min="<?= e($minDateTime) ?>"
                               value="<?= e($_POST['event_date'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="location">Ubicación</label>
                        <input type="text" id="location" name="location" class="form-control"
                               maxlength="255" value="<?= e($_POST['location'] ?? '') ?>"
                               placeholder="Lugar del evento (opcional)">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="image">Imagen</label>
                        <input type="file" id="image" name="image" class="form-control"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text">JPG, PNG, WebP o GIF. Máximo 5 MB.</div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Guardar evento
                        </button>
                        <a href="<?= url('events') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
