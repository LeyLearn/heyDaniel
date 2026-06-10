<?php
include_once "../../Connect.php";

$response = [
    'data'           => [],
    'pinned_product' => [],
    'filters'     => [],
    'context'        => null,
    'error'          => null
];

include_once "../../Function/Auth/ArrayAuth.php";

$perspective = $data['perspective'] ?? null;

$validPerspective = ['product_id', 'categories', 'recommendations'];
if(!in_array($perspective, $validPerspective, true)){
    http_response_code(400);
    $response['error'] = "Invalid data sources specified.";
    echo json_encode($response);
    exit;
}

if($perspective === 'product_id'){
    if(!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0){
        http_response_code(400);
        $response['error'] = "Invalid or missing product information.";
        echo json_encode($response);
        exit;
    }
    $productId = (int)$data['product_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT p.* 
                           ");


}


$product_id = (int)($data['product_id'] ?? 0);
$list_type  = $data['list_type'] ?? null; // popular, best_seller, recently_viewed

/* ==================== 2. DETERMINE MODE & CONTEXT ==================== */
$base_where  = "p.inStock = 'Yes'";
$base_params = [];
$order_by    = "p.Id DESC";
$pinned      = null;
$context     = ['level' => 'global', 'type' => 'catalog'];

if ($product_id > 0) {
    // ───── CLICKED SINGLE PRODUCT ─────
    $stmt = $pdo->prepare("
        SELECT p.*, COALESCE(pi.ImageUrl, p.Picture) AS display_picture
        FROM Products p
        LEFT JOIN ProductImages pi ON pi.ProductId = p.Id 
            AND pi.Id = (SELECT MIN(Id) FROM ProductImages WHERE ProductId = p.Id)
        WHERE p.Id = ? AND p.inStock = 'Yes'
        LIMIT 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        $response['error'] = 'Product not found or out of stock';
        echo json_encode($response); exit;
    }

    // Find deepest category
    $levels = ['ext' => 'Ext_Category', 'third' => 'Thd_Category', 'sub' => 'Snd_Category', 'main' => 'Pr_Category'];
    $locked_col = 'Pr_Category';
    $locked_val = $product['Pr_Category'];

    foreach (['ext','third','sub'] as $lvl) {
        $col = $levels[$lvl];
        if (!empty($product[$col]) && $product[$col] !== 'null') {
            $locked_col = $col;
            $locked_val = $product[$col];
            break;
        }
    }

    $base_where .= " AND p.`$locked_col` = ?";
    $base_params[] = $locked_val;

    $context = ['level' => array_search($locked_col, $levels), 'parent' => $locked_val, 'type' => 'product'];
    $order_by = "p.Id = ? DESC, p.Id DESC";
    array_unshift($base_params, $product_id);

    $pinned = [
        'Id'         => $product['Id'],
        'Brand'      => $product['Brand'],
        'Name'       => $product['Name'],
        'Oz'         => $product['Oz'],
        'Price'      => $product['Price'],
        'Picture'    => $product['display_picture'],
        'isOnSale'   => $product['isOnSale'],
        'SalePrice'  => $product['SalePrice'],
        'isBogo'     => $product['isBogo']
    ];

} else {
    // ───── LIST MODE (popular, best_seller, recently_viewed, etc.) ─────
    $valid_lists = ['popular', 'best_seller', 'recently_viewed', 'recommended'];
    $list_type = in_array($list_type, $valid_lists, true) ? $list_type : 'popular';

    switch ($list_type) {
        case 'recently_viewed':
            $base_where .= " AND p.Id IN (SELECT product_id FROM RecentlyViewed WHERE user_id = ? ORDER BY viewed_at DESC)";
            $base_params[] = $_SESSION['user_id'] ?? 0;
            $order_by = "p.Id DESC";
            break;
        case 'best_seller':
            $order_by = "(SELECT COALESCE(SUM(quantity),0) FROM OrderItems WHERE product_id = p.Id) DESC, p.Id DESC";
            break;
        case 'popular':
            $order_by = "p.views DESC, p.Id DESC";
            break;
        case 'recommended':
            $order_by = "p.rating DESC, p.Id DESC";
            break;
    }
    $context['type'] = $list_type;
}

/* ==================== 3. GENERATE FILTERS (scoped or global) ==================== */
$filters = [];

// Brands
$stmt = $pdo->prepare("SELECT DISTINCT Brand FROM Products p WHERE $base_where ORDER BY Brand");
$stmt->execute($base_params);
$filters['brands'] = array_values(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN)));

// Sizes
$stmt = $pdo->prepare("SELECT DISTINCT Oz FROM Products p WHERE $base_where ORDER BY CAST(REPLACE(Oz,'oz','') AS DECIMAL) ASC");
$stmt->execute($base_params);
$filters['sizes'] = array_values(array_filter($stmt->fetchAll(PDO::FETCH_COLUMN)));

// Price range
$stmt = $pdo->prepare("SELECT MIN(CAST(Price AS DECIMAL(10,2))), MAX(CAST(Price AS DECIMAL(10,2))) FROM Products p WHERE $base_where");
$stmt->execute($base_params);
$row = $stmt->fetch(PDO::FETCH_NUM);
$filters['price_range'] = ['min' => (float)($row[0] ?? 0), 'max' => (float)($row[1] ?? 0)];

/* ==================== 4. APPLY USER FILTERS ==================== */
$user_filters = $data['filters'] ?? [];

if (!empty($user_filters['price_min'])) {
    $base_where .= " AND CAST(p.Price AS DECIMAL(10,2)) >= ?";
    $base_params[] = (float)$user_filters['price_min'];
}
if (!empty($user_filters['price_max'])) {
    $base_where .= " AND CAST(p.Price AS DECIMAL(10,2)) <= ?";
    $base_params[] = (float)$user_filters['price_max'];
}
if (!empty($user_filters['brands']) && is_array($user_filters['brands'])) {
    $brands = array_intersect($user_filters['brands'], $filters['brands']);
    if ($brands) {
        $placeholders = str_repeat('?,', count($brands)-1).'?';
        $base_where .= " AND p.Brand IN ($placeholders)";
        $base_params = array_merge($base_params, $brands);
    }
}
if (!empty($user_filters['sizes']) && is_array($user_filters['sizes'])) {
    $sizes = array_intersect($user_filters['sizes'], $filters['sizes']);
    if ($sizes) {
        $placeholders = str_repeat('?,', count($sizes)-1).'?';
        $base_where .= " AND p.Oz IN ($placeholders)";
        $base_params = array_merge($base_params, $sizes);
    }
}
if (!empty($user_filters['on_sale'])) $base_where .= " AND p.isOnSale = 'Yes'";
if (!empty($user_filters['bogo']))    $base_where .= " AND p.isBogo = 'Yes'";

/* ==================== 5. FINAL PRODUCTS QUERY ==================== */
$page  = max(1, (int)($data['page'] ?? 1));
$limit = min(50, max(10, (int)($data['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$sql = "SELECT p.Id, p.Brand, p.Name, p.Oz, p.Price, COALESCE(pi.ImageUrl, p.Picture) AS Picture,
               p.isOnSale, p.SalePrice, p.isBogo
        FROM Products p
        LEFT JOIN ProductImages pi ON pi.ProductId = p.Id AND pi.Id = (SELECT MIN(Id) FROM ProductImages WHERE ProductId = p.Id)
        WHERE $base_where
        ORDER BY $order_by
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($base_params, [$limit, $offset]));
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==================== 6. RESPONSE ==================== */
$response['data']           = $products;
$response['filters']        = $filters;
$response['context']        = $context;
$response['pinned_product'] = $pinned;
$response['pagination'] = [
    'page'  => $page,
    'limit' => $limit,
    'total' => (int)$pdo->prepare("SELECT COUNT(*) FROM Products p WHERE $base_where")
                       ->execute($base_params) ? $pdo->fetchColumn() : 0
];

echo json_encode($response);