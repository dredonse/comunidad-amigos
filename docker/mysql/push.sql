-- Suscripciones Web Push (PWA)
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    endpoint   TEXT NOT NULL,
    p256dh     VARCHAR(255) NOT NULL,
    auth       VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_endpoint (user_id, auth)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
