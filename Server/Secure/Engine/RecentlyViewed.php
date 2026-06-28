<?php
include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

if (!isset($data['device_signature'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device signature is required.']);
    exit;
}

$userDeviceType    = $data['device_type'];
$validDeviceTypes  = ['iOS', 'Android', 'Web'];
$userId            = 0;
$taxRate           = 0.00;
$isSameDayEligible = false;
$deviceSignature   = (string)$data['device_signature'];

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId            = (int)($data['user_id'] ?? 0);
    $taxRate           = (float)($data['tax_rate'] ?? 0.00);
    $isSameDayEligible = (bool)($data['same_day_eligible'] ?? false);
} else {
    session_start();
    $userId            = (int)($_SESSION['user_id'] ?? 0);
    $taxRate           = (float)($_SESSION['tax_rate'] ?? 0.00);
    $isSameDayEligible = (bool)($_SESSION['same_day_eligible'] ?? false);
}

$result = recentlyViewed($pdo, $userId, $deviceSignature, $isSameDayEligible, $taxRate);

if (!empty($result['error'])) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

echo json_encode(['products' => $result['products']]);
exit;
