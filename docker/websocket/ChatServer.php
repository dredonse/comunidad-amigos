<?php

declare(strict_types=1);

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class ChatServer implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface> */
    private \SplObjectStorage $clients;

    /** @var array<int, ConnectionInterface> */
    private array $userConnections = [];

    /** @var array<int, int> resourceId => user_id */
    private array $connectionUsers = [];

    private PDO $db;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->db      = $this->createDatabase();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $conn->authenticated = false;
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode((string)$msg, true);
        if (!is_array($data) || empty($data['type'])) {
            return;
        }

        $type = (string)$data['type'];

        if ($type === 'auth') {
            $this->handleAuth($from, $data);

            return;
        }

        if ($type === 'message') {
            $this->handleMessage($from, $data);
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $resourceId = (int)$conn->resourceId;

        if (isset($this->connectionUsers[$resourceId])) {
            $userId = $this->connectionUsers[$resourceId];
            unset($this->connectionUsers[$resourceId]);

            if (isset($this->userConnections[$userId])
                && $this->userConnections[$userId] === $conn) {
                unset($this->userConnections[$userId]);
            }
        }

        $this->removeWsSession((string)$resourceId);
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        error_log('ChatServer: ' . $e->getMessage());
        $conn->close();
    }

    private function handleAuth(ConnectionInterface $conn, array $data): void
    {
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId < 1 || !$this->userExists($userId)) {
            $conn->send(json_encode(['type' => 'error', 'message' => 'Auth inválida']));

            return;
        }

        $username = trim((string)($data['username'] ?? ''));
        $avatar   = trim((string)($data['avatar'] ?? 'default.png'));

        $conn->authenticated = true;
        $conn->userId        = $userId;
        $conn->username      = $username !== '' ? $username : $this->fetchUsername($userId);
        $conn->avatar        = $avatar !== '' ? $avatar : 'default.png';

        $resourceId = (int)$conn->resourceId;
        $this->connectionUsers[$resourceId] = $userId;
        $this->userConnections[$userId]     = $conn;

        $this->saveWsSession($userId, (string)$resourceId);

        $conn->send(json_encode(['type' => 'auth_ok']));
    }

    private function handleMessage(ConnectionInterface $from, array $data): void
    {
        if (empty($from->authenticated) || empty($from->userId)) {
            $from->send(json_encode(['type' => 'error', 'message' => 'No autenticado']));

            return;
        }

        $senderId   = (int)$from->userId;
        $receiverId = (int)($data['receiver_id'] ?? 0);
        $content    = trim((string)($data['content'] ?? ''));

        if ($receiverId < 1 || $receiverId === $senderId) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Destinatario no válido']));

            return;
        }

        if (!$this->userExists($receiverId)) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Usuario no encontrado']));

            return;
        }

        if (strlen($content) < 1 || strlen($content) > 1000) {
            $from->send(json_encode(['type' => 'error', 'message' => 'Mensaje inválido']));

            return;
        }

        $messageId  = $this->insertMessage($senderId, $receiverId, $content);
        $createdAt  = $this->fetchMessageCreatedAt($messageId) ?? date('Y-m-d H:i:s');
        $username   = (string)($from->username ?? '');
        $avatar     = (string)($from->avatar ?? 'default.png');

        $from->send(json_encode([
            'type'       => 'sent',
            'message_id' => $messageId,
            'created_at' => $createdAt,
        ], JSON_UNESCAPED_UNICODE));

        if (isset($this->userConnections[$receiverId])) {
            $receiverConn = $this->userConnections[$receiverId];
            $this->markConversationRead($receiverId, $senderId);

            $receiverConn->send(json_encode([
                'type'       => 'message',
                'message_id' => $messageId,
                'sender_id'  => $senderId,
                'username'   => $username,
                'avatar'     => $avatar,
                'content'    => $content,
                'created_at' => $createdAt,
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    private function insertMessage(int $senderId, int $receiverId, string $content): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$senderId, $receiverId, $content]);

        return (int)$this->db->lastInsertId();
    }

    private function fetchMessageCreatedAt(int $messageId): ?string
    {
        $stmt = $this->db->prepare('SELECT created_at FROM messages WHERE id = ? LIMIT 1');
        $stmt->execute([$messageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['created_at'] ?? null;
    }

    private function markConversationRead(int $receiverId, int $senderId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE messages SET is_read = 1
             WHERE receiver_id = ? AND sender_id = ? AND is_read = 0'
        );
        $stmt->execute([$receiverId, $senderId]);
    }

    private function userExists(int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE id = ? AND active = 1 LIMIT 1');
        $stmt->execute([$userId]);

        return (bool)$stmt->fetch();
    }

    private function fetchUsername(int $userId): string
    {
        $stmt = $this->db->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['username'] ?? 'usuario';
    }

    private function saveWsSession(int $userId, string $connId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ws_sessions (user_id, conn_id) VALUES (?, ?)'
        );
        $stmt->execute([$userId, $connId]);
    }

    private function removeWsSession(string $connId): void
    {
        $stmt = $this->db->prepare('DELETE FROM ws_sessions WHERE conn_id = ?');
        $stmt->execute([$connId]);
    }

    private function createDatabase(): PDO
    {
        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'comunidad';
        $user = getenv('DB_USER') ?: 'usuario';
        $pass = getenv('DB_PASS') ?: 'password';

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
