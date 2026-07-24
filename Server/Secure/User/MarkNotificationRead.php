<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$notificationId = (int)($data['notification_id'] ?? 0);

$result = markNotificationRead($pdo, $userId, $notificationId);

if (!empty($result['error'])) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['success' => true]);
exit;
