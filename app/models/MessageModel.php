<?php

declare(strict_types=1);

class MessageModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getConversations(int $userId): array
    {
        $sql = 'SELECT t.other_id, u.username, u.avatar,
                       m.content AS last_message, m.created_at AS last_at,
                       (SELECT COUNT(*) FROM messages um
                        WHERE um.receiver_id = ? AND um.sender_id = t.other_id
                          AND um.is_read = 0) AS unread_count
                FROM (
                    SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_id,
                           MAX(id) AS last_id
                    FROM messages
                    WHERE sender_id = ? OR receiver_id = ?
                    GROUP BY other_id
                ) t
                INNER JOIN messages m ON m.id = t.last_id
                INNER JOIN users u ON u.id = t.other_id
                ORDER BY m.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $userId]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['unread_count'] = (int)$row['unread_count'];
        }

        return $rows;
    }

    public function getMessages(int $userId, int $otherUserId, int $limit = 50, int $offset = 0): array
    {
        $this->markConversationRead($userId, $otherUserId);

        $stmt = $this->db->prepare(
            'SELECT id, sender_id, receiver_id, content, is_read, created_at
             FROM messages
             WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)
             ORDER BY created_at ASC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId, $limit, $offset]);

        return $stmt->fetchAll();
    }

    public function markConversationRead(int $userId, int $otherUserId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE messages SET is_read = 1
             WHERE receiver_id = ? AND sender_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId, $otherUserId]);
    }

    public function send(int $senderId, int $receiverId, string $content): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$senderId, $receiverId, $content]);

        return (int)$this->db->lastInsertId();
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }

    public function countMessages(int $userId, int $otherUserId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM messages
             WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)'
        );
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);

        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, sender_id, receiver_id, content, is_read, created_at
             FROM messages WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }
}
