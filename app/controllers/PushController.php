<?php

declare(strict_types=1);

class PushController
{
    private PushModel $pushModel;

    public function __construct()
    {
        requireLogin();
        $this->pushModel = new PushModel();
    }

    public function subscribe(): void
    {
        verifyCsrf();

        $payload = $this->readJsonBody();

        $endpoint = trim((string)($payload['endpoint'] ?? ''));
        $p256dh   = trim((string)($payload['keys']['p256dh'] ?? ''));
        $auth     = trim((string)($payload['keys']['auth'] ?? ''));

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            jsonResponse(['success' => false, 'error' => 'Suscripción incompleta.'], 400);
        }

        // El servidor hará peticiones a este endpoint: solo HTTPS y solo a servicios push
        // conocidos, para que nadie lo use para hacer peticiones a otras máquinas (SSRF).
        if (!$this->isKnownPushService($endpoint)
            || strlen($endpoint) > 1000 || strlen($p256dh) > 255 || strlen($auth) > 255) {
            jsonResponse(['success' => false, 'error' => 'Suscripción no válida.'], 400);
        }

        $userId = (int)currentUser()['id'];
        $this->pushModel->save($userId, $endpoint, $p256dh, $auth);

        jsonResponse(['success' => true]);
    }

    public function unsubscribe(): void
    {
        verifyCsrf();

        $payload = $this->readJsonBody();
        $auth    = trim((string)($payload['auth'] ?? ''));

        if ($auth === '') {
            jsonResponse(['success' => false, 'error' => 'Token auth requerido.'], 400);
        }

        $userId = (int)currentUser()['id'];
        $this->pushModel->delete($userId, $auth);

        jsonResponse(['success' => true]);
    }

    private function isKnownPushService(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }
        $host = strtolower($parts['host']);
        foreach ([
            'fcm.googleapis.com',               // Chrome, Edge, Opera
            'push.services.mozilla.com',        // Firefox
            'notify.windows.com',               // Edge (antiguo)
            'push.apple.com',                   // Safari
        ] as $service) {
            if ($host === $service || str_ends_with($host, '.' . $service)) {
                return true;
            }
        }

        return false;
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }
}
