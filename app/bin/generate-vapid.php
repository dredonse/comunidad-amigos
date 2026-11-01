<?php

declare(strict_types=1);

/**
 * Genera el par de claves VAPID para las notificaciones push.
 *
 *   docker compose exec web php bin/generate-vapid.php
 *
 * Copia las dos líneas en tu .env y reinicia: docker compose up -d
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__ . '/../config/app.php';

if (!class_exists(\Minishlink\WebPush\VAPID::class)) {
    fwrite(STDERR, "Falta minishlink/web-push. Reconstruye la imagen: docker compose build web\n");
    exit(1);
}

$keys = \Minishlink\WebPush\VAPID::createVapidKeys();

echo 'VAPID_PUBLIC_KEY=' . $keys['publicKey'] . PHP_EOL;
echo 'VAPID_PRIVATE_KEY=' . $keys['privateKey'] . PHP_EOL;
