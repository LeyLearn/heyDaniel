<?php
include_once "../connect.php";
include_once "../Function/Func_Total.php";

header('Content-Type: application/json; charset=utf-8');
$rawInput = file_get_contents('php://input');

$response = [
  'success'         => false,
  'error'           => null,
  'data'            => [],
  'tableSource'     => null
];

$userId = 0;

if ($rawInput === false || $rawInput === "" || strlen($rawInput) > 256) {
  http_response_code(400);
  $response["error"] = "Invalid request payload size or empty content.";
  echo json_encode($response);
  exit;
}
$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  $response["error"] = "Malformed JSON.";
  echo json_encode($response);
  exit;
}

if (!isset($data['device_type']) || !is_string($data['device_type']) || $data['device_type'] === "") {
  http_response_code(400);
  $response["error"] = "Missing device type.";
  echo json_encode($response);
  exit;
}
$deviceType = $data['device_type'];

$allowedDevices = ['Web', 'iOS', 'Android'];
if (!in_array($deviceType, $allowedDevices, true)) {
  http_response_code(400);
  $response["error"] = "Invalid device type.";
  echo json_encode($response);
  exit;
}
if ($deviceType === "Web") {
  session_start();
  $userId = isset($_SESSION['UserId']) ? (int)$_SESSION['UserId'] : 0;
} else {
  if (!isset($data['UserId']) || !is_int($data['UserId'])) {
    http_response_code(400);
    $response["error"] = "UserId required for mobile devices.";
    echo json_encode($response);
    exit;
  }
  $userId = (int)$data['UserId'] ?? 0;
}

$funcData = authenticateUser($pdo, $userId);

if (!empty($funcData['error'])) {
  http_response_code(400);
  $response['error'] = $funcData['error'];
  echo json_encode($response);
  exit;
}

$sourceTable = $funcData['tableSource'];


if ($sourceTable === "Process") {
  $sql = "
    SELECT 
        s.ProductId,
        p.*,
        COALESCE(src.Quantity, 0)   AS ItemQuantity,
        COALESCE(r.avg_rating, 0)   AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
    FROM Saved s
    INNER JOIN Products p ON s.ProductId = p.Id
    LEFT JOIN Process src 
           ON src.ProductId = s.ProductId AND src.UserId = ?
    LEFT JOIN (
        SELECT 
            ProductId,
            ROUND(AVG(Stars), 2)   AS avg_rating,
            COUNT(*)               AS review_count
        FROM ItemReviews 
        GROUP BY ProductId
    ) r ON r.ProductId = s.ProductId
     WHERE s.UserId = ?
     ORDER BY s.DateAdded DESC
";
} else {
  $sql = "
    SELECT 
        s.ProductId,
        p.*,
        COALESCE(src.Quantity, 0)   AS ItemQuantity,
        COALESCE(r.avg_rating, 0)   AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
    FROM Saved s
    INNER JOIN Products p ON s.ProductId = p.Id
    LEFT JOIN Cart src 
           ON src.ProductId = s.ProductId AND src.UserId = ?
    LEFT JOIN (
        SELECT 
            ProductId,
            ROUND(AVG(Stars), 2)   AS avg_rating,
            COUNT(*)               AS review_count
        FROM ItemReviews 
        GROUP BY ProductId
    ) r ON r.ProductId = s.ProductId
      WHERE s.UserId = ?
      ORDER BY s.DateAdded DESC
";
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
      'ProductId'    => $row['ProductId'],
      'Brand'        => $row['Brand'],
      'Name'         => $row['Name'],
      'Oz'           => $row['Oz'],
      'Price'        => $row['Price'],
      'Picture'      => $row['Picture'],
      'isOnSale'     => (bool)$row['isOnSale'],
      'SalePrice'    => $row['SalePrice'],
      'isBogo'       => (bool)$row['isBogo'],
      'isSaved'      => (int)$row['isSaved'],
      'isInCart'     => $row['ItemQuantity'] > 0 ? 1 : 0,
      'Quantity'     => (int)$row['ItemQuantity'],
      'tableSource'  => $sourceTable,
      'Ratings'      => $rating,
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
