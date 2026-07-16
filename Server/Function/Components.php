<?php

include_once __DIR__ . '/Cache.php';

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
                if ($userId > 0) {
                    $stmt = $db->prepare("SELECT 1 FROM OrderSent WHERE UserID = ? AND (OrderStatus = 'Processing' OR OrderStatus = 'Pending') LIMIT 1");
                    $stmt->execute([$userId]);
                    $response['has_active_order'] = $stmt->fetchColumn() !== false;
                } else {
                    $response['has_active_order'] = false;
                }
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
function updateDeviceZip(\PDO $db, string $deviceSignature, string $deviceType, string $zipcode, int $userId): array
{
    $response = [
        'zipcode'               => $zipcode,
        'same_day_eligible'     => false,
        'tax_rate'              => 0.00,
        'city'                  => null,
        'state'                 => null,
        'requires_confirmation' => false,
        'perishable_items'      => [],
        'message'               => null,
        'error'                 => null
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

    // 5. If the new zip isn't eligible, a conflict is only possible if the
    // cart actually holds perishables — check before committing anything.
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

    // 6. No conflict — safe to commit the device's new zip/eligibility now.
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
        'error' => null
    ];

    if ($userId <= 0) {
        return $response; // Not logged in, return default cart icon
    }

    try {
        // check if user has active same-day order
        $stmt = $db->prepare("SELECT 1 FROM Process WHERE UserId = ?");
        $stmt->execute([$userId]);
        $activeSameDayCount = (int)$stmt->fetchColumn();
        $response['has_active_order'] = (bool)$activeSameDayCount;
        $response['icon'] = $activeSameDayCount ? 'icon_process' : 'icon_cart';

        $table = $activeSameDayCount ? 'Process' : 'Cart';
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE UserId = ? AND Quantity > 0");
        $stmt->execute([$userId]);
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
        CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
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

            $stmt = $db->prepare($sql);
            $stmt->execute([$userId, $userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($results) {
                QueryCache::set($cacheKey, $results, 3600); // Cache for 1 hour
            }
        }

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
        $table = $hasActiveOrder ? 'Process' : 'Cart';
        $response['table_source'] = $table;

        $stmt = $db->prepare("INSERT INTO {$table} (UserId, ProductId, Quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE Quantity = Quantity + 1");
        $stmt->execute([$userId, $productId]);

        $stmt = $db->prepare("SELECT Quantity FROM {$table} WHERE UserId = ? AND ProductId = ?");
        $stmt->execute([$userId, $productId]);
        $quantity = (int)$stmt->fetchColumn();

        $response['quantity'] = $quantity;

        $countStmt = $db->prepare("
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
            WHERE CI.UserId = ?
        ");
        $countStmt->execute([$userId]);
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
        $table = $hasActiveOrder ? 'Process' : 'Cart';
        $response['table_source'] = $table;

        $stmt = $db->prepare("UPDATE {$table} SET Quantity = GREATEST(Quantity - 1, 0) WHERE UserId = ? AND ProductId = ?");
        $stmt->execute([$userId, $productId]);

        $stmt = $db->prepare("DELETE FROM {$table} WHERE UserId = ? AND ProductId = ? AND Quantity = 0");
        $stmt->execute([$userId, $productId]);

        $stmt = $db->prepare("SELECT Quantity FROM {$table} WHERE UserId = ? AND ProductId = ?");
        $stmt->execute([$userId, $productId]);
        $quantity = (int)$stmt->fetchColumn();

        $response['quantity'] = $quantity;

        $countStmt = $db->prepare("
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
            WHERE CI.UserId = ?
        ");
        $countStmt->execute([$userId]);
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

    $table = $hasActiveOrder ? 'Process' : 'Cart';

    try {
        $cacheKey = "saved_content:{$userId}";
        $cachedResults = QueryCache::get($cacheKey);

        if ($cachedResults) {
            $results = $cachedResults;
        } else {
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
                   ON b.ProductId = src.ProductId AND b.UserId = ?
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

            $stmt = $db->prepare($sql);
            $stmt->execute([$userId, $userId]);
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
        $table = $hasActiveOrder ? 'Process' : 'Cart';

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
            WHERE CI.UserId = ?
        ");
        $countStmt->execute([$userId]);
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

    if ($table === "Recommendations") {
        $table = "SearchHistory";
    } else if ($table === "RecentlyBought") {
        $table = "ItemBoughtHistory";
    } else {
        $table = "RecentlyViewed";
    }


    try {
        $buildQuery = "
            SELECT
                r.ProductId,
                p.*,
                CASE WHEN s.Id IS NOT NULL THEN 1 ELSE 0 END AS isSaved,
                COALESCE(cart.Quantity, 0)   AS CartQuantity,
                COALESCE(proc.Quantity, 0)   AS ProcessQuantity,
                COALESCE(rv.avg_rating, 0)   AS avg_rating,
                COALESCE(rv.review_count, 0) AS review_count
            FROM {$table} r
            INNER JOIN Products p ON p.Id = r.ProductId
            LEFT JOIN Saved s
                ON s.ProductId = r.ProductId AND s.UserId = ?
            LEFT JOIN Cart cart
                ON cart.ProductId = r.ProductId AND cart.UserId = ?
            LEFT JOIN Process proc
                ON proc.ProductId = r.ProductId AND proc.UserId = ?
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
            ORDER BY r.Id DESC
            LIMIT 16
        ";

        $stmt = $db->prepare($buildQuery);
        $stmt->execute([$userId, $userId, $userId, (int)$isSameDayEligible]);
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
                ON proc.ProductId = rv.ProductId AND proc.UserId = ?
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
        $stmt->execute([$userId, $deviceSignature, $userId, $userId, $userId, (int)$isSameDayEligible]);
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

function searchEngine(\PDO $db, string $searchTerm, bool $isSameDayEligible, float $taxRate): array
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
        $eligibilityFilter = $isSameDayEligible
            ? ""
            : "AND pc.MainCategory NOT IN ('Grocery', 'Frozen', 'Produce', 'Dairy')";

        $query = "
            SELECT p.* FROM Products p
            LEFT JOIN ProductCategories pc ON pc.ProductId = p.Id
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
        $stmt->execute([$inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $response['products'][] = [
                'product_id' => $row['Id'],
                'brand'      => $row['Brand'],
                'name'       => $row['Name'],
                'oz'         => $row['Size'],
                'price'      => (float)round($row['Price'] * (1 + $taxRate), 2),
                'picture'    => $row['Picture'],
                'is_on_sale' => (bool)$row['isOnSale'],
                'sale_price' => (float)round($row['SalePrice'] * (1 + $taxRate), 2),
                'is_bogo'    => (bool)$row['isBogo'],
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

    $table = $hasActiveOrder ? 'Process' : 'Cart';

    $productFilters  = ['Brand', 'Name', 'Size', 'Price', 'isOnSale', 'SalePrice', 'isBogo', 'inStock'];
    $categoryFilters = ['MainCategory', 'SubCategory', 'ThirdCategory', 'ExtCategory'];

    $whereClauses = ["p.inStock = 1"];
    $params       = [$userId, $userId, $userId];

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
                ON proc.ProductId = p.Id AND proc.UserId = ?
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

        $simParams = [$userId, $userId, $userId, $anchor['MainCategory'], $anchor['Brand']];
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
                ON proc.ProductId = p.Id AND proc.UserId = ?
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

    $table = $hasActiveOrder ? 'Process' : 'Cart';

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
                ON proc.ProductId = p.Id AND proc.UserId = ?
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

        $stmt->execute([$userId, $userId, $userId, $productId]);
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

        $response['user'] = [
            'user_id'       => (int)$user['Id'],
            'user_name'     => $user['Name'],
            'user_email'    => $user['Email'],
            'user_phone'    => null,
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

        $response['user'] = [
            'user_id'       => (int)$user['Id'],
            'user_name'     => $user['Name'],
            'user_email'    => $user['Email'],
            'user_phone'    => null,
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
                    'name' => $category['MainCategory']
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
                    'name' => $subcategory['SubCategory']
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
                    'name' => $category['ThirdCategory']
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

function submitCheckout(\PDO $db, int $userId, bool $isSameDay, float $taxRate, float $tipAmount, string $paymentMethodId, array $address): array
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
        $total     = round($subtotal + $taxAmount + $tip, 2);
        $itemCount = array_sum(array_column($cartItems, 'Quantity'));

        // Check for existing Stripe Customer
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

        $stmt = $db->prepare("SELECT StripeCustomerId FROM CustomerPaymentMethod WHERE UserId = ? LIMIT 1");
        $stmt->execute([$userId]);
        $paymentRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($paymentRow) {
            $stripeCustomerId = $paymentRow['StripeCustomerId'];

            // Attach new payment method to existing customer
            \Stripe\PaymentMethod::attach($paymentMethodId, [
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

        // Move cart items to Process (same-day) or OrderTracking (standard
        // shipping) — the two tables track different things per item, so
        // they need different column lists rather than one shared insert.
        if ($isSameDay) {
            $insertStmt = $db->prepare("
                INSERT INTO Process (UserId, ProductId, Quantity, isStocked)
                VALUES (?, ?, ?, 1)
            ");

            foreach ($cartItems as $item) {
                $insertStmt->execute([$userId, $item['ProductId'], $item['Quantity']]);
            }
        } else {
            $insertStmt = $db->prepare("
                INSERT INTO OrderTracking (UserId, ProductId, ItemQuantity, OrderRevenue, OrderLiability)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($cartItems as $item) {
                $itemRevenue = round($item['UnitPrice'] * $item['Quantity'], 2);
                $insertStmt->execute([
                    $userId,
                    $item['ProductId'],
                    $item['Quantity'],
                    $itemRevenue,
                    round($itemRevenue * $taxRate, 2)
                ]);
            }
        }

        // Insert into OrderSent
        $stmt = $db->prepare("
            INSERT INTO OrderSent (
                UserId, ItemQuantity, OrderRevenue, FinalOrderRevenue, OrderLiability,
                TipAmount, isSameDay, isTipped, OrderStatus
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $userId,
            $itemCount,
            $subtotal,
            $total,
            $taxAmount,
            $tip,
            (int)$isSameDay,
            (int)($tip > 0)
        ]);

        $orderId = (int)$db->lastInsertId();

        // Clear cart
        $stmt = $db->prepare("DELETE FROM Cart WHERE UserId = ?");
        $stmt->execute([$userId]);

        $db->commit();

        $response['order_id'] = $orderId;
    } catch (\PDOException $e) {
        $db->rollBack();
        error_log("DB Error in submitCheckout: " . $e->getMessage());
        $response['error'] = "Internal server error during checkout.";
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
                OrderStatus = 'delivered',
                TimeDelivered = NOW()
            WHERE Id = ? AND UserId = ?
        ");
        $stmt->execute([
            (int)($tipAmount > 0),
            $tipAmount,
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

// Order-level summaries only. OrderSent has no foreign key linking it to the
// Process/OrderTracking rows that held its line items at checkout time, so
// there's no reliable way to show which products belonged to which order —
// building that would mean guessing off timestamps, which can silently
// misattribute items for orders placed close together.
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
                TipAmount, OrderStatus, DateAdded, TimeDelivered, isClosed
            FROM OrderSent
            WHERE UserId = ?
            ORDER BY DateAdded DESC, Id DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $response['orders'][] = [
                'order_id'       => (int)$row['Id'],
                'item_count'     => (int)$row['ItemQuantity'],
                'subtotal'       => (float)$row['OrderRevenue'],
                'tax'            => (float)$row['OrderLiability'],
                'tip'            => (float)$row['TipAmount'],
                'total'          => (float)$row['FinalOrderRevenue'],
                'status'         => $row['OrderStatus'],
                'date_added'     => $row['DateAdded'],
                'time_delivered' => $row['TimeDelivered'],
                'is_closed'      => (bool)$row['isClosed']
            ];
        }
    } catch (\PDOException $e) {
        error_log("DB Error in orderHistory: " . $e->getMessage());
        $response['error'] = "Internal server error during order history retrieval.";
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

function verifyCode(\PDO $db, string $userEmail, string $uniqueCode): array
{
    $response = [
        'success' => false,
        'error'   => null
    ];

    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = 'Invalid email format.';
        return $response;
    }

    if (!preg_match('/^\d{6}$/', $uniqueCode)) {
        $response['error'] = 'Invalid code format.';
        return $response;
    }

    try {
        date_default_timezone_set('America/New_York');
        $currentTime = date('Y-m-d H:i:s');

        $stmt = $db->prepare("SELECT UniqueCode, ExpiredAt FROM PasswordResetCodes WHERE Email = ? LIMIT 1");
        $stmt->execute([$userEmail]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $response['error'] = 'No code was sent to this email. Please request a code first.';
            return $response;
        }

        if ($currentTime > $row['ExpiredAt']) {
            $stmt = $db->prepare("DELETE FROM PasswordResetCodes WHERE Email = ?");
            $stmt->execute([$userEmail]);
            $response['error'] = 'The code has expired. Please request a new one.';
            return $response;
        }

        if ($uniqueCode !== $row['UniqueCode']) {
            $response['error'] = 'The code entered was not valid.';
            return $response;
        }

        $response['success'] = true;
    } catch (\PDOException $e) {
        error_log("DB Error in verifyCode: " . $e->getMessage());
        $response['error'] = "Internal server error during code verification.";
    }

    return $response;
}

function updatePassword(\PDO $db, string $userEmail, string $password, string $confirmPassword): array
{
    $response = [
        "error" => null
    ];

    if ($userEmail === "" || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $response['error'] = 'Invalid email format.';
        return $response;
    }
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
