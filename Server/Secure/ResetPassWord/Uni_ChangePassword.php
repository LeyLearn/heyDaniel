<?php

include_once "../connect.php";
header('Content-Type: application/json; charset=utf-8');

$response = [
    "success" => false,
    "message" => null
];
$rawInput = file_get_contents('php://input');
if ($rawInput === false || $rawInput === '' || strlen($rawInput) > 2048) {
    http_response_code(400);
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}
$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['message'] = 'Malformed JSON';
    echo json_encode($response);
    exit;
}
if (!is_array($data) || !isset($data['userEmail']) || !isset($data['newPassword']) || !isset($data['confirmPassword']) || count($data) !== 3) {
    http_response_code(400);
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}
$userEmail = strtolower(trim($data['userEmail']));
$newPassword = trim($data['newPassword']);
$confirmPassword = trim($data['confirmPassword']);
if ($userEmail === "" || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit;
}

if (
    strlen($newPassword) < 8 ||
    !preg_match('/[A-Z]/', $newPassword) ||     // at least one uppercase
    !preg_match('/[a-z]/', $newPassword) ||     // at least one lowercase
    !preg_match('/[0-9]/', $newPassword) ||     // at least one digit
    !preg_match('/[^A-Za-z0-9]/', $newPassword) // at least one special char
) {
    http_response_code(400);
    $response['message'] = 'Password too weak. Must be 8+ chars with uppercase, lowercase, number, and symbol.';
    echo json_encode($response);
    exit;
}
if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    $response['message'] = 'Passwords do not match';;
    echo json_encode($response);
    exit;
}
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE Users SET Password = ? WHERE Email = ?");
$success = $stmt->execute([$hashedPassword, $userEmail]);
if (!$success) {
    http_response_code(500);
    $response['message'] = 'Failed to update password. Please try again later.';
    echo json_encode($response);
    exit;
} else {
    // delete any existing reset codes for this email
    $deleteStmt = $pdo->prepare("DELETE FROM PasswordResetCodes WHERE Email = ?");
    $deleteStmt->execute([$userEmail]);
    $response['success'] = true;
    $response['message'] = 'Success';
}
echo json_encode($response);
