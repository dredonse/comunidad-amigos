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
                        <div class="form-text">Haz clic en el mapa para fijar coordenadas o escribe la ubicación a mano.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ubicación en el mapa</label>
                        <div id="event-create-map" class="event-map border"
                             style="height:350px;border-radius:0.375rem"></div>
                        <p id="event-coords-display" class="small text-muted mt-2 mb-0">
                            Sin coordenadas seleccionadas.
                        </p>
                        <input type="hidden" id="latitude" name="latitude"
                               value="<?= e($_POST['latitude'] ?? '') ?>">
                        <input type="hidden" id="longitude" name="longitude"
                               value="<?= e($_POST['longitude'] ?? '') ?>">
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>
<script>
(function () {
    const defaultCenter = [40.416, -3.703];
    const defaultZoom = 6;
    const mapEl = document.getElementById('event-create-map');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const locationInput = document.getElementById('location');
    const coordsDisplay = document.getElementById('event-coords-display');

    const map = L.map(mapEl).setView(defaultCenter, defaultZoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    let marker = null;
    let geocodeTimer = null;

    function updateCoordsDisplay(lat, lng) {
        coordsDisplay.textContent = 'Coordenadas: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
    }

    function reverseGeocode(lat, lng) {
        const url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat='
            + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);

        fetch(url, {
            headers: { 'Accept': 'application/json', 'Accept-Language': 'es' }
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.display_name) {
                    locationInput.value = data.display_name;
                }
            })
            .catch(function () { /* ignorar errores de red */ });
    }

    function scheduleGeocode(lat, lng) {
        clearTimeout(geocodeTimer);
        geocodeTimer = setTimeout(function () {
            reverseGeocode(lat, lng);
        }, 500);
    }

    function setMarker(lat, lng, runGeocode) {
        latInput.value = String(lat);
        lngInput.value = String(lng);
        updateCoordsDisplay(lat, lng);

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }

        if (runGeocode) {
            scheduleGeocode(lat, lng);
        }
    }

    map.on('click', function (e) {
        setMarker(e.latlng.lat, e.latlng.lng, true);
    });

    const savedLat = parseFloat(latInput.value);
    const savedLng = parseFloat(lngInput.value);
    if (!Number.isNaN(savedLat) && !Number.isNaN(savedLng)) {
        setMarker(savedLat, savedLng, false);
        map.setView([savedLat, savedLng], 12);
    }
})();
</script>

<?php require_once VIEWS_PATH . '/layout/footer.php'; ?>
