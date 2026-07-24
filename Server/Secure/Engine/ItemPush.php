<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing product ID.']);
    exit;
}

$allowedTables = ['RecentlyViewed', 'SearchHistory'];

if (!isset($data['table']) || !in_array($data['table'], $allowedTables, true)) {
    http_response_code(400);
    echo json_encode(['error' => "Invalid or missing table. Must be 'RecentlyViewed' or 'SearchHistory'."]);
    exit;
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId           = 0;
$deviceSignature  = '';

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId          = resolveMobileUserId($data);
    $deviceSignature = (string)($data['device_signature'] ?? '');
} else {
    session_start();
    $userId          = (int)($_SESSION['user_id'] ?? 0);
    $deviceSignature = (string)($_SESSION['device_signature'] ?? '');
}

$productId = $data['product_id'];
$table     = $data['table'];

$result = itemPush($pdo, $userId, $deviceSignature, $productId, $table);

if (!empty($result['error'])) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

echo json_encode(['success' => true]);
exit;
