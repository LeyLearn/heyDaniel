<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$theme = (string)($data['theme'] ?? '');

$result = setUserTheme($pdo, $userId, $theme);

if (!empty($result['error'])) {
    http_response_code($result['error'] === 'Invalid theme.' ? 400 : 500);
    echo json_encode(['error' => $result['error']]);
    exit;
}

$_SESSION['user_theme'] = $theme;

echo json_encode(['success' => true]);
exit;
