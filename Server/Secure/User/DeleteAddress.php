<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$addressId = (int)($data['address_id'] ?? 0);

$result = deleteAddress($pdo, $userId, $addressId);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['success' => true]);
exit;
