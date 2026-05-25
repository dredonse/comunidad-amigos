<?php
$pageTitle = 'Crear cuenta';
$errors    = $_SESSION['form_errors'] ?? [];
$formData  = $_SESSION['form_data']   ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-person-plus-fill text-primary" style="font-size:2.5rem"></i>
                    <h1 class="h4 mt-2 fw-bold">Crear cuenta</h1>
                    <p class="text-muted small">Únete a la comunidad</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('register') ?>" novalidate>
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="username">Nombre de usuario</label>
                        <input type="text" id="username" name="username" class="form-control"
                               value="<?= e($formData['username'] ?? '') ?>"
                               placeholder="john_doe" required autofocus
                               pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50">
                        <div class="form-text">Solo letras, números y guiones bajos.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($formData['email'] ?? '') ?>"
                               placeholder="tu@email.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password">Contraseña</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password"
                                   class="form-control" placeholder="Mínimo 8 caracteres"
                                   minlength="8" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="progress mt-1" style="height:4px">
                            <div class="progress-bar" id="pwdStrength" style="width:0%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password_confirm">Repetir contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm"
                               class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-check me-1"></i> Registrarme
                        </button>
                    </div>
                </form>

                <hr class="my-3">
                <p class="text-center text-muted mb-0">
                    ¿Ya tienes cuenta?
                    <a href="<?= url('login') ?>" class="text-decoration-none fw-semibold">Inicia sesión</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle contraseña
document.getElementById('togglePwd').addEventListener('click', function() {
    const pwd  = document.getElementById('password');
    const icon = this.querySelector('i');
    pwd.type   = pwd.type === 'password' ? 'text' : 'password';
    icon.className = pwd.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
});

// Medidor de fortaleza de contraseña
document.getElementById('password').addEventListener('input', function() {
    const val = this.value;
    const bar = document.getElementById('pwdStrength');
    let score = 0;
    if (val.length >= 8)                  score++;
    if (/[A-Z]/.test(val))               score++;
    if (/[0-9]/.test(val))               score++;
    if (/[^a-zA-Z0-9]/.test(val))        score++;

    const colors = ['', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];
    bar.style.width = (score * 25) + '%';
    bar.className = 'progress-bar ' + (colors[score] || '');
});
</script>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
