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

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId           = 0;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId = resolveMobileUserId($data);
} else {
    session_start();
    $userId = (int)($_SESSION['user_id'] ?? 0);
}

$productId   = $data['product_id'];
$stars       = (int)($data['stars'] ?? 0);
$expectation = (int)($data['expectation'] ?? 0);
$title       = trim((string)($data['title'] ?? ''));
$review      = trim((string)($data['review'] ?? ''));

$result = addReview($pdo, $userId, $productId, $stars, $expectation, $title, $review);

if (!empty($result['error'])) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

echo json_encode(['success' => true]);
exit;
