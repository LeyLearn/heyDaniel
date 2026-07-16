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

$userDeviceType    = $data['device_type'];
$validDeviceTypes  = ['iOS', 'Android', 'Web'];
$userId            = 0;
$taxRate           = 0.00;
$isSameDayEligible = false;
$hasActiveOrder    = false;
$productId         = $data['product_id'];

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId            = (int)($data['user_id'] ?? 0);
    $taxRate           = (float)($data['tax_rate'] ?? 0.00);
    $isSameDayEligible = (bool)($data['same_day_eligible'] ?? false);
    $hasActiveOrder    = (bool)($data['has_active_order'] ?? false);
} else {
    session_start();
    $userId            = (int)($_SESSION['user_id'] ?? 0);
    $taxRate           = (float)($_SESSION['tax_rate'] ?? 0.00);
    $isSameDayEligible = (bool)($_SESSION['same_day_eligible'] ?? false);
    $hasActiveOrder    = (bool)($_SESSION['has_active_order'] ?? false);
}

$result = productDetails($pdo, $productId, $userId, $hasActiveOrder, $isSameDayEligible, $taxRate);

if (!empty($result['error'])) {
    http_response_code(404);
    echo json_encode([]);
    exit;
}

echo json_encode(['product' => $result['product']]);
exit;
