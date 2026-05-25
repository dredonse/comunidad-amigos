<?php

declare(strict_types=1);

class NotificationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(int $userId, string $type, string $message, ?int $referenceId = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (user_id, type, reference_id, message)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $type, $referenceId, $message]);

        return (int)$this->db->lastInsertId();
    }

    public function getForUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, type, reference_id, message, is_read, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $limit, $offset]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['is_read'] = (bool)$row['is_read'];
        }

        return $rows;
    }

    public function countAll(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE id = ? AND user_id = ?'
        );

        return $stmt->execute([$notificationId, $userId]);
    }

    public function markAllRead(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
        );

        return $stmt->execute([$userId]);
    }
}
