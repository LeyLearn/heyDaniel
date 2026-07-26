<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

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
    'pulling_products'   => 'Secure/Products/ProductSlide.php',

    // Checkout & Orders
    'checkout'          => 'Secure/Checkout/Checkout.php',
    'finalize_order'    => 'Secure/Checkout/FinalizeOrder.php',
    'order_history'     => 'Secure/Order/OrderHistory.php',
    'order_details'     => 'Secure/Order/OrderDetails.php',
    'cancel_order'      => 'Secure/Order/CancelOrder.php',
    'reschedule_delivery' => 'Secure/Order/RescheduleDelivery.php',

    // Reviews
    'reviews_get'       => 'Secure/Reviews/GetReviews.php',
    'reviews_add'       => 'Secure/Reviews/AddReview.php',

    // User
    'register'          => 'Secure/User/Register.php',
    'login'             => 'Secure/User/Login.php',
    'google_login'      => 'Secure/User/GoogleLogin.php',
    'logout'            => 'Secure/User/Logout.php',
    'update_profile'    => 'Secure/User/UpdateProfile.php',
    'save_address'      => 'Secure/User/SaveAddress.php',
    'delete_address'    => 'Secure/User/DeleteAddress.php',
    'list_addresses'    => 'Secure/User/ListAddresses.php',
    'payment_methods'   => 'Secure/User/PaymentMethods.php',
    'change_account_password' => 'Secure/User/ChangeAccountPassword.php',
    'notifications'     => 'Secure/User/Notifications.php',
    'mark_notification_read' => 'Secure/User/MarkNotificationRead.php',
    'subscribe_membership' => 'Secure/User/SubscribeMembership.php',
    'cancel_membership' => 'Secure/User/CancelMembership.php',

    // Password Reset
    'collect_email'     => 'Secure/CollectEmail.php',
    'verify_code'       => 'Secure/ResetPassWord/VerifyEmail.php',
    'change_password'   => 'Secure/ResetPassWord/ChangePassword.php',
];

$rawInput = file_get_contents('php://input');

if ($rawInput === false || $rawInput === '' || strlen($rawInput) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

$data = json_decode($rawInput, true);

if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Malformed JSON input']);
    exit;
}

$action = $data['action'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'No action specified']);
    exit;
}

if (!isset($routes[$action])) {
    http_response_code(404);
    echo json_encode(['error' => "Route '$action' not found"]);
    exit;
}

// Include and execute the route
$routePath = __DIR__ . '/' . $routes[$action];
if (!file_exists($routePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Route file not found']);
    exit;
}

include $routePath;
