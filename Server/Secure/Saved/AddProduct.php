<?php
include_once __DIR__ . "/../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    'is_saved'    => false,
    'saved_count' => 0,
    'message'     => null,
    'error'       => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['message'] = "Device type is required.";
    echo json_encode($response);
    exit;
}
if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    http_response_code(400);
    $response['message'] = "Invalid or missing product ID.";
    echo json_encode($response);
    exit;
}
$productId = $data['product_id'];
$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['message'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = (int)($data['user_id'] ?? 0);
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
}

$addSaved = addSaved($pdo, $productId, $userId);

if (!empty($)) {
    http_response_code(400);
    echo json_encode($response);
    exit;
}
if (!empty($)) {
    $response['message'] = $;
    echo json_encode($response);
    exit;
}

$response['saved_count'] = $addSaved['saved_count'] ?? 0;
$response['is_saved'] = $addSaved['is_saved'] ?? false;

echo json_encode($response);
exit;
