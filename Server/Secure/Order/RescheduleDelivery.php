<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$window = (string)($data['window'] ?? '');

$result = rescheduleDelivery($pdo, $userId, $window);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode([
    'success'                   => true,
    'scheduled_delivery_window' => $result['scheduled_delivery_window']
]);
exit;
