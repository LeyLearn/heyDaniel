<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$result = cancelOrder($pdo, $userId);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['success' => true]);
exit;
