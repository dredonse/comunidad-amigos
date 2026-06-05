<?php

declare(strict_types=1);

class PostModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Feed ────────────────────────────────────────────────────

    public function getFeed(int $userId, int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT p.id, p.user_id, p.content, p.image, p.created_at,
                       u.username, u.avatar,
                       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
                       (SELECT COUNT(*) FROM likes l
                        WHERE l.post_id = p.id AND l.user_id = ?) > 0 AS user_liked
                FROM posts p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.user_id = ?
                   OR p.user_id IN (
                        SELECT CASE
                            WHEN sender_id = ? THEN receiver_id
                            ELSE sender_id
                        END
                        FROM friends
                        WHERE status = \'accepted\'
                          AND (sender_id = ? OR receiver_id = ?)
                   )
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $userId,
            $userId,
            $userId,
            $userId,
            $limit,
            $offset,
        ]);

        $posts = $stmt->fetchAll();
        foreach ($posts as &$post) {
            $post['like_count']    = (int)$post['like_count'];
            $post['comment_count'] = (int)$post['comment_count'];
            $post['user_liked']    = (bool)$post['user_liked'];
        }

        return $posts;
    }

    public function getNewPosts(int $userId, int $lastId, int $limit = 10): array
    {
        $sql = 'SELECT p.id, p.user_id, p.content, p.image, p.created_at,
                       u.username, u.avatar,
                       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
                       (SELECT COUNT(*) FROM likes l
                        WHERE l.post_id = p.id AND l.user_id = ?) > 0 AS user_liked
                FROM posts p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.id > ?
                  AND (
                        p.user_id = ?
                     OR p.user_id IN (
                            SELECT CASE
                                WHEN sender_id = ? THEN receiver_id
                                ELSE sender_id
                            END
                            FROM friends
                            WHERE status = \'accepted\'
                              AND (sender_id = ? OR receiver_id = ?)
                       )
                  )
                ORDER BY p.id ASC
                LIMIT ?';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $lastId,
            $userId,
            $userId,
            $userId,
            $userId,
            $limit,
        ]);

        $posts = $stmt->fetchAll();
        foreach ($posts as &$post) {
            $post['like_count']    = (int)$post['like_count'];
            $post['comment_count'] = (int)$post['comment_count'];
            $post['user_liked']    = (bool)$post['user_liked'];
        }

        return $posts;
    }

    public function getByUser(int $profileId, int $viewerId, int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT p.id, p.user_id, p.content, p.image, p.created_at,
                       u.username, u.avatar,
                       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
                       (SELECT COUNT(*) FROM likes l
                        WHERE l.post_id = p.id AND l.user_id = ?) > 0 AS user_liked
                FROM posts p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$viewerId, $profileId, $limit, $offset]);

        $posts = $stmt->fetchAll();
        foreach ($posts as &$post) {
            $post['like_count']    = (int)$post['like_count'];
            $post['comment_count'] = (int)$post['comment_count'];
            $post['user_liked']    = (bool)$post['user_liked'];
        }

        return $posts;
    }

    public function countByUser(int $profileId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
        $stmt->execute([$profileId]);

        return (int)$stmt->fetchColumn();
    }

    public function countFeed(int $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM posts p
                WHERE p.user_id = ?
                   OR p.user_id IN (
                        SELECT CASE
                            WHEN sender_id = ? THEN receiver_id
                            ELSE sender_id
                        END
                        FROM friends
                        WHERE status = \'accepted\'
                          AND (sender_id = ? OR receiver_id = ?)
                   )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $userId]);

        return (int)$stmt->fetchColumn();
    }

    // ── CRUD publicaciones ──────────────────────────────────────

    public function create(int $userId, string $content, ?string $image = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $content, $image]);

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.user_id, p.content, p.image, p.created_at,
                    u.username, u.avatar
             FROM posts p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM posts WHERE id = ? AND user_id = ?'
        );

        return $stmt->execute([$id, $userId]);
    }

    // ── Comentarios ─────────────────────────────────────────────

    public function getComments(int $postId, int $limit = 3): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.post_id, c.user_id, c.comment, c.created_at,
                    u.username, u.avatar
             FROM comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.post_id = ?
             ORDER BY c.created_at ASC
             LIMIT ?'
        );
        $stmt->execute([$postId, $limit]);

        return $stmt->fetchAll();
    }

    public function addComment(int $postId, int $userId, string $comment): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)'
        );
        $stmt->execute([$postId, $userId, $comment]);

        return (int)$this->db->lastInsertId();
    }

    // ── Likes ───────────────────────────────────────────────────

    public function toggleLike(int $postId, int $userId): array
    {
        $check = $this->db->prepare(
            'SELECT id FROM likes WHERE post_id = ? AND user_id = ? LIMIT 1'
        );
        $check->execute([$postId, $userId]);

        if ($check->fetch()) {
            $del = $this->db->prepare(
                'DELETE FROM likes WHERE post_id = ? AND user_id = ?'
            );
            $del->execute([$postId, $userId]);
            $liked = false;
        } else {
            $ins = $this->db->prepare(
                'INSERT INTO likes (post_id, user_id) VALUES (?, ?)'
            );
            $ins->execute([$postId, $userId]);
            $liked = true;
        }

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM likes WHERE post_id = ?'
        );
        $countStmt->execute([$postId]);

        return [
            'liked' => $liked,
            'count' => (int)$countStmt->fetchColumn(),
        ];
    }
}
