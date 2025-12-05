<?php
include_once "../connect.php";
date_default_timezone_set('America/New_York');

header('Content-Type: application/json; charset=utf-8');
$rawInput = file_get_contents('php://input');
$response = [
    'isSaved' => null,
    'savedCount' => 0,
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
$viewedDate = date('Y-m-d H:i:s'); // store as proper DATETIME
$deviceType = $data['device_type'];

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
}else{
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
        $response['isSaved'] = false;
    } else {
        // add to Saved (DateAdded is stored using $viewedDate)
        $stmt = $pdo->prepare("INSERT INTO Saved (UserId, ProductId, DateAdded) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $productId, $viewedDate]);
        $response['isSaved'] = true;
    }

    // 5.2. Get updated count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Saved WHERE UserId = ?");
    $stmt->execute([$userId]);
    $savedCount = $stmt->fetchColumn();
    $response['savedCount'] = (int)$savedCount;
    
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
