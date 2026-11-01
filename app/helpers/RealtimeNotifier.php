<?php

declare(strict_types=1);

/**
 * Avisa al servidor WebSocket de que hay un mensaje nuevo para que lo entregue en vivo.
 *
 * Usa el canal interno del contenedor websocket (puerto 8091, solo accesible dentro de la red
 * de Docker) y el secreto compartido APP_SECRET. Si el servidor no está, no pasa nada:
 * el mensaje ya está guardado y el destinatario lo verá al abrir la conversación.
 */
class RealtimeNotifier
{
    public static function messageCreated(int $messageId, int $senderId, int $receiverId, string $content, string $createdAt): void
    {
        $secret = env('APP_SECRET', '');
        $target = env('WS_INTERNAL_ADDRESS', 'websocket:8091');
        if ($secret === '') {
            return;
        }

        $line = json_encode([
            'secret'      => $secret,
            'message_id'  => $messageId,
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'content'     => $content,
            'created_at'  => $createdAt,
        ], JSON_UNESCAPED_UNICODE) . "\n";

        $socket = @stream_socket_client('tcp://' . $target, $errno, $errstr, 0.5);
        if ($socket === false) {
            error_log("RealtimeNotifier: servidor WebSocket no disponible ({$errstr})");

            return;
        }
        stream_set_timeout($socket, 1);
        fwrite($socket, $line);
        fclose($socket);
    }
}
