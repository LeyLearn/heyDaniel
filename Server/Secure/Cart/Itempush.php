<?php
include_once "../connect.php";
include_once "../../Function/Func_Total.php";

$response = [
   'is_in_cart' => null,
    'cart_count' => 0,
    'cart_total' => 0.0,
    'error' => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    http_response_code(400);
    $response['error'] = "invalid or Missing Product information.";
    echo json_encode($response);
    exit;
}
$productId = $data['product_id'];
$quantity = 1;

$deviceTypeData = authDeviceType($data);
if (!empty($deviceTypeData['error'])) {
    http_response_code(400);
    $response['error'] = $deviceTypeData['error'];
    echo json_encode($response);
    exit;
}
$userId = $deviceTypeData['user_id'];

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
        $updateStmt = $pdo->prepare("UPDATE Cart SET Quantity = ?, DateAdded = NOW() WHERE UserId = ? AND ProductId = ?");
        $updateStmt->execute([$newQuantity, $userId, $productId]);
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO Cart (UserId, ProductId, Quantity, DateAdded) VALUES (?, ?, ?, NOW())");
        $insertStmt->execute([$userId, $productId, $quantity]);
    }
    $response['is_in_cart'] = true;
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

    $response['cart_count'] = (int)$cartData['ItemCount'];
    $response['cart_total'] = round((float)$cartData['TotalAmount'], 2);

    $pdo->commit();

    echo json_encode($response);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    $response['error'] = "Server error: " . $e->getMessage();
    echo json_encode($response);
    exit;
}
