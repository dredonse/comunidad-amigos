<?php
$pageTitle = 'Iniciar sesión';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-people-fill text-primary" style="font-size:2.5rem"></i>
                    <h1 class="h4 mt-2 fw-bold">Comunidad</h1>
                    <p class="text-muted small">Inicia sesión para continuar</p>
                </div>

                <?= renderFlash('auth') ?>

                <form method="POST" action="<?= url('login') ?>" novalidate>
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($_POST['email'] ?? '') ?>"
                               placeholder="tu@email.com" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password">Contraseña</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password"
                                   class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePwd"
                                    title="Mostrar/ocultar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                        </button>
                    </div>
                </form>

                <hr class="my-3">
                <p class="text-center text-muted mb-0">
                    ¿No tienes cuenta?
                    <a href="<?= url('register') ?>" class="text-decoration-none fw-semibold">Regístrate</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    const icon = this.querySelector('i');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
