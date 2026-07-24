<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";


$response = [
    'is_device_known'   => false,
    'zipcode'           => null,
    'city'              => null,
    'state'             => null,
    'same_day_eligible' => false,
    'tax_rate'          => 0.00,
    'has_active_order'  => false,
    'message'           => null,
    'error'             => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;
$userDeviceSignature = null;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = resolveMobileUserId($data);
    $userDeviceSignature = (string)($data['device_signature'] ?? '');
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
    $userDeviceSignature = generateDeviceSignature();
    $_SESSION['device_signature'] = $userDeviceSignature;
}

$isSameDayEligible = isSameDayEligible($pdo, $userDeviceSignature, $userId);

if (!empty($isSameDayEligible['error'])) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

if (!empty($isSameDayEligible['message'])) {
    $response['message'] = $isSameDayEligible['message'];
    echo json_encode($response);
    exit;
}

$response['zipcode'] = $isSameDayEligible['zipcode'];
$response['city'] = $isSameDayEligible['city'];
$response['state'] = $isSameDayEligible['state'];
$response['same_day_eligible'] = $isSameDayEligible['same_day_eligible'];
$response['is_device_known'] = $isSameDayEligible['is_device_known'];
$response['tax_rate'] = $isSameDayEligible['tax_rate'];
$response['has_active_order'] = $isSameDayEligible['has_active_order'];

if ($response['tax_rate'] !== 0.00) {
    $_SESSION['tax_rate'] = $response['tax_rate'];
}

$_SESSION['zipcode'] = $response['zipcode'];
$_SESSION['city'] = $response['city'];
$_SESSION['state'] = $response['state'];
$_SESSION['same_day_eligible'] = $response['same_day_eligible'];
$_SESSION['has_active_order'] = $response['has_active_order'];

echo json_encode($response);
exit;
