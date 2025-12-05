<?php
include_once "../connect.php";
date_default_timezone_set('America/New_York');
header('Content-Type: application/json; charset=utf-8');
$rawInput = file_get_contents('php://input');
$response = [
    'isInCart' => null,
    'cartCount' => 0,
    'cartTotal' => 0.0,
    'error' => null
];

$userId = 0;
if ($rawInput === false || $rawInput === "" || strlen($rawInput) > 1000) {
    http_response_code(400);
    $response['error'] = "Invalid request payload size or empty content.";
    echo json_encode($response);
    exit;
}
$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['error'] = "Malformed JSON.";
    echo json_encode($response);
    exit;
}
if (!isset($data['ProductId']) || !isset($data['device_type']) || !is_int($data['ProductId']) || $data['ProductId'] <= 0 || $data['device_type'] === "") {
    http_response_code(400);
    $response['error'] = "invalid or Missing Product information.";
    echo json_encode($response);
    exit;
}
$productId = $data['ProductId'];
$addedDate = date('Y-m-d H:i:s');
$deviceType = $data['device_type'];
$quantity = 1;

$allowedDevices = ['Web', 'iOS', 'Android'];
if (!in_array($deviceType, $allowedDevices, true)) {
    http_response_code(400);
    $response['error'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}
if ($deviceType === "Web") {
    session_start();
    $userId = $_SESSION['UserId'] ?? 0;
} else {
    if (!isset($data['UserId']) || !is_int($data['UserId'])) {
        http_response_code(400);
        $response['error'] = "UserId required for mobile devices.";
        echo json_encode($response);
        exit;
    }
    $userId = (int)$data['UserId'] ?? 0;
}
if ($userId <= 0) {
    http_response_code(400);
    $response['error'] = "Please log in to add product to cart.";
    echo json_encode($response);
    exit;
}
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT Quantity FROM Cart WHERE UserId = ? AND ProductId = ?");
    $stmt->execute([$userId, $productId]);
    $existingQuantity = $stmt->fetchColumn();

    if ($existingQuantity !== false) {
        $newQuantity = $existingQuantity + $quantity;
        $updateStmt = $pdo->prepare("UPDATE Cart SET Quantity = ?, DateAdded = ? WHERE UserId = ? AND ProductId = ?");
        $updateStmt->execute([$newQuantity, $addedDate, $userId, $productId]);
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO Cart (UserId, ProductId, Quantity, DateAdded) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$userId, $productId, $quantity, $addedDate]);
    }
    $response['isInCart'] = true;
    $countStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS ItemCount, 
            SUM(
                CI.Quantity * COALESCE(
                    CASE 
                        WHEN P.isOnSale = 1 THEN P.SalePrice 
                        ELSE P.Price 
                    END, P.Price)
            ) AS TotalAmount
        FROM Cart CI 
        INNER JOIN Products P 
            ON CI.ProductId = P.Id 
        WHERE CI.UserId = ?
    ");
    $countStmt->execute([$userId]);
    $cartData = $countStmt->fetch(PDO::FETCH_ASSOC);

    $response['cartCount'] = (int)$cartData['ItemCount'];
    $response['cartTotal'] = round((float)$cartData['TotalAmount'], 2);

    $pdo->commit();

    echo json_encode($response);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    $response['error'] = "Server error: " . $e->getMessage();
    echo json_encode($response);
    exit;
}
