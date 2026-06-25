<?php

declare(strict_types=1);

// PERFORMANCE: Enable gzip compression
if (extension_loaded('zlib') &&
    str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip')) {
    ob_start('ob_gzhandler');
    header('Content-Encoding: gzip');
} else {
    ob_start();
}

// SECURITY: Enforce HTTPS (Vulnerability #7, #16)
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://' . $host . $uri);
    exit;
}

// SECURITY: Add Security Headers (Vulnerability #12)
header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Access-Control-Allow-Origin: none');

include_once 'Function/Response.php';

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

// SECURITY: Input size validation (Vulnerability #13)
$maxPayloadSize = 1024 * 1024;  // 1MB limit
$rawInput = file_get_contents('php://input');

if ($rawInput === false) {
    respondWithError('Failed to read input', 400);
}

if (strlen($rawInput) > $maxPayloadSize) {
    respondWithMsg('Payload too large', 413);
}

$data = json_decode($rawInput, true);
if ($data === null && $rawInput !== '') {
    respondWithError('Invalid JSON', 400);
}

$action = trim($data['action'] ?? '');

if ($action === '' || !isset($routes[$action])) {
    respondWithMsg('Not found', 404);
}

// SECURITY: Path traversal prevention (Vulnerability #1)
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

ob_start();
try {
    include_once $file;
} catch (Exception $e) {
    error_log("Error in $action: " . $e->getMessage());
    respondWithError('Internal server error', 500);
}
$output = ob_get_clean();

echo $output;
