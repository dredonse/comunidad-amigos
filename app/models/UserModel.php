<?php

declare(strict_types=1);

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Búsqueda ──────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, avatar, bio, role, active, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getPasswordHash(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();

        return $hash !== false ? (string)$hash : null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower($email)]);
        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    // ── Registro ──────────────────────────────────────────────

    public function create(
        string $username,
        string $email,
        string $password,
        string $role = 'user'
    ): int {
        $role = $role === 'admin' ? 'admin' : 'user';
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([trim($username), strtolower(trim($email)), $hash, $role]);

        return (int)$this->db->lastInsertId();
    }

    // ── Validaciones ─────────────────────────────────────────

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower($email)]);
        return (bool)$stmt->fetch();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return (bool)$stmt->fetch();
    }

    // ── Autenticación ─────────────────────────────────────────

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    // ── Perfil ────────────────────────────────────────────────

    public function updateProfile(int $id, string $bio): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET bio = ? WHERE id = ?');
        return $stmt->execute([$bio, $id]);
    }

    public function updateAvatar(int $id, string $avatar): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET avatar = ? WHERE id = ?');
        return $stmt->execute([$avatar, $id]);
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
        return $stmt->execute([$hash, $id]);
    }

    // ── Listados ──────────────────────────────────────────────

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, avatar, role, active, created_at
             FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    // ── Admin ─────────────────────────────────────────────────

    public function toggleActive(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET active = NOT active WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
