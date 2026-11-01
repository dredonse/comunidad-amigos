<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/ChatServer.php';

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;

$secret  = getenv('APP_SECRET') ?: '';
$origins = array_values(array_filter(array_map('trim', explode(',', getenv('WS_ALLOWED_ORIGINS') ?: ''))));

$loop = Loop::get();
$chat = new ChatServer($secret, $origins);

// 1. Puerto público (8090): los navegadores se conectan y se identifican con su token.
$public = new SocketServer('0.0.0.0:8090', [], $loop);
new IoServer(new HttpServer(new WsServer($chat)), $public, $loop);

// 2. Canal interno (8091, sin publicar en el host): la web PHP avisa de cada mensaje guardado.
//    Una línea JSON por mensaje: {"secret": "...", "receiver_id": 7, "sender_id": 3, ...}
$internal = new SocketServer('0.0.0.0:8091', [], $loop);
$internal->on('connection', function (ConnectionInterface $conn) use ($chat, $secret): void {
    $buffer = '';
    $conn->on('data', function (string $data) use (&$buffer, $chat, $secret, $conn): void {
        $buffer .= $data;
        if (strlen($buffer) > 65536) {
            $conn->close();

            return;
        }
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line   = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 1);

            $message = json_decode($line, true);
            if (is_array($message) && hash_equals($secret, (string)($message['secret'] ?? ''))) {
                $chat->deliver($message);
            }
        }
    });
});

echo "WebSocket en :8090 · canal interno en :8091\n";
$loop->run();
