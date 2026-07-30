<?php

include_once __DIR__ . '/Cache.php';

// Verifies the HS256 JWT issued by Login.php / GoogleLogin.php (hand-rolled
// there, not through a library, so this has to match their exact encoding —
// header/payload are plain base64, only the signature is base64url with
// padding stripped). Returns the decoded claims, or null if the token is
// malformed, forged, or expired.
function verifyJWT(string $jwt, string $secret): ?array
{
    if ($jwt === '' || $secret === '') {
        return null;
    }

    $parts = explode('.', $jwt);

    if (count($parts) !== 3) {
        return null;
    }

    [$header, $payload, $signature] = $parts;

    $expectedSignature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
    $expectedSignature = rtrim(strtr($expectedSignature, '+/', '-_'), '=');

    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    $claims = json_decode(base64_decode($payload), true);

    if (!is_array($claims) || !isset($claims['exp']) || (int)$claims['exp'] < time()) {
        return null;
    }

    return $claims;
}

// iOS/Android requests carry the JWT they were issued at login instead of a
// session cookie. Never trust a client-supplied user_id directly — resolve
// it from a verified token, or treat the caller as a guest (0).
function resolveMobileUserId(array $data): int
{
    $claims = verifyJWT((string)($data['jwt'] ?? ''), $_ENV['JWT_SECRET'] ?? '');
    return $claims !== null ? (int)($claims['user_id'] ?? 0) : 0;
}

function requireAuthenticatedUser(array $data): int
{
    if (!isset($data['device_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Device type is required.']);
        exit;
    }

    $userDeviceType   = $data['device_type'];
    $validDeviceTypes = ['iOS', 'Android', 'Web'];

    if (!in_array($userDeviceType, $validDeviceTypes, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid device type.']);
        exit;
    }

    if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
        $userId = resolveMobileUserId($data);
    } else {
        session_start();
        $userId = (int)($_SESSION['user_id'] ?? 0);
    }

    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(['error' => 'User not authenticated. Please log in.']);
        exit;
    }

    return $userId;
}

// Single source of truth for "does this user have a same-day order that's
// still pending/being processed/out for delivery" — used both to gate UI
// (Add to cart vs Add to order, cart icon vs process icon) and to scope
// every Process-table read/write to the one order it actually belongs to.
// Process rows are kept forever (buy-again history spanning every past
// order), so nothing here can assume "any row for this user" means "the
// active order" the way it could when Process only ever held one order's
// worth of rows. Includes Shipped so Cart.php keeps routing to Process.php
// (and blocking a new cart) while the driver is en route, not just while
// the order is being picked.
function getActiveSameDayOrderId(\PDO $db, int $userId): ?int
{
    if ($userId <= 0) {
        return null;
    }

    $stmt = $db->prepare("SELECT Id FROM OrderSent WHERE UserId = ? AND OrderStatus IN ('Pending', 'Processing', 'Shipped') ORDER BY DateAdded DESC LIMIT 1");
    $stmt->execute([$userId]);
    $orderId = $stmt->fetchColumn();

    return $orderId !== false ? (int)$orderId : null;
}

// Display-only status for the header cart/process icon — kept separate from
// getActiveSameDayOrderId() (returns an id, not a display string, and
// normalizes casing) even though both now cover the same Pending/
// Processing/Shipped set.
function getDisplayOrderStatus(\PDO $db, int $userId): ?string
{
    if ($userId <= 0) {
        return null;
    }

    // Case-insensitive match (matches the column's ci collation) — but the
    // raw stored value's casing is inconsistent in practice (seen both
    // 'pending' and 'Processing' in the data), so the returned value is
    // normalized to Title Case rather than passed through as-is. The
    // frontend's status-to-color/label mapping depends on that consistency.
    $stmt = $db->prepare("SELECT OrderStatus FROM OrderSent WHERE UserId = ? AND OrderStatus IN ('Pending', 'Processing', 'Shipped') ORDER BY DateAdded DESC LIMIT 1");
    $stmt->execute([$userId]);
    $status = $stmt->fetchColumn();

    return $status !== false ? ucfirst(strtolower((string)$status)) : null;
}

function isSameDayEligible(\PDO $db, string $deviceSignature, int $userId): array
{
    $response = [
        'is_device_known'   => false,
        'zipcode'           => null,
        'city'              => null,
        'state'             => null,
        'same_day_eligible' => false,
        'tax_rate'          => 0.00,
        'has_active_order'  => false,
        'order_status'      => null,
        'message'           => null,
        'error'             => null
    ];

    // SECURITY: Weak device signature validation (Vulnerability #19)
    // Device signatures should be proper SHA256 hashes
    if (
        $deviceSignature === ""
        || strlen($deviceSignature) !== 64
        || !preg_match('/^[a-f0-9]{64}$/', $deviceSignature)
    ) {
        $response['error'] = "Invalid device signature format";
        $response['message'] = "There've been an error, please try again later";
        return $response;
    }

    try {
        $stmt = $db->prepare("SELECT isSameDayEligible, ZipCode FROM Devices WHERE DeviceSignature = ? LIMIT 1");
        $stmt->execute([$deviceSignature]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $zipcode = $row['ZipCode'] ?? null;
            if ($zipcode) {
                $stmt = $db->prepare("SELECT City, State, TaxRate FROM ZipcodeAllowed WHERE Zipcode = ? LIMIT 1");
                $stmt->execute([$zipcode]);
                $zipcodeRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($zipcodeRow) {
                    $response['city'] = (string)($zipcodeRow['City'] ?? 'Unknown');
                    $response['state'] = (string)($zipcodeRow['State'] ?? 'Unknown');
                    $response['tax_rate'] = (float)($zipcodeRow['TaxRate'] ?? 0.00);
                }
            }
            $response['zipcode']           = $zipcode;
            $response['is_device_known']   = true;
            $response['same_day_eligible'] = (bool)$row['isSameDayEligible'];

            if ($response['same_day_eligible']) {
                $response['has_active_order'] = getActiveSameDayOrderId($db, $userId) !== null;
                $response['order_status'] = getDisplayOrderStatus($db, $userId);
            }
        } else {
            return $response; // Device not found, return with is_device_known = false
        }
    } catch (\PDOException $e) {
        error_log("DB Error in isSameDayEligible: " . $e->getMessage());
        $response['error'] = "Internal server error during eligibility check.";
        $response['message'] = "There've been an error, please try again later";
    }

    return $response;
}

function generateDeviceSignature(): string
{
    $payload = implode('|', [
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_POST['platform'] ?? '',
        $_POST['screen_resolution'] ?? ''
    ]);

    return hash('sha256', $payload);
}

// Replaces the old DeviceLog() + logDevice() + cartHasPerishables() split.
// Resolves the submitted zip, and only commits it to the Devices table
// immediately if there's nothing to ask the user about first:
//   - new zip IS same-day eligible            -> smooth, commit now
//   - new zip ISN'T eligible, cart has no perishables -> also smooth, commit now
//   - new zip ISN'T eligible, cart HAS perishables    -> hold off; caller
//     must show the conflict and only commit via removePerishablesFromCart()
//     once the user confirms (see that function below).
function updateDeviceZip(\PDO $db, string $deviceSignature, string $deviceType, string $zipcode, int $userId, bool $confirmCancelOrder = false): array
{
    $response = [
        'zipcode'                            => $zipcode,
        'same_day_eligible'                  => false,
        'tax_rate'                           => 0.00,
        'city'                               => null,
        'state'                              => null,
        'requires_confirmation'              => false,
        'perishable_items'                   => [],
        'requires_order_cancel_confirmation' => false,
        'order_in_transit'                   => false,
        'message'                            => null,
        'error'                              => null
    ];

    // 1. Input validation
    if (!preg_match('/^[a-f0-9]{64}$/', $deviceSignature)) {
        $response['error'] = "Invalid device signature format.";
        $response['message'] = "There has been an error, please try again later.";
        return $response;
    }

    $validDeviceTypes = ['iOS', 'Android', 'Web'];
    if (!in_array($deviceType, $validDeviceTypes, true)) {
        $response['error'] = "Invalid device type.";
        $response['message'] = "There has been an error, please try again later.";
        return $response;
    }

    if (!preg_match('/^[a-zA-Z0-9\- ]{1,16}$/', $zipcode)) {
        $response['error'] = "Invalid zipcode format.";
        $response['message'] = "There has been an error, please try again later.";
        return $response;
    }
    $validZip = "https://api.zippopotam.us/us/" . urlencode($zipcode);
    $zipResponse = @file_get_contents($validZip);

    if (!$zipResponse) {
        $response['error'] = "Invalid zipcode format.";
        $response['message'] = "There has been an error, please try again later.";
        return $response;
    }
    // $zipResponse itself is only used to confirm the zip is real — the
    // actual city/state/tax data now comes from the zip-tax.com call below.

    $cacheKey = "zipcode:{$zipcode}";

    try {
        // 2. Cache/DB Layer Fetch
        $row = QueryCache::get($cacheKey);

        if (!$row) {
            $stmt = $db->prepare("SELECT City, State, isSameDayEligible, TaxRate FROM ZipcodeAllowed WHERE Zipcode = ? LIMIT 1");
            $stmt->execute([$zipcode]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                QueryCache::set($cacheKey, $row, 86400);
            }
        }

        // 3. Fallback to External API (When Zipcode is missing or needs tax updates)
        if (!$row) {
            $fallbackTax = 0.10;
            $fallbackCity = 'Unknown';
            $fallbackState = 'Unknown';

            // $apiKey = $_ENV['TAX_KEY'] ?? '';
            // if (empty($apiKey)) {
            //     $response['error'] = "can't find .env file.";
            //     $response['message'] = "An unexpected error occurred.";
            //     return $response;
            // }

            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, "https://api.zip-tax.com/request/v60?key=" . urlencode($apiKey) . "&postalcode=" . urlencode($zipcode));
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            // $taxInformation = curl_exec($ch);
            // $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // curl_close($ch);

            // if ($taxInformation !== false && $httpStatus === 200) {
            //     $taxData = json_decode($taxInformation, true);
            //     $result = $taxData['results'][0] ?? null;
            //     if ($result) {
            //         $fallbackTax = (float)($result['taxSales'] ?? 0.10);
            //         $fallbackCity = (string)($result['geoCity'] ?? 'Unknown');
            //         $fallbackState = (string)($result['geoState'] ?? 'Unknown');
            //     }
            // }

            /*
             * Atomic Upsert Logic:
             * 1. Missing zipcodes are inserted with isSameDayEligible = 0.
             * 2. Existing zipcodes (whitelisted) only update their TaxRate, preserving eligibility status.
             */
            $stmt = $db->prepare("
                INSERT INTO ZipcodeAllowed (Zipcode, City, State, isSameDayEligible, TaxRate)
                VALUES (?, ?, ?, 0, ?)
                ON DUPLICATE KEY UPDATE
                    TaxRate = VALUES(TaxRate)
            ");
            $stmt->execute([$zipcode, $fallbackCity, $fallbackState, $fallbackTax]);

            // Re-fetch guarantees $row accurately reflects the post-upsert state of `isSameDayEligible`
            $stmt = $db->prepare("SELECT City, State, isSameDayEligible, TaxRate FROM ZipcodeAllowed WHERE Zipcode = ? LIMIT 1");
            $stmt->execute([$zipcode]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            QueryCache::set($cacheKey, $row, 86400);
        }

        // 4. Hydrate Response
        $response['city']              = (string)$row['City'];
        $response['state']             = (string)$row['State'];
        $response['same_day_eligible'] = (bool)$row['isSameDayEligible'];
        $response['tax_rate']          = (float)$row['TaxRate'];
    } catch (\Throwable $e) {
        error_log("Error in updateDeviceZip: " . $e->getMessage());
        $response['error'] = "Internal server error. " . $e->getMessage();
        $response['message'] = "An unexpected error occurred.";
        return $response;
    }

    // 5. If the new zip isn't eligible, a Pending/Processing order can no
    // longer be fulfilled at this address — but cancelling it is a real
    // consequence, so unless the caller already confirmed via
    // $confirmCancelOrder, hold off here and let the user decide (mirrors
    // the perishables-in-cart confirmation below, just gated on a separate
    // flag since these are independent things to confirm). A Shipped order
    // is already out for delivery to the address on file, so it's left
    // alone either way; the caller just gets a heads-up via
    // order_in_transit instead. Runs before the perishables check so it
    // still fires even when that branch would otherwise hold off too.
    if (!$response['same_day_eligible'] && $userId > 0) {
        $activeOrderStatus = getDisplayOrderStatus($db, $userId);
        if ($activeOrderStatus === 'Shipped') {
            $response['order_in_transit'] = true;
        } elseif ($activeOrderStatus !== null) {
            if (!$confirmCancelOrder) {
                $response['requires_order_cancel_confirmation'] = true;
                return $response; // hold off — caller must not commit yet
            }
            cancelOrder($db, $userId, ['Pending', 'Processing']);
        }
    }

    // 6. A conflict is only possible if the cart actually holds
    // perishables — check before committing anything.
    if (!$response['same_day_eligible'] && $userId > 0) {
        try {
            $stmt = $db->prepare("
                SELECT p.Id AS ProductId, p.Name
                FROM Cart c
                INNER JOIN Products p ON p.Id = c.ProductId
                INNER JOIN ProductCategories pc ON pc.ProductId = c.ProductId
                WHERE c.UserId = ? AND pc.MainCategory IN ('Grocery', 'Frozen', 'Produce', 'Dairy')
            ");
            $stmt->execute([$userId]);
            $perishables = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($perishables)) {
                foreach ($perishables as $item) {
                    $response['perishable_items'][] = [
                        'product_id' => (int)$item['ProductId'],
                        'name'       => $item['Name']
                    ];
                }
                $response['requires_confirmation'] = true;
                return $response; // hold off — caller must not commit yet
            }
        } catch (\PDOException $e) {
            error_log("DB Error in updateDeviceZip (perishables check): " . $e->getMessage());
            $response['error'] = "Internal server error while checking cart.";
            return $response;
        }
    }

    // 7. No conflict — safe to commit the device's new zip/eligibility now.
    // This write is what isSameDayEligible() reads back on every future page
    // load to rehydrate the session, so it must never happen before we know
    // there's nothing left for the user to confirm.
    try {
        $stmt = $db->prepare("INSERT INTO Devices (DeviceSignature, DeviceType, Zipcode, isSameDayEligible, isActive, DateAdded)
                VALUES (?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    DeviceType = VALUES(DeviceType),
                    Zipcode = VALUES(Zipcode),
                    isSameDayEligible = VALUES(isSameDayEligible),
                    isActive = VALUES(isActive),
                    DateAdded = VALUES(DateAdded)
        ");
        $stmt->execute([$deviceSignature, $deviceType, $zipcode, $response['same_day_eligible'] ? 1 : 0]);
    } catch (\PDOException $e) {
        error_log("DB Error in updateDeviceZip (commit): " . $e->getMessage());
        $response['error'] = "Internal server error while logging device.";
    }

    return $response;
}

function cartIcon(\PDO $db, int $userId): array
{
    $response = [
        'icon' => 'icon_cart',
        'total_count' => 0,
        'has_active_order' => false,
        'order_status' => null,
        'error' => null
    ];

    if ($userId <= 0) {
        return $response; // Not logged in, return default cart icon
    }

    try {
        $activeOrderId = getActiveSameDayOrderId($db, $userId);
        $response['has_active_order'] = $activeOrderId !== null;
        $response['icon'] = $activeOrderId !== null ? 'icon_process' : 'icon_cart';
        $response['order_status'] = getDisplayOrderStatus($db, $userId);

        if ($activeOrderId !== null) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM Process WHERE UserId = ? AND OrderId = ? AND Quantity > 0");
            $stmt->execute([$userId, $activeOrderId]);
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM Cart WHERE UserId = ? AND Quantity > 0");
            $stmt->execute([$userId]);
        }
        $totalCount = (int)$stmt->fetchColumn();

        $response['total_count'] = $totalCount;
    } catch (\PDOException $e) {
        error_log("DB Error in cartIcon: " . $e->getMessage());
        $response['error'] = "Internal server error during cart icon retrieval.";
    }
    return $response;
}

function cartContent(\PDO $db, int $userId, float $taxRate): array
{
    $response = [
        'cart_items' => [],
        'error'      => null
    ];

    try {
        $cacheKey = "cart_content:{$userId}";
        $cachedResults = QueryCache::get($cacheKey);

        if ($cachedResults) {
            $results = $cachedResults;
        } else {
            $sql = " SELECT
        src.ProductId,
        p.*,
        pc.MainCategory,
        CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
        COALESCE(src.Quantity, 0)   AS ItemQuantity,
        COALESCE(r.avg_rating, 0)   AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
    FROM Cart src
    INNER JOIN Products p
            ON src.ProductId = p.Id
    LEFT JOIN ProductCategories pc
           ON pc.ProductId = p.Id
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

            $stmt = $db->prepare($sql);
            $stmt->execute([$userId, $userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($results) {
                QueryCache::set($cacheKey, $results, 3600); // Cache for 1 hour
            }
        }

        $sameDayCategories = ['Grocery', 'Frozen', 'Produce', 'Dairy'];

        foreach ($results as $row) {
            $rating = $row['review_count'] > 0
                ? round((float)$row['avg_rating'], 2)
                : "No ratings yet";

            $response['cart_items'][] = [
                'product_id'    => $row['ProductId'],
                'brand'        => $row['Brand'],
                'name'         => $row['Name'],
                'oz'           => $row['Size'],
                'price'        => (float)round($row['Price'] * (1 + $taxRate), 2),
                'picture'      => $row['Picture'],
                'is_on_sale'     => (bool)$row['isOnSale'],
                'sale_price'    => (float)round($row['SalePrice'] * (1 + $taxRate), 2),
                'is_bogo'       => (bool)$row['isBogo'],
                'is_saved'      => (bool)$row['isSaved'],
                'quantity'     => (int)$row['ItemQuantity'],
                'ratings'      => $rating,
                'review_count' => (int)$row['review_count'],
                'same_day_eligible' => in_array($row['MainCategory'], $sameDayCategories, true),
                'total_price'   => (float)round(
                    ($row['isOnSale'] ? $row['SalePrice'] : $row['Price']) * (1 + $taxRate) * (int)$row['ItemQuantity'],
                    2
                )
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in cartContent: " . $e->getMessage());
        $response['error'] = "Internal server error during cart retrieval.";
    }

    return $response;
}

// Same shape/response key as cartContent() (Carted.php picks whichever one
// ran) so the frontend's rendering code doesn't need to branch on shape —
// only Cart.php's header/checkout-button visibility differs based on
// is_processing. $orderStatus (from getDisplayOrderStatus(), passed in by
// the caller rather than re-queried here) distinguishes a NULL QuantityFound
// meaning "order is Pending, nobody's picked anything yet" from "order is
// Processing, a shopper just hasn't resolved this particular item yet" —
// same underlying NULL, different pick_status shown to the customer.
function processContent(\PDO $db, int $userId, int $orderId, float $taxRate, ?string $orderStatus = null): array
{
    $response = [
        'cart_items' => [],
        'error'      => null
    ];

    try {
        $cacheKey = "process_content:{$userId}:{$orderId}";
        $cachedResults = QueryCache::get($cacheKey);

        if ($cachedResults) {
            $results = $cachedResults;
        } else {
            $sql = " SELECT
        src.ProductId,
        p.*,
        pc.MainCategory,
        CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
        COALESCE(src.Quantity, 0)   AS ItemQuantity,
        src.QuantityFound,
        COALESCE(r.avg_rating, 0)   AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
    FROM Process src
    INNER JOIN Products p
            ON src.ProductId = p.Id
    LEFT JOIN ProductCategories pc
           ON pc.ProductId = p.Id
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
      WHERE src.UserId = ? AND src.OrderId = ? AND src.Quantity > 0
      ORDER BY src.DateAdded DESC ";

            $stmt = $db->prepare($sql);
            $stmt->execute([$userId, $userId, $orderId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($results) {
                QueryCache::set($cacheKey, $results, 3600); // Cache for 1 hour
            }
        }

        $sameDayCategories = ['Grocery', 'Frozen', 'Produce', 'Dairy'];

        foreach ($results as $row) {
            $rating = $row['review_count'] > 0
                ? round((float)$row['avg_rating'], 2)
                : "No ratings yet";

            // NULL QuantityFound = not yet picked up by anyone — 'pending' if
            // the order itself hasn't started being picked yet, 'processing'
            // if a shopper is actively working the order and just hasn't
            // gotten to this item. 0 = out of stock. Equal to the requested
            // quantity = fully found. Anything in between = partially found.
            $quantityFound = $row['QuantityFound'];
            if ($quantityFound === null) {
                $pickStatus = ($orderStatus === 'Pending') ? 'pending' : 'processing';
            } elseif ((int)$quantityFound === 0) {
                $pickStatus = 'out_of_stock';
            } elseif ((int)$quantityFound >= (int)$row['ItemQuantity']) {
                $pickStatus = 'in_stock';
            } else {
                $pickStatus = 'partial';
            }

            $response['cart_items'][] = [
                'product_id'    => $row['ProductId'],
                'brand'        => $row['Brand'],
                'name'         => $row['Name'],
                'oz'           => $row['Size'],
                'price'        => (float)round($row['Price'] * (1 + $taxRate), 2),
                'picture'      => $row['Picture'],
                'is_on_sale'     => (bool)$row['isOnSale'],
                'sale_price'    => (float)round($row['SalePrice'] * (1 + $taxRate), 2),
                'is_bogo'       => (bool)$row['isBogo'],
                'is_saved'      => (bool)$row['isSaved'],
                'quantity'     => (int)$row['ItemQuantity'],
                'quantity_found' => $quantityFound === null ? null : (int)$quantityFound,
                'pick_status'  => $pickStatus,
                'ratings'      => $rating,
                'review_count' => (int)$row['review_count'],
                'same_day_eligible' => in_array($row['MainCategory'], $sameDayCategories, true),
                'total_price'   => (float)round(
                    ($row['isOnSale'] ? $row['SalePrice'] : $row['Price']) * (1 + $taxRate) * (int)$row['ItemQuantity'],
                    2
                )
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in processContent: " . $e->getMessage());
        $response['error'] = "Internal server error during order retrieval.";
    }

    return $response;
}

function clearCart(\PDO $db, int $userId): array
{
    $response = ['error' => null];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated. Please log in.";
        return $response;
    }

    try {
        $stmt = $db->prepare("DELETE FROM Cart WHERE UserId = ?");
        $stmt->execute([$userId]);

        QueryCache::delete("cart_content:{$userId}");
    } catch (\PDOException $e) {
        error_log("DB Error in clearCart: " . $e->getMessage());
        $response['error'] = "Internal server error during cart clearance.";
    }

    return $response;
}

// The Continue-click half of the flow: the user already saw the conflict
// from updateDeviceZip() and chose to proceed, so this both clears the
// perishables AND finalizes the zip commit that updateDeviceZip() held off
// on — eligibility is already known to be false here (that's the only way
// this path gets reached), so there's no need to re-resolve it.
function removePerishablesFromCart(\PDO $db, int $userId, string $deviceSignature, string $deviceType, string $zipcode): array
{
    $response = [
        'removed_count' => 0,
        'city'          => null,
        'state'         => null,
        'tax_rate'      => 0.00,
        'error'         => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated.";
        return $response;
    }

    try {
        $stmt = $db->prepare("
            DELETE c FROM Cart c
            INNER JOIN ProductCategories pc ON pc.ProductId = c.ProductId
            WHERE c.UserId = ? AND pc.MainCategory IN ('Grocery', 'Frozen', 'Produce', 'Dairy')
        ");
        $stmt->execute([$userId]);
        $response['removed_count'] = $stmt->rowCount();

        QueryCache::delete("cart_content:{$userId}");

        // Plain read for display — the zip was already resolved (and found
        // non-eligible) by updateDeviceZip() moments earlier, so this isn't
        // a full re-resolve and never touches the external zip API.
        $cacheKey = "zipcode:{$zipcode}";
        $zipRow = QueryCache::get($cacheKey);

        if (!$zipRow) {
            $stmt = $db->prepare("SELECT City, State, TaxRate FROM ZipcodeAllowed WHERE Zipcode = ? LIMIT 1");
            $stmt->execute([$zipcode]);
            $zipRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        $response['city']     = $zipRow['City'] ?? null;
        $response['state']    = $zipRow['State'] ?? null;
        $response['tax_rate'] = (float)($zipRow['TaxRate'] ?? 0.00);

        $stmt = $db->prepare("INSERT INTO Devices (DeviceSignature, DeviceType, Zipcode, isSameDayEligible, isActive, DateAdded)
                VALUES (?, ?, ?, 0, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    DeviceType = VALUES(DeviceType),
                    Zipcode = VALUES(Zipcode),
                    isSameDayEligible = VALUES(isSameDayEligible),
                    isActive = VALUES(isActive),
                    DateAdded = VALUES(DateAdded)
        ");
        $stmt->execute([$deviceSignature, $deviceType, $zipcode]);
    } catch (\PDOException $e) {
        error_log("DB Error in removePerishablesFromCart: " . $e->getMessage());
        $response['error'] = "Internal server error while updating cart.";
    }

    return $response;
}

function addProduct(\PDO $db, int $productId, int $userId, bool $hasActiveOrder, float $taxRate): array
{
    $response = [
        'table_source' => null,
        'subtotal'     => 0.00,
        'quantity'     => 0,
        'total_count'    => 0,
        'error'        => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated. Please log in.";
        return $response;
    }

    if ($productId <= 0) {
        $response['error'] = "Invalid product ID.";
        return $response;
    }

    try {
        // Process rows persist forever (buy-again history across every past
        // order), so every read/write against it has to be scoped to the
        // specific active OrderId — UserId+ProductId alone would also match
        // rows from the user's old, already-delivered orders.
        $activeOrderId = $hasActiveOrder ? getActiveSameDayOrderId($db, $userId) : null;
        $table = $activeOrderId !== null ? 'Process' : 'Cart';
        $response['table_source'] = $table;

        if ($table === 'Process') {
            $stmt = $db->prepare("INSERT INTO Process (UserId, ProductId, OrderId, Quantity) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE Quantity = Quantity + 1");
            $stmt->execute([$userId, $productId, $activeOrderId]);

            $stmt = $db->prepare("SELECT Quantity FROM Process WHERE UserId = ? AND ProductId = ? AND OrderId = ?");
            $stmt->execute([$userId, $productId, $activeOrderId]);
        } else {
            $stmt = $db->prepare("INSERT INTO Cart (UserId, ProductId, Quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE Quantity = Quantity + 1");
            $stmt->execute([$userId, $productId]);

            $stmt = $db->prepare("SELECT Quantity FROM Cart WHERE UserId = ? AND ProductId = ?");
            $stmt->execute([$userId, $productId]);
        }
        $quantity = (int)$stmt->fetchColumn();

        $response['quantity'] = $quantity;

        $countSql = "
             SELECT
                COUNT(*) AS ItemCount,
                SUM(
                    CI.Quantity * COALESCE(
                        CASE
                            WHEN P.isOnSale = 1 THEN P.SalePrice
                            ELSE P.Price
                        END, P.Price)
                ) AS TotalAmount
            FROM {$table} CI
            INNER JOIN Products P
                ON CI.ProductId = P.Id
            WHERE CI.UserId = ?" . ($table === 'Process' ? " AND CI.OrderId = ?" : "");
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($table === 'Process' ? [$userId, $activeOrderId] : [$userId]);
        $cartData = $countStmt->fetch(PDO::FETCH_ASSOC);

        $totaltax = (float)$cartData['TotalAmount'] * $taxRate;

        $response['total_count'] = (int)$cartData['ItemCount'];
        $response['subtotal'] = round($cartData['TotalAmount'] + $totaltax, 2);

        QueryCache::delete("cart_content:{$userId}");
    } catch (\PDOException $e) {
        error_log("DB Error in addProduct: " . $e->getMessage());
        $response['error'] = "Internal server error during product addition.";
    }

    return $response;
}

function decrementProduct(\PDO $db, int $productId, int $userId, bool $hasActiveOrder, float $taxRate): array
{
    $response = [
        'table_source' => null,
        'subtotal'     => 0.00,
        'quantity'     => 0,
        'total_count'    => 0,
        'error'        => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated. Please log in.";
        return $response;
    }

    if ($productId <= 0) {
        $response['error'] = "Invalid product ID.";
        return $response;
    }

    try {
        $activeOrderId = $hasActiveOrder ? getActiveSameDayOrderId($db, $userId) : null;
        $table = $activeOrderId !== null ? 'Process' : 'Cart';
        $response['table_source'] = $table;

        if ($table === 'Process') {
            $stmt = $db->prepare("UPDATE Process SET Quantity = GREATEST(Quantity - 1, 0) WHERE UserId = ? AND ProductId = ? AND OrderId = ?");
            $stmt->execute([$userId, $productId, $activeOrderId]);

            $stmt = $db->prepare("DELETE FROM Process WHERE UserId = ? AND ProductId = ? AND OrderId = ? AND Quantity = 0");
            $stmt->execute([$userId, $productId, $activeOrderId]);

            $stmt = $db->prepare("SELECT Quantity FROM Process WHERE UserId = ? AND ProductId = ? AND OrderId = ?");
            $stmt->execute([$userId, $productId, $activeOrderId]);
        } else {
            $stmt = $db->prepare("UPDATE Cart SET Quantity = GREATEST(Quantity - 1, 0) WHERE UserId = ? AND ProductId = ?");
            $stmt->execute([$userId, $productId]);

            $stmt = $db->prepare("DELETE FROM Cart WHERE UserId = ? AND ProductId = ? AND Quantity = 0");
            $stmt->execute([$userId, $productId]);

            $stmt = $db->prepare("SELECT Quantity FROM Cart WHERE UserId = ? AND ProductId = ?");
            $stmt->execute([$userId, $productId]);
        }
        $quantity = (int)$stmt->fetchColumn();

        $response['quantity'] = $quantity;

        $countSql = "
             SELECT
                COUNT(*) AS ItemCount,
                SUM(
                    CI.Quantity * COALESCE(
                        CASE
                            WHEN P.isOnSale = 1 THEN P.SalePrice
                            ELSE P.Price
                        END, P.Price)
                ) AS TotalAmount
            FROM {$table} CI
            INNER JOIN Products P
                ON CI.ProductId = P.Id
            WHERE CI.UserId = ?" . ($table === 'Process' ? " AND CI.OrderId = ?" : "");
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($table === 'Process' ? [$userId, $activeOrderId] : [$userId]);
        $cartData = $countStmt->fetch(PDO::FETCH_ASSOC);

        $totaltax = (float)$cartData['TotalAmount'] * $taxRate;

        $response['total_count'] = (int)$cartData['ItemCount'];
        $response['subtotal'] = round($cartData['TotalAmount'] + $totaltax, 2);

        QueryCache::delete("cart_content:{$userId}");
    } catch (\PDOException $e) {
        error_log("DB Error in decrementProduct: " . $e->getMessage());
        $response['error'] = "Internal server error during product decrement.";
    }

    return $response;
}

function savedCount(\PDO $db, int $userId): array
{
    $response = [
        'saved_count' => 0,
        'error' => null
    ];

    if ($userId <= 0) {
        return $response; // Not logged in, return default response
    }

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM Saved WHERE UserId = ?");
        $stmt->execute([$userId]);
        $response['saved_count'] = (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log("DB Error in wishListCount: " . $e->getMessage());
        $response['error'] = "Internal server error during wishlist count retrieval.";
    }

    return $response;
}

function addSaved(\PDO $db, int $productId, int $userId): array
{
    $response = [
        'is_saved'       => false,
        'saved_count' => 0,
        'error'          => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated. Please log in.";
        return $response;
    }

    if ($productId <= 0) {
        $response['error'] = "Invalid product ID.";
        return $response;
    }

    try {
        // Check if already saved
        $stmt = $db->prepare("SELECT 1 FROM Saved WHERE UserId = ? AND ProductId = ? LIMIT 1");
        $stmt->execute([$userId, $productId]);

        if ($stmt->fetchColumn()) {
            // Already saved — remove it
            $stmt = $db->prepare("DELETE FROM Saved WHERE UserId = ? AND ProductId = ?");
            $stmt->execute([$userId, $productId]);
            $response['is_saved'] = false;
        } else {
            // Not saved — add it
            $stmt = $db->prepare("INSERT INTO Saved (UserId, ProductId, DateAdded) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $productId]);
            $response['is_saved'] = true;
        }

        // Get updated count
        $stmt = $db->prepare("SELECT COUNT(*) FROM Saved WHERE UserId = ?");
        $stmt->execute([$userId]);
        $response['saved_count'] = (int)$stmt->fetchColumn();

        QueryCache::delete("saved_content:{$userId}");
    } catch (\PDOException $e) {
        error_log("DB Error in addSaved: " . $e->getMessage());
        $response['error'] = "Internal server error during wishlist toggle.";
    }

    return $response;
}

function savedContent(\PDO $db, int $userId, bool $hasActiveOrder, float $taxRate): array
{
    $response = [
        'saved_items' => [],
        'location'    => 'Cart',
        'message'     => null,
        'error'       => null
    ];

    if ($userId <= 0) {
        $response['message'] = "need to log in";
        return $response;
        // Not logged in, return default response
    }

    $activeOrderId = $hasActiveOrder ? getActiveSameDayOrderId($db, $userId) : null;
    $table = $activeOrderId !== null ? 'Process' : 'Cart';

    try {
        $cacheKey = "saved_content:{$userId}:{$table}:" . ($activeOrderId ?? '0');
        $cachedResults = QueryCache::get($cacheKey);

        if ($cachedResults) {
            $results = $cachedResults;
        } else {
            $joinOrderClause = $table === 'Process' ? " AND b.OrderId = ?" : "";
            $sql = "
            SELECT
                src.ProductId,
                p.*,
                CASE WHEN b.ProductId IS NOT NULL THEN 1 ELSE 0 END AS isbought,
                COALESCE(b.Quantity, 0)      AS ItemQuantity,
                COALESCE(r.avg_rating, 0)    AS avg_rating,
                COALESCE(r.review_count, 0)  AS review_count
            FROM Saved src
            INNER JOIN Products p
                    ON src.ProductId = p.Id
            LEFT JOIN {$table} b
                   ON b.ProductId = src.ProductId AND b.UserId = ?{$joinOrderClause}
            LEFT JOIN (
                SELECT
                    ProductId,
                    ROUND(AVG(Stars), 2)  AS avg_rating,
                    COUNT(*)              AS review_count
                FROM ItemReviews
                GROUP BY ProductId
            ) r ON r.ProductId = src.ProductId
            WHERE src.UserId = ?
            ORDER BY src.DateAdded DESC
        ";

            $params = $table === 'Process' ? [$userId, $activeOrderId, $userId] : [$userId, $userId];
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($results) {
                QueryCache::set($cacheKey, $results, 3600); // Cache for 1 hour
            }
        }

        $response['location'] = $table;

        foreach ($results as $row) {
            $rating = $row['review_count'] > 0
                ? round((float)$row['avg_rating'], 2)
                : "No ratings yet";

            $response['saved_items'][] = [
                'product_id'   => $row['ProductId'],
                'brand'        => $row['Brand'],
                'name'         => $row['Name'],
                'oz'           => $row['Size'],
                'price'        => (float)round($row['Price'] * (1 + $taxRate), 2),
                'picture'      => $row['Picture'],
                'is_on_sale'   => (bool)$row['isOnSale'],
                'sale_price'   => (float)round($row['SalePrice'] * (1 + $taxRate), 2),
                'is_bogo'      => (bool)$row['isBogo'],
                'is_bought'    => (bool)$row['isbought'],
                'quantity'     => (int)$row['ItemQuantity'],
                'ratings'      => $rating,
                'review_count' => (int)$row['review_count']
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in savedContent: " . $e->getMessage());
        $response['error'] = "Internal server error during saved content retrieval.";
    }

    return $response;
}

function Summary(\PDO $db, int $userId, bool $hasActiveOrder, float $taxRate): array
{
    $response = [
        'table_source' => 'Cart',
        'subtotal' => 0.00,
        'error' => null
    ];

    if ($userId <= 0) {
        return $response; // Not logged in, return default response
    }

    try {
        $activeOrderId = $hasActiveOrder ? getActiveSameDayOrderId($db, $userId) : null;
        $table = $activeOrderId !== null ? 'Process' : 'Cart';

        $countStmt = $db->prepare("
             SELECT
                SUM(
                    CI.Quantity * COALESCE(
                        CASE
                            WHEN P.isOnSale = 1 THEN P.SalePrice
                            ELSE P.Price
                        END, P.Price)
                ) AS TotalAmount
            FROM {$table} CI
            INNER JOIN Products P
                ON CI.ProductId = P.Id
            WHERE CI.UserId = ?" . ($table === 'Process' ? " AND CI.OrderId = ?" : "") . "
        ");
        $countStmt->execute($table === 'Process' ? [$userId, $activeOrderId] : [$userId]);
        $cartData = $countStmt->fetch(PDO::FETCH_ASSOC);

        $totaltax = (float)$cartData['TotalAmount'] * $taxRate;

        $response['subtotal'] = round($cartData['TotalAmount'] + $totaltax, 2);
        $response['table_source'] = $table;
    } catch (\PDOException $e) {
        error_log("DB Error in Summary: " . $e->getMessage());
        $response['error'] = "Internal server error during summary.";
    }

    return $response;
}

function pullingProducts(\PDO $db, bool $isSameDayEligible, string $table, int $userId, float $taxRate): array
{
    $response = [
        'products' => [],
        'message' => null,
        'error'    => null
    ];

    $allowedTables = ['Recommendations', 'RecentlyBought', 'Popular'];

    if (!in_array($table, $allowedTables, true)) {
        $response['error'] = "Invalid table source.";
        return $response;
    }

    // "Popular" (RecentlyViewed) is intentionally site-wide trending, not
    // personal to the caller. "Discover Great Deals" (SearchHistory) and
    // "RecentlyBought" (ItemBoughtHistory) are personal history, so those two
    // get scoped to the caller's own UserId - without this they were pulling
    // every user's search/purchase history mixed together.
    $scopeToUser = true;
    if ($table === "Recommendations") {
        $table = "SearchHistory";
    } else if ($table === "RecentlyBought") {
        $table = "ItemBoughtHistory";
    } else {
        $table = "RecentlyViewed";
        $scopeToUser = false;
    }

    $userScopeFilter = $scopeToUser ? "WHERE UserId = ?" : "";

    try {
        $activeOrderId = getActiveSameDayOrderId($db, $userId);

        // Dedup (latest row per product) happens in its own subquery before
        // joining Products, same reasoning as recentlyViewed(): grouping by
        // r.ProductId at the outer level while selecting p.* would violate
        // ONLY_FULL_GROUP_BY. Without this, a product with multiple rows in
        // the source table (e.g. viewed several times) showed up repeated in
        // the slider instead of once.
        $buildQuery = "
            SELECT
                r.ProductId,
                p.*,
                CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
                COALESCE(cart.Quantity, 0)   AS CartQuantity,
                COALESCE(proc.Quantity, 0)   AS ProcessQuantity,
                COALESCE(rv.avg_rating, 0)   AS avg_rating,
                COALESCE(rv.review_count, 0) AS review_count
            FROM (
                SELECT ProductId, MAX(Id) AS LastId
                FROM {$table}
                {$userScopeFilter}
                GROUP BY ProductId
            ) r
            INNER JOIN Products p ON p.Id = r.ProductId
            LEFT JOIN Saved s
                ON s.ProductId = r.ProductId AND s.UserId = ?
            LEFT JOIN Cart cart
                ON cart.ProductId = r.ProductId AND cart.UserId = ?
            LEFT JOIN Process proc
                ON proc.ProductId = r.ProductId AND proc.UserId = ? AND proc.OrderId = ?
            LEFT JOIN (
                SELECT
                    ProductId,
                    ROUND(AVG(Stars), 2) AS avg_rating,
                    COUNT(*)             AS review_count
                FROM ItemReviews
                GROUP BY ProductId
            ) rv ON rv.ProductId = r.ProductId
            LEFT JOIN ProductCategories pc ON pc.ProductId = r.ProductId
            WHERE (? = 1 OR pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy'))
            ORDER BY r.LastId DESC
            LIMIT 16
        ";

        $params = $scopeToUser ? [$userId] : [];
        array_push($params, $userId, $userId, $userId, $activeOrderId, (int)$isSameDayEligible);

        $stmt = $db->prepare($buildQuery);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $item) {
            $inProcess = $item['ProcessQuantity'] > 0;

            $response['products'][] = [
                'product_id'   => $item['ProductId'],
                'brand'        => $item['Brand'],
                'name'         => $item['Name'],
                'oz'           => $item['Size'],
                'price'        => (float)round($item['Price'] + ($item['Price'] * $taxRate), 2),
                'picture'      => $item['Picture'],
                'is_on_sale'   => (bool)$item['isOnSale'],
                'sale_price'   => (float)round($item['SalePrice'] + ($item['SalePrice'] * $taxRate), 2),
                'is_bogo'      => (bool)$item['isBogo'],
                'is_saved'     => (bool)$item['isSaved'],
                'in_cart'      => $item['CartQuantity'] > 0,
                'in_process'   => $inProcess,
                'quantity'     => $inProcess ? (int)$item['ProcessQuantity'] : (int)$item['CartQuantity'],
                'rating'       => $item['review_count'] > 0
                    ? round((float)$item['avg_rating'], 2)
                    : "No ratings yet",
                'review_count' => (int)$item['review_count']
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in pullingProducts: " . $e->getMessage());
        $response['error'] = "Internal server error during product fetch.";
    }

    return $response;
}

function recentlyViewed(\PDO $db, int $userId, string $deviceSignature, bool $isSameDayEligible, float $taxRate): array
{
    $response = [
        'products' => [],
        'error'    => null
    ];

    if ($userId <= 0 && $deviceSignature === '') {
        return $response;
    }

    try {
        $activeOrderId = getActiveSameDayOrderId($db, $userId);

        // The dedup (latest view per product) has to happen in its own
        // GROUP BY before joining Products — grouping by rv.ProductId at the
        // outer level while selecting p.* would violate ONLY_FULL_GROUP_BY,
        // since MySQL/MariaDB can't infer that a joined table's columns are
        // functionally dependent on another table's grouped column.
        $stmt = $db->prepare("
            SELECT
                rv.ProductId,
                p.*,
                CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
                COALESCE(cart.Quantity, 0)   AS CartQuantity,
                COALESCE(proc.Quantity, 0)   AS ProcessQuantity,
                COALESCE(r.avg_rating, 0)    AS avg_rating,
                COALESCE(r.review_count, 0)  AS review_count
            FROM (
                SELECT ProductId, MAX(DateViewed) AS LastViewed
                FROM RecentlyViewed
                WHERE UserId = ? OR DeviceSignature = ?
                GROUP BY ProductId
            ) rv
            INNER JOIN Products p ON p.Id = rv.ProductId
            LEFT JOIN Saved s
                ON s.ProductId = rv.ProductId AND s.UserId = ?
            LEFT JOIN Cart cart
                ON cart.ProductId = rv.ProductId AND cart.UserId = ?
            LEFT JOIN Process proc
                ON proc.ProductId = rv.ProductId AND proc.UserId = ? AND proc.OrderId = ?
            LEFT JOIN (
                SELECT
                    ProductId,
                    ROUND(AVG(Stars), 2) AS avg_rating,
                    COUNT(*)             AS review_count
                FROM ItemReviews
                GROUP BY ProductId
            ) r ON r.ProductId = rv.ProductId
            LEFT JOIN ProductCategories pc ON pc.ProductId = rv.ProductId
            WHERE (? = 1 OR pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy'))
            ORDER BY rv.LastViewed DESC
            LIMIT 16
        ");
        $stmt->execute([$userId, $deviceSignature, $userId, $userId, $userId, $activeOrderId, (int)$isSameDayEligible]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $item) {
            $inProcess = $item['ProcessQuantity'] > 0;

            $response['products'][] = [
                'product_id'   => (int)$item['ProductId'],
                'brand'        => $item['Brand'],
                'name'         => $item['Name'],
                'oz'           => $item['Size'],
                'price'        => (float)round($item['Price'] * (1 + $taxRate), 2),
                'picture'      => $item['Picture'],
                'is_on_sale'   => (bool)$item['isOnSale'],
                'sale_price'   => (float)round($item['SalePrice'] * (1 + $taxRate), 2),
                'is_bogo'      => (bool)$item['isBogo'],
                'is_saved'     => (bool)$item['isSaved'],
                'in_cart'      => $item['CartQuantity'] > 0,
                'in_process'   => $inProcess,
                'quantity'     => $inProcess ? (int)$item['ProcessQuantity'] : (int)$item['CartQuantity'],
                'rating'       => $item['review_count'] > 0
                    ? round((float)$item['avg_rating'], 2)
                    : "No ratings yet",
                'review_count' => (int)$item['review_count']
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in recentlyViewed: " . $e->getMessage());
        $response['error'] = "Internal server error during recently-viewed fetch.";
    }

    return $response;
}

function searchEngine(\PDO $db, string $searchTerm, bool $isSameDayEligible, float $taxRate, int $userId = 0): array
{
    $response = [
        'products' => [],
        'error'    => null
    ];

    if (trim($searchTerm) === "" || strlen($searchTerm) > 100 || !preg_match('/^[a-zA-Z0-9 ]+$/u', $searchTerm)) {
        $response['error'] = "Invalid or missing search term.";
        return $response;
    }

    $inputInfo = "%{$searchTerm}%";

    try {
        $activeOrderId = getActiveSameDayOrderId($db, $userId);

        $eligibilityFilter = $isSameDayEligible
            ? ""
            : "AND pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy')";

        $query = "
            SELECT p.*, pc.MainCategory, COALESCE(cart.Quantity, 0) AS CartQuantity, COALESCE(proc.Quantity, 0) AS ProcessQuantity FROM Products p
            LEFT JOIN ProductCategories pc ON pc.ProductId = p.Id
            LEFT JOIN Cart cart ON cart.ProductId = p.Id AND cart.UserId = ?
            LEFT JOIN Process proc ON proc.ProductId = p.Id AND proc.UserId = ? AND proc.OrderId = ?
            WHERE (
                p.Brand           LIKE ? OR
                pc.MainCategory   LIKE ? OR
                pc.SubCategory    LIKE ? OR
                pc.ThirdCategory  LIKE ? OR
                pc.ExtCategory    LIKE ? OR
                p.Name            LIKE ? OR
                p.Size            LIKE ?
            )
            {$eligibilityFilter}
            LIMIT 7
        ";

        $stmt = $db->prepare($query);
        $stmt->execute([$userId, $userId, $activeOrderId, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $inProcess = $row['ProcessQuantity'] > 0;

            $response['products'][] = [
                'product_id' => $row['Id'],
                'brand'      => $row['Brand'],
                'name'       => $row['Name'],
                'oz'         => $row['Size'],
                'category'   => $row['MainCategory'],
                'price'      => (float)round($row['Price'] * (1 + $taxRate), 2),
                'picture'    => $row['Picture'],
                'is_on_sale' => (bool)$row['isOnSale'],
                'sale_price' => (float)round($row['SalePrice'] * (1 + $taxRate), 2),
                'is_bogo'    => (bool)$row['isBogo'],
                'in_cart'    => $row['CartQuantity'] > 0,
                'in_process' => $inProcess,
                'quantity'   => $inProcess ? (int)$row['ProcessQuantity'] : (int)$row['CartQuantity'],
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in searchEngine: " . $e->getMessage());
        $response['error'] = "Internal server error during product search.";
    }

    return $response;
}

function store(\PDO $db, int $userId, bool $hasActiveOrder, bool $isSameDayEligible, float $taxRate, array $filter, int $limit): array
{
    $response = [
        'products'          => [],
        'similar_products'  => [],
        'available_filters' => [],
        'error'             => null
    ];

    $activeOrderId = $hasActiveOrder ? getActiveSameDayOrderId($db, $userId) : null;

    $productFilters  = ['Brand', 'Name', 'Size', 'Price', 'isOnSale', 'SalePrice', 'isBogo', 'inStock'];
    $categoryFilters = ['MainCategory', 'SubCategory', 'ThirdCategory', 'ExtCategory'];

    $whereClauses = ["p.inStock = 1"];
    $params       = [$userId, $userId, $userId, $activeOrderId];

    foreach ($filter as $key => $value) {
        if (in_array($key, $productFilters, true) && $value !== '' && $value !== null) {
            $whereClauses[] = "p.{$key} = ?";
            $params[]       = $value;
        } elseif (in_array($key, $categoryFilters, true) && is_string($value) && $value !== '') {
            $whereClauses[] = "pc.{$key} = ?";
            $params[]       = $value;
        }
    }

    if (!$isSameDayEligible) {
        $whereClauses[] = "pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy')";
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
    $params[] = $limit;

    try {
        $sql = "
            SELECT
                p.*,
                pc.MainCategory,
                pc.SubCategory,
                pc.ThirdCategory,
                pc.ExtCategory,
                CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
                COALESCE(cart.Quantity, 0)   AS CartQuantity,
                COALESCE(proc.Quantity, 0)   AS ProcessQuantity,
                COALESCE(rv.avg_rating, 0)   AS avg_rating,
                COALESCE(rv.review_count, 0) AS review_count
            FROM Products p
            LEFT JOIN ProductCategories pc
                ON pc.ProductId = p.Id
            LEFT JOIN Saved s
                ON s.ProductId = p.Id AND s.UserId = ?
            LEFT JOIN Cart cart
                ON cart.ProductId = p.Id AND cart.UserId = ?
            LEFT JOIN Process proc
                ON proc.ProductId = p.Id AND proc.UserId = ? AND proc.OrderId = ?
            LEFT JOIN (
                SELECT
                    ProductId,
                    ROUND(AVG(Stars), 2) AS avg_rating,
                    COUNT(*)             AS review_count
                FROM ItemReviews
                GROUP BY ProductId
            ) rv ON rv.ProductId = p.Id
            {$whereSQL}
            ORDER BY p.Id DESC
            LIMIT ?
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            $response['error'] = "No products found.";
            return $response;
        }

        foreach ($results as $product) {
            $inProcess = $product['ProcessQuantity'] > 0;

            $response['products'][] = [
                'product_id'     => (int)$product['Id'],
                'brand'          => $product['Brand'],
                'name'           => $product['Name'],
                'oz'             => $product['Size'],
                'price'          => (float)round($product['Price'] * (1 + $taxRate), 2),
                'picture'        => $product['Picture'],
                'description'    => $product['Description'],
                'is_on_sale'     => (bool)$product['isOnSale'],
                'sale_price'     => (float)round($product['SalePrice'] * (1 + $taxRate), 2),
                'is_bogo'        => (bool)$product['isBogo'],
                'in_stock'       => (bool)$product['inStock'],
                'is_saved'       => (bool)$product['isSaved'],
                'in_cart'        => $product['CartQuantity'] > 0,
                'in_process'     => $inProcess,
                'quantity'       => $inProcess
                    ? (int)$product['ProcessQuantity']
                    : (int)$product['CartQuantity'],
                'main_category'  => $product['MainCategory'],
                'sub_category'   => $product['SubCategory'],
                'third_category' => $product['ThirdCategory'],
                'ext_category'   => $product['ExtCategory'],
                'rating'         => $product['review_count'] > 0
                    ? round((float)$product['avg_rating'], 2)
                    : "No ratings yet",
                'review_count'   => (int)$product['review_count']
            ];
        }

        // Build available filters from current results
        $prices = array_column($response['products'], 'price');

        $response['available_filters'] = [
            'brands'          => array_values(array_unique(array_column($response['products'], 'brand'))),
            'main_categories' => array_values(array_unique(array_column($response['products'], 'main_category'))),
            'sub_categories'  => array_values(array_unique(array_column($response['products'], 'sub_category'))),
            'third_categories' => array_values(array_unique(array_column($response['products'], 'third_category'))),
            'ext_categories'  => array_values(array_unique(array_column($response['products'], 'ext_category'))),
            'oz'              => array_values(array_unique(array_column($response['products'], 'oz'))),
            'price_range'     => [
                'min' => min($prices),
                'max' => max($prices)
            ],
            'is_on_sale'      => in_array(true, array_column($response['products'], 'is_on_sale'), true),
            'is_bogo'         => in_array(true, array_column($response['products'], 'is_bogo'), true),
        ];

        // Similar products — based on first result's category and brand, excluding already shown products
        $anchor       = $results[0];
        $simExclude   = array_column($results, 'Id');
        $placeholders = implode(',', array_fill(0, count($simExclude), '?'));

        $simEligibility = !$isSameDayEligible
            ? "AND pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy')"
            : "";

        $simParams = [$userId, $userId, $userId, $activeOrderId, $anchor['MainCategory'], $anchor['Brand']];
        $simParams = array_merge($simParams, $simExclude, [$limit]);

        $simSQL = "
            SELECT
                p.*,
                pc.MainCategory,
                pc.SubCategory,
                pc.ThirdCategory,
                pc.ExtCategory,
                CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
                COALESCE(cart.Quantity, 0)  AS CartQuantity,
                COALESCE(proc.Quantity, 0)  AS ProcessQuantity
            FROM Products p
            LEFT JOIN ProductCategories pc
                ON pc.ProductId = p.Id
            LEFT JOIN Saved s
                ON s.ProductId = p.Id AND s.UserId = ?
            LEFT JOIN Cart cart
                ON cart.ProductId = p.Id AND cart.UserId = ?
            LEFT JOIN Process proc
                ON proc.ProductId = p.Id AND proc.UserId = ? AND proc.OrderId = ?
            WHERE (pc.MainCategory = ? OR p.Brand = ?)
            AND p.Id NOT IN ({$placeholders})
            AND p.inStock = 1
            {$simEligibility}
            ORDER BY p.Id DESC
            LIMIT ?
        ";

        $stmt = $db->prepare($simSQL);
        $stmt->execute($simParams);
        $similarProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($similarProducts as $similar) {
            $inProcess = $similar['ProcessQuantity'] > 0;

            $response['similar_products'][] = [
                'product_id'     => (int)$similar['Id'],
                'brand'          => $similar['Brand'],
                'name'           => $similar['Name'],
                'oz'             => $similar['Size'],
                'price'          => (float)round($similar['Price'] * (1 + $taxRate), 2),
                'picture'        => $similar['Picture'],
                'description'    => $similar['Description'],
                'is_on_sale'     => (bool)$similar['isOnSale'],
                'sale_price'     => (float)round($similar['SalePrice'] * (1 + $taxRate), 2),
                'is_bogo'        => (bool)$similar['isBogo'],
                'in_stock'       => (bool)$similar['inStock'],
                'is_saved'       => (bool)$similar['isSaved'],
                'in_cart'        => $similar['CartQuantity'] > 0,
                'in_process'     => $inProcess,
                'quantity'       => $inProcess
                    ? (int)$similar['ProcessQuantity']
                    : (int)$similar['CartQuantity'],
                'main_category'  => $similar['MainCategory'],
                'sub_category'   => $similar['SubCategory'],
                'third_category' => $similar['ThirdCategory'],
                'ext_category'   => $similar['ExtCategory'],
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in store: " . $e->getMessage());
        $response['error'] = "Internal server error during store fetch.";
    }

    return $response;
}

function productDetails(\PDO $db, int $productId, int $userId, bool $hasActiveOrder, bool $isSameDayEligible, float $taxRate): array
{
    $response = [
        'product' => [],
        'error'   => null
    ];

    if ($productId <= 0) {
        $response['error'] = "Invalid product ID.";
        return $response;
    }

    $activeOrderId = $hasActiveOrder ? getActiveSameDayOrderId($db, $userId) : null;

    $eligibilityFilter = $isSameDayEligible
        ? ""
        : "AND pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy')";

    try {
        $stmt = $db->prepare("
            SELECT
                p.*,
                pc.MainCategory,
                pc.SubCategory,
                pc.ThirdCategory,
                pc.ExtCategory,
                CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
                COALESCE(cart.Quantity, 0)  AS CartQuantity,
                COALESCE(proc.Quantity, 0)  AS ProcessQuantity,
                COALESCE(rv.avg_rating, 0)  AS avg_rating,
                COALESCE(rv.review_count,0) AS review_count
            FROM Products p
            LEFT JOIN ProductCategories pc
                ON pc.ProductId = p.Id
            LEFT JOIN Saved s
                ON s.ProductId = p.Id AND s.UserId = ?
            LEFT JOIN Cart cart
                ON cart.ProductId = p.Id AND cart.UserId = ?
            LEFT JOIN Process proc
                ON proc.ProductId = p.Id AND proc.UserId = ? AND proc.OrderId = ?
            LEFT JOIN (
                SELECT
                    ProductId,
                    ROUND(AVG(Stars), 2) AS avg_rating,
                    COUNT(*)             AS review_count
                FROM ItemReviews
                GROUP BY ProductId
            ) rv ON rv.ProductId = p.Id
            WHERE p.Id = ?
            {$eligibilityFilter}
            LIMIT 1
        ");

        $stmt->execute([$userId, $userId, $userId, $activeOrderId, $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $response['error'] = "Product not found or not eligible.";
            return $response;
        }

        $inProcess = $product['ProcessQuantity'] > 0;

        $response['product'] = [
            'product_id'     => (int)$product['Id'],
            'brand'          => $product['Brand'],
            'name'           => $product['Name'],
            'oz'             => $product['Size'],
            'price'          => (float)round($product['Price'] * (1 + $taxRate), 2),
            'picture'        => $product['Picture'],
            'description'    => $product['Description'],
            'is_on_sale'     => (bool)$product['isOnSale'],
            'sale_price'     => (float)round($product['SalePrice'] * (1 + $taxRate), 2),
            'is_bogo'        => (bool)$product['isBogo'],
            'in_stock'       => (bool)$product['inStock'],
            'is_saved'       => (bool)$product['isSaved'],
            'in_cart'        => $product['CartQuantity'] > 0,
            'in_process'     => $inProcess,
            'quantity'       => $inProcess
                ? (int)$product['ProcessQuantity']
                : (int)$product['CartQuantity'],
            'main_category'  => $product['MainCategory'],
            'sub_category'   => $product['SubCategory'],
            'third_category' => $product['ThirdCategory'],
            'ext_category'   => $product['ExtCategory'],
            'rating'         => $product['review_count'] > 0
                ? round((float)$product['avg_rating'], 2)
                : "No ratings yet",
            'review_count'   => (int)$product['review_count']
        ];
    } catch (\PDOException $e) {
        error_log("DB Error in productDetails: " . $e->getMessage());
        $response['error'] = "Internal server error during product fetch.";
    }

    return $response;
}

function getReviews(\PDO $db, int $productId, int $page, int $limit): array
{
    $response = [
        'reviews'     => [],
        'total_count' => 0,
        'avg_rating'  => 0,
        'error'       => null
    ];

    if ($productId <= 0) {
        $response['error'] = "Invalid product ID.";
        return $response;
    }

    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) AS ReviewCount, ROUND(AVG(Stars), 2) AS AvgRating
            FROM ItemReviews
            WHERE ProductId = ?
        ");
        $stmt->execute([$productId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['total_count'] = (int)$counts['ReviewCount'];
        $response['avg_rating']  = $counts['ReviewCount'] > 0 ? (float)$counts['AvgRating'] : 0;

        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare("
            SELECT r.Id, r.Stars, r.Expectation, r.ReviewTitle, r.Review, r.DateAdded, u.Name AS UserName
            FROM ItemReviews r
            INNER JOIN Users u ON u.Id = r.UserId
            WHERE r.ProductId = ?
            ORDER BY r.DateAdded DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $productId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $response['reviews'][] = [
                'review_id'   => (int)$row['Id'],
                'user_name'   => $row['UserName'],
                'stars'       => (int)$row['Stars'],
                'expectation' => (int)$row['Expectation'],
                'title'       => $row['ReviewTitle'],
                'review'      => $row['Review'],
                'date_added'  => $row['DateAdded']
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in getReviews: " . $e->getMessage());
        $response['error'] = "Internal server error during review fetch.";
    }

    return $response;
}

function addReview(\PDO $db, int $userId, int $productId, int $stars, int $expectation, string $title, string $review): array
{
    $response = ['error' => null];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated. Please log in.";
        return $response;
    }

    if ($productId <= 0) {
        $response['error'] = "Invalid product ID.";
        return $response;
    }

    if ($stars < 1 || $stars > 5) {
        $response['error'] = "Star rating must be between 1 and 5.";
        return $response;
    }

    if ($expectation < 1 || $expectation > 5) {
        $response['error'] = "Expectation rating must be between 1 and 5.";
        return $response;
    }

    $title  = trim($title);
    $review = trim($review);

    if ($title === '' || strlen($title) > 255) {
        $response['error'] = "Review title is required and must be under 255 characters.";
        return $response;
    }

    if ($review === '') {
        $response['error'] = "Review text is required.";
        return $response;
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO ItemReviews (UserId, ProductId, Stars, Expectation, ReviewTitle, Review)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $productId, $stars, $expectation, $title, $review]);
    } catch (\PDOException $e) {
        error_log("DB Error in addReview: " . $e->getMessage());
        $response['error'] = "Internal server error during review submission.";
    }

    return $response;
}

function registerUser(\PDO $db, string $userName, string $email, string $password): array
{
    $response = [
        'success' => false,
        'message' => null,
        'error' => null
    ];

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT 1 FROM Users WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $db->rollBack();
            $response['message'] = "Registration failed. Try logging in!";
            return $response;
        }

        $stmt = $db->prepare("
            INSERT INTO Users (Name, Email, Password, Credits, isMember, isActive, TimeRegister) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userName,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            0.00,
            0,
            1
        ]);

        $db->commit();
        $response['success'] = true;
    } catch (\PDOException $e) {
        $db->rollBack();
        error_log("DB Error in registerUser: " . $e->getMessage());
        $response['error'] = "Internal server error during registration." . $e->getMessage();
    }

    return $response;
}

function loginUser(\PDO $db, string $email, string $password): array
{
    $response = [
        'user'  => [],
        'message' => null,
        'error' => null
    ];

    try {
        $stmt = $db->prepare("SELECT Id, Name, Email, Password, Credits, isMember, isActive, TimeRegister FROM Users WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // SECURITY: Timing attack prevention (Vulnerability #6)
        // Always perform password verification to prevent user enumeration
        $dummyHash = '$2y$10$invalid.dummy.hash.for.timing.attack.prevention.';
        $passwordCorrect = $user && password_verify($password, $user['Password']);

        // If user not found, verify against dummy hash to consume time
        if (!$user) {
            password_verify($password, $dummyHash);
            $response['message'] = "Invalid email or password.";
            return $response;
        }

        // Check password
        if (!$passwordCorrect) {
            $response['message'] = "Invalid email or password.";
            return $response;
        }

        if (!(bool)$user['isActive']) {
            $response['message'] = "Account is deactivated.";
            return $response;
        }

        $phoneStmt = $db->prepare("SELECT Phone FROM UserAddresses WHERE UserId = ? AND Phone != '' ORDER BY DateAdded DESC LIMIT 1");
        $phoneStmt->execute([(int)$user['Id']]);
        $userPhone = $phoneStmt->fetchColumn();

        $response['user'] = [
            'user_id'       => (int)$user['Id'],
            'user_name'     => $user['Name'],
            'user_email'    => $user['Email'],
            'user_phone'    => $userPhone !== false ? $userPhone : null,
            'credits'       => (float)$user['Credits'],
            'is_member'     => (bool)$user['isMember'],
            'time_register' => $user['TimeRegister'],
        ];
    } catch (\PDOException $e) {
        error_log("DB Error in loginUser: " . $e->getMessage());
        $response['error'] = "Internal server error during login.";
    }

    return $response;
}

function googleLoginUser(\PDO $db, string $email, string $name): array
{
    $response = [
        'user'  => [],
        'error' => null
    ];

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT Id, Name, Email, Password, Credits, isMember, isActive, TimeRegister FROM Users WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['Password'] !== null) {
                $db->rollBack();
                $response['error'] = "An account with this email already exists. Please log in with your password.";
                return $response;
            }

            if (!(bool)$user['isActive']) {
                $db->rollBack();
                $response['error'] = "Account is deactivated.";
                return $response;
            }

            $db->commit();
        } else {
            $stmt = $db->prepare("
                INSERT INTO Users (Name, Email, Password, Credits, isMember, isActive, TimeRegister)
                VALUES (?, ?, NULL, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $name,
                $email,
                0.00,
                (int) false,
                (int) true
            ]);

            $stmt = $db->prepare("SELECT Id, Name, Email, Password, Credits, isMember, isActive, TimeRegister FROM Users WHERE Id = ? LIMIT 1");
            $stmt->execute([(int)$db->lastInsertId()]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $db->commit();
        }

        $phoneStmt = $db->prepare("SELECT Phone FROM UserAddresses WHERE UserId = ? AND Phone != '' ORDER BY DateAdded DESC LIMIT 1");
        $phoneStmt->execute([(int)$user['Id']]);
        $userPhone = $phoneStmt->fetchColumn();

        $response['user'] = [
            'user_id'       => (int)$user['Id'],
            'user_name'     => $user['Name'],
            'user_email'    => $user['Email'],
            'user_phone'    => $userPhone !== false ? $userPhone : null,
            'credits'       => (float)$user['Credits'],
            'is_member'     => (bool)$user['isMember'],
            'time_register' => $user['TimeRegister'],
        ];
    } catch (\PDOException $e) {
        $db->rollBack();
        error_log("DB Error in googleLoginUser: " . $e->getMessage());
        $response['error'] = "Internal server error during Google login.";
    }

    return $response;
}

function logoutUser(\PDO $db, int $userId, string $token): array
{
    $response = ['error' => null];

    try {
        $stmt = $db->prepare("INSERT INTO Tokens (UserId, Token, Type, ExpiresAt) VALUES (?, ?, 'blocklist', ?)");
        $stmt->execute([$userId, $token, date('Y-m-d H:i:s', strtotime('+1 hour'))]);

        session_destroy();
        $response['success'] = true;
    } catch (\PDOException $e) {
        error_log("DB Error in logoutUser: " . $e->getMessage());
        $response['error'] = "Internal server error during logout.";
    }

    return $response;
}

// Categories are backend-driven (whatever text a product gets tagged with),
// but icon images are still hand-uploaded files named after the category
// (Assets/Categories/{CategoryName}.png) — there's no DB-backed icon mapping.
// A category with no matching file yet (e.g. just added, or an existing one
// whose name doesn't match the legacy hardcoded set like "Beauty & Personal
// Care") falls back to a generic placeholder rather than a broken image.
// Returns a filename relative to Assets/Categories/, not a full path.
// Front-page product tiles ("On Sale" / "BOGO" / "What's New" / "Featured"),
// same-day-eligibility-aware like every other product listing. Featured is
// backed by Products.isFeatured, which someone has to actually set per
// product for that section to have content — it isn't derived from anything.
// A section with zero matching products is left out entirely rather than
// rendered empty. DISTINCT on the product columns (not pc.*) keeps this safe
// even if a product ever has more than one ProductCategories row.
function homepageCollections(\PDO $db, bool $isSameDayEligible): array
{
    $eligibilityFilter = $isSameDayEligible
        ? ""
        : "AND pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy')";

    // 'filter' is a ready-to-use query string fragment Store.php's URL reader
    // understands (see item 13) - e.g. "sale=1" makes the "Shop more" link
    // Store?sale=1. Null for sections Store has no matching filter for yet
    // (What's New has no hard-filter equivalent, only the "Newest" sort;
    // Featured has no Store-side concept at all).
    $sections = [
        ['title' => 'On Sale',    'bg' => '#F3F3F3', 'where' => 'p.isOnSale = 1',   'filter' => 'sale=1'],
        ['title' => 'BOGO',       'bg' => '#f2f3f2', 'where' => 'p.isBogo = 1',     'filter' => 'bogo=1'],
        ['title' => "What's New", 'bg' => '#f0e517', 'where' => '1 = 1',            'filter' => null],
        ['title' => 'Featured',   'bg' => '#0bb1f3', 'where' => 'p.isFeatured = 1', 'filter' => null],
    ];

    $collections = [];

    foreach ($sections as $section) {
        try {
            $stmt = $db->prepare("
                SELECT DISTINCT p.Id, p.Name, p.Picture, pc.MainCategory
                FROM Products p
                LEFT JOIN ProductCategories pc ON pc.ProductId = p.Id
                WHERE {$section['where']}
                {$eligibilityFilter}
                ORDER BY p.DateAdded DESC
                LIMIT 4
            ");
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                continue;
            }

            $collections[] = [
                'title'  => $section['title'],
                'bg'     => $section['bg'],
                'filter' => $section['filter'],
                'items'  => array_map(static fn ($row) => [
                    'product_id' => (int)$row['Id'],
                    'image'      => $row['Picture'],
                    'alt'        => $row['Name'],
                    'category'   => $row['MainCategory'],
                ], $items),
            ];
        } catch (\PDOException $e) {
            error_log("DB Error in homepageCollections ({$section['title']}): " . $e->getMessage());
        }
    }

    return $collections;
}

// Second box-category row, grouped by MainCategory instead of a product
// flag (isOnSale/isBogo/etc.) - same shape/return format as
// homepageCollections() so both feed the same renderer. As of 2026-07-29 the
// catalog has zero products tagged with any of these four categories, so
// every section here will legitimately come back empty (and get skipped,
// same as any homepageCollections() section with no matches) until real
// products in these categories exist - that's expected, not a bug. The
// exact category name to match against is a best guess (no existing product
// to confirm the real naming convention against) - if products eventually
// get added under a different exact spelling, update the 'category' value
// here to match.
function homepageCategoryCollections(\PDO $db, bool $isSameDayEligible): array
{
    $eligibilityFilter = $isSameDayEligible
        ? ""
        : "AND pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy')";

    $sections = [
        ['title' => 'Pet food',  'bg' => '#FBEAE3', 'category' => 'Pets'],
        ['title' => 'Car tools', 'bg' => '#EAF3FB', 'category' => 'Automotive'],
        ['title' => 'Gardening', 'bg' => '#EAF3E6', 'category' => 'Garden'],
        ['title' => 'Gym',       'bg' => '#FBEAF0', 'category' => 'Fitness'],
    ];

    $collections = [];

    foreach ($sections as $section) {
        try {
            $stmt = $db->prepare("
                SELECT DISTINCT p.Id, p.Name, p.Picture, pc.MainCategory
                FROM Products p
                INNER JOIN ProductCategories pc ON pc.ProductId = p.Id
                WHERE pc.MainCategory = ?
                {$eligibilityFilter}
                ORDER BY p.DateAdded DESC
                LIMIT 4
            ");
            $stmt->execute([$section['category']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                continue;
            }

            $collections[] = [
                'title'  => $section['title'],
                'bg'     => $section['bg'],
                'filter' => 'category=' . urlencode($section['category']),
                'items'  => array_map(static fn ($row) => [
                    'product_id' => (int)$row['Id'],
                    'image'      => $row['Picture'],
                    'alt'        => $row['Name'],
                    'category'   => $row['MainCategory'],
                ], $items),
            ];
        } catch (\PDOException $e) {
            error_log("DB Error in homepageCategoryCollections ({$section['title']}): " . $e->getMessage());
        }
    }

    return $collections;
}

function categoryIconPath(string $categoryName): string
{
    $safeName = preg_replace('/[^A-Za-z0-9 &-]/', '', $categoryName);
    $iconFile = __DIR__ . "/../../Assets/Categories/{$safeName}.png";

    return file_exists($iconFile) ? "{$safeName}.png" : "Placeholder.svg";
}

function mainCategories(\PDO $db, bool $isSameDayEligible): array
{
    $response = [
        'categories' => [],
        'error'      => null
    ];


    try {
        if (!$isSameDayEligible) {
            $stmt = $db->query("SELECT DISTINCT MainCategory FROM ProductCategories WHERE MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy') ORDER BY MainCategory ASC");
        } else {
            $stmt = $db->query("SELECT DISTINCT MainCategory FROM ProductCategories ORDER BY MainCategory ASC");
        }
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as $category) {
            if ($category['MainCategory'] !== null) {
                $response['categories'][] = [
                    'name' => $category['MainCategory'],
                    'icon' => categoryIconPath($category['MainCategory'])
                ];
            }
        }
    } catch (\PDOException $e) {
        error_log("DB Error in mainCategories: " . $e->getMessage());
        $response['error'] = "Internal server error during category retrieval.";
    }

    return $response;
}

function subCategories(\PDO $db, string $mainCategory, bool $isSameDayEligible): array
{
    $response = [
        'sub_categories' => [],
        'error'         => null
    ];

    try {
        if (!$isSameDayEligible) {
            $stmt = $db->prepare("SELECT DISTINCT SubCategory FROM ProductCategories WHERE MainCategory = ? AND MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy') ORDER BY SubCategory ASC");
        } else {
            $stmt = $db->prepare("SELECT DISTINCT SubCategory FROM ProductCategories WHERE MainCategory = ? ORDER BY SubCategory ASC");
        }
        $stmt->execute([$mainCategory]);
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($subcategories as $subcategory) {
            if ($subcategory['SubCategory'] !== null) {
                $response['sub_categories'][] = [
                    'name' => $subcategory['SubCategory'],
                    'icon' => categoryIconPath($subcategory['SubCategory'])
                ];
            }
        }
    } catch (\PDOException $e) {
        error_log("DB Error in subCategories: " . $e->getMessage());
        $response['error'] = "Internal server error during subcategory retrieval.";
    }

    return $response;
}

function thirdCategories(\PDO $db, string $subCategory, bool $isSameDayEligible): array
{
    $response = [
        'third_categories' => [],
        'error'            => null
    ];

    try {
        if (!$isSameDayEligible) {
            $stmt = $db->prepare("SELECT DISTINCT ThirdCategory FROM ProductCategories WHERE SubCategory = ? AND MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy') ORDER BY ThirdCategory ASC");
        } else {
            $stmt = $db->prepare("SELECT DISTINCT ThirdCategory FROM ProductCategories WHERE SubCategory = ? ORDER BY ThirdCategory ASC");
        }
        $stmt->execute([$subCategory]);
        $thirdCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($thirdCategories as $category) {
            if ($category['ThirdCategory'] !== null) {
                $response['third_categories'][] = [
                    'name' => $category['ThirdCategory'],
                    'icon' => categoryIconPath($category['ThirdCategory'])
                ];
            }
        }
    } catch (\PDOException $e) {
        error_log("DB Error in thirdCategories: " . $e->getMessage());
        $response['error'] = "Internal server error during third category retrieval.";
    }

    return $response;
}

function filterStore(\PDO $db, int $userId, bool $hasActiveOrder, bool $isSameDayEligible, float $taxRate, array $filter, int $limit): array
{
    return store($db, $userId, $hasActiveOrder, $isSameDayEligible, $taxRate, $filter, $limit);
}

// Flat one-time fee for same-day delivery when the customer isn't a
// HeyDaniel+ member — the membership's $9.99/mo perk is unlimited free
// same-day, this is the pay-per-order alternative for everyone else.
const SAME_DAY_PAID_FEE = 7.99;

function submitCheckout(\PDO $db, int $userId, bool $isSameDay, bool $paidSameDay, float $taxRate, float $tipAmount, string $paymentMethodId, array $address): array
{
    $response = [
        'order_id' => null,
        'error'    => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated.";
        return $response;
    }

    if (empty($paymentMethodId)) {
        $response['error'] = "Invalid payment method.";
        return $response;
    }

    // A negative tip would otherwise reduce $total below (and the amount
    // actually charged via Stripe) — clamp before it ever reaches the math.
    $tipAmount = max(0.0, $tipAmount);

    $sameDayFee = 0.00;

    // Membership-gated free same-day is a HeyDaniel+ perk — the client
    // already gates this in the UI, but a stale page or a direct API call
    // could still send is_same_day=true for a non-member, so re-check
    // server-side rather than trusting the flag.
    if ($isSameDay) {
        $stmt = $db->prepare("SELECT IsMember FROM Users WHERE Id = ? LIMIT 1");
        $stmt->execute([$userId]);
        if (!(bool)$stmt->fetchColumn()) {
            $isSameDay = false;
        }
    } elseif ($paidSameDay) {
        // Pay-per-order same-day — no membership required, but it isn't
        // free: SAME_DAY_PAID_FEE gets added to the total and charged below.
        $isSameDay  = true;
        $sameDayFee = SAME_DAY_PAID_FEE;
    }

    if (!$isSameDay) {
        $tipAmount = 0.0;
    }

    // Validate address
    $requiredAddressFields = ['Address', 'City', 'State', 'ZipCode', 'Phone'];
    foreach ($requiredAddressFields as $field) {
        if (empty($address[$field])) {
            $response['error'] = "Missing address field: {$field}.";
            return $response;
        }
    }

    try {
        // Fetch cart items
        $stmt = $db->prepare("
            SELECT
                CI.ProductId,
                CI.Quantity,
                COALESCE(
                    CASE
                        WHEN P.isOnSale = 1 THEN P.SalePrice
                        ELSE P.Price
                    END, P.Price
                ) AS UnitPrice
            FROM Cart CI
            INNER JOIN Products P ON CI.ProductId = P.Id
            WHERE CI.UserId = ?
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cartItems)) {
            $response['error'] = "Cart is empty.";
            return $response;
        }

        // Calculate totals
        $subtotal  = array_sum(array_map(fn($item) => $item['UnitPrice'] * $item['Quantity'], $cartItems));
        $taxAmount = round($subtotal * $taxRate, 2);
        $tip       = $isSameDay ? round($tipAmount, 2) : 0.00;
        $total     = round($subtotal + $taxAmount + $tip + $sameDayFee, 2);
        $itemCount = array_sum(array_column($cartItems, 'Quantity'));

        // Check for existing Stripe Customer
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

        $stmt = $db->prepare("SELECT StripeCustomerId FROM CustomerPaymentMethod WHERE UserId = ? LIMIT 1");
        $stmt->execute([$userId]);
        $paymentRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($paymentRow) {
            $stripeCustomerId = $paymentRow['StripeCustomerId'];

            // Attach new payment method to existing customer
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach([
                'customer' => $stripeCustomerId,
            ]);
        } else {
            // Fetch user info for Stripe Customer creation
            $stmt = $db->prepare("SELECT Name, Email FROM Users WHERE Id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Create new Stripe Customer
            $customer = \Stripe\Customer::create([
                'name'           => $user['Name'],
                'email'          => $user['Email'],
                'payment_method' => $paymentMethodId,
            ]);

            $stripeCustomerId = $customer->id;

            // Save to CustomerPaymentMethod
            $stmt = $db->prepare("
                INSERT INTO CustomerPaymentMethod (UserId, PaymentMethod, StripeCustomerId)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $paymentMethodId, $stripeCustomerId]);
        }

        // Create Payment Intent with manual capture
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount'               => (int)round($total * 100),
            'currency'             => 'usd',
            'customer'             => $stripeCustomerId,
            'payment_method'       => $paymentMethodId,
            'payment_method_types' => ['card'],
            'capture_method'       => 'manual',
            'confirm'              => true,
            'description'          => "HeyDaniel, LLC order - UserId: {$userId}",
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe Error in submitCheckout: " . $e->getMessage());
        $response['error'] = "Payment processing failed.";
        return $response;
    } catch (\PDOException $e) {
        error_log("DB Error in submitCheckout: " . $e->getMessage());
        $response['error'] = "Internal server error during checkout.";
        return $response;
    }

    // Everything from here on is the atomic "commit the order" step, kept to
    // a short transaction of pure DB writes — the slow Stripe round-trips
    // above no longer happen while a transaction (and its row locks) is open.
    try {
        $db->beginTransaction();

        // Store Payment Intent ID in CustomerPaymentMethod
        $stmt = $db->prepare("
            UPDATE CustomerPaymentMethod 
            SET StripePaymentIntentId = ? 
            WHERE UserId = ?
        ");
        $stmt->execute([$paymentIntent->id, $userId]);

        // Save address
        $stmt = $db->prepare("
            INSERT INTO UserAddresses (UserId, Address, Apt, City, State, ZipCode, LatnLong, GateCode, Note, Phone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $address['Address'],
            $address['Apt']      ?? '',
            $address['City'],
            $address['State'],
            $address['ZipCode'],
            $address['LatnLong']  ?? '',
            $address['GateCode'] ?? '',
            $address['Note']     ?? '',
            $address['Phone']
        ]);

        // A same-day order needs a real 3-hour buffer (shopping + prep +
        // delivery) to still land before closing — one placed too late in
        // the day to fit that buffer can't honestly promise "today," so it
        // gets pinned upfront to tomorrow's opening window instead of the
        // usual ASAP placeholder ETA. Uses the same store-hours constants
        // as the reschedule flow (getRescheduleWindowOptions()) so the two
        // stay consistent.
        date_default_timezone_set('America/New_York');
        $now = time();
        $closeToday = strtotime(date('Y-m-d', $now) . ' ' . STORE_CLOSE_HOUR . ':00:00');
        $missedSameDayCutoff = $isSameDay && ($now + 3 * 3600 >= $closeToday);

        $scheduledWindow = null;
        $scheduledStart  = null;
        $scheduledEnd    = null;
        if ($missedSameDayCutoff) {
            $nextWindow      = getNextDayOpeningWindow($now);
            $scheduledWindow = $nextWindow['label'];
            $scheduledStart  = date('Y-m-d H:i:s', $nextWindow['start']);
            $scheduledEnd    = date('Y-m-d H:i:s', $nextWindow['end']);
        }

        // Insert into OrderSent first so its Id is available to tag each
        // line item below with the OrderId it belongs to.
        $stmt = $db->prepare("
            INSERT INTO OrderSent (
                UserId, ItemQuantity, OrderRevenue, FinalOrderRevenue, OrderLiability,
                TipAmount, SameDayFee, isSameDay, isTipped, OrderStatus,
                ScheduledDeliveryWindow, ScheduledDeliveryStart, ScheduledDeliveryEnd
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $itemCount,
            $subtotal,
            $total,
            $taxAmount,
            $tip,
            $sameDayFee,
            (int)$isSameDay,
            (int)($tip > 0),
            $scheduledWindow,
            $scheduledStart,
            $scheduledEnd
        ]);

        $orderId = (int)$db->lastInsertId();

        // Move cart items to Process (same-day) or OrderTracking (standard
        // shipping) — the two tables track different things per item, so
        // they need different column lists rather than one shared insert.
        if ($isSameDay) {
            $insertStmt = $db->prepare("
                INSERT INTO Process (UserId, ProductId, OrderId, Quantity, isStocked)
                VALUES (?, ?, ?, ?, 1)
            ");

            foreach ($cartItems as $item) {
                $insertStmt->execute([$userId, $item['ProductId'], $orderId, $item['Quantity']]);
            }
        } else {
            $insertStmt = $db->prepare("
                INSERT INTO OrderTracking (UserId, ProductId, OrderId, ItemQuantity, OrderRevenue, OrderLiability)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($cartItems as $item) {
                $itemRevenue = round($item['UnitPrice'] * $item['Quantity'], 2);
                $insertStmt->execute([
                    $userId,
                    $item['ProductId'],
                    $orderId,
                    $item['Quantity'],
                    $itemRevenue,
                    round($itemRevenue * $taxRate, 2)
                ]);
            }
        }

        // Clear cart
        $stmt = $db->prepare("DELETE FROM Cart WHERE UserId = ?");
        $stmt->execute([$userId]);

        $db->commit();

        $response['order_id'] = $orderId;

        createNotification(
            $db,
            $userId,
            'order',
            'Order placed',
            "Your order #{$orderId} has been placed for \$" . number_format($total, 2) . "."
        );
    } catch (\PDOException $e) {
        $db->rollBack();
        error_log("DB Error in submitCheckout: " . $e->getMessage());
        $response['error'] = "Internal server error during checkout.";
    }

    return $response;
}

function subscribeMembership(\PDO $db, int $userId, string $paymentMethodId): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated.";
        return $response;
    }

    if (empty($paymentMethodId)) {
        $response['error'] = "Invalid payment method.";
        return $response;
    }

    $priceId = $_ENV['STRIPE_MEMBERSHIP_PRICE_ID'] ?? '';
    if (empty($priceId)) {
        error_log("subscribeMembership: STRIPE_MEMBERSHIP_PRICE_ID is not configured.");
        $response['error'] = "Membership is not available right now.";
        return $response;
    }

    try {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

        $stmt = $db->prepare("SELECT StripeCustomerId, StripeSubscriptionId FROM CustomerPaymentMethod WHERE UserId = ? LIMIT 1");
        $stmt->execute([$userId]);
        $paymentRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($paymentRow['StripeSubscriptionId'])) {
            $existing = \Stripe\Subscription::retrieve($paymentRow['StripeSubscriptionId']);
            if (in_array($existing->status, ['active', 'trialing'], true)) {
                $response['error'] = "You're already subscribed.";
                return $response;
            }
        }

        if ($paymentRow) {
            $stripeCustomerId = $paymentRow['StripeCustomerId'];

            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $stripeCustomerId]);

            \Stripe\Customer::update($stripeCustomerId, [
                'invoice_settings' => ['default_payment_method' => $paymentMethodId],
            ]);
        } else {
            $stmt = $db->prepare("SELECT Name, Email FROM Users WHERE Id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $customer = \Stripe\Customer::create([
                'name'             => $user['Name'],
                'email'            => $user['Email'],
                'payment_method'   => $paymentMethodId,
                'invoice_settings' => ['default_payment_method' => $paymentMethodId],
            ]);

            $stripeCustomerId = $customer->id;

            $stmt = $db->prepare("
                INSERT INTO CustomerPaymentMethod (UserId, PaymentMethod, StripeCustomerId)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $paymentMethodId, $stripeCustomerId]);
        }

        $subscription = \Stripe\Subscription::create([
            'customer'                => $stripeCustomerId,
            'items'                   => [['price' => $priceId]],
            'default_payment_method'  => $paymentMethodId,
            'expand'                  => ['latest_invoice.payment_intent'],
        ]);

        if (!in_array($subscription->status, ['active', 'trialing'], true)) {
            $paymentIntent = $subscription->latest_invoice->payment_intent ?? null;
            // Leave the subscription as 'incomplete' rather than cancelling it —
            // Stripe will retry the invoice, and the client can retry confirming
            // the payment intent if it required additional authentication.
            if ($paymentIntent && $paymentIntent->status === 'requires_action') {
                $response['requires_action'] = true;
                $response['client_secret']   = $paymentIntent->client_secret;
                return $response;
            }

            $response['error'] = "Your card was declined.";
            return $response;
        }

        $stmt = $db->prepare("UPDATE Users SET IsMember = 1, TimeMembership = NOW() WHERE Id = ?");
        $stmt->execute([$userId]);

        $stmt = $db->prepare("UPDATE CustomerPaymentMethod SET StripeSubscriptionId = ? WHERE UserId = ?");
        $stmt->execute([$subscription->id, $userId]);

        $response['success'] = true;

        createNotification($db, $userId, 'membership', 'Membership activated', 'Your HeyDaniel+ membership is now active.');
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe Error in subscribeMembership: " . $e->getMessage());
        $response['error'] = "Payment processing failed.";
    } catch (\PDOException $e) {
        error_log("DB Error in subscribeMembership: " . $e->getMessage());
        $response['error'] = "Internal server error during subscription.";
    }

    return $response;
}

function cancelMembership(\PDO $db, int $userId): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated.";
        return $response;
    }

    try {
        $stmt = $db->prepare("SELECT StripeSubscriptionId FROM CustomerPaymentMethod WHERE UserId = ? LIMIT 1");
        $stmt->execute([$userId]);
        $subscriptionId = $stmt->fetchColumn();

        if ($subscriptionId) {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');
            try {
                \Stripe\Subscription::retrieve($subscriptionId)->cancel();
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Already cancelled on Stripe's side (e.g. via a prior webhook) — treat as success.
            }

            $stmt = $db->prepare("UPDATE CustomerPaymentMethod SET StripeSubscriptionId = NULL WHERE UserId = ?");
            $stmt->execute([$userId]);
        }

        $stmt = $db->prepare("UPDATE Users SET IsMember = 0 WHERE Id = ?");
        $stmt->execute([$userId]);

        $response['success'] = true;

        createNotification($db, $userId, 'membership', 'Membership cancelled', 'Your HeyDaniel+ membership has been cancelled.');
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe Error in cancelMembership: " . $e->getMessage());
        $response['error'] = "Unable to cancel membership.";
    } catch (\PDOException $e) {
        error_log("DB Error in cancelMembership: " . $e->getMessage());
        $response['error'] = "Internal server error while cancelling membership.";
    }

    return $response;
}

function finalizeOrder(\PDO $db, int $userId, int $orderId, float $tipAmount): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if ($userId <= 0 || $orderId <= 0) {
        $response['error'] = "Invalid request.";
        return $response;
    }

    $tipAmount = max(0.0, $tipAmount);

    try {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

        // Fetch order
        $stmt = $db->prepare("
            SELECT * FROM OrderSent WHERE Id = ? AND UserId = ? LIMIT 1
        ");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $response['error'] = "Order not found.";
            return $response;
        }

        // HeyDaniel+ members earn cash back on every completed order: a flat
        // $9.99 (the value of the same-day delivery fee they don't pay) plus
        // $0.05 for every full $50 of the order subtotal (OrderRevenue, i.e.
        // before tax/tip). Non-members earn nothing.
        $stmt = $db->prepare("SELECT IsMember FROM Users WHERE Id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $isMember = (bool)$stmt->fetchColumn();

        $creditsEarned = 0.00;
        if ($isMember) {
            $creditsEarned = round(9.99 + floor($order['OrderRevenue'] / 50) * 0.05, 2);
        }

        // Fetch Payment Intent ID
        $stmt = $db->prepare("
            SELECT StripeCustomerId, PaymentMethod, StripePaymentIntentId 
            FROM CustomerPaymentMethod WHERE UserId = ? LIMIT 1
        ");
        $stmt->execute([$userId]);
        $paymentRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paymentRow || empty($paymentRow['StripePaymentIntentId'])) {
            $response['error'] = "Payment information not found.";
            return $response;
        }

        // Charge tip separately if same-day and tipped
        if ($order['isSameDay'] && $tipAmount > 0) {
            \Stripe\PaymentIntent::create([
                'amount'               => (int)round($tipAmount * 100),
                'currency'             => 'usd',
                'customer'             => $paymentRow['StripeCustomerId'],
                'payment_method'       => $paymentRow['PaymentMethod'],
                'payment_method_types' => ['card'],
                'off_session'          => true,
                'confirm'              => true,
                'description'          => "HeyDaniel, LLC tip - OrderId: {$orderId}",
            ]);
        }

        // Capture main Payment Intent at FinalOrderRevenue
        $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentRow['StripePaymentIntentId']);
        $paymentIntent->capture([
            'amount_to_capture' => (int)round($order['FinalOrderRevenue'] * 100),
        ]);

        // Update OrderSent
        $stmt = $db->prepare("
            UPDATE OrderSent
            SET isClosed = 1,
                isTipped = ?,
                TipAmount = ?,
                CreditsEarned = ?,
                OrderStatus = 'delivered',
                TimeDelivered = NOW()
            WHERE Id = ? AND UserId = ?
        ");
        $stmt->execute([
            (int)($tipAmount > 0),
            $tipAmount,
            $creditsEarned,
            $orderId,
            $userId
        ]);

        // Clear Payment Intent ID from CustomerPaymentMethod
        $stmt = $db->prepare("
            UPDATE CustomerPaymentMethod
            SET StripePaymentIntentId = NULL
            WHERE UserId = ?
        ");
        $stmt->execute([$userId]);

        if ($creditsEarned > 0) {
            $stmt = $db->prepare("UPDATE Users SET Credits = Credits + ? WHERE Id = ?");
            $stmt->execute([$creditsEarned, $userId]);
        }

        $response['success'] = true;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe Error in finalizeOrder: " . $e->getMessage());
        $response['error'] = "Payment capture failed.";
    } catch (\PDOException $e) {
        error_log("DB Error in finalizeOrder: " . $e->getMessage());
        $response['error'] = "Internal server error during order finalization.";
    }

    return $response;
}

// Cancels the caller's active same-day order. Default caller (the manual
// Cancel Order button) is scoped to Pending only — once a shopper has
// started Processing/picking it, the UI offers Reschedule Delivery instead
// (getActiveSameDayOrderId can return a Processing/Shipped order id too;
// the default rejects those rather than trusting the UI to have gated it).
// updateDeviceZip() passes a wider $allowedStatuses (Pending/Processing/
// Shipped) since an ineligible zip switch cancels the order outright
// regardless of how far along it is. Mirrors cancelMembership's shape.
// checkout() only authorizes the PaymentIntent (capture_method: manual,
// captured later in finalizeOrder at delivery), so cancelling here means
// voiding that hold via Stripe's PaymentIntent::cancel(), not issuing a
// refund. Process/OrderTracking rows are left alone — they persist forever
// as buy-again history regardless of the order's outcome (see
// getActiveSameDayOrderId's comment).
// Best-effort "check this order right now" ping to the order-status
// WebSocket daemon (Server/WebSocket/run.php), so a write this request just
// committed shows up for anyone watching that order immediately instead of
// waiting for the daemon's own periodic poll. Fire-and-forget on purpose:
// a very short connect timeout and every failure mode (daemon not running,
// port closed, whatever) is swallowed rather than surfaced, since the
// WebSocket path is a nice-to-have on top of the REST API this request is
// already correctly serving - it must never be able to fail or slow down
// the actual write it's reporting on.
function notifyOrderStatusServer(int $orderId): void
{
    $socket = @stream_socket_client(
        "tcp://127.0.0.1:8081",
        $errno,
        $errstr,
        0.2
    );

    if ($socket === false) {
        return;
    }

    @fwrite($socket, json_encode(['order_id' => $orderId]) . "\n");
    @fclose($socket);
}

function cancelOrder(\PDO $db, int $userId, array $allowedStatuses = ['Pending']): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated.";
        return $response;
    }

    $orderId = getActiveSameDayOrderId($db, $userId);

    if ($orderId === null) {
        $response['error'] = "No active order to cancel.";
        return $response;
    }

    try {
        $stmt = $db->prepare("SELECT StripePaymentIntentId FROM CustomerPaymentMethod WHERE UserId = ? LIMIT 1");
        $stmt->execute([$userId]);
        $paymentIntentId = $stmt->fetchColumn();

        if ($paymentIntentId) {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');
            try {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                if (in_array($paymentIntent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action', 'requires_capture'], true)) {
                    $paymentIntent->cancel();
                }
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Already cancelled/captured on Stripe's side — proceed with the DB update regardless.
            }

            $stmt = $db->prepare("UPDATE CustomerPaymentMethod SET StripePaymentIntentId = NULL WHERE UserId = ?");
            $stmt->execute([$userId]);
        }

        $placeholders = implode(',', array_fill(0, count($allowedStatuses), '?'));
        $stmt = $db->prepare("
            UPDATE OrderSent
            SET OrderStatus = 'Cancelled', isClosed = 1
            WHERE Id = ? AND UserId = ? AND OrderStatus IN ($placeholders)
        ");
        $stmt->execute([$orderId, $userId, ...$allowedStatuses]);

        if ($stmt->rowCount() === 0) {
            $response['error'] = "Order can no longer be cancelled.";
            return $response;
        }

        $response['success'] = true;
        notifyOrderStatusServer($orderId);

        createNotification($db, $userId, 'order', 'Order cancelled', "Your order #{$orderId} has been cancelled.");
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe Error in cancelOrder: " . $e->getMessage());
        $response['error'] = "Unable to cancel order.";
    } catch (\PDOException $e) {
        error_log("DB Error in cancelOrder: " . $e->getMessage());
        $response['error'] = "Internal server error while cancelling order.";
    }

    return $response;
}

// Store hours used to generate reschedule delivery windows.
const STORE_OPEN_HOUR  = 8;
const STORE_CLOSE_HOUR = 21;

// Reschedule always offers at least this many options, spilling into
// following days once the current/nearest day runs out of room rather than
// collapsing to a single fallback slot.
const MIN_RESCHEDULE_OPTIONS = 6;

// Tomorrow's opening delivery block (store open time to open+4h) — the
// fallback slot getRescheduleWindowOptions() offers once today runs out of
// room, and also what a same-day order automatically gets pinned to when
// it's placed too late for same-day delivery to be physically possible
// (see submitCheckout()'s cutoff check).
function getNextDayOpeningWindow(?int $now = null): array
{
    date_default_timezone_set('America/New_York');
    $now = $now ?? time();

    $today        = date('Y-m-d', $now);
    $openToday    = strtotime("{$today} " . STORE_OPEN_HOUR . ':00:00');
    $openTomorrow = $openToday + 86400;
    $closeTomorrow = $openTomorrow + 4 * 3600;
    $day  = date('D, j M', $openTomorrow);
    $time = date('g:i A', $openTomorrow) . ' – ' . date('g:i A', $closeTomorrow);

    return [
        'start' => $openTomorrow,
        'end'   => $closeTomorrow,
        'day'   => $day,
        'time'  => $time,
        'label' => "{$day}, {$time}",
    ];
}

// Generates the delivery windows a customer can reschedule into: 2-hour
// blocks, the first starting 3 hours from now (rounded up to the next
// :00/:30 for a clean display time). Walks forward day by day — today,
// tomorrow, the day after — generating each day's blocks between store
// open/close, until at least MIN_RESCHEDULE_OPTIONS exist. This is what
// makes it keep going into tomorrow (and beyond, if needed) once the
// current day runs out of room, rather than stopping at whatever's left
// today plus one fixed fallback slot — the 7-day cap is just a safety net,
// not a real constraint (store hours alone give ~6 slots/day, so this
// realistically never needs more than 2 days). There's no real
// delivery-routing/scheduling system behind this yet (the ETA shown
// elsewhere is a hardcoded placeholder), so this only produces the labels
// offered to and validated for rescheduleDelivery() — not real
// capacity/availability.
function getRescheduleWindowOptions(?int $now = null): array
{
    date_default_timezone_set('America/New_York');
    $now = $now ?? time();

    $start = $now + 3 * 3600;
    $start -= $start % 60;
    $remainder = ($start / 60) % 30;
    if ($remainder !== 0) {
        $start += (30 - $remainder) * 60;
    }

    $options = [];

    for ($daysAhead = 0; $daysAhead < 7 && count($options) < MIN_RESCHEDULE_OPTIONS; $daysAhead++) {
        $dayDate  = date('Y-m-d', $now + $daysAhead * 86400);
        $openDay  = strtotime("{$dayDate} " . STORE_OPEN_HOUR . ':00:00');
        $closeDay = strtotime("{$dayDate} " . STORE_CLOSE_HOUR . ':00:00');

        // Only the lead-time floor applies to today; every later day starts
        // fresh at opening.
        $slotStart = $daysAhead === 0 ? max($start, $openDay) : $openDay;

        while ($slotStart + 2 * 3600 <= $closeDay && count($options) < MIN_RESCHEDULE_OPTIONS) {
            $slotEnd = $slotStart + 2 * 3600;
            // Same "Today" vs weekday-date convention formatScheduledDeliveryWindow()
            // uses for displaying an already-saved window, so labels stay
            // consistent once a picked slot is stored and redisplayed later.
            $day  = $daysAhead === 0 ? 'Today' : date('D, j M', $slotStart);
            $time = date('g:i A', $slotStart) . ' – ' . date('g:i A', $slotEnd);
            $options[] = [
                'start' => $slotStart,
                'end'   => $slotEnd,
                'day'   => $day,
                'time'  => $time,
                'label' => "{$day}, {$time}",
            ];
            $slotStart = $slotEnd;
        }
    }

    return $options;
}

// Formats a saved delivery window for display: if its date matches the
// viewer's current date, shows "Today, <time range>" instead of whatever
// weekday/date it was labeled with at reschedule time — so a window saved
// as "Mon, 27 Jul, 8:00 AM – 12:00 PM" correctly reads as "Today, 8:00 AM –
// 12:00 PM" once that day actually arrives, rather than staying frozen on
// the date it was picked under. Falls back to the raw saved label when
// Start/End weren't recorded (rows written before those columns existed).
function formatScheduledDeliveryWindow(?string $start, ?string $end, ?string $rawLabel = null): ?string
{
    if (!$start || !$end) {
        return $rawLabel;
    }

    date_default_timezone_set('America/New_York');

    $startTs = strtotime($start);
    $endTs   = strtotime($end);
    $day     = (date('Y-m-d', $startTs) === date('Y-m-d')) ? 'Today' : date('D, j M', $startTs);
    $time    = date('g:i A', $startTs) . ' – ' . date('g:i A', $endTs);

    return "{$day}, {$time}";
}

// Reschedules the caller's active order to one of the same-day/next-day
// windows computed by getRescheduleWindowOptions(), validated server-side
// against that same generator rather than trusting whatever string the
// client sends. Only offered (and only takes effect) while the order is
// Processing. A reschedule throws out whatever picking progress exists —
// it resets every Process row's QuantityFound back to NULL (= "processing"/
// not yet picked, per processContent()'s NULL-means-unpicked convention)
// and drops OrderStatus back to Pending, so the order re-enters the queue
// for the new window rather than resuming a partially-picked state from the
// old one. That also means Cancel Order becomes available again afterward
// (Pending, not Processing) — intentional, not a bug.
function rescheduleDelivery(\PDO $db, int $userId, string $window): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if ($userId <= 0) {
        $response['error'] = "User not authenticated.";
        return $response;
    }

    $windowOptions = getRescheduleWindowOptions();
    $matchedOption = null;
    foreach ($windowOptions as $option) {
        if ($option['label'] === $window) {
            $matchedOption = $option;
            break;
        }
    }

    if ($matchedOption === null) {
        $response['error'] = "Invalid delivery window.";
        return $response;
    }

    $orderId = getActiveSameDayOrderId($db, $userId);

    if ($orderId === null) {
        $response['error'] = "No active order to reschedule.";
        return $response;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            UPDATE OrderSent
            SET ScheduledDeliveryWindow = ?, ScheduledDeliveryStart = FROM_UNIXTIME(?), ScheduledDeliveryEnd = FROM_UNIXTIME(?), OrderStatus = 'Pending', WasRescheduled = 1
            WHERE Id = ? AND UserId = ? AND OrderStatus = 'Processing'
        ");
        $stmt->execute([$window, $matchedOption['start'], $matchedOption['end'], $orderId, $userId]);

        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            $response['error'] = "Order can no longer be rescheduled.";
            return $response;
        }

        $stmt = $db->prepare("UPDATE Process SET QuantityFound = NULL WHERE OrderId = ? AND UserId = ?");
        $stmt->execute([$orderId, $userId]);

        $db->commit();

        // processContent() caches the raw Process rows (including
        // QuantityFound) for an hour — nothing wrote to that column before
        // this function existed, so that cache was never actually
        // exercised until now. Must invalidate or the customer keeps seeing
        // pre-reset picking progress.
        QueryCache::delete("process_content:{$userId}:{$orderId}");

        $response['success'] = true;
        $response['scheduled_delivery_window'] = $window;
        notifyOrderStatusServer($orderId);

        createNotification($db, $userId, 'order', 'Delivery rescheduled', "Your order #{$orderId}'s delivery has been rescheduled to {$window}.");
    } catch (\PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("DB Error in rescheduleDelivery: " . $e->getMessage());
        $response['error'] = "Internal server error while rescheduling delivery.";
    }

    return $response;
}

// Sum of CreditsEarned across orders delivered so far this calendar month —
// powers the "You've saved $X this month" figure on the homepage member
// banner. Scoped to TimeDelivered (when the credit was actually earned in
// finalizeOrder()), not DateAdded (when the order was placed).
function monthlySavings(\PDO $db, int $userId): float
{
    if ($userId <= 0) {
        return 0.00;
    }

    $monthStart = date('Y-m-01 00:00:00');

    $stmt = $db->prepare("
        SELECT COALESCE(SUM(CreditsEarned), 0)
        FROM OrderSent
        WHERE UserId = ? AND isClosed = 1 AND TimeDelivered >= ?
    ");
    $stmt->execute([$userId, $monthStart]);

    return (float)$stmt->fetchColumn();
}

function orderHistory(\PDO $db, int $userId, float $taxRate): array
{
    $response = [
        'orders' => [],
        'error'  => null
    ];

    if ($userId <= 0) {
        return $response;
    }

    try {
        $stmt = $db->prepare("
            SELECT
                Id, ItemQuantity, OrderRevenue, FinalOrderRevenue, OrderLiability,
                TipAmount, isSameDay, OrderStatus, DateAdded, TimeDelivered, isClosed
            FROM OrderSent
            WHERE UserId = ?
            ORDER BY DateAdded DESC, Id DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Small preview of what was ordered — first few product pictures, plus
        // the total distinct line count so the row can show a "+N" overflow —
        // shown on the collapsed row before the full item list is expanded.
        $processPicStmt = $db->prepare("
            SELECT p.Picture FROM Process t JOIN Products p ON p.Id = t.ProductId
            WHERE t.OrderId = ? AND t.UserId = ? LIMIT 3
        ");
        $trackingPicStmt = $db->prepare("
            SELECT p.Picture FROM OrderTracking t JOIN Products p ON p.Id = t.ProductId
            WHERE t.OrderId = ? AND t.UserId = ? LIMIT 3
        ");
        $processCountStmt = $db->prepare("SELECT COUNT(*) FROM Process WHERE OrderId = ? AND UserId = ?");
        $trackingCountStmt = $db->prepare("SELECT COUNT(*) FROM OrderTracking WHERE OrderId = ? AND UserId = ?");

        foreach ($rows as $row) {
            $orderId = (int)$row['Id'];
            $isSameDay = (bool)$row['isSameDay'];

            $picStmt = $isSameDay ? $processPicStmt : $trackingPicStmt;
            $picStmt->execute([$orderId, $userId]);
            $pictures = $picStmt->fetchAll(PDO::FETCH_COLUMN);

            $countStmt = $isSameDay ? $processCountStmt : $trackingCountStmt;
            $countStmt->execute([$orderId, $userId]);
            $lineCount = (int)$countStmt->fetchColumn();

            $response['orders'][] = [
                'order_id'       => $orderId,
                'item_count'     => (int)$row['ItemQuantity'],
                'subtotal'       => (float)$row['OrderRevenue'],
                'tax'            => (float)$row['OrderLiability'],
                'tip'            => (float)$row['TipAmount'],
                'total'          => (float)$row['FinalOrderRevenue'],
                'status'         => $row['OrderStatus'],
                'date_added'     => $row['DateAdded'],
                'time_delivered' => $row['TimeDelivered'],
                'is_closed'      => (bool)$row['isClosed'],
                'pictures'       => $pictures,
                'more_count'     => max(0, $lineCount - count($pictures))
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in orderHistory: " . $e->getMessage());
        $response['error'] = "Internal server error during order history retrieval.";
    }

    return $response;
}

function orderDetails(\PDO $db, int $userId, int $orderId): array
{
    $response = [
        'order' => null,
        'error' => null
    ];

    if ($userId <= 0 || $orderId <= 0) {
        $response['error'] = "Invalid request.";
        return $response;
    }

    try {
        $stmt = $db->prepare("
            SELECT
                Id, ItemQuantity, OrderRevenue, FinalOrderRevenue, OrderLiability,
                TipAmount, isSameDay, OrderStatus, DateAdded, TimeDelivered, isClosed
            FROM OrderSent
            WHERE Id = ? AND UserId = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $response['error'] = "Order not found.";
            return $response;
        }

        // Process (same-day) uses `Quantity`; OrderTracking (standard) uses `ItemQuantity`.
        // Process also tracks per-item isStocked; OrderTracking has no such column, so
        // standard-shipping items are always reported as stocked.
        $itemsTable = (bool)$order['isSameDay'] ? 'Process' : 'OrderTracking';
        $quantityColumn = $itemsTable === 'Process' ? 'Quantity' : 'ItemQuantity';
        $stockedColumn = $itemsTable === 'Process' ? 't.isStocked' : '1';
        $stmt = $db->prepare("
            SELECT t.ProductId, t.{$quantityColumn} AS Quantity, t.isMissing, {$stockedColumn} AS isStocked,
                   p.Name, p.Brand, p.Picture, p.Price
            FROM {$itemsTable} t
            JOIN Products p ON p.Id = t.ProductId
            WHERE t.OrderId = ? AND t.UserId = ?
        ");
        $stmt->execute([$orderId, $userId]);
        $itemRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($itemRows as $row) {
            $items[] = [
                'product_id' => (int)$row['ProductId'],
                'name'       => $row['Name'],
                'brand'      => $row['Brand'],
                'picture'    => $row['Picture'],
                'price'      => (float)$row['Price'],
                'quantity'   => (int)$row['Quantity'],
                'is_missing' => (int)$row['isMissing'],
                'in_stock'   => (bool)$row['isStocked']
            ];
        }

        $response['order'] = [
            'order_id'       => (int)$order['Id'],
            'item_count'     => (int)$order['ItemQuantity'],
            'subtotal'       => (float)$order['OrderRevenue'],
            'tax'            => (float)$order['OrderLiability'],
            'tip'            => (float)$order['TipAmount'],
            'total'          => (float)$order['FinalOrderRevenue'],
            'status'         => $order['OrderStatus'],
            'date_added'     => $order['DateAdded'],
            'time_delivered' => $order['TimeDelivered'],
            'is_closed'      => (bool)$order['isClosed'],
            'items'          => $items
        ];
    } catch (\PDOException $e) {
        error_log("DB Error in orderDetails: " . $e->getMessage());
        $response['error'] = "Internal server error during order detail retrieval.";
    }

    return $response;
}

function itemPush(\PDO $db, int $userId, string $deviceSignature, int $productId, string $table): array
{
    $response = ['error' => null];

    $allowedTables = ['RecentlyViewed', 'SearchHistory'];
    if (!in_array($table, $allowedTables, true)) {
        $response['error'] = "Invalid table.";
        return $response;
    }

    if ($productId <= 0) {
        $response['error'] = "Invalid product ID.";
        return $response;
    }

    if ($deviceSignature === "" || strlen($deviceSignature) > 128 || !preg_match('/^[a-zA-Z0-9-]+$/', $deviceSignature)) {
        $response['error'] = "Invalid device signature.";
        return $response;
    }

    try {
        $stmt = $db->prepare("INSERT INTO {$table} (UserId, ProductId, DeviceSignature, DateViewed) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $productId, $deviceSignature]);
    } catch (\PDOException $e) {
        error_log("DB Error in itemPush: " . $e->getMessage());
        $response['error'] = "Internal server error during item view logging.";
    }

    return $response;
}

function collectEmail(\PDO $db, string $userEmail, bool $isUpdatingPassword): array
{
    $response = [
        "is_registered" => false,
        "email" => null,
        "message" => null,
        "error" => null
    ];

    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = 'Invalid email format';
        return $response;
    }

    try {
        $stmt = $db->prepare("SELECT 1 FROM Users WHERE Email = ? LIMIT 1");
        $stmt->execute([$userEmail]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            if ($isUpdatingPassword) {
                $uniqueCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                date_default_timezone_set('America/New_York');

                $codeExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $stmt = $db->prepare("
                    INSERT INTO PasswordResetCodes (Email, UniqueCode, SentIn, ExpiredAt)
                    VALUES (?, ?, NOW(), ?)
                    ON DUPLICATE KEY UPDATE UniqueCode = ?, SentIn = NOW(), ExpiredAt = ?
                ");
                $stmt->execute([$userEmail, $uniqueCode, $codeExpiry, $uniqueCode, $codeExpiry]);

                $response['is_registered'] = true;

                $subject = "Your HeyDaniel Password Reset Code";
                $body    = "Hi,\n\nYour password reset code is: {$uniqueCode}\n\nThis code expires in 10 minutes.\n\nIf you did not request this, please ignore this email.\n\n— The HeyDaniel Team";
                $headers = implode("\r\n", [
                    "From: HeyDaniel <no-reply@heydaniel.com>",
                    "Reply-To: no-reply@heydaniel.com",
                    "Content-Type: text/plain; charset=UTF-8",
                    "X-Mailer: PHP/" . phpversion()
                ]);

                mail($userEmail, $subject, $body, $headers);

                $response['message'] = "Password reset code sent to your email.";
                $response['email'] = $userEmail;
                return $response;
            } else {
                $response['error'] = "Email is already registered. try logging in or use a different email.";
                return $response;
            }
        } else {
            if ($isUpdatingPassword) {
                $response['error'] = "Email not found. Please check the email or register for a new account.";
                return $response;
            } else {
                $response['email'] = $userEmail;
                $response['message'] = "Enter your name and password to register";
                $response['is_registered'] = false;
                return $response;
            }
        }
    } catch (\PDOException $e) {
        error_log("DB Error in collectEmail: " . $e->getMessage());
        $response['error'] = "Internal server error during email collection.";
    }

    return $response;
}

function subscribeNewsletter(\PDO $db, string $email): array
{
    $response = [
        'success' => false,
        'message' => null,
        'error'   => null
    ];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = 'Invalid email format.';
        return $response;
    }

    try {
        // Signing up again with the same email is a no-op, not an error —
        // the UNIQUE key on Email absorbs the repeat via ON DUPLICATE KEY
        // (updating DateAdded is harmless and avoids a separate SELECT).
        $stmt = $db->prepare("
            INSERT INTO NewsletterSubscribers (Email)
            VALUES (?)
            ON DUPLICATE KEY UPDATE DateAdded = DateAdded
        ");
        $stmt->execute([$email]);

        $response['success'] = true;
        $response['message'] = "You're subscribed! Look out for deals in your inbox.";
    } catch (\PDOException $e) {
        error_log("DB Error in subscribeNewsletter: " . $e->getMessage());
        $response['error'] = "Internal server error during newsletter signup.";
    }

    return $response;
}

function validateResetCode(\PDO $db, string $userEmail, string $uniqueCode): ?string
{
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email format.';
    }

    if (!preg_match('/^\d{6}$/', $uniqueCode)) {
        return 'Invalid code format.';
    }

    date_default_timezone_set('America/New_York');
    $currentTime = date('Y-m-d H:i:s');

    $stmt = $db->prepare("SELECT UniqueCode, ExpiredAt FROM PasswordResetCodes WHERE Email = ? LIMIT 1");
    $stmt->execute([$userEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return 'No code was sent to this email. Please request a code first.';
    }

    if ($currentTime > $row['ExpiredAt']) {
        $stmt = $db->prepare("DELETE FROM PasswordResetCodes WHERE Email = ?");
        $stmt->execute([$userEmail]);
        return 'The code has expired. Please request a new one.';
    }

    if ($uniqueCode !== $row['UniqueCode']) {
        return 'The code entered was not valid.';
    }

    return null;
}

function verifyCode(\PDO $db, string $userEmail, string $uniqueCode): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    try {
        $error = validateResetCode($db, $userEmail, $uniqueCode);

        if ($error !== null) {
            $response['error'] = $error;
            return $response;
        }

        $response['success'] = true;
    } catch (\PDOException $e) {
        error_log("DB Error in verifyCode: " . $e->getMessage());
        $response['error'] = "Internal server error during code verification.";
    }

    return $response;
}

function updatePassword(\PDO $db, string $userEmail, string $uniqueCode, string $password, string $confirmPassword): array
{
    $response = [
        "error" => null
    ];

    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||     // at least one uppercase
        !preg_match('/[a-z]/', $password) ||     // at least one lowercase
        !preg_match('/[0-9]/', $password) ||     // at least one digit
        !preg_match('/[^A-Za-z0-9]/', $password)
    ) {
        $response['error'] = 'Password did not match the criteria. Must be 8+ chars with uppercase, lowercase, number, and symbol.';
        return $response;
    }

    if ($password !== $confirmPassword) {
        $response['error'] = 'Passwords do not match';
        return $response;
    }

    try {
        $codeError = validateResetCode($db, $userEmail, $uniqueCode);

        if ($codeError !== null) {
            $response['error'] = $codeError;
            return $response;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE Users SET Password = ? WHERE Email = ?");
        $success = $stmt->execute([$hashedPassword, $userEmail]);
        if (!$success) {
            $response['error'] = 'Failed to update password. Please try again later.';
            return $response;
        } else {
            // delete any existing reset codes for this email
            $deleteStmt = $db->prepare("DELETE FROM PasswordResetCodes WHERE Email = ?");
            $deleteStmt->execute([$userEmail]);
            $response['success'] = true;
        }
    } catch (\PDOException $e) {
        error_log("DB Error in updating passoword: " . $e->getMessage());
        $response['error'] = "Internal server error during password update.";
    }

    return $response;
}

function changeAccountPassword(\PDO $db, int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if ($userId <= 0) {
        $response['error'] = 'Invalid request.';
        return $response;
    }

    if (
        strlen($newPassword) < 8 ||
        !preg_match('/[A-Z]/', $newPassword) ||
        !preg_match('/[a-z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword) ||
        !preg_match('/[^A-Za-z0-9]/', $newPassword)
    ) {
        $response['error'] = 'New password did not match the criteria. Must be 8+ chars with uppercase, lowercase, number, and symbol.';
        return $response;
    }

    if ($newPassword !== $confirmPassword) {
        $response['error'] = 'New passwords do not match.';
        return $response;
    }

    try {
        $stmt = $db->prepare("SELECT Password FROM Users WHERE Id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($currentPassword, $hash)) {
            $response['error'] = 'Current password is incorrect.';
            return $response;
        }

        $stmt = $db->prepare("UPDATE Users SET Password = ? WHERE Id = ?");
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

        $response['success'] = true;
    } catch (\PDOException $e) {
        error_log("DB Error in changeAccountPassword: " . $e->getMessage());
        $response['error'] = "Internal server error while changing password.";
    }

    return $response;
}

function updateProfile(\PDO $db, int $userId, string $name, string $phone): array
{
    $response = [
        'success' => false,
        'name'    => null,
        'phone'   => null,
        'error'   => null
    ];

    if ($userId <= 0) {
        $response['error'] = 'Invalid request.';
        return $response;
    }

    $name = trim($name);
    if ($name === '' || mb_strlen($name) > 100) {
        $response['error'] = 'Please enter a valid name.';
        return $response;
    }

    $digitsOnly = preg_replace('/\D/', '', $phone);
    if (strlen($digitsOnly) < 10 || strlen($digitsOnly) > 15) {
        $response['error'] = 'Please enter a valid phone number.';
        return $response;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE Users SET Name = ? WHERE Id = ?");
        $stmt->execute([$name, $userId]);

        // Phone lives on the user's most recent address, not on Users —
        // mirrors the same "latest address" lookup loginUser() uses to read it.
        $stmt = $db->prepare("SELECT Id FROM UserAddresses WHERE UserId = ? ORDER BY DateAdded DESC LIMIT 1");
        $stmt->execute([$userId]);
        $addressId = $stmt->fetchColumn();

        if ($addressId) {
            $stmt = $db->prepare("UPDATE UserAddresses SET Phone = ? WHERE Id = ?");
            $stmt->execute([$digitsOnly, $addressId]);
        }

        $db->commit();

        $response['success'] = true;
        $response['name'] = $name;
        $response['phone'] = $digitsOnly;
    } catch (\PDOException $e) {
        $db->rollBack();
        error_log("DB Error in updateProfile: " . $e->getMessage());
        $response['error'] = "Internal server error while updating profile.";
    }

    return $response;
}

function saveAddress(\PDO $db, int $userId, array $address, int $addressId = 0): array
{
    $response = [
        'success'    => false,
        'address_id' => null,
        'error'      => null
    ];

    if ($userId <= 0) {
        $response['error'] = 'Invalid request.';
        return $response;
    }

    $label    = trim((string)($address['Label'] ?? '')) ?: 'Home';
    $street   = trim((string)($address['Address'] ?? ''));
    $apt      = trim((string)($address['Apt'] ?? ''));
    $city     = trim((string)($address['City'] ?? ''));
    $state    = trim((string)($address['State'] ?? ''));
    $zip      = trim((string)($address['ZipCode'] ?? ''));
    $gateCode = trim((string)($address['GateCode'] ?? ''));
    $note     = trim((string)($address['Note'] ?? ''));
    $phone    = preg_replace('/\D/', '', (string)($address['Phone'] ?? ''));

    if ($street === '' || $city === '' || $state === '' || $zip === '') {
        $response['error'] = 'Please fill in address, city, state, and zip code.';
        return $response;
    }

    if ($phone !== '' && (strlen($phone) < 10 || strlen($phone) > 15)) {
        $response['error'] = 'Please enter a valid phone number.';
        return $response;
    }

    try {
        if ($addressId > 0) {
            $stmt = $db->prepare("SELECT Id FROM UserAddresses WHERE Id = ? AND UserId = ? LIMIT 1");
            $stmt->execute([$addressId, $userId]);
            if (!$stmt->fetchColumn()) {
                $response['error'] = 'Address not found.';
                return $response;
            }

            $stmt = $db->prepare("
                UPDATE UserAddresses
                SET Label = ?, Address = ?, Apt = ?, City = ?, State = ?, ZipCode = ?, GateCode = ?, Note = ?, Phone = ?
                WHERE Id = ? AND UserId = ?
            ");
            $stmt->execute([$label, $street, $apt, $city, $state, $zip, $gateCode, $note, $phone, $addressId, $userId]);
            $response['address_id'] = $addressId;
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM UserAddresses WHERE UserId = ?");
            $stmt->execute([$userId]);
            if ((int)$stmt->fetchColumn() >= 10) {
                $response['error'] = 'You can save up to 10 addresses.';
                return $response;
            }

            $stmt = $db->prepare("
                INSERT INTO UserAddresses (UserId, Label, Address, Apt, City, State, ZipCode, LatnLong, GateCode, Note, Phone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $label, $street, $apt, $city, $state, $zip, '', $gateCode, $note, $phone]);
            $response['address_id'] = (int)$db->lastInsertId();
        }

        $response['success'] = true;
    } catch (\PDOException $e) {
        error_log("DB Error in saveAddress: " . $e->getMessage());
        $response['error'] = "Internal server error while saving address.";
    }

    return $response;
}

function deleteAddress(\PDO $db, int $userId, int $addressId): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if ($userId <= 0 || $addressId <= 0) {
        $response['error'] = 'Invalid request.';
        return $response;
    }

    try {
        $stmt = $db->prepare("DELETE FROM UserAddresses WHERE Id = ? AND UserId = ?");
        $stmt->execute([$addressId, $userId]);

        if ($stmt->rowCount() === 0) {
            $response['error'] = 'Address not found.';
            return $response;
        }

        $response['success'] = true;
    } catch (\PDOException $e) {
        error_log("DB Error in deleteAddress: " . $e->getMessage());
        $response['error'] = "Internal server error while deleting address.";
    }

    return $response;
}

function listAddresses(\PDO $db, int $userId): array
{
    $response = [
        'addresses' => [],
        'error'     => null
    ];

    if ($userId <= 0) {
        return $response;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM UserAddresses WHERE UserId = ? ORDER BY DateAdded DESC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $index => $row) {
            $response['addresses'][] = [
                'address_id' => (int)$row['Id'],
                'label'      => $row['Label'],
                'address'    => $row['Address'],
                'apt'        => $row['Apt'],
                'city'       => $row['City'],
                'state'      => $row['State'],
                'zip'        => $row['ZipCode'],
                'gate_code'  => $row['GateCode'],
                'note'       => $row['Note'],
                'phone'      => $row['Phone'],
                'is_default' => $index === 0
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in listAddresses: " . $e->getMessage());
        $response['error'] = "Internal server error while loading addresses.";
    }

    return $response;
}

function listPaymentMethods(\PDO $db, int $userId): array
{
    $response = [
        'payment_methods' => [],
        'error'           => null
    ];

    if ($userId <= 0) {
        return $response;
    }

    try {
        $stmt = $db->prepare("SELECT StripeCustomerId FROM CustomerPaymentMethod WHERE UserId = ? LIMIT 1");
        $stmt->execute([$userId]);
        $stripeCustomerId = $stmt->fetchColumn();

        // No checkout yet means no Stripe customer, and therefore no cards
        // to list — an empty result here is a legitimate first-time state,
        // not an error.
        if (!$stripeCustomerId) {
            return $response;
        }

        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');
        $cards = \Stripe\PaymentMethod::all(['customer' => $stripeCustomerId, 'type' => 'card']);

        foreach ($cards->data as $card) {
            $response['payment_methods'][] = [
                'id'        => $card->id,
                'brand'     => $card->card->brand,
                'last4'     => $card->card->last4,
                'exp_month' => $card->card->exp_month,
                'exp_year'  => $card->card->exp_year
            ];
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe Error in listPaymentMethods: " . $e->getMessage());
        $response['error'] = "Unable to load payment methods.";
    } catch (\PDOException $e) {
        error_log("DB Error in listPaymentMethods: " . $e->getMessage());
        $response['error'] = "Internal server error while loading payment methods.";
    }

    return $response;
}

// Nothing in the app generates rows here yet (no order-status listener,
// no price-drop watcher) — this is intentionally a real, currently-empty
// feature rather than a stub, per project decision.
function getNotifications(\PDO $db, int $userId): array
{
    $response = [
        'notifications' => [],
        'error'         => null
    ];

    if ($userId <= 0) {
        return $response;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM Notifications WHERE UserId = ? ORDER BY DateAdded DESC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $response['notifications'][] = [
                'id'         => (int)$row['Id'],
                'type'       => $row['Type'],
                'title'      => $row['Title'],
                'body'       => $row['Body'],
                'is_read'    => (bool)$row['IsRead'],
                'date_added' => $row['DateAdded']
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in getNotifications: " . $e->getMessage());
        $response['error'] = "Internal server error while loading notifications.";
    }

    return $response;
}

// Failing to write a notification should never break the flow that
// triggered it (an order, a subscription change) — log and move on.
function createNotification(\PDO $db, int $userId, string $type, string $title, string $body): void
{
    try {
        $stmt = $db->prepare("
            INSERT INTO Notifications (UserId, Type, Title, Body)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $type, $title, $body]);
    } catch (\PDOException $e) {
        error_log("DB Error in createNotification: " . $e->getMessage());
    }
}

function markNotificationRead(\PDO $db, int $userId, int $notificationId): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if ($userId <= 0 || $notificationId <= 0) {
        $response['error'] = "Invalid request.";
        return $response;
    }

    try {
        $stmt = $db->prepare("UPDATE Notifications SET IsRead = 1 WHERE Id = ? AND UserId = ?");
        $stmt->execute([$notificationId, $userId]);
        $response['success'] = true;
    } catch (\PDOException $e) {
        error_log("DB Error in markNotificationRead: " . $e->getMessage());
        $response['error'] = "Internal server error while updating notification.";
    }

    return $response;
}
