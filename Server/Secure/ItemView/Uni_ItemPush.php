<?php
include_once "../connect.php";
include_once "../../Function/Func_Total.php";

$response = [
    "error" => null
];
include_once "../../Function/Auth/ArrayAuth.php";

$deviceTypeData = authDeviceType($data);
if (!empty($deviceTypeData['error'])) {
    http_response_code(400);
    $response['error'] = $deviceTypeData['error'];
    echo json_encode($response);
    exit;
}
$userId = $deviceTypeData['user_id'];

if (!isset($data['product_id']) || !is_int($data['product_id'])) {
    http_response_code(400);
    $response['error'] = "Invalid or missing product ID.";
    echo json_encode($response);
    exit();
}
$productId = (int)$data['product_id'];
$stmt = $pdo->prepare("INSERT INTO ItemView (UserId, ProductId, DateViewed) VALUES (?, ?, ?, NOW())");
$stmt->execute([$userId, $productId]);
echo json_encode($response);;
exit();
