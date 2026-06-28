<?php
include_once __DIR__ . "/../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";


$response = [
    'zipcode'           => null,
    'city'              => null,
    'state'             => null,
    'same_day_eligible' => false,
    'tax_rate'          => 0.00,
    'message'           => null,
    'error'             => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

if (!isset($data['zipcode']) || empty($data['zipcode']) || strlen($data['zipcode']) > 16 || !preg_match('/^[a-zA-Z0-9\- ]+$/', $data['zipcode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing zipcode.']);
    exit;
}

$userDeviceType = $data['device_type'];
$zipcode = $data['zipcode'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;
$userDeviceSignature = null;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = (int)($data['user_id'] ?? 0);
    $userDeviceSignature = (string)($data['device_signature'] ?? '');
} else {
    session_start();
    $userDeviceSignature = $_SESSION['device_signature'];
}

$deviceLog = DeviceLog($pdo, $userDeviceSignature, $userDeviceType, $zipcode);

if (!empty($deviceLog['error'])) {
    http_response_code(500);
    echo json_encode([]);
    exit;
}

if (!empty($deviceLog['message'])) {
    $response['message'] = $deviceLog['message'];
    echo json_encode($response);
    exit;
}

$response['zipcode'] = $deviceLog['zipcode'];
$response['city'] = $deviceLog['city'];
$response['state'] = $deviceLog['state'];
$response['same_day_eligible'] = $deviceLog['same_day_eligible'];
$response['tax_rate'] = $deviceLog['tax_rate'];

$_SESSION['zipcode'] = $response['zipcode'];
$_SESSION['city'] = $response['city'];
$_SESSION['state'] = $response['state'];
$_SESSION['tax_rate'] = $response['tax_rate'];
$_SESSION['same_day_eligible'] = $response['same_day_eligible'];

echo json_encode($response);
exit;
