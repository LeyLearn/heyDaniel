<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$currentPassword = (string)($data['current_password'] ?? '');
$newPassword     = (string)($data['new_password'] ?? '');
$confirmPassword = (string)($data['confirm_password'] ?? '');

$result = changeAccountPassword($pdo, $userId, $currentPassword, $newPassword, $confirmPassword);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['success' => true]);
exit;
