<?php
$pageTitle = 'Página no encontrada';
require_once VIEWS_PATH . '/layout/header.php';
?>
<div class="row justify-content-center text-center py-5">
    <div class="col-md-6">
        <i class="bi bi-compass text-muted" style="font-size:5rem"></i>
        <h1 class="display-4 fw-bold mt-3">404</h1>
        <p class="lead text-muted">Esta página no existe o fue eliminada.</p>
        <a href="<?= url(isLoggedIn() ? 'feed' : 'login') ?>" class="btn btn-primary">
            <i class="bi bi-house me-1"></i> Volver al inicio
        </a>
    </div>
</div>
<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
