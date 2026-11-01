-- ============================================
-- Revisión de seguridad (noviembre 2026)
-- Solo para bases de datos creadas antes de este cambio; init.sql ya lo incluye.
-- ============================================

SET NAMES utf8mb4;

-- Grupos privados: unirse crea una solicitud que aprueba un administrador del grupo
ALTER TABLE group_members
    ADD COLUMN IF NOT EXISTS status ENUM('approved','pending') NOT NULL DEFAULT 'approved' AFTER role;

-- Rechazar una solicitud de amistad ahora borra la fila; las antiguas bloqueaban la relación
DELETE FROM friends WHERE status = 'rejected';

-- El servidor WebSocket ya no usa esta tabla
DROP TABLE IF EXISTS ws_sessions;

-- Índices redundantes (UNIQUE ya crea un índice)
ALTER TABLE users DROP INDEX IF EXISTS idx_username;
ALTER TABLE users DROP INDEX IF EXISTS idx_email;
