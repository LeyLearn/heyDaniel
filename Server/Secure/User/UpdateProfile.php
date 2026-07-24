<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);
$userDeviceType = $data['device_type'];

$name  = (string)($data['name'] ?? '');
$phone = (string)($data['phone'] ?? '');

$result = updateProfile($pdo, $userId, $name, $phone);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

if ($userDeviceType === 'Web') {
    $_SESSION['user_name']  = $result['name'];
    $_SESSION['user_phone'] = $result['phone'];
}

echo json_encode(['success' => true, 'name' => $result['name'], 'phone' => $result['phone']]);
exit;
