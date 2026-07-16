<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

$userId = 0;
$token  = '';

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId = (int)($data['user_id'] ?? 0);
    $token  = trim($data['token']    ?? '');

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token is required.']);
        exit;
    }
} else {
    session_start();
    $userId = (int)($_SESSION['user_id'] ?? 0);
}

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$logout = logoutUser($pdo, $userId, $token);

if (!empty($logout['error'])) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

echo json_encode(['success' => true]);
exit;
