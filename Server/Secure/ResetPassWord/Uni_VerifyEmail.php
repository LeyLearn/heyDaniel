<?php

include_once "../connect.php";

$response = [
    "success" => false,
    "error" => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!is_array($data) || !isset($data['user_email']) || !isset($data['unique_code']) || count($data) !== 2) {
    http_response_code(400);
    $response['error'] = 'Invalid request';
    echo json_encode($response);
    exit;
}
$userEmail = strtolower(trim($data['user_email']));
$uniqueCode = trim($data['unique_code']);

if ($userEmail === "" || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['error'] = 'Invalid email format';
    echo json_encode($response);
    exit;
}
if ($uniqueCode === null || !preg_match('/^\d{6}$/', $uniqueCode)) {
    http_response_code(400);
    $response['error'] = 'Invalid code format';
    echo json_encode($response);
    exit;
}
date_default_timezone_set('America/New_York');
$currentTime = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT Code, ExpiredAt FROM PasswordResetCodes WHERE Email = ? LIMIT 1");
$stmt->execute([$userEmail]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    $response['error'] = 'No code was sent to this email. Please request a code first.';
    echo json_encode($response);
    exit;
}
$dbCode = $row['Code'];
$expiredAt = $row['ExpiredAt'];
if ($uniqueCode !== $dbCode) {
    $response['error'] = 'The code entered was not valid.';
    echo json_encode($response);
    exit;
} else if ($currentTime > $expiredAt) {
    $response['error'] = 'The code has expired. Please request a new one.';
    // delete the expired code from the database here
    $deleteStmt = $pdo->prepare("DELETE FROM PasswordResetCodes WHERE Email = ?");
    $deleteStmt->execute([$userEmail]);
    echo json_encode($response);
    exit;
} else {
    $response['success'] = true;
    echo json_encode($response);
    exit;
}