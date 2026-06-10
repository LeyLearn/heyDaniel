<?php
include_once "../Connect.php";
include_once "../../Function/Func_Total.php";

$response = [
    'location' => null,
    'count'    => 0,
    'total'    => 0.0,
    'error'    => null
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

$stmt = $pdo->prepare("
        SELECT 1 FROM OrderSent
        WHERE UserId = ? AND OrderStatus = 'Processing' AND isSameDay = 1
        LIMIT 1
");
$stmt->execute([$userId]);
$hasActiveSameDayOrder = $stmt->fetchColumn() !== false;

$validTables = ['Process', 'Cart'];
$sourceTables = $hasActiveSameDayOrder ? 'Process' : 'Cart';
if (!in_array($sourceTables, $validTables, true)) {
    http_response_code(400);
    $response['error'] = "Invalid table source";
    echo json_encode($response);
    exit;
}

$query = null;
if ($sourceTables === 'Process') {
    $query = "SELECT 
            COUNT(*) AS ItemCount, 
            SUM(
                CI.Quantity * COALESCE(
                    CASE 
                        WHEN P.isOnSale = 1 THEN P.SalePrice 
                        ELSE P.Price 
                    END, P.Price)
            ) AS TotalAmount
        FROM Process CI 
        INNER JOIN Products P 
            ON CI.ProductId = P.Id 
        WHERE CI.UserId = ? ";

    $response['location'] = "Process";
} else {
    $query = "SELECT 
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
        WHERE CI.UserId = ? ";

    $response['location'] = "Cart";
}

$countStmt = $pdo->prepare($query);
$countStmt->execute([$userId]);
$cartData = $countStmt->fetch(PDO::FETCH_ASSOC);

$response['count'] = (int)$cartData['ItemCount'];
$response['total'] = round((float)$cartData['TotalAmount'], 2);

echo json_encode($response);
exit;
