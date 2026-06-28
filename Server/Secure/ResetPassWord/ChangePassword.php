<?php
include_once __DIR__ . "/../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    "success" => false,
    "message" => null,
    "error"   => null
];


if (!isset($data['user_email']) || !isset($data['new_password']) || !isset($data['confirm_password'])) {
    http_response_code(400);
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}

$userEmail = strtolower(trim($data['user_email']));
$password = trim($data['new_password']);
$confirmPassword = trim($data['confirm_password']);

$updatePassword = updatePassword($pdo, $userEmail, $password, $confirmPassword);

if (!empty($)) {
    http_response_code(400);
    echo json_encode($response);
    exit;
}
if (!empty($)) {
    $response['message'] = $;
    echo json_encode($response);
    exit;
}

$response['success'] = $updatePassword['success'] ?? false;

echo json_encode($response);
exit;

