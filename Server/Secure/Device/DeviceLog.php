<?php
include_once "../connect.php";
include_once "../../Function/Components.php";


$response = [
    'same_day_eligible' => false,
    'tax_rate'          => 0.00,
    'message'           => null,
    'error'             => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['message'] = "Device type is required.";
    echo json_encode($response);
    exit;
}

if(!isset($data['zipcode']) || empty($data['zipcode']) || strlen($data['zipcode']) > 16 || !preg_match('/^[a-zA-Z0-9\- ]+$/', $data['zipcode'])) {
    http_response_code(400);
    $response['message'] = "Invalid or missing zipcode.";
    echo json_encode($response);
    exit;
}

$userDeviceType = $data['device_type'];
$zipcode = $data['zipcode'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;
$userDeviceSignature = null;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['message'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = (int)($data['user_id'] ?? 0);
    $userDeviceSignature = (string)($data['device_signature'] ?? '');
} else {
    session_start();
    $userDeviceSignature = $_SESSION['device_signature'];
}

$deviceLog = DeviceLog($pdo, $userDeviceSignature,$userDeviceType, $zipcode);

if (!empty($deviceLog['error'])) {
    http_response_code(400);
    $response['error'] = $deviceLog['error'];
    echo json_encode($response);
    exit;
}

$response['same_day_eligible'] = $deviceLog['same_day_eligible'];
$response['tax_rate'] = $deviceLog['tax_rate'];

$_SESSION['tax_rate'] = $response['tax_rate'];
$_SESSION['same_day_eligible'] = $response['same_day_eligible'];

echo json_encode($response);
exit;
