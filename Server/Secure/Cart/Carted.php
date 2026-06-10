<?php
include_once "../connect.php";
include_once "../../Function/Func_Total.php";

$response = [
    'success'         => false,
    'error'           => null,
    'data'            => []
];

include_once "../../Function/Auth/ArrayAuth.php";

$deviceTypeData = authDeviceType($data);
if (!empty($deviceTypeData['error'])) {
    http_response_code(400);
    $response['error'] = $deviceTypeData['error'];
    echo json_encode($response);
    exit;
}
$userId = $deviceTypeData['user_id'];

$userData = authenticateUser($pdo, $userId);

if (!empty($userData['error'])) {
    http_response_code(400);
    $response['error'] = $userData['error'];
    echo json_encode($response);
    exit;
}

$sourceTable = $userData['tableSource'];

if ($sourceTable === "Process") {
    $sql = " SELECT 
        src.ProductId,
        p.*,
        COALESCE(s.isSaved, 0)      AS isSaved,
        COALESCE(src.Quantity, 0)   AS ItemQuantity,
        COALESCE(r.avg_rating, 0)   AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
    FROM Process src
    INNER JOIN Products p
            ON src.ProductId = p.Id
    LEFT JOIN Saved s 
           ON s.ProductId = src.ProductId AND s.UserId = ?
    LEFT JOIN (
        SELECT 
            ProductId,
            ROUND(AVG(Stars), 2)   AS avg_rating,
            COUNT(*)               AS review_count
        FROM ItemReviews 
        GROUP BY ProductId
    ) r ON r.ProductId = src.ProductId
      WHERE src.UserId = ?
      ORDER BY src.DateAdded DESC ";
} else {
    $sql = " SELECT 
        src.ProductId,
        p.*,
        COALESCE(s.isSaved, 0)      AS isSaved,
        COALESCE(src.Quantity, 0)   AS ItemQuantity,
        COALESCE(r.avg_rating, 0)   AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
    FROM Cart src
    INNER JOIN Products p
            ON src.ProductId = p.Id
    LEFT JOIN Saved s 
           ON s.ProductId = src.ProductId AND s.UserId = ?
    LEFT JOIN (
        SELECT 
            ProductId,
            ROUND(AVG(Stars), 2)   AS avg_rating,
            COUNT(*)               AS review_count
        FROM ItemReviews 
        GROUP BY ProductId
    ) r ON r.ProductId = src.ProductId
      WHERE src.UserId = ?
      ORDER BY src.DateAdded DESC ";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productData = [];

    foreach ($results as $row) {
        $rating = $row['review_count'] > 0
            ? round((float)$row['avg_rating'], 2)
            : "No ratings yet";

        $productData[] = [
            'product_id'    => $row['ProductId'],
            'brand'        => $row['Brand'],
            'name'         => $row['Name'],
            'oz'           => $row['Oz'],
            'price'        => $row['Price'],
            'Picture'      => $row['Picture'],
            'is_on_sale'     => (bool)$row['isOnSale'],
            'sale_price'    => $row['SalePrice'],
            'is_bogo'       => (bool)$row['isBogo'],
            'is_saved'      => (int)$row['isSaved'],
            'is_in_cart'     => $row['ItemQuantity'] > 0 ? 1 : 0,
            'quantity'     => (int)$row['ItemQuantity'],
            'table_source'  => $sourceTable,
            'ratings'      => $rating,
            'review_count' => (int)$row['review_count']
        ];
    }


    $response['success'] = true;
    $response['data'] = $productData;
} catch (PDOException $e) {
    // CRITICAL FIX: Added database error handling
    error_log("DB Query Error: " . $e->getMessage());
    http_response_code(500);
    $response['success'] = false;
    $response['error'] = "Database query failed.";
}

echo json_encode($response);
exit;
