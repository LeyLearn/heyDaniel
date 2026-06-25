<?php

include_once __DIR__ . '/Cache.php';

function isSameDayEligible(\PDO $db, string $deviceSignature, int $userId): array
{
    $response = [
        'is_device_known'   => false,
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
                $stmt = $db->prepare("SELECT TaxRate FROM ZipcodeAllowed WHERE Zipcode = ? LIMIT 1");
                $stmt->execute([$zipcode]);
                $response['tax_rate'] = (float)($stmt->fetchColumn() ?? 0.00);
            }
            $response['is_device_known']   = true;
            $response['same_day_eligible'] = (bool)$row['isSameDayEligible'];

            if ($response['same_day_eligible']) {
                if ($userId > 0) {
                    $stmt = $db->prepare("SELECT 1 FROM OrderSent WHERE UserID = ? AND OrderStatus = 'Processing' LIMIT 1");
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

function DeviceLog(\PDO $db, string $deviceSignature, string $deviceType, string $zipcode): array
{
    $response = [
        'same_day_eligible' => false,
        'tax_rate'          => 0.00,
        'message'           => null,
        'error'             => null
    ];

    // SECURITY: Input validation (Vulnerability #11, #15, #19)
    // Validate device signature format (should be SHA256 hash)
    if (
        $deviceSignature === ""
        || strlen($deviceSignature) !== 64
        || !preg_match('/^[a-f0-9]{64}$/', $deviceSignature)
    ) {
        $response['error'] = "Invalid device signature format.";
        return $response;
    }

    // Validate device type (SQL injection prevention for Vulnerability #15)
    $validDeviceTypes = ['iOS', 'Android', 'Web'];
    if ($deviceType === "" || !in_array($deviceType, $validDeviceTypes, true)) {
        $response['message'] = "Invalid device type.";
        return $response;
    }

    // Validate zipcode
    if (
        $zipcode === ""
        || strlen($zipcode) > 16
        || !preg_match('/^[a-zA-Z0-9\- ]+$/', $zipcode)
    ) {
        $response['message'] = "Invalid zipcode format.";
        return $response;
    }

    $columnMap = [
        'iOS'     => 'AppleDevice',
        'Android' => 'AndroidDevice',
        'Web'     => 'WebDevice'
    ];

    $isActive       = true;
    $dateRegistered = date("Y-m-d H:i:s");

    try {
        $cacheKey = "zipcode:{$zipcode}";
        $cachedZipcode = QueryCache::get($cacheKey);

        if ($cachedZipcode) {
            $row = $cachedZipcode;
        } else {
            $stmt = $db->prepare("SELECT isSameDayEligible, TaxRate FROM ZipcodeAllowed WHERE Zipcode = ? LIMIT 1");
            $stmt->execute([$zipcode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                QueryCache::set($cacheKey, $row, 86400); // Cache for 24 hours
            }
        }

        if ($row) {
            $response['same_day_eligible'] = (bool)$row['isSameDayEligible'];
            $response['tax_rate']          = (float)($row['TaxRate'] ?? 0.10);
        } else {
            $config = parse_ini_file('/path/to/.env');
            $apiKey = $config['ZIPTAX_KEY'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.zip.tax/v1/rates?key={$apiKey}&zip={$zipcode}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $taxInformation = curl_exec($ch);
            curl_close($ch);

            if ($taxInformation === false) {
                $response['tax_rate'] = 0.10;
                $stmt = $db->prepare("INSERT INTO ZipcodeAllowed (Zipcode, isSameDayEligible, TaxRate) VALUES (?, ?, ?)");
                $stmt->execute([$zipcode, false, $response['tax_rate']]);
            } else {
                $data   = json_decode($taxInformation, true);
                $result = $data['results'][0] ?? null;

                if ($result) {
                    $response['tax_rate'] = (float)($result['taxSales'] ?? 0.10);
                } else {
                    $response['tax_rate'] = 0.10;
                }

                $stmt = $db->prepare("INSERT INTO ZipcodeAllowed (Zipcode, isSameDayEligible, TaxRate) VALUES (?, ?, ?)");
                $stmt->execute([$zipcode, false, $response['tax_rate']]);
            }
        }

        $stmt = $db->prepare("INSERT INTO Devices (DeviceSignature, DeviceType, Zipcode, isSameDayEligible, isActive, DateAdded) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$deviceSignature, $columnMap[$deviceType], $zipcode, $response['same_day_eligible'], $isActive, $dateRegistered]);
    } catch (\PDOException $e) {
        error_log("DB Error in DeviceLog: " . $e->getMessage());
        $response['error'] = "Internal server error during device logging.";
    }

    return $response;
}

function cartIcon(\PDO $db, int $userId, bool $isSameDayEligible): array
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

    if (!$isSameDayEligible) {
        return $response; // Not eligible for same-day, return default cart icon
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
                'oz'           => $row['Oz'],
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
        $response['wishlist_count'] = (int)$stmt->fetchColumn();
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
        'error'       => null
    ];

    if ($userId <= 0) {
        return $response; // Not logged in, return default response
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
                'oz'           => $row['Oz'],
                'price'        => (float)round($row['Price'] * $taxRate, 2),
                'picture'      => $row['Picture'],
                'is_on_sale'   => (bool)$row['isOnSale'],
                'sale_price'   => (float)round($row['SalePrice'] * $taxRate, 2),
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
        'error'    => null
    ];

    $allowedTables = ['Recommendations', 'RecentlyBought', 'Popular'];

    if (!in_array($table, $allowedTables, true)) {
        $response['error'] = "Invalid table source.";
        return $response;
    }

    // $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    try {
        $buildQuery = "
            SELECT
                r.ProductId,
                p.*,
                COALESCE(s.isSaved, 0)       AS isSaved,
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
                'oz'           => $item['Oz'],
                'price'        => (float)round($item['Price'] * $taxRate, 2),
                'picture'      => $item['Picture'],
                'is_on_sale'   => (bool)$item['isOnSale'],
                'sale_price'   => (float)round($item['SalePrice'] * $taxRate, 2),
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
                pc.Ext_Category   LIKE ? OR
                p.Name            LIKE ? OR
                p.Oz              LIKE ?
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
                'oz'         => $row['Oz'],
                'price'      => (float)round($row['Price'] * $taxRate, 2),
                'picture'    => $row['Picture'],
                'is_on_sale' => (bool)$row['isOnSale'],
                'sale_price' => (float)round($row['SalePrice'] * $taxRate, 2),
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

    $productFilters  = ['Brand', 'Name', 'Oz', 'Price', 'isOnSale', 'SalePrice', 'isBogo', 'inStock'];
    $categoryFilters = ['MainCategory', 'SubCategory', 'ThirdCategory', 'Ext_Category'];

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
                pc.Ext_Category,
                COALESCE(s.isSaved, 0)       AS isSaved,
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
                'oz'             => $product['Oz'],
                'price'          => (float)round($product['Price'] * $taxRate, 2),
                'picture'        => $product['Picture'],
                'description'    => $product['Description'],
                'is_on_sale'     => (bool)$product['isOnSale'],
                'sale_price'     => (float)round($product['SalePrice'] * $taxRate, 2),
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
                'ext_category'   => $product['Ext_Category'],
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
                pc.Ext_Category,
                COALESCE(s.isSaved, 0)      AS isSaved,
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
                'oz'             => $similar['Oz'],
                'price'          => (float)round($similar['Price'] * $taxRate, 2),
                'picture'        => $similar['Picture'],
                'description'    => $similar['Description'],
                'is_on_sale'     => (bool)$similar['isOnSale'],
                'sale_price'     => (float)round($similar['SalePrice'] * $taxRate, 2),
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
                'ext_category'   => $similar['Ext_Category'],
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
                pc.Ext_Category,
                COALESCE(s.isSaved, 0)      AS isSaved,
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
            'oz'             => $product['Oz'],
            'price'          => (float)round($product['Price'] * $taxRate, 2),
            'picture'        => $product['Picture'],
            'description'    => $product['Description'],
            'is_on_sale'     => (bool)$product['isOnSale'],
            'sale_price'     => (float)round($product['SalePrice'] * $taxRate, 2),
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
            'ext_category'   => $product['Ext_Category'],
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

function registerUser(\PDO $db, string $userName, string $email, string $password): array
{
    $response = ['error' => null];

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT 1 FROM Users WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $db->rollBack();
            $response['error'] = "Registration failed.";
            return $response;
        }

        $stmt = $db->prepare("
            INSERT INTO Users (Name, Email, Password, Phone, Credits, isMember, isActive, TimeRegister) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userName,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            null,
            0.00,
            (int) false,
            (int) true
        ]);

        $db->commit();
    } catch (\PDOException $e) {
        $db->rollBack();
        error_log("DB Error in registerUser: " . $e->getMessage());
        $response['error'] = "Internal server error during registration.";
    }

    return $response;
}

function loginUser(\PDO $db, string $email, string $password): array
{
    $response = [
        'user'  => [],
        'error' => null
    ];

    try {
        $stmt = $db->prepare("SELECT Id, Name, Email, Phone, Password, Credits, isMember, isActive, TimeRegister FROM Users WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // SECURITY: Timing attack prevention (Vulnerability #6)
        // Always perform password verification to prevent user enumeration
        $dummyHash = '$2y$10$invalid.dummy.hash.for.timing.attack.prevention.';
        $passwordCorrect = $user && password_verify($password, $user['Password']);

        // If user not found, verify against dummy hash to consume time
        if (!$user) {
            password_verify($password, $dummyHash);
            $response['error'] = "Invalid email or password.";
            return $response;
        }

        // Check password
        if (!$passwordCorrect) {
            $response['error'] = "Invalid email or password.";
            return $response;
        }

        if (!(bool)$user['isActive']) {
            $response['error'] = "Account is deactivated.";
            return $response;
        }

        $response['user'] = [
            'user_id'       => (int)$user['Id'],
            'user_name'     => $user['Name'],
            'user_email'    => $user['Email'],
            'user_phone'    => $user['Phone'],
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

    // Validate address
    $requiredAddressFields = ['Address', 'City', 'State', 'ZipCode', 'Phone'];
    foreach ($requiredAddressFields as $field) {
        if (empty($address[$field])) {
            $response['error'] = "Missing address field: {$field}.";
            return $response;
        }
    }

    try {
        $db->beginTransaction();

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
            $db->rollBack();
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
        \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

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

            $date = date('Y-m-d');
            $time = date('H:i:s');

            // Save to CustomerPaymentMethod
            $stmt = $db->prepare("
                INSERT INTO CustomerPaymentMethod (UserId, PaymentMethod, StripeCustomerId, DateAdded, TimeAdded)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $paymentMethodId, $stripeCustomerId, $date, $time]);
        }

        // Create Payment Intent with manual capture
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount'         => (int)round($total * 100),
            'currency'       => 'usd',
            'customer'       => $stripeCustomerId,
            'payment_method' => $paymentMethodId,
            'capture_method' => 'manual',
            'confirm'        => true,
            'description'    => "HeyDaniel, LLC order - UserId: {$userId}",
        ]);

        // Store Payment Intent ID in CustomerPaymentMethod
        $stmt = $db->prepare("
            UPDATE CustomerPaymentMethod 
            SET StripePaymentIntentId = ? 
            WHERE UserId = ?
        ");
        $stmt->execute([$paymentIntent->id, $userId]);

        $date = date('Y-m-d');
        $time = date('H:i:s');

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
            $address['LatnLong'],
            $address['GateCode'] ?? '',
            $address['Note']     ?? '',
            $address['Phone']
        ]);

        // Move cart items to Process or orderTracking
        $destinationTable = $isSameDay ? 'Process' : 'orderTracking';

        $insertStmt = $db->prepare("
            INSERT INTO {$destinationTable} (UserId, ProductId, Quantity, isStocked, DateAdded, TimeAdded)
            VALUES (?, ?, ?, 1, ?, ?)
        ");

        foreach ($cartItems as $item) {
            $insertStmt->execute([
                $userId,
                $item['ProductId'],
                $item['Quantity'],
                $date,
                $time
            ]);
        }

        // Insert into OrderSent
        $stmt = $db->prepare("
            INSERT INTO OrderSent (
                UserId, ItemQuantity, OrderRevenue, FinalOrderRevenue, OrderLiability,
                TipAmount, isSameDay, isTipped, OrderStatus, DateAdded, TimeAdded
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $itemCount,
            $subtotal,
            $total,
            $taxAmount,
            $tip,
            (int)$isSameDay,
            (int)($tip > 0),
            $date,
            $time
        ]);

        $orderId = (int)$db->lastInsertId();

        // Clear cart
        $stmt = $db->prepare("DELETE FROM Cart WHERE UserId = ?");
        $stmt->execute([$userId]);

        $db->commit();

        $response['order_id'] = $orderId;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $db->rollBack();
        error_log("Stripe Error in submitCheckout: " . $e->getMessage());
        $response['error'] = "Payment processing failed.";
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

    try {
        \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

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
                'amount'         => (int)round($tipAmount * 100),
                'currency'       => 'usd',
                'customer'       => $paymentRow['StripeCustomerId'],
                'payment_method' => $paymentRow['PaymentMethod'],
                'off_session'    => true,
                'confirm'        => true,
                'description'    => "HeyDaniel, LLC tip - OrderId: {$orderId}",
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
                TimeDelivered = ?
            WHERE Id = ? AND UserId = ?
        ");
        $stmt->execute([
            (int)($tipAmount > 0),
            $tipAmount,
            date('H:i:s'),
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

    try{
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

    }
    catch (\PDOException $e) {
        error_log("DB Error in updating passoword: " . $e->getMessage());
        $response['error'] = "Internal server error during password update.";
    }

    return $response;
}
