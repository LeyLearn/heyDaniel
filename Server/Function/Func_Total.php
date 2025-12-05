<?php


// authenticate user by device signature
function resolveDevice(\PDO $db, string $signature, string $tableName, int $userId): array
{
    // Default response (worst case)
    $response = [
        'zipcode'           => null,
        'same_day_eligible' => false,
        'error'             => null
    ];
    $validInboundTables = ['RecentlyViewed', 'Recommendations', 'SearchHistory']; // Define your allowed set
    if (!in_array($tableName, $validInboundTables, true)) {
        // Halt execution immediately on invalid input
        $response['error'] = "Invalid data source table specified.";
        return $response;
    }

    $tableMap = [
        'RecentlyViewed'   => 'RecentlyViewed',
        'Recommendations'  => 'Recommendations',
        'SearchHistory'    => 'SearchHistory'
    ];
    $mappedTable = $tableMap[$tableName] ?? null;

    if (!$mappedTable) {
        $response['error'] = "Invalid data source.";
        return $response;
    }

    // 2. Fallback to Devices table
    $stmt = $db->prepare("
        SELECT Zipcodes AS zipcode
        FROM Devices
        WHERE DeviceSignature = ?
        LIMIT 1
    ");
    $stmt->execute([$signature]);

    if ($row = $stmt->fetch()) {
        $response['zipcode']   = $row['zipcode'];
    } else {
        $stmt = $db->prepare("
    SELECT id, Zipcode AS zipcode
    FROM Users
    WHERE WebDevice = ? OR AppleDevice = ? OR AndroidDevice = ?
    LIMIT 1 ");


        $stmt->execute([$signature, $signature, $signature]);
        if ($row = $stmt->fetch()) {
            $response['zipcode'] = $row['zipcode'];
        } else {
            // No device or user found
            $response['error'] = "Device not recognized.";
            return $response;
        }
    }
    // 3. If we have a zipcode → check eligibility
    if ($response['zipcode'] !== null) {
        $stmt = $db->prepare("
        SELECT 1 FROM AllowedZip WHERE Zipcode = ? LIMIT 1
    ");
        $stmt->execute([$response['zipcode']]);

        if ($stmt->fetch()) {
            $response['same_day_eligible'] = true;
        }
    } else {
        // No zipcode found
        $response['error'] = "Zipcode not found for device.";
        return $response;
    }

    $isSameDayEligible = $response['same_day_eligible'];
    $hasActiveSameDayOrder = false;

    if ($isSameDayEligible) {
        $stmt = $db->prepare("
        SELECT 1 FROM OrderSent 
        WHERE UserId = ? AND OrderStatus = 'Processing' AND isSameDay = 1 
        LIMIT 1
    ");
        $stmt->execute([$userId]);
        $hasActiveSameDayOrder = $stmt->fetchColumn() !== false;
    }

    $validTables = ['Process', 'Cart'];
    $sourceTable = ($isSameDayEligible && $hasActiveSameDayOrder) ? 'Process' : 'Cart';
    if (!in_array($sourceTable, $validTables, true)) {
        // Invalid table scenario
        $response['error'] = "Invalid source table.";
        return $response;
    }

    if ($sourceTable === 'Process') {
        $sql = "
    SELECT 
        iv.ProductId,
        p.*,
        COALESCE(s.isSaved, 0)      AS isSaved,
        COALESCE(src.Quantity, 0)   AS ItemQuantity,
        COALESCE(r.avg_rating, 0)   AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
    FROM `$mappedTable` iv
    CROSS JOIN Products p ON iv.ProductId = p.Id
    LEFT JOIN Saved s 
           ON s.ProductId = iv.ProductId AND s.UserId = ?
    LEFT JOIN Process src 
           ON src.ProductId = iv.ProductId AND src.UserId = ?
    LEFT JOIN (
        SELECT 
            ProductId,
            ROUND(AVG(Stars), 2)   AS avg_rating,
            COUNT(*)               AS review_count
        FROM ItemReviews 
        GROUP BY ProductId
    ) r ON r.ProductId = iv.ProductId
    WHERE (p.Pr_Category != 'Grocery' OR ? = 1)
    ORDER BY iv.Id DESC
    LIMIT 16 ";
    } else {
        $sql = "
   SELECT 
        iv.ProductId,
        p.*,
        COALESCE(s.isSaved, 0)      AS isSaved,
        COALESCE(src.Quantity, 0)   AS ItemQuantity,
        COALESCE(r.avg_rating, 0)   AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
    FROM `$mappedTable` iv
    CROSS JOIN Products p ON iv.ProductId = p.Id
    LEFT JOIN Saved s 
           ON s.ProductId = iv.ProductId AND s.UserId = ?
    LEFT JOIN Cart src 
           ON src.ProductId = iv.ProductId AND src.UserId = ?
    LEFT JOIN (
        SELECT 
            ProductId,
            ROUND(AVG(Stars), 2)   AS avg_rating,
            COUNT(*)               AS review_count
        FROM ItemReviews 
        GROUP BY ProductId
    ) r ON r.ProductId = iv.ProductId
    WHERE (p.Pr_Category != 'Grocery' OR ? = 1)
    ORDER BY iv.Id DESC
    LIMIT 16 ";
    }
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $userId, $isSameDayEligible]);
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
    } catch (\PDOException $e) {
        error_log("DB Error in resolveDevice: " . $e->getMessage());
        // Return a generic error status, the caller will set HTTP 500
        $response['error'] = "Internal server error during data retrieval.";
    }

    return $productData;
}

// authenticate user  by userID
function authenticateUser(\PDO $db, int $userId): array
{
    // default response
    $response = [
        'isLoggedIn' => false,
        'isZipcodeAllowed' => false,
        'hasProcessingSameDayOrder' => false,
        'tableSource' => null,
        'error' => null
    ];

    if ($userId <= 0) {
        $response['error'] = "Authentication failed. Please log in.";
        return $response;
    }

    $response['isLoggedIn'] = true;

    try {
        // Start transaction for read consistency across multiple SELECTs
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT Zipcodes FROM Users WHERE Id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userZipcodes = $user ? $user['Zipcodes'] : null;

        if ($userZipcodes === null || $userZipcodes === '') {
            $response['error'] = "Zipcode not set for user.";
            $db->rollBack();
            return $response;
        }
        $stmt = $db->prepare("SELECT 1 FROM ZipcodeAllowed WHERE Zipcode = ?");
        $stmt->execute([$userZipcodes]);
        $isSameDayEligible = $stmt->fetchColumn() !== false;
        $response['isZipcodeAllowed'] = $isSameDayEligible;


        if ($isSameDayEligible) {
            $stmt = $db->prepare(" SELECT 1 FROM OrderSent WHERE UserId = ? AND OrderStatus = 'Processing' AND isSameDay = 1 LIMIT 1 ");
            $stmt->execute([$userId]);
            $hasProcessingSameDayOrder = $stmt->fetchColumn() !== false;
            $response['hasProcessingSameDayOrder'] = $hasProcessingSameDayOrder;
        }
        $validTable = ['Process', 'Cart'];
        $sourceTable = ($isSameDayEligible && $response['hasProcessingSameDayOrder']) ? 'Process' : 'Cart';
        if (!in_array($sourceTable, $validTable, true)) {
            $response['error'] = "Invalid source table.";
            return $response;
        }
        $response['tableSource'] = $sourceTable;
        $db->commit(); // Commit if all SELECTs succeeded

    } catch (\PDOException $e) {
        $db->rollBack(); // Rollback on any database failure
        error_log("Authentication DB Error: " . $e->getMessage());
        // Return a generic error status, the caller will set HTTP 500
        $response['error'] = "Internal server error during authorization checks.";
    }

    return $response;
}

// array intake authenticator

