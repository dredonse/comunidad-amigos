<?php

declare(strict_types=1);

class PushModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function save(int $userId, string $endpoint, string $p256dh, string $auth): bool
    {
        $existing = $this->findByEndpoint($userId, $endpoint);
        if ($existing !== null) {
            $stmt = $this->db->prepare(
                'UPDATE push_subscriptions
                 SET p256dh = ?, auth = ?, created_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND user_id = ?'
            );

            return $stmt->execute([$p256dh, $auth, $existing['id'], $userId]);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                endpoint = VALUES(endpoint),
                p256dh = VALUES(p256dh),
                created_at = CURRENT_TIMESTAMP'
        );

        return $stmt->execute([$userId, $endpoint, $p256dh, $auth]);
    }

    public function delete(int $userId, string $auth): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM push_subscriptions WHERE user_id = ? AND auth = ?'
        );

        return $stmt->execute([$userId, $auth]);
    }

    public function deleteByEndpoint(string $endpoint): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM push_subscriptions WHERE endpoint = ?'
        );

        return $stmt->execute([$endpoint]);
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, endpoint, p256dh, auth, created_at
             FROM push_subscriptions
             WHERE user_id = ?
             ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function getAllSubscriptions(): array
    {
        return $this->db->query(
            'SELECT id, user_id, endpoint, p256dh, auth, created_at
             FROM push_subscriptions
             ORDER BY user_id ASC'
        )->fetchAll();
    }

    /**
     * @param int[] $userIds
     */
    public function getSubscriptionsByUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(
            array_map('intval', $userIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($userIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT id, user_id, endpoint, p256dh, auth, created_at
             FROM push_subscriptions
             WHERE user_id IN ({$placeholders})"
        );
        $stmt->execute($userIds);

        return $stmt->fetchAll();
    }

    private function findByEndpoint(int $userId, string $endpoint): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM push_subscriptions
             WHERE user_id = ? AND endpoint = ?
             LIMIT 1'
        );
        $stmt->execute([$userId, $endpoint]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
