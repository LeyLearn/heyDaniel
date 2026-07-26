<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    'cart_items'                => [],
    'is_processing'             => false,
    'order_status'              => null,
    'order_placed_at'           => null,
    'scheduled_delivery_window' => null,
    'message'                   => null,
    'error'                     => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['message'] = "Device type is required.";
    echo json_encode($response);
    exit;
}
$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;
$taxRate = 0.00;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['message'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = resolveMobileUserId($data);
    $taxRate = (float)($data['tax_rate'] ?? 0.00);
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
    $taxRate = (float)($_SESSION['tax_rate'] ?? 0.00);
}

$activeOrderId = getActiveSameDayOrderId($pdo, $userId);

if ($activeOrderId !== null) {
    $orderStatus = getDisplayOrderStatus($pdo, $userId);
    $content = processContent($pdo, $userId, $activeOrderId, $taxRate, $orderStatus);
    $response['is_processing'] = true;
    $response['order_status'] = $orderStatus;

    $stmt = $pdo->prepare("SELECT DateAdded, ScheduledDeliveryWindow FROM OrderSent WHERE Id = ?");
    $stmt->execute([$activeOrderId]);
    $orderRow = $stmt->fetch();
    $response['order_placed_at'] = $orderRow['DateAdded'] ?? null;
    $response['scheduled_delivery_window'] = $orderRow['ScheduledDeliveryWindow'] ?? null;
} else {
    $content = cartContent($pdo, $userId, $taxRate);
}

if (!empty($content['error'])) {
    http_response_code(500);
    $response['error'] = $content['error'];
    echo json_encode($response);
    exit;
}

$response['cart_items'] = $content['cart_items'];


echo json_encode($response);
exit;
