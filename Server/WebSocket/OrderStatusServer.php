<?php

namespace HeyDaniel\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once __DIR__ . "/OrderStatusFunctions.php";

// Exists only because Ratchet's library API requires implementing this
// interface on an object passed to IoServer - there's no procedural entry
// point into Ratchet. This class holds no logic of its own: every method is
// a one-line forward into a plain function in OrderStatusFunctions.php,
// which is where the actual behavior (auth, fingerprinting, subscriber
// bookkeeping) lives, matching the rest of this codebase's style. The three
// properties below exist only because Ratchet needs *something* to persist
// them between calls on this object between events - they're passed into
// every function explicitly by reference, never read or mutated here.
class OrderStatusServer implements MessageComponentInterface
{
    private \PDO $db;
    private array $subscribers = [];
    private \SplObjectStorage $connectionOrder;
    private array $lastFingerprint = [];

    public function __construct(\PDO $db)
    {
        $this->db = $db;
        $this->connectionOrder = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Nothing to do until the client sends a subscribe message.
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        wsHandleSubscribe($this->db, $from, $msg, $this->subscribers, $this->connectionOrder);
    }

    public function onClose(ConnectionInterface $conn)
    {
        wsHandleDisconnect($conn, $this->subscribers, $this->connectionOrder, $this->lastFingerprint);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        error_log("OrderStatusServer connection error: " . $e->getMessage());
        $conn->close();
    }

    public function checkForUpdates(): void
    {
        wsCheckAllWatchedOrders($this->db, $this->subscribers, $this->lastFingerprint);
    }

    public function checkOneOrder(int $orderId): void
    {
        wsCheckOneOrder($this->db, $orderId, $this->subscribers, $this->lastFingerprint);
    }
}
