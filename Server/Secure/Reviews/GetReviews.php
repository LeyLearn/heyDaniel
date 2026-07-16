<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing product ID.']);
    exit;
}

$productId = $data['product_id'];
$page      = max(1, (int)($data['page'] ?? 1));
$limit     = min(50, max(1, (int)($data['limit'] ?? 10)));

$result = getReviews($pdo, $productId, $page, $limit);

if (!empty($result['error'])) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

echo json_encode([
    'reviews'     => $result['reviews'],
    'total_count' => $result['total_count'],
    'avg_rating'  => $result['avg_rating']
]);
exit;
