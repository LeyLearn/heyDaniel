<?php
include_once __DIR__ . "/../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    'success' => false,
    'message' => null,
    'error'   => null
];


if (!isset($data['user_email']) || !isset($data['unique_code'])) {
    http_response_code(400);
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

$userEmail  = strtolower(trim($data['user_email']));
$uniqueCode = trim($data['unique_code']);

$verifyCode = verifyCode($pdo, $userEmail, $uniqueCode);

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

$response['success'] = true;

echo json_encode($response);
exit;
