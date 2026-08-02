<?php

// Plain-function core of the order-status WebSocket daemon, matching the
// rest of this codebase's style (see Components.php: explicit \PDO $db
// parameters, no hidden object/global state, no classes for business
// logic). The only class in this feature is the thin Ratchet adapter in
// OrderStatusServer.php, which exists purely because Ratchet's library API
// requires implementing MessageComponentInterface on an object - every one
// of its methods is a one-line forward into one of these functions, and it
// holds no logic of its own.

function wsHandleSubscribe(\PDO $db, $conn, string $rawMessage, array &$subscribers, \SplObjectStorage $connectionOrder): void
{
    $data = json_decode($rawMessage, true);
    if (!is_array($data) || ($data['action'] ?? '') !== 'subscribe') {
        return;
    }

    $orderId = (int)($data['order_id'] ?? 0);
    if ($orderId <= 0) {
        return;
    }

    $userId = wsUserIdFromHandshake($conn);
    if ($userId <= 0 || !wsUserOwnsOrder($db, $userId, $orderId)) {
        $conn->send(json_encode(['type' => 'error', 'message' => 'Not authorized for this order.']));
        $conn->close();
        return;
    }

    if (!isset($subscribers[$orderId])) {
        $subscribers[$orderId] = new \SplObjectStorage();
    }
    $subscribers[$orderId]->attach($conn);
    $connectionOrder->attach($conn, $orderId);
}

function wsHandleDisconnect($conn, array &$subscribers, \SplObjectStorage $connectionOrder, array &$lastFingerprint): void
{
    if (!$connectionOrder->contains($conn)) {
        return;
    }

    $orderId = $connectionOrder[$conn];
    if (isset($subscribers[$orderId])) {
        $subscribers[$orderId]->detach($conn);
        if ($subscribers[$orderId]->count() === 0) {
            unset($subscribers[$orderId], $lastFingerprint[$orderId]);
        }
    }
    $connectionOrder->detach($conn);
}

// Catch-all safety net for changes that didn't come through wsCheckOneOrder()
// being called directly by notifyOrderStatusServer() (see Components.php) -
// checks every order that currently has a subscriber.
function wsCheckAllWatchedOrders(\PDO $db, array &$subscribers, array &$lastFingerprint): void
{
    foreach (array_keys($subscribers) as $orderId) {
        wsCheckOneOrder($db, $orderId, $subscribers, $lastFingerprint);
    }
}

// Checks one order immediately and pushes to its subscribers only if the
// fingerprint changed since last check. Called both by the periodic
// catch-all above and directly (via the loopback trigger socket in run.php)
// the instant cancelOrder()/rescheduleDelivery() commit a change - that
// direct call is what makes this instant instead of "up to a few seconds."
function wsCheckOneOrder(\PDO $db, int $orderId, array &$subscribers, array &$lastFingerprint): void
{
    if (!isset($subscribers[$orderId]) || $subscribers[$orderId]->count() === 0) {
        return;
    }

    $fingerprint = wsFingerprintForOrder($db, $orderId);
    if ($fingerprint === null) {
        return;
    }

    if (($lastFingerprint[$orderId] ?? null) === $fingerprint) {
        return;
    }
    $lastFingerprint[$orderId] = $fingerprint;

    $payload = json_encode(['type' => 'order_update', 'order_id' => $orderId]);
    foreach ($subscribers[$orderId] as $conn) {
        $conn->send($payload);
    }
}

function wsFingerprintForOrder(\PDO $db, int $orderId): ?string
{
    try {
        $stmt = $db->prepare("
            SELECT OrderStatus, WasRescheduled, ScheduledDeliveryWindow
            FROM OrderSent
            WHERE Id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$order) {
            return null;
        }

        $stmt = $db->prepare("
            SELECT MD5(COALESCE(GROUP_CONCAT(
                CONCAT(ProductId, ':', QuantityFound, ':', isStocked, ':', isMissing)
                ORDER BY ProductId
            ), '')) AS items_fp
            FROM Process
            WHERE OrderId = ?
        ");
        $stmt->execute([$orderId]);
        $itemsFingerprint = $stmt->fetchColumn();

        // COUNT + MAX(Id) is enough to detect a new message without hashing
        // every message body on every check - a message's content never
        // changes after it's sent, so a new row is the only way this pair
        // changes.
        $stmt = $db->prepare("SELECT COUNT(*), COALESCE(MAX(Id), 0) FROM Messages WHERE OrderId = ?");
        $stmt->execute([$orderId]);
        $messagesFingerprint = implode(':', $stmt->fetch(\PDO::FETCH_NUM));

        return implode('|', [
            $order['OrderStatus'],
            $order['WasRescheduled'],
            $order['ScheduledDeliveryWindow'],
            $itemsFingerprint,
            $messagesFingerprint,
        ]);
    } catch (\PDOException $e) {
        error_log("WebSocket fingerprint error: " . $e->getMessage());
        return null;
    }
}

// Deliberately doesn't use session_start()/session_id(): those try to send a
// Set-Cookie header, and PHP's CLI SAPI considers headers "sent" the moment
// run.php's startup echo() runs, so every later call would fail with "Cannot
// start session when headers already sent". Reads the session file straight
// off disk instead (same session.save_path Apache's PHP already uses - see
// php.ini) and pulls UserId out with a targeted regex matching exactly how
// PHP's default "php" session serialize_handler encodes an int
// ($_SESSION['user_id'] is always (int)-cast before being stored, see
// Components.php's loginUser()/googleLogin()) - not a general-purpose
// session deserializer, just enough for this one known field.
function wsUserIdFromHandshake($conn): int
{
    $cookieHeader = $conn->httpRequest->getHeaderLine('Cookie');
    if (!preg_match('/PHPSESSID=([a-zA-Z0-9,-]+)/', $cookieHeader, $m)) {
        return 0;
    }

    $sessionFile = rtrim(session_save_path(), '/') . '/sess_' . $m[1];
    if (!is_readable($sessionFile)) {
        return 0;
    }

    $raw = file_get_contents($sessionFile);
    if ($raw === false || !preg_match('/user_id\|i:(\d+);/', $raw, $userMatch)) {
        return 0;
    }

    return (int)$userMatch[1];
}

function wsUserOwnsOrder(\PDO $db, int $userId, int $orderId): bool
{
    return getActiveSameDayOrderId($db, $userId) === $orderId;
}
