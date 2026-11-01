<?php

declare(strict_types=1);

class EventController
{
    private EventModel $eventModel;

    public function __construct()
    {
        requireLogin();
        $this->eventModel = new EventModel();
    }

    public function index(): void
    {
        $userId  = (int)currentUser()['id'];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 9;

        $total = $this->eventModel->countAll();
        $pag   = paginate($total, $perPage, $page);

        $events = $this->eventModel->getAll(
            $userId,
            $pag['perPage'],
            $pag['offset']
        );

        $mapEvents = $this->eventModel->getAllWithCoordinates();

        require_once VIEWS_PATH . '/events/index.php';
    }

    public function show(int $id): void
    {
        $user  = currentUser();
        $event = $this->eventModel->findById($id, (int)$user['id']);

        if (!$event) {
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            return;
        }

        $goingAttendees = $this->eventModel->getAttendees($id, 'going');
        $maybeAttendees = $this->eventModel->getAttendees($id, 'maybe');
        $isCreator      = $this->eventModel->isCreator($id, (int)$user['id']);
        $isAdmin        = ($user['role'] ?? '') === 'admin';

        require_once VIEWS_PATH . '/events/show.php';
    }

    public function create(): void
    {
        require_once VIEWS_PATH . '/events/create.php';
    }

    public function store(): void
    {
        verifyCsrf();

        $user = currentUser();

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDateRaw = $_POST['event_date'] ?? '';
        $location    = trim($_POST['location'] ?? '');
        $latitude    = $this->parseCoordinate($_POST['latitude'] ?? null, true);
        $longitude   = $this->parseCoordinate($_POST['longitude'] ?? null, false);

        if ($latitude === false || $longitude === false) {
            flash('events', 'Las coordenadas del mapa no son válidas.', 'danger');
            redirect('/events/create');
        }

        if (mb_strlen($title) < 3 || mb_strlen($title) > 200) {
            flash('events', 'El título debe tener entre 3 y 200 caracteres.', 'danger');
            redirect('/events/create');
        }

        if (mb_strlen($description) > 2000) {
            flash('events', 'La descripción no puede superar 2000 caracteres.', 'danger');
            redirect('/events/create');
        }

        if (mb_strlen($location) > 255) {
            flash('events', 'La ubicación no puede superar 255 caracteres.', 'danger');
            redirect('/events/create');
        }

        $eventDate = $this->parseEventDate($eventDateRaw);
        if ($eventDate === null) {
            flash('events', 'La fecha del evento no es válida.', 'danger');
            redirect('/events/create');
        }

        if (strtotime($eventDate) <= time()) {
            flash('events', 'La fecha del evento debe ser futura.', 'danger');
            redirect('/events/create');
        }

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImage($_FILES['image'], 'events');
            if ($image === null) {
                flash('events', 'La imagen no es válida. Usa JPG, PNG, WebP o GIF (máx. 5 MB).', 'danger');
                redirect('/events/create');
            }
        }

        $eventId = $this->eventModel->create(
            (int)$user['id'],
            $title,
            $description !== '' ? $description : null,
            $eventDate,
            $location !== '' ? $location : null,
            $latitude,
            $longitude,
            $image
        );

        flash('events', 'Evento creado correctamente.');
        redirect('/events/' . $eventId);
    }

    public function attend(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $eventId = (int)($_POST['event_id'] ?? 0);
        $status  = $_POST['status'] ?? '';

        if ($eventId < 1 || !$this->eventModel->findById($eventId, (int)$user['id'])) {
            flash('events', 'Evento no encontrado.', 'danger');
            redirect('/events');
        }

        if (!in_array($status, ['going', 'maybe', 'not_going'], true)) {
            flash('events', 'Estado de asistencia no válido.', 'danger');
            redirect('/events/' . $eventId);
        }

        $this->eventModel->attend($eventId, (int)$user['id'], $status);
        flash('events', 'Asistencia actualizada.');
        redirect('/events/' . $eventId);
    }

    public function cancel(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $eventId = (int)($_POST['event_id'] ?? 0);

        if ($eventId < 1) {
            flash('events', 'Evento no válido.', 'danger');
            redirect('/events');
        }

        $this->eventModel->cancelAttendance($eventId, (int)$user['id']);
        flash('events', 'Asistencia cancelada.');
        redirect('/events/' . $eventId);
    }

    public function delete(): void
    {
        verifyCsrf();

        $user    = currentUser();
        $eventId = (int)($_POST['event_id'] ?? 0);

        if ($eventId < 1) {
            flash('events', 'Evento no válido.', 'danger');
            redirect('/events');
        }

        $event = $this->eventModel->findById($eventId, (int)$user['id']);
        if (!$event) {
            flash('events', 'Evento no encontrado.', 'danger');
            redirect('/events');
        }

        $isCreator = $this->eventModel->isCreator($eventId, (int)$user['id']);
        $isAdmin   = ($user['role'] ?? '') === 'admin';

        if (!$isCreator && !$isAdmin) {
            flash('events', 'No tienes permiso para eliminar este evento.', 'danger');
            redirect('/events/' . $eventId);
        }

        $this->eventModel->delete($eventId, (int)$user['id'], $isAdmin);
        deleteUpload('events', $event['image'] ?? null);

        flash('events', 'Evento eliminado.');
        redirect('/events');
    }

    /**
     * @return float|null Coordenada válida, null si vacía, false si inválida.
     */
    private function parseCoordinate(mixed $value, bool $isLatitude): float|null|false
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        if (!is_numeric($raw)) {
            return false;
        }

        $coord = (float)$raw;

        if ($isLatitude) {
            if ($coord < -90 || $coord > 90) {
                return false;
            }
        } elseif ($coord < -180 || $coord > 180) {
            return false;
        }

        return $coord;
    }

    private function parseEventDate(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw);
        if ($dt === false) {
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $raw);
        }
        if ($dt === false) {
            $ts = strtotime($raw);
            if ($ts === false) {
                return null;
            }
            return date('Y-m-d H:i:s', $ts);
        }

        return $dt->format('Y-m-d H:i:s');
    }
}
