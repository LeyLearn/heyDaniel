<?php
include_once "../../Connect.php";
include_once "../../Function/Components.php";
include_once "../../Function/Response.php";

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    respondWithMsg("Device type is required.");
}

if (!isset($data['device_signature'])) {
    respondWithMsg("Device signature is required.");
}

if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    respondWithMsg("Invalid or missing product ID.");
}

$allowedTables = ['RecentlyViewed', 'SearchHistory'];

if (!isset($data['table']) || !in_array($data['table'], $allowedTables, true)) {
    respondWithMsg("Invalid or missing table. Must be 'RecentlyViewed' or 'SearchHistory'.");
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId           = 0;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    respondWithMsg("Invalid device type.");
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId = (int)($data['user_id'] ?? 0);
} else {
    session_start();
    $userId = (int)($_SESSION['user_id'] ?? 0);
}

$productId       = $data['product_id'];
$deviceSignature = (string)$data['device_signature'];
$table           = $data['table'];

$result = itemPush($pdo, $userId, $deviceSignature, $productId, $table);

if (!empty($result['error'])) {
    respondWithMsg($result['error']);
}

respondSuccess(['success' => true]);
