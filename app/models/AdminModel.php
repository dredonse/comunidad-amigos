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
            (SELECT COUNT(*) FROM users
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_users_week,
            (SELECT COUNT(*) FROM posts) AS total_posts,
            (SELECT COUNT(*) FROM posts
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_posts_week,
            (SELECT COUNT(*) FROM events) AS total_events,
            (SELECT COUNT(*) FROM events
             WHERE event_date > NOW()) AS upcoming_events,
            (SELECT COUNT(*) FROM messages) AS total_messages,
            (SELECT COUNT(*) FROM `groups`) AS total_groups,
            (SELECT COUNT(*) FROM `groups` WHERE privacy = \'public\') AS public_groups,
            (SELECT COUNT(*) FROM `groups` WHERE privacy = \'private\') AS private_groups';

        $row = $this->db->query($sql)->fetch();

        return [
            'total_users'      => (int)$row['total_users'],
            'new_users_week'   => (int)$row['new_users_week'],
            'total_posts'      => (int)$row['total_posts'],
            'new_posts_week'   => (int)$row['new_posts_week'],
            'total_events'     => (int)$row['total_events'],
            'upcoming_events'  => (int)$row['upcoming_events'],
            'total_messages'   => (int)$row['total_messages'],
            'total_groups'     => (int)$row['total_groups'],
            'public_groups'    => (int)$row['public_groups'],
            'private_groups'   => (int)$row['private_groups'],
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

    public function countAllPosts(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    }

    public function getAllPosts(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.user_id, p.content, p.image, p.created_at,
                    u.username, u.avatar
             FROM posts p
             INNER JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);

        return $stmt->fetchAll();
    }

    public function countAllComments(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    }

    public function getAllComments(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.post_id, c.user_id, c.comment, c.created_at,
                    u.username, u.avatar
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);

        return $stmt->fetchAll();
    }

    public function countAllEvents(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM events')->fetchColumn();
    }

    public function getAllEvents(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id, e.creator_id, e.title, e.event_date, e.location,
                    e.image, e.created_at,
                    u.username AS creator_username
             FROM events e
             INNER JOIN users u ON u.id = e.creator_id
             ORDER BY e.event_date DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);

        return $stmt->fetchAll();
    }

    public function countAllGroups(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM `groups`')->fetchColumn();
    }

    public function getAllGroups(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT g.id, g.name, g.privacy, g.avatar, g.creator_id, g.created_at,
                    u.username AS creator_username,
                    (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count
             FROM `groups` g
             INNER JOIN users u ON u.id = g.creator_id
             ORDER BY g.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['member_count'] = (int)$row['member_count'];
        }

        return $rows;
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

    public function deleteComment(int $commentId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id = ?');
        $stmt->execute([$commentId]);

        return $stmt->rowCount() > 0;
    }

    public function deleteEvent(int $eventId): bool
    {
        $stmt = $this->db->prepare('SELECT image FROM events WHERE id = ? LIMIT 1');
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();

        if (!$event) {
            return false;
        }

        $del = $this->db->prepare('DELETE FROM events WHERE id = ?');
        $ok  = $del->execute([$eventId]);
        deleteUpload('events', $event['image'] ?? null);

        return $ok;
    }

    /** Borra el grupo; además de su avatar, se borran las imágenes de sus publicaciones. */
    public function deleteGroup(int $groupId): bool
    {
        $stmt = $this->db->prepare('SELECT avatar FROM `groups` WHERE id = ? LIMIT 1');
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();

        if (!$group) {
            return false;
        }

        $images = $this->db->prepare('SELECT image FROM group_posts WHERE group_id = ? AND image IS NOT NULL');
        $images->execute([$groupId]);
        $files = $images->fetchAll(PDO::FETCH_COLUMN);

        $del = $this->db->prepare('DELETE FROM `groups` WHERE id = ?');
        $ok  = $del->execute([$groupId]);

        deleteUpload('groups', $group['avatar'] ?? null);
        foreach ($files as $file) {
            deleteUpload('groups', $file);
        }

        return $ok;
    }

    public function toggleGroupPrivacy(int $groupId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE `groups` SET privacy = IF(privacy = \'public\', \'private\', \'public\') WHERE id = ?'
        );
        $stmt->execute([$groupId]);

        return $stmt->rowCount() > 0;
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
