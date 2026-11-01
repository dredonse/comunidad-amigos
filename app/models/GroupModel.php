<?php

declare(strict_types=1);

class GroupModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Grupos ──────────────────────────────────────────────────

    public function getAll(
        int $userId,
        int $limit = 9,
        int $offset = 0,
        bool $isSiteAdmin = false
    ): array {
        $visibilityWhere = $isSiteAdmin
            ? ''
            : 'WHERE g.privacy = \'public\'
                  OR (
                      g.privacy = \'private\'
                      AND EXISTS (
                          SELECT 1 FROM group_members gm
                          WHERE gm.group_id = g.id AND gm.user_id = ?
                      )
                  )';

        $sql = 'SELECT g.id, g.name, g.description, g.avatar, g.creator_id,
                       g.privacy, g.created_at,
                       u.username AS creator_username,
                       (SELECT COUNT(*) FROM group_members gm
                        WHERE gm.group_id = g.id AND gm.status = \'approved\') AS member_count,
                       EXISTS(
                           SELECT 1 FROM group_members gm
                           WHERE gm.group_id = g.id AND gm.user_id = ? AND gm.status = \'approved\'
                       ) AS is_member,
                       EXISTS(
                           SELECT 1 FROM group_members gm
                           WHERE gm.group_id = g.id AND gm.user_id = ? AND gm.status = \'invited\'
                       ) AS is_invited
                FROM `groups` g
                INNER JOIN users u ON u.id = g.creator_id
                ' . $visibilityWhere . '
                ORDER BY g.created_at DESC
                LIMIT ? OFFSET ?';

        $params = $isSiteAdmin
            ? [$userId, $userId, $limit, $offset]
            : [$userId, $userId, $userId, $limit, $offset];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['member_count'] = (int)$row['member_count'];
            $row['is_member']    = (bool)$row['is_member'];
            $row['is_invited']   = (bool)$row['is_invited'];
        }

        return $rows;
    }

    public function countAll(int $userId, bool $isSiteAdmin = false): int
    {
        if ($isSiteAdmin) {
            return (int)$this->db->query('SELECT COUNT(*) FROM `groups`')->fetchColumn();
        }

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM `groups` g
             WHERE g.privacy = \'public\'
                OR (
                    g.privacy = \'private\'
                    AND EXISTS (
                        SELECT 1 FROM group_members gm
                        WHERE gm.group_id = g.id AND gm.user_id = ?
                    )
                )'
        );
        $stmt->execute([$userId]);

        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT g.id, g.name, g.description, g.avatar, g.creator_id,
                    g.privacy, g.created_at,
                    u.username AS creator_username, u.avatar AS creator_avatar,
                    (SELECT COUNT(*) FROM group_members gm
                     WHERE gm.group_id = g.id AND gm.status = \'approved\') AS member_count
             FROM `groups` g
             INNER JOIN users u ON u.id = g.creator_id
             WHERE g.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['member_count'] = (int)$row['member_count'];

        return $row;
    }

    public function create(
        int $creatorId,
        string $name,
        ?string $description,
        string $privacy,
        ?string $avatar
    ): int {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO `groups` (name, description, creator_id, privacy, avatar)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $name,
                $description,
                $creatorId,
                $privacy,
                $avatar ?? 'default.png',
            ]);
            $groupId = (int)$this->db->lastInsertId();

            $member = $this->db->prepare(
                'INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, \'admin\')'
            );
            $member->execute([$groupId, $creatorId]);

            $this->db->commit();

            return $groupId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getUserGroups(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT g.id, g.name, g.avatar
             FROM `groups` g
             INNER JOIN group_members gm ON gm.group_id = g.id
             WHERE gm.user_id = ? AND gm.status = \'approved\'
             ORDER BY g.name ASC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    // ── Miembros ──────────────────────────────────────────────

    public function getMembers(int $groupId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT gm.user_id, gm.role, gm.joined_at,
                    u.username, u.avatar
             FROM group_members gm
             INNER JOIN users u ON u.id = gm.user_id
             WHERE gm.group_id = ? AND gm.status = \'approved\'
             ORDER BY gm.role ASC, gm.joined_at ASC
             LIMIT ?'
        );
        $stmt->execute([$groupId, $limit]);

        return $stmt->fetchAll();
    }

    public function isMember(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM group_members
             WHERE group_id = ? AND user_id = ? AND status = \'approved\' LIMIT 1'
        );
        $stmt->execute([$groupId, $userId]);

        return (bool)$stmt->fetch();
    }

    public function isInvited(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM group_members
             WHERE group_id = ? AND user_id = ? AND status = \'invited\' LIMIT 1'
        );
        $stmt->execute([$groupId, $userId]);

        return (bool)$stmt->fetch();
    }

    public function isAdmin(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM group_members
             WHERE group_id = ? AND user_id = ? AND role = \'admin\' AND status = \'approved\' LIMIT 1'
        );
        $stmt->execute([$groupId, $userId]);

        return (bool)$stmt->fetch();
    }

    public function countAdmins(int $groupId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM group_members
             WHERE group_id = ? AND role = \'admin\' AND status = \'approved\''
        );
        $stmt->execute([$groupId]);

        return (int)$stmt->fetchColumn();
    }

    /** Solo para grupos públicos: entra directamente (los privados son por invitación). */
    public function join(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO group_members (group_id, user_id, role, status) VALUES (?, ?, \'member\', \'approved\')'
        );
        $stmt->execute([$groupId, $userId]);

        return $stmt->rowCount() > 0;
    }

    /** Invitación a un grupo privado: queda pendiente hasta que la persona la acepte. */
    public function invite(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO group_members (group_id, user_id, role, status) VALUES (?, ?, \'member\', \'invited\')'
        );
        $stmt->execute([$groupId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function acceptInvite(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE group_members SET status = \'approved\', joined_at = CURRENT_TIMESTAMP
             WHERE group_id = ? AND user_id = ? AND status = \'invited\''
        );
        $stmt->execute([$groupId, $userId]);

        return $stmt->rowCount() > 0;
    }

    /** Rechazar (la persona invitada) o cancelar (un admin del grupo) una invitación. */
    public function removeInvite(int $groupId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND status = \'invited\''
        );
        $stmt->execute([$groupId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function getInvited(int $groupId): array
    {
        $stmt = $this->db->prepare(
            'SELECT gm.user_id, gm.joined_at AS invited_at, u.username, u.avatar
             FROM group_members gm
             INNER JOIN users u ON u.id = gm.user_id
             WHERE gm.group_id = ? AND gm.status = \'invited\'
             ORDER BY gm.joined_at ASC'
        );
        $stmt->execute([$groupId]);

        return $stmt->fetchAll();
    }

    /** Amigos de $userId que todavía no están en el grupo ni invitados. */
    public function getInvitableFriends(int $groupId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.username, u.avatar
             FROM friends f
             INNER JOIN users u ON u.id = CASE WHEN f.sender_id = ? THEN f.receiver_id ELSE f.sender_id END
             WHERE f.status = \'accepted\'
               AND (f.sender_id = ? OR f.receiver_id = ?)
               AND u.active = 1
               AND NOT EXISTS (
                   SELECT 1 FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = u.id
               )
             ORDER BY u.username ASC'
        );
        $stmt->execute([$userId, $userId, $userId, $groupId]);

        return $stmt->fetchAll();
    }

    /** @return int[] */
    public function getAdminIds(int $groupId): array
    {
        $stmt = $this->db->prepare(
            'SELECT user_id FROM group_members
             WHERE group_id = ? AND role = \'admin\' AND status = \'approved\''
        );
        $stmt->execute([$groupId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function leave(int $groupId, int $userId): bool
    {
        if ($this->isAdmin($groupId, $userId) && $this->countAdmins($groupId) <= 1) {
            return false;
        }

        $stmt = $this->db->prepare(
            'DELETE FROM group_members WHERE group_id = ? AND user_id = ?'
        );

        return $stmt->execute([$groupId, $userId]);
    }

    // ── Publicaciones del grupo ───────────────────────────────

    public function getPosts(int $groupId, int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT gp.id, gp.group_id, gp.user_id, gp.content, gp.image, gp.created_at,
                    u.username, u.avatar,
                    (SELECT COUNT(*) FROM group_post_comments gpc
                     WHERE gpc.group_post_id = gp.id) AS comment_count
             FROM group_posts gp
             INNER JOIN users u ON u.id = gp.user_id
             WHERE gp.group_id = ?
             ORDER BY gp.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$groupId, $limit, $offset]);

        $posts = $stmt->fetchAll();
        foreach ($posts as &$post) {
            $post['comment_count'] = (int)$post['comment_count'];
        }

        return $posts;
    }

    public function countPosts(int $groupId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM group_posts WHERE group_id = ?');
        $stmt->execute([$groupId]);

        return (int)$stmt->fetchColumn();
    }

    public function addPost(int $groupId, int $userId, string $content, ?string $image): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO group_posts (group_id, user_id, content, image) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$groupId, $userId, $content, $image]);

        return (int)$this->db->lastInsertId();
    }

    public function findPostById(int $postId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, group_id, user_id, content, image, created_at
             FROM group_posts WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$postId]);

        return $stmt->fetch() ?: null;
    }

    public function deletePost(int $postId, int $userId, int $groupId): bool
    {
        $post = $this->findPostById($postId);
        if (!$post || (int)$post['group_id'] !== $groupId) {
            return false;
        }

        $isAuthor = (int)$post['user_id'] === $userId;
        if (!$isAuthor && !$this->isAdmin($groupId, $userId)) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM group_posts WHERE id = ?');
        $ok   = $stmt->execute([$postId]);
        deleteUpload('groups', $post['image'] ?? null);

        return $ok;
    }

    // ── Comentarios ─────────────────────────────────────────────

    public function getPostComments(int $groupPostId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT gpc.id, gpc.group_post_id, gpc.user_id, gpc.comment, gpc.created_at,
                    u.username, u.avatar
             FROM group_post_comments gpc
             INNER JOIN users u ON u.id = gpc.user_id
             WHERE gpc.group_post_id = ?
             ORDER BY gpc.created_at ASC
             LIMIT ?'
        );
        $stmt->execute([$groupPostId, $limit]);

        return $stmt->fetchAll();
    }

    public function addComment(int $groupPostId, int $userId, string $comment): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO group_post_comments (group_post_id, user_id, comment)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([$groupPostId, $userId, $comment]);

        return (int)$this->db->lastInsertId();
    }
}
