<?php
include_once "../../Connect.php";
include_once "../../Function/Components.php";
include_once "../../Function/Response.php";

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    respondWithMsg("Invalid or missing product ID.");
}

$productId = $data['product_id'];
$page      = max(1, (int)($data['page'] ?? 1));
$limit     = min(50, max(1, (int)($data['limit'] ?? 10)));

$result = getReviews($pdo, $productId, $page, $limit);

if (!empty($result['error'])) {
    respondWithMsg($result['error']);
}

respondSuccess([
    'reviews'     => $result['reviews'],
    'total_count' => $result['total_count'],
    'avg_rating'  => $result['avg_rating']
]);
