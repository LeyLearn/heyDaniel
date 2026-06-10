<?php
include_once "../connect.php";
include_once "../../Function/Func_Total.php";

$response = [
    'is-saved' => null,
    'saved_count' => 0,
    'error' => null
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


if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    http_response_code(400);
    $response['error'] = "invalid or Missing Product information.";
    echo json_encode($response);
    exit;
}

$productId = $data['product_id'];

if ($userId <= 0) {
    http_response_code(400);
    $response['error'] = "Please log in to save item.";
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();

    // 5.1. Check if saved
    $stmt = $pdo->prepare("SELECT 1 FROM Saved WHERE UserId = ? AND ProductId = ?");
    $stmt->execute([$userId, $productId]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // remove from Saved
        $stmt = $pdo->prepare("DELETE FROM Saved WHERE UserId = ? AND ProductId = ?");
        $stmt->execute([$userId, $productId]);
        $response['is_saved'] = false;
    } else {
        // add to Saved (DateAdded is stored using $viewedDate)
        $stmt = $pdo->prepare("INSERT INTO Saved (UserId, ProductId, DateAdded) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $productId]);
        $response['is_saved'] = true;
    }

    // 5.2. Get updated count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Saved WHERE UserId = ?");
    $stmt->execute([$userId]);
    $savedCount = $stmt->fetchColumn();
    $response['saved_count'] = (int)$savedCount;
    
    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("DB Error in Save Toggle: " . $e->getMessage()); // Log the actual error
    http_response_code(500);
    $response['error'] = "Internal server error during save operation.";
    echo json_encode($response);
    exit;
}

echo json_encode($response);
