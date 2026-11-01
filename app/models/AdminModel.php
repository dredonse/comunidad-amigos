<?php

declare(strict_types=1);

class AdminModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getStats(): array
    {
        $sql = 'SELECT
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM users WHERE active = 1) AS active_users,
            (SELECT COUNT(*) FROM posts) AS total_posts,
            (SELECT COUNT(*) FROM comments) AS total_comments,
            (SELECT COUNT(*) FROM events) AS total_events,
            (SELECT COUNT(*) FROM messages) AS total_messages,
            (SELECT COUNT(*) FROM users
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_users_week,
            (SELECT COUNT(*) FROM posts
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_posts_week';

        $row = $this->db->query($sql)->fetch();

        return [
            'total_users'    => (int)$row['total_users'],
            'active_users'   => (int)$row['active_users'],
            'total_posts'    => (int)$row['total_posts'],
            'total_comments' => (int)$row['total_comments'],
            'total_events'   => (int)$row['total_events'],
            'total_messages' => (int)$row['total_messages'],
            'new_users_week' => (int)$row['new_users_week'],
            'new_posts_week' => (int)$row['new_posts_week'],
        ];
    }

    public function getRecentUsers(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, avatar, active, created_at
             FROM users ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function getRecentPosts(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.user_id, p.content, p.image, p.created_at,
                    u.username, u.avatar
             FROM posts p
             INNER JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function getAllUsers(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, avatar, role, active, created_at
             FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);

        return $stmt->fetchAll();
    }

    public function countUsers(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function deletePost(int $postId): bool
    {
        $stmt = $this->db->prepare('SELECT image FROM posts WHERE id = ? LIMIT 1');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            return false;
        }

        $del = $this->db->prepare('DELETE FROM posts WHERE id = ?');
        $ok  = $del->execute([$postId]);
        deleteUpload('posts', $post['image'] ?? null);

        return $ok;
    }

    /**
     * Borra un usuario (no administradores). ON DELETE CASCADE borra sus filas en la BD,
     * pero la BD no sabe nada de los ficheros: se recogen antes sus imágenes y se borran después.
     */
    public function deleteUser(int $userId, int $adminId): bool
    {
        if ($userId === $adminId) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT avatar, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || $user['role'] === 'admin') {
            return false;
        }

        $files = [['avatars', $user['avatar']]];
        foreach ([
            ['posts',  'SELECT image FROM posts WHERE user_id = ? AND image IS NOT NULL'],
            ['events', 'SELECT image FROM events WHERE creator_id = ? AND image IS NOT NULL'],
            ['groups', 'SELECT gp.image FROM group_posts gp
                        LEFT JOIN `groups` g ON g.id = gp.group_id
                        WHERE (gp.user_id = ? OR g.creator_id = ?) AND gp.image IS NOT NULL'],
            ['groups', 'SELECT avatar FROM `groups` WHERE creator_id = ?'],
        ] as [$subdir, $sql]) {
            $q = $this->db->prepare($sql);
            $q->execute(array_fill(0, substr_count($sql, '?'), $userId));
            foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $file) {
                $files[] = [$subdir, $file];
            }
        }

        $del = $this->db->prepare('DELETE FROM users WHERE id = ?');
        if (!$del->execute([$userId])) {
            return false;
        }

        foreach ($files as [$subdir, $file]) {
            deleteUpload($subdir, $file);
        }

        return true;
    }
}
