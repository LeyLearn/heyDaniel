<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$orderId = (int)($data['order_id'] ?? 0);

$result = orderDetails($pdo, $userId, $orderId);

if (!empty($result['error'])) {
    http_response_code($result['order'] === null && $result['error'] === 'Order not found.' ? 404 : 400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['order' => $result['order']]);
exit;
