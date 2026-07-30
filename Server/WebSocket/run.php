<?php

// Standalone long-running process - NOT served through Apache. Start it with:
//   /Applications/XAMPP/xamppfiles/bin/php Server/WebSocket/run.php
// (must use the XAMPP php binary, not the bare `php` on PATH - see
// [[project_scaling_pass]]/[[project_issuefix_pass_1]] memory: the bare PATH
// php can't reach MySQL, wrong socket).

require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/OrderStatusServer.php";
require __DIR__ . "/../Connect.php";
require __DIR__ . "/../Function/Components.php";

use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use HeyDaniel\WebSocket\OrderStatusServer;

$port = getenv('ORDER_WS_PORT') ?: 8080;
$triggerPort = getenv('ORDER_WS_TRIGGER_PORT') ?: 8081;

$loop = Loop::get();
$orderStatusServer = new OrderStatusServer($pdo);

$socket = new SocketServer("0.0.0.0:{$port}", [], $loop);
$server = new IoServer(
    new HttpServer(new WsServer($orderStatusServer)),
    $socket,
    $loop
);

// Loopback-only "check this order right now" trigger - Components.php calls
// this immediately after committing a write it knows changed an order
// (cancelOrder(), rescheduleDelivery()), via notifyOrderStatusServer(), so
// those changes push instantly instead of waiting for the periodic timer
// below. Bound to 127.0.0.1 so it's only reachable from this same machine,
// not the public WS port - it takes a bare order_id with no auth of its own,
// which is fine for "re-check this order" (checkOneOrder() only ever pushes
// to already-authorized subscribers of that order) but would not be fine to
// expose beyond localhost. Raw newline-delimited JSON, not WebSocket - this
// is a one-shot internal signal, not a client protocol.
$triggerSocket = new SocketServer("127.0.0.1:{$triggerPort}", [], $loop);
$triggerSocket->on('connection', function ($connection) use ($orderStatusServer) {
    $buffer = '';
    $connection->on('data', function ($chunk) use (&$buffer, $connection, $orderStatusServer) {
        $buffer .= $chunk;
        if (strpos($buffer, "\n") === false) {
            return;
        }
        $data = json_decode(trim($buffer), true);
        $orderId = (int)($data['order_id'] ?? 0);
        if ($orderId > 0) {
            $orderStatusServer->checkOneOrder($orderId);
        }
        $connection->close();
    });
});

// Catch-all safety net for any order changes that didn't come through the
// trigger above (see checkForUpdates()'s docblock) - the interval only
// bounds staleness for that fallback case now, not the common path.
$loop->addPeriodicTimer(3, function () use ($orderStatusServer) {
    $orderStatusServer->checkForUpdates();
});

echo "Order status WebSocket server listening on 0.0.0.0:{$port} (trigger on 127.0.0.1:{$triggerPort})\n";
$server->run();
