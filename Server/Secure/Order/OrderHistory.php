<?php
include_once "../../Connect.php";
include_once "../../Function/Components.php";
include_once "../../Function/Response.php";

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    respondWithMsg("Device type is required.");
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId           = 0;
$taxRate          = 0.00;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    respondWithMsg("Invalid device type.");
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId  = (int)($data['user_id'] ?? 0);
    $taxRate = (float)($data['tax_rate'] ?? 0.00);
} else {
    session_start();
    $userId  = (int)($_SESSION['user_id'] ?? 0);
    $taxRate = (float)($_SESSION['tax_rate'] ?? 0.00);
}

if ($userId <= 0) {
    respondWithMsg("User not authenticated. Please log in.", 401);
}

$result = orderHistory($pdo, $userId, $taxRate);

if (!empty($result['error'])) {
    respondWithMsg($result['error']);
}

respondSuccess(['orders' => $result['orders']]);
