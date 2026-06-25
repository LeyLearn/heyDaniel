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
    respondWithMsg("Device type is required.");
}

if (!isset($data['zipcode']) || empty($data['zipcode']) || strlen($data['zipcode']) > 16 || !preg_match('/^[a-zA-Z0-9\- ]+$/', $data['zipcode'])) {
    respondWithMsg("Invalid or missing zipcode.");
}

$userDeviceType = $data['device_type'];
$zipcode = $data['zipcode'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;
$userDeviceSignature = null;

// SECURITY: SQL injection prevention (Vulnerability #15)
if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    respondWithMsg("Invalid device type.");
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = (int)($data['user_id'] ?? 0);
    $userDeviceSignature = (string)($data['device_signature'] ?? '');
} else {
    session_start();
    $userDeviceSignature = $_SESSION['device_signature'];
}

$deviceLog = DeviceLog($pdo, $userDeviceSignature, $userDeviceType, $zipcode);

// SECURITY: Error handling (Vulnerability #14)
if (!empty($deviceLog['error'])) {
    respondWithError($deviceLog['error'], 500);
}

if (!empty($deviceLog['message'])) {
    respondWithMsg($deviceLog['message']);
}

$response['same_day_eligible'] = $deviceLog['same_day_eligible'];
$response['tax_rate'] = $deviceLog['tax_rate'];

$_SESSION['tax_rate'] = $response['tax_rate'];
$_SESSION['same_day_eligible'] = $response['same_day_eligible'];

respondSuccess($response);
