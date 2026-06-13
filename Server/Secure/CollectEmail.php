<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    "is_registered" => false,
    "email" => null,
    "message" => null,
    "error" => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['is_updating_password'])) {
    http_response_code(400);
    $response['error'] = "Missing is_update_password field.";
    echo json_encode($response);
    exit;
}

if (!isset($data['email']) || $data['email'] === "") {
    http_response_code(400);
    $response['error'] = "Email is required.";
    echo json_encode($response);
    exit;
}

$userEmail = trim($data['email']);
$isUpdatingPassword = (bool)$data['is_updating_password'];

$collectEmail = collectEmail($pdo, $userEmail, $isUpdatingPassword);

if (!empty($collectEmail['error'])) {
    http_response_code(400);
    $response['error'] = $collectEmail['error'];
    echo json_encode($response);
    exit;
}

$response['is_registered'] = $collectEmail['is_registered'];
$response['email'] = $collectEmail['email'];
$response['message'] = $collectEmail['message'];

echo json_encode($response);
exit;
