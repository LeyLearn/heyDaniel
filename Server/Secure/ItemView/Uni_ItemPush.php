<?php
include_once "../connect.php";

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/New_York');
$rawInput = file_get_contents('php://input');

$userId = 0;
$response = [
    "error" => null
];

if ($rawInput === false || $rawInput === "" || strlen($rawInput) > 1000) {
    http_response_code(400);
    $response["error"] = "Invalid input data.";
    echo json_encode($response);
    exit();
}
$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['error'] = "Malformed JSON input.";
    echo json_encode($response);
    exit();
}

if (!isset($data['device_type']) || !is_string($data['device_type']) || $data['device_type'] === "") {
    http_response_code(400);
    $response['error'] = "Invalid or missing device type.";
    echo json_encode($response);
    exit();
}
$deviceType = trim($data['device_type']);
$allowedDevices = ["Web", "iOS", "Android"];
if (!in_array($deviceType, $allowedDevices, true)) {
    http_response_code(400);
    $response['error'] = "Unsupported device type.";
    echo json_encode($response);
    exit();
}
if ($deviceType === "Web") {
    session_start();
    $userId = isset($_SESSION['UserId']) ? intval($_SESSION['UserId']) : 0;
    
} else {
    if (!isset($data['user_id']) || !is_int($data['user_id'])) {
        http_response_code(400);
        $response['error'] = "Invalid or missing user ID.";
        echo json_encode($response);
        exit();
    }
    (int)$data['UserId'] ?? 0;
}
if (!isset($data['product_id']) || !is_int($data['product_id'])) {
    http_response_code(400);
    $response['error'] = "Invalid or missing product ID.";
    echo json_encode($response);
    exit();
}
$productId = (int)$data['product_id'];
$viewedDate = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("INSERT INTO ItemView (UserId, ProductId, DateViewed) VALUES (?, ?, ?, ?)");
$stmt->execute([$userId, $productId, $viewedDate]);
echo json_encode($response);;
exit();
