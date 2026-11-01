-- ============================================
-- Revisión de seguridad (noviembre 2026)
-- Solo para bases de datos creadas antes de este cambio; init.sql ya lo incluye.
-- ============================================

SET NAMES utf8mb4;

-- Grupos privados: solo por invitación ('invited' hasta que la persona acepta)
ALTER TABLE group_members
    ADD COLUMN IF NOT EXISTS status ENUM('approved','invited') NOT NULL DEFAULT 'approved' AFTER role;
-- (una versión previa de esta migración usaba 'pending' para solicitudes: se descartan)
DELETE FROM group_members WHERE status NOT IN ('approved', 'invited');
ALTER TABLE group_members
    MODIFY COLUMN status ENUM('approved','invited') NOT NULL DEFAULT 'approved';

-- Rechazar una solicitud de amistad ahora borra la fila; las antiguas bloqueaban la relación
DELETE FROM friends WHERE status = 'rejected';

-- El servidor WebSocket ya no usa esta tabla
DROP TABLE IF EXISTS ws_sessions;

-- Índices redundantes (UNIQUE ya crea un índice)
ALTER TABLE users DROP INDEX IF EXISTS idx_username;
ALTER TABLE users DROP INDEX IF EXISTS idx_email;
