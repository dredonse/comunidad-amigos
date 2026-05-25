<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/ChatServer.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8090,
    '0.0.0.0'
);

echo "Ratchet WS Server running on port 8090\n";
$server->run();
