<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    'success' => false,
    'error'   => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['user_email']) || !isset($data['unique_code'])) {
    http_response_code(400);
    $response['error'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

$userEmail  = strtolower(trim($data['user_email']));
$uniqueCode = trim($data['unique_code']);

$verifyCode = verifyCode($pdo, $userEmail, $uniqueCode);

if (!empty($verifyCode['error'])) {
    http_response_code(400);
    $response['error'] = $verifyCode['error'];
    echo json_encode($response);
    exit;
}

$response['success'] = true;

echo json_encode($response);
exit;
