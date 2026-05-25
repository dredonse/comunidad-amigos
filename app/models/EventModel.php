<?php

declare(strict_types=1);

class EventModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Listados ────────────────────────────────────────────────

    public function getAll(int $userId, int $limit = 9, int $offset = 0): array
    {
        $sql = 'SELECT e.id, e.creator_id, e.title, e.description, e.event_date,
                       e.location, e.image, e.created_at,
                       u.username AS creator_username, u.avatar AS creator_avatar,
                       (SELECT COUNT(*) FROM event_attendees ea
                        WHERE ea.event_id = e.id AND ea.status = \'going\') AS going_count,
                       (SELECT ea.status FROM event_attendees ea
                        WHERE ea.event_id = e.id AND ea.user_id = ? LIMIT 1) AS user_status
                FROM events e
                INNER JOIN users u ON u.id = e.creator_id
                ORDER BY e.event_date ASC
                LIMIT ? OFFSET ?';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);

        $events = $stmt->fetchAll();
        foreach ($events as &$event) {
            $event['going_count'] = (int)$event['going_count'];
            $event['user_status'] = $event['user_status'] ?: null;
        }

        return $events;
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM events')->fetchColumn();
    }

    public function findById(int $id, int $userId = 0): ?array
    {
        $sql = 'SELECT e.id, e.creator_id, e.title, e.description, e.event_date,
                       e.location, e.image, e.created_at,
                       u.username AS creator_username, u.avatar AS creator_avatar,
                       (SELECT COUNT(*) FROM event_attendees ea
                        WHERE ea.event_id = e.id AND ea.status = \'going\') AS going_count,
                       (SELECT COUNT(*) FROM event_attendees ea
                        WHERE ea.event_id = e.id AND ea.status = \'maybe\') AS maybe_count,
                       (SELECT COUNT(*) FROM event_attendees ea
                        WHERE ea.event_id = e.id AND ea.status = \'not_going\') AS not_going_count,
                       (SELECT ea.status FROM event_attendees ea
                        WHERE ea.event_id = e.id AND ea.user_id = ? LIMIT 1) AS user_status
                FROM events e
                INNER JOIN users u ON u.id = e.creator_id
                WHERE e.id = ?
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $id]);
        $event = $stmt->fetch();

        if (!$event) {
            return null;
        }

        $event['going_count']     = (int)$event['going_count'];
        $event['maybe_count']     = (int)$event['maybe_count'];
        $event['not_going_count'] = (int)$event['not_going_count'];
        $event['user_status']     = $event['user_status'] ?: null;

        return $event;
    }

    // ── CRUD ────────────────────────────────────────────────────

    public function create(
        int $creatorId,
        string $title,
        ?string $description,
        string $eventDate,
        ?string $location,
        ?string $image
    ): int {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO events (creator_id, title, description, event_date, location, image)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$creatorId, $title, $description, $eventDate, $location, $image]);
            $eventId = (int)$this->db->lastInsertId();

            $att = $this->db->prepare(
                'INSERT INTO event_attendees (event_id, user_id, status) VALUES (?, ?, \'going\')'
            );
            $att->execute([$eventId, $creatorId]);

            $this->db->commit();

            return $eventId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $eventId, int $userId, bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            $stmt = $this->db->prepare('DELETE FROM events WHERE id = ?');

            return $stmt->execute([$eventId]);
        }

        $stmt = $this->db->prepare(
            'DELETE FROM events WHERE id = ? AND creator_id = ?'
        );

        return $stmt->execute([$eventId, $userId]);
    }

    public function isCreator(int $eventId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM events WHERE id = ? AND creator_id = ? LIMIT 1'
        );
        $stmt->execute([$eventId, $userId]);

        return (bool)$stmt->fetch();
    }

    // ── Asistencia ──────────────────────────────────────────────

    public function getAttendees(int $eventId, ?string $status = null): array
    {
        $sql = 'SELECT ea.user_id, ea.status, ea.created_at,
                       u.username, u.avatar
                FROM event_attendees ea
                INNER JOIN users u ON u.id = ea.user_id
                WHERE ea.event_id = ?';

        $params = [$eventId];

        if ($status !== null) {
            $sql .= ' AND ea.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY ea.created_at ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function attend(int $eventId, int $userId, string $status): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO event_attendees (event_id, user_id, status)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)'
        );

        return $stmt->execute([$eventId, $userId, $status]);
    }

    public function cancelAttendance(int $eventId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM event_attendees WHERE event_id = ? AND user_id = ?'
        );

        return $stmt->execute([$eventId, $userId]);
    }
}
