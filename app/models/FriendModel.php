<?php

declare(strict_types=1);

class FriendModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Listados ────────────────────────────────────────────────

    public function getFriends(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.username, u.avatar, u.bio, f.created_at AS friends_since
             FROM friends f
             INNER JOIN users u ON u.id = CASE
                 WHEN f.sender_id = ? THEN f.receiver_id
                 ELSE f.sender_id
             END
             WHERE f.status = \'accepted\'
               AND (f.sender_id = ? OR f.receiver_id = ?)
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([$userId, $userId, $userId]);

        return $stmt->fetchAll();
    }

    public function getPendingReceived(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT f.id AS request_id, f.sender_id, f.created_at,
                    u.id, u.username, u.avatar, u.bio
             FROM friends f
             INNER JOIN users u ON u.id = f.sender_id
             WHERE f.receiver_id = ? AND f.status = \'pending\'
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function getPendingSent(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT f.id AS request_id, f.receiver_id, f.created_at,
                    u.id, u.username, u.avatar, u.bio
             FROM friends f
             INNER JOIN users u ON u.id = f.receiver_id
             WHERE f.sender_id = ? AND f.status = \'pending\'
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function getSuggestions(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.username, u.avatar, u.bio
             FROM users u
             WHERE u.id != ?
               AND u.active = 1
               AND NOT EXISTS (
                   SELECT 1 FROM friends f
                   WHERE (f.sender_id = ? AND f.receiver_id = u.id)
                      OR (f.sender_id = u.id AND f.receiver_id = ?)
               )
             ORDER BY RAND()
             LIMIT ?'
        );
        $stmt->execute([$userId, $userId, $userId, $limit]);

        return $stmt->fetchAll();
    }

    public function countPending(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM friends
             WHERE receiver_id = ? AND status = \'pending\''
        );
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }

    // ── Estado y acciones ─────────────────────────────────────

    public function getStatus(int $userId, int $otherUserId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT status, sender_id FROM friends
             WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)
             LIMIT 1'
        );
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'status'    => $row['status'],
            'sender_id' => (int)$row['sender_id'],
        ];
    }

    public function sendRequest(int $senderId, int $receiverId): bool
    {
        if ($this->getStatus($senderId, $receiverId) !== null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO friends (sender_id, receiver_id, status) VALUES (?, ?, \'pending\')'
        );

        return $stmt->execute([$senderId, $receiverId]);
    }

    public function accept(int $userId, int $senderId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE friends SET status = \'accepted\'
             WHERE sender_id = ? AND receiver_id = ? AND status = \'pending\''
        );
        $stmt->execute([$senderId, $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Rechazar borra la solicitud. Antes quedaba una fila 'rejected' y, como sendRequest()
     * no deja enviar si existe cualquier fila, ninguno de los dos podía volver a intentarlo.
     */
    public function reject(int $userId, int $senderId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM friends
             WHERE sender_id = ? AND receiver_id = ? AND status = \'pending\''
        );
        $stmt->execute([$senderId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function areFriends(int $userId, int $otherUserId): bool
    {
        $status = $this->getStatus($userId, $otherUserId);

        return $status !== null && $status['status'] === 'accepted';
    }

    public function unfriend(int $userId, int $otherUserId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM friends
             WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)'
        );

        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);

        return $stmt->rowCount() > 0;
    }
}
