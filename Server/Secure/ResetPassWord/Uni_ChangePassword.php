<?php

include_once "../connect.php";

$response = [
    "success" => false,
    "error" => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!is_array($data) || !isset($data['user_email']) || !isset($data['new_password']) || !isset($data['confirm_password']) || count($data) !== 3) {
    http_response_code(400);
    $response['error'] = 'Invalid request';
    echo json_encode($response);
    exit;
}
$userEmail = strtolower(trim($data['user_email']));
$newPassword = trim($data['new_password']);
$confirmPassword = trim($data['confirm_password']);
if ($userEmail === "" || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['error'] = 'Invalid email format';
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
    $response['error'] = 'Password too weak. Must be 8+ chars with uppercase, lowercase, number, and symbol.';
    echo json_encode($response);
    exit;
}
if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    $response['error'] = 'Passwords do not match';;
    echo json_encode($response);
    exit;
}
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE Users SET Password = ? WHERE Email = ?");
$success = $stmt->execute([$hashedPassword, $userEmail]);
if (!$success) {
    http_response_code(500);
    $response['error'] = 'Failed to update password. Please try again later.';
    echo json_encode($response);
    exit;
} else {
    // delete any existing reset codes for this email
    $deleteStmt = $pdo->prepare("DELETE FROM PasswordResetCodes WHERE Email = ?");
    $deleteStmt->execute([$userEmail]);
    $response['success'] = true;
}
echo json_encode($response);
