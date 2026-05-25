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

        $endpoint = trim($payload['endpoint'] ?? '');
        $p256dh   = trim($payload['keys']['p256dh'] ?? '');
        $auth     = trim($payload['keys']['auth'] ?? '');

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            $this->json(['success' => false, 'error' => 'Suscripción incompleta.'], 400);
        }

        $userId = (int)currentUser()['id'];
        $this->pushModel->save($userId, $endpoint, $p256dh, $auth);

        $this->json(['success' => true]);
    }

    public function unsubscribe(): void
    {
        verifyCsrf();

        $payload = $this->readJsonBody();
        $auth    = trim($payload['auth'] ?? '');

        if ($auth === '') {
            $this->json(['success' => false, 'error' => 'Token auth requerido.'], 400);
        }

        $userId = (int)currentUser()['id'];
        $this->pushModel->delete($userId, $auth);

        $this->json(['success' => true]);
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

    private function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
