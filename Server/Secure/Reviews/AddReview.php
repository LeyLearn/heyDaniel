<?php
include_once "../../Connect.php";
include_once "../../Function/Components.php";
include_once "../../Function/Response.php";

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    respondWithMsg("Device type is required.");
}

if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    respondWithMsg("Invalid or missing product ID.");
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

$productId   = $data['product_id'];
$stars       = (int)($data['stars'] ?? 0);
$expectation = (int)($data['expectation'] ?? 0);
$title       = trim((string)($data['title'] ?? ''));
$review      = trim((string)($data['review'] ?? ''));

$result = addReview($pdo, $userId, $productId, $stars, $expectation, $title, $review);

if (!empty($result['error'])) {
    respondWithMsg($result['error']);
}

respondSuccess(['success' => true]);
