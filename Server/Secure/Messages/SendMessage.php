<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$orderId = (int)($data['order_id'] ?? 0);
$message = (string)($data['message'] ?? '');

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Order id is required.']);
    exit;
}

$result = sendOrderMessage($pdo, $userId, $orderId, $message);

if (!empty($result['error'])) {
    http_response_code($result['error'] === 'Order not found.' ? 404 : 400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['success' => true, 'message_id' => $result['message_id']]);
exit;
