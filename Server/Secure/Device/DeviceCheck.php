<?php
include_once "../connect.php";
include_once "../../Function/Components.php";


$response = [
    'is_device_known'   => false,
    'same_day_eligible' => false,
    'tax_rate'          => 0.00,
    'has_active_order'  => false,
    'message'           => null,
    'error'             => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    respondWithMsg("Device type is required.");
}

$userDeviceType = $data['device_type'];
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
    $userId              = (int)($_SESSION['user_id'] ?? 0);
    $userDeviceSignature = generateDeviceSignature();
    $_SESSION['device_signature'] = $userDeviceSignature;
}

$isSameDayEligible = isSameDayEligible($pdo, $userDeviceSignature, $userId);

// SECURITY: Error handling (Vulnerability #14)
if (!empty($isSameDayEligible['error'])) {
    respondWithError($isSameDayEligible['error'], 500);
}

if (!empty($isSameDayEligible['message'])) {
    respondWithMsg($isSameDayEligible['message']);
}

$response['same_day_eligible'] = $isSameDayEligible['same_day_eligible'];
$response['is_device_known'] = $isSameDayEligible['is_device_known'];
$response['tax_rate'] = $isSameDayEligible['tax_rate'];
$response['has_active_order'] = $isSameDayEligible['has_active_order'];

// Store values in session for later use
if ($response['tax_rate'] !== 0.00) {
    $_SESSION['tax_rate'] = $response['tax_rate'];
}

$_SESSION['same_day_eligible'] = $response['same_day_eligible'];
$_SESSION['has_active_order'] = $response['has_active_order'];

respondSuccess($response);
