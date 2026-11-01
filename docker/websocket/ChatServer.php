<?php

declare(strict_types=1);

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Servidor de chat en tiempo real.
 *
 * Solo EMPUJA mensajes del servidor al navegador. Los mensajes se envían siempre por HTTP
 * (MessageController: sesión, CSRF, validación, notificación y push) y la web avisa a este
 * proceso por un canal interno (ver server.php) para que los entregue en vivo.
 *
 * Autenticación: el navegador presenta un token firmado por la web con HMAC-SHA256 y
 * APP_SECRET (ver wsToken() en app/helpers/functions.php). El user_id sale del token
 * verificado, nunca de un dato que el cliente pueda inventar.
 */
class ChatServer implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface, array{userId: int|null}> */
    private \SplObjectStorage $clients;

    /** @var array<int, \SplObjectStorage<ConnectionInterface, null>> user_id → conexiones (varias pestañas) */
    private array $userConnections = [];

    private string $secret;

    /** @var string[] */
    private array $allowedOrigins;

    private ?PDO $db = null;

    public function __construct(string $secret, array $allowedOrigins)
    {
        if ($secret === '') {
            throw new RuntimeException('APP_SECRET no está definido: el servidor WebSocket no puede verificar tokens.');
        }
        $this->clients        = new \SplObjectStorage();
        $this->secret         = $secret;
        $this->allowedOrigins = $allowedOrigins;
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        // Los navegadores no aplican la política del mismo origen a los WebSockets:
        // se comprueba Origin para que otra web no pueda abrir conexiones (CSWSH).
        $origin = $conn->httpRequest->getHeaderLine('Origin');
        if ($this->allowedOrigins !== [] && !in_array($origin, $this->allowedOrigins, true)) {
            $conn->close();

            return;
        }

        $this->clients->attach($conn, ['userId' => null]);
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (!$this->clients->contains($from)) {
            return;
        }

        $data = json_decode((string)$msg, true);
        if (!is_array($data) || ($data['type'] ?? '') !== 'auth') {
            $this->send($from, ['type' => 'error', 'message' => 'Solo se acepta el mensaje auth; los mensajes se envían por HTTP.']);

            return;
        }

        $userId = $this->verifyToken((string)($data['token'] ?? ''));
        if ($userId === null || !$this->userIsActive($userId)) {
            $this->send($from, ['type' => 'auth_error']);
            $from->close();

            return;
        }

        $this->clients[$from] = ['userId' => $userId];
        $this->userConnections[$userId] ??= new \SplObjectStorage();
        $this->userConnections[$userId]->attach($from);

        $this->send($from, ['type' => 'auth_ok']);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        if (!$this->clients->contains($conn)) {
            return;
        }

        $userId = $this->clients[$conn]['userId'];
        if ($userId !== null && isset($this->userConnections[$userId])) {
            $this->userConnections[$userId]->detach($conn);
            if (count($this->userConnections[$userId]) === 0) {
                unset($this->userConnections[$userId]);
            }
        }

        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        error_log('ChatServer: ' . $e->getMessage());
        $conn->close();
    }

    /**
     * Entrega en vivo un mensaje ya guardado por la web (llamado desde el canal interno).
     *
     * @param array{receiver_id?: int, sender_id?: int, message_id?: int, content?: string, created_at?: string} $message
     */
    public function deliver(array $message): void
    {
        $receiverId = (int)($message['receiver_id'] ?? 0);
        if ($receiverId < 1 || !isset($this->userConnections[$receiverId])) {
            return;
        }

        $payload = [
            'type'       => 'message',
            'message_id' => (int)($message['message_id'] ?? 0),
            'sender_id'  => (int)($message['sender_id'] ?? 0),
            'content'    => (string)($message['content'] ?? ''),
            'created_at' => (string)($message['created_at'] ?? ''),
        ];

        foreach ($this->userConnections[$receiverId] as $conn) {
            $this->send($conn, $payload);
        }
    }

    private function verifyToken(string $token): ?int
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$userId, $expires, $signature] = $parts;

        if (!ctype_digit($userId) || !ctype_digit($expires) || (int)$expires < time()) {
            return null;
        }

        $expected = hash_hmac('sha256', 'ws|' . $userId . '.' . $expires, $this->secret);

        return hash_equals($expected, $signature) ? (int)$userId : null;
    }

    private function userIsActive(int $userId): bool
    {
        $stmt = $this->run('SELECT id FROM users WHERE id = ? AND active = 1 LIMIT 1', [$userId]);

        return (bool)$stmt->fetch();
    }

    private function send(ConnectionInterface $conn, array $data): void
    {
        $conn->send(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Ejecuta una consulta reconectando una vez si MariaDB cerró la conexión
     * (wait_timeout): este proceso vive días, no una petición.
     */
    private function run(string $sql, array $params): PDOStatement
    {
        try {
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);

            return $stmt;
        } catch (PDOException $e) {
            error_log('ChatServer: reconectando a la BD (' . $e->getMessage() . ')');
            $this->db = null;
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);

            return $stmt;
        }
    }

    private function db(): PDO
    {
        if ($this->db === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $name = getenv('DB_NAME') ?: 'comunidad';
            $user = getenv('DB_USER') ?: '';
            $pass = getenv('DB_PASS') ?: '';

            $this->db = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return $this->db;
    }
}
