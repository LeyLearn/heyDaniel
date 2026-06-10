<?php
include_once "../../Connect.php";

$response = [
    'data'  => [],
    'error' => null
];

include_once "../../Function/Auth/ArrayAuth.php";
/* --------------------------------------------------------------
   1. INPUT — we expect TWO fields from the app
   -------------------------------------------------------------- */
$level  = $data['level']  ?? null;   // "main", "sub", "third"
$parent = $data['parent'] ?? null;   // the actual category name user clicked (e.g. "Beer", "IPA")

// Basic validation
if (!is_string($level) || !is_string($parent) || $parent === '' || strlen($parent) > 100) {
    http_response_code(400);
    $response['error'] = 'Invalid or missing level/parent';
    echo json_encode($response);
    exit;
}

$level = strtolower(trim($level));
$parent = trim($parent);

/* --------------------------------------------------------------
   2. MAPPING — which column are we on? which one comes next?
   -------------------------------------------------------------- */
$columnMap = [
    'main'  => 'Pr_Category',
    'sub'   => 'Snd_Category',
    'third' => 'Thd_Category',
    'ext'   => 'Ext_Category'    // if you ever go deeper
];

$currentColumn = $columnMap[$level] ?? null;

if ($currentColumn === null) {
    http_response_code(400);
    $response['error'] = 'Invalid level';
    echo json_encode($response);
    exit;
}

// Define what the "next" column is
$nextColumnMap = [
    'main'  => 'Snd_Category',
    'sub'   => 'Thd_Category',
    'third' => 'Ext_Category',
];

$nextColumn = $nextColumnMap[$level] ?? null;

if ($nextColumn === null) {
    http_response_code(400);
    $response['error'] = 'No deeper level available';
    echo json_encode($response);
    exit;
}

/* --------------------------------------------------------------
   3. BUILD THE DYNAMIC QUERY — one query for all levels
   -------------------------------------------------------------- */
$sql = "
    SELECT DISTINCT
        p.`$nextColumn` AS category_name,
        (
            SELECT pi.ImageUrl
            FROM ProductImages pi
            WHERE pi.ProductId = p.Id
            ORDER BY pi.Id DESC
            LIMIT 1
        ) AS picture
    FROM Products p
    WHERE p.`$currentColumn` = ?
      AND p.`$nextColumn` != ''
      AND p.`$nextColumn` IS NOT NULL
    GROUP BY p.`$nextColumn`
    HAVING picture IS NOT NULL
    ORDER BY category_name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$parent]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------------------
   4. FORMAT OUTPUT
   -------------------------------------------------------------- */
$response['data'] = array_map(function($row) {
    return [
        'category' => $row['category_name'],
        'picture'  => $row['picture']
    ];
}, $results);

echo json_encode($response);