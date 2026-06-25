<?php
declare(strict_types=1);

// =====================================================
// PRODUCTION OPTIMIZED INDEX.PHP
// Performance optimizations for 1M+ concurrent users
// =====================================================

// Start output buffering with gzip compression
if (extension_loaded('zlib') &&
    str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip')) {
    ob_start('ob_gzhandler');
    header('Content-Encoding: gzip');
} else {
    ob_start();
}

// SECURITY: Enforce HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://' . $host . $uri);
    exit;
}

// SECURITY: Add Security Headers
header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Access-Control-Allow-Origin: none');

// PERFORMANCE: Enable caching headers
header('Cache-Control: private, max-age=60');
header('Vary: Accept-Encoding');

include_once 'Function/Response.php';
include_once 'Function/Cache.php';

// Define API routes
$routes = [
    // Device
    'device_check'      => 'Secure/Device/DeviceCheck.php',
    'device_log'        => 'Secure/Device/DeviceLog.php',

    // Cart
    'cart_icon'         => 'Secure/Cart/CartIcon.php',
    'cart_items'        => 'Secure/Cart/Carted.php',
    'cart_add'          => 'Secure/Cart/AddProduct.php',
    'cart_decrement'    => 'Secure/Cart/DecrementProduct.php',
    'cart_clear'        => 'Secure/Cart/ClearCart.php',

    // Saved
    'saved_count'       => 'Secure/Saved/Count.php',
    'saved_items'       => 'Secure/Saved/SavedItem.php',
    'saved_add'         => 'Secure/Saved/AddProduct.php',

    // Header
    'summary'           => 'Secure/Header/Summary.php',

    // Store
    'store'             => 'Secure/Store/Store.php',
    'product_detail'    => 'Secure/Store/ProductDetail.php',
    'filter'            => 'Secure/Store/Filter/Filter.php',
    'main_categories'   => 'Secure/Store/Filter/MainCategory.php',
    'sub_categories'    => 'Secure/Store/Filter/SubCategory.php',
    'third_categories'  => 'Secure/Store/Filter/ThirdCategory.php',

    // Search & Discovery
    'search'            => 'Secure/Engine/Search.php',
    'item_push'         => 'Secure/Engine/ItemPush.php',
    'recently_viewed'   => 'Secure/Engine/RecentlyViewed.php',

    // Checkout & Orders
    'checkout'          => 'Secure/Checkout/Checkout.php',
    'finalize_order'    => 'Secure/Checkout/FinalizeOrder.php',
    'order_history'     => 'Secure/Order/OrderHistory.php',

    // Reviews
    'reviews_get'       => 'Secure/Reviews/GetReviews.php',
    'reviews_add'       => 'Secure/Reviews/AddReview.php',

    // User
    'register'          => 'Secure/User/Register.php',
    'login'             => 'Secure/User/Login.php',
    'logout'            => 'Secure/User/Logout.php',

    // Password Reset
    'collect_email'     => 'Secure/CollectEmail.php',
    'verify_code'       => 'Secure/ResetPassWord/VerifyEmail.php',
    'change_password'   => 'Secure/ResetPassWord/ChangePassword.php',
];

// PERFORMANCE: Measure request time
$requestStart = microtime(true);

// SECURITY: Input size validation
$maxPayloadSize = 1024 * 1024;  // 1MB limit
$rawInput = file_get_contents('php://input');

if ($rawInput === false) {
    respondWithError('Failed to read input', 400);
}

if (strlen($rawInput) > $maxPayloadSize) {
    respondWithMsg('Payload too large', 413);
}

// Parse JSON input
$data = json_decode($rawInput, true);
if ($data === null && $rawInput !== '') {
    respondWithError('Invalid JSON', 400);
}

$action = trim($data['action'] ?? '');

// Route validation
if ($action === '' || !isset($routes[$action])) {
    respondWithMsg('Not found', 404);
}

// SECURITY: Path traversal prevention
$file = __DIR__ . '/' . $routes[$action];
$realPath = realpath($file);
$allowedDir = realpath(__DIR__);

if (!$realPath || strpos($realPath, $allowedDir) !== 0) {
    respondWithError('Access denied', 403);
}

if (!file_exists($file)) {
    respondWithError('Not found', 404);
}

chdir(dirname($file));

// PERFORMANCE: Cache key for similar operations
$cacheKey = "{$action}:" . md5(json_encode($data));

try {
    // Check if response is cacheable (GET-like operations)
    $cacheableActions = [
        'product_detail' => 3600,
        'main_categories' => 3600,
        'sub_categories' => 3600,
        'reviews_get' => 1800,
        'store' => 300
    ];

    if (isset($cacheableActions[$action])) {
        $cached = QueryCache::get($cacheKey);
        if ($cached !== null) {
            header('X-Cache: HIT');
            echo json_encode(['data' => $cached, 'message' => null, 'error' => null]);
            exit;
        }
    }

    // Execute the endpoint
    include_once $file;

} catch (Exception $e) {
    error_log("Error in $action: " . $e->getMessage());
    respondWithError('Internal server error', 500);
}

// PERFORMANCE: Log request time for slow query detection
$requestTime = microtime(true) - $requestStart;
if ($requestTime > 0.1) {  // 100ms threshold
    error_log("SLOW REQUEST: $action took " . number_format($requestTime * 1000, 2) . "ms");
}

// Flush output
ob_end_flush();
