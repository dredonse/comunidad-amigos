<?php
$pageTitle = 'Crear grupo';
require_once VIEWS_PATH . '/layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="h3 mb-4"><i class="bi bi-people-fill me-2"></i>Crear grupo</h1>

        <?= renderFlash('groups') ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="<?= url('groups/store') ?>" enctype="multipart/form-data">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="name">Nombre *</label>
                        <input type="text" id="name" name="name" class="form-control"
                               minlength="3" maxlength="100" required
                               value="<?= e($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="description">Descripción</label>
                        <textarea id="description" name="description" class="form-control"
                                  rows="4" maxlength="1000"><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="privacy">Privacidad</label>
                        <select id="privacy" name="privacy" class="form-select">
                            <option value="public" <?= ($_POST['privacy'] ?? 'public') === 'public' ? 'selected' : '' ?>>
                                Público — cualquiera puede ver y unirse
                            </option>
                            <option value="private" <?= ($_POST['privacy'] ?? '') === 'private' ? 'selected' : '' ?>>
                                Privado — solo miembros ven las publicaciones
                            </option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="avatarInput">Avatar del grupo</label>
                        <input type="file" id="avatarInput" name="avatar" class="form-control"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <img id="avatarPreview" src="" alt="" class="rounded-circle mt-2 d-none"
                             width="80" height="80">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Crear grupo
                        </button>
                        <a href="<?= url('groups') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('avatarInput')?.addEventListener('change', function() {
    const file = this.files?.[0];
    const preview = document.getElementById('avatarPreview');
    if (!file || !preview) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target?.result ?? '';
        preview.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
});
</script>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
