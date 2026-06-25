-- =====================================================
-- PERFORMANCE OPTIMIZATION INDEXES
-- HeyDaniel E-Commerce Platform
-- =====================================================

-- =====================================================
-- PHASE 1: CRITICAL INDEXES (30 minutes)
-- =====================================================

-- Products table - Search and filtering
ALTER TABLE Products ADD INDEX idx_brand (Brand(50));
ALTER TABLE Products ADD INDEX idx_name (Name(100));
ALTER TABLE Products ADD INDEX idx_stock (inStock);

-- ItemReviews - Fast aggregation lookups
ALTER TABLE ItemReviews ADD INDEX idx_product_id (ProductId);
ALTER TABLE ItemReviews ADD INDEX idx_product_stars (ProductId, Stars);

-- Cart - User-specific queries
ALTER TABLE Cart ADD UNIQUE INDEX idx_user_product (UserId, ProductId);
ALTER TABLE Cart ADD INDEX idx_user_id (UserId);
ALTER TABLE Cart ADD INDEX idx_date_added (DateAdded);

-- Saved items - User wishlist queries
ALTER TABLE Saved ADD UNIQUE INDEX idx_user_product (UserId, ProductId);
ALTER TABLE Saved ADD INDEX idx_user_id (UserId);

-- Devices - Device lookup
ALTER TABLE Devices ADD UNIQUE INDEX idx_device_sig (DeviceSignature);
ALTER TABLE Devices ADD INDEX idx_active (isActive);

-- Users - Authentication
ALTER TABLE Users ADD UNIQUE INDEX idx_email (Email);
ALTER TABLE Users ADD INDEX idx_active (isActive);

-- OrderSent - Order tracking
ALTER TABLE OrderSent ADD INDEX idx_user_status (UserID, OrderStatus);
ALTER TABLE OrderSent ADD INDEX idx_date (OrderDate);

-- ZipcodeAllowed - Tax rate lookups
ALTER TABLE ZipcodeAllowed ADD UNIQUE INDEX idx_zipcode (Zipcode);
ALTER TABLE ZipcodeAllowed ADD INDEX idx_eligible (isSameDayEligible);

-- =====================================================
-- PHASE 2: FULLTEXT SEARCH (Optional, improves search by 10x)
-- =====================================================

-- Enable FULLTEXT search on products
ALTER TABLE Products ADD FULLTEXT INDEX idx_fulltext_search (Name, Brand);

-- ProductCategories - Category lookups
ALTER TABLE ProductCategories ADD INDEX idx_product_id (ProductId);
ALTER TABLE ProductCategories ADD INDEX idx_main_category (MainCategory);
ALTER TABLE ProductCategories ADD INDEX idx_sub_category (SubCategory);
ALTER TABLE ProductCategories ADD UNIQUE INDEX idx_product_categories (ProductId, MainCategory, SubCategory, ThirdCategory, Ext_Category);

-- =====================================================
-- PHASE 3: DENORMALIZED TABLES (Eliminates N+1 queries)
-- =====================================================

-- Pre-computed product ratings (prevents GROUP BY on every query)
CREATE TABLE IF NOT EXISTS ProductRatings (
    ProductId INT PRIMARY KEY,
    avg_rating DECIMAL(3, 2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_rating (avg_rating),
    INDEX idx_count (review_count),
    INDEX idx_updated (last_updated),

    FOREIGN KEY (ProductId) REFERENCES Products(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger to automatically update ProductRatings when reviews change
DELIMITER //

DROP TRIGGER IF EXISTS update_product_ratings_on_review_insert //

CREATE TRIGGER update_product_ratings_on_review_insert
AFTER INSERT ON ItemReviews
FOR EACH ROW
BEGIN
    INSERT INTO ProductRatings (ProductId, avg_rating, review_count)
    SELECT
        NEW.ProductId,
        ROUND(AVG(ir.Stars), 2),
        COUNT(*)
    FROM ItemReviews ir
    WHERE ir.ProductId = NEW.ProductId
    ON DUPLICATE KEY UPDATE
        avg_rating = ROUND(AVG(ir.Stars), 2),
        review_count = COUNT(*);
END //

DROP TRIGGER IF EXISTS update_product_ratings_on_review_update //

CREATE TRIGGER update_product_ratings_on_review_update
AFTER UPDATE ON ItemReviews
FOR EACH ROW
BEGIN
    INSERT INTO ProductRatings (ProductId, avg_rating, review_count)
    SELECT
        NEW.ProductId,
        ROUND(AVG(ir.Stars), 2),
        COUNT(*)
    FROM ItemReviews ir
    WHERE ir.ProductId = NEW.ProductId
    ON DUPLICATE KEY UPDATE
        avg_rating = ROUND(AVG(ir.Stars), 2),
        review_count = COUNT(*);
END //

DROP TRIGGER IF EXISTS update_product_ratings_on_review_delete //

CREATE TRIGGER update_product_ratings_on_review_delete
AFTER DELETE ON ItemReviews
FOR EACH ROW
BEGIN
    UPDATE ProductRatings
    SET avg_rating = (SELECT ROUND(AVG(Stars), 2) FROM ItemReviews WHERE ProductId = OLD.ProductId),
        review_count = (SELECT COUNT(*) FROM ItemReviews WHERE ProductId = OLD.ProductId)
    WHERE ProductId = OLD.ProductId;
END //

DELIMITER ;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check index creation status
SELECT
    TABLE_NAME,
    INDEX_NAME,
    SEQ_IN_INDEX,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'heydaniel'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- Check table sizes (for optimization planning)
SELECT
    TABLE_NAME,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_MB',
    TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'heydaniel'
ORDER BY data_length DESC;

-- =====================================================
-- PERFORMANCE MONITORING QUERIES
-- =====================================================

-- Monitor slow queries (requires slow query log enabled)
-- SET GLOBAL slow_query_log = 'ON';
-- SET GLOBAL long_query_time = 0.5;

-- Monitor index usage
SELECT
    object_schema,
    object_name,
    count_read,
    count_insert,
    count_update,
    count_delete
FROM performance_schema.table_io_waits_summary_by_table
WHERE object_schema = 'heydaniel'
ORDER BY count_read DESC;

-- =====================================================
-- COMPRESSION (Optional, saves 50% disk space)
-- =====================================================

-- Enable compression on large tables
-- ALTER TABLE Products COMPRESSION='ZSTD';
-- ALTER TABLE ItemReviews COMPRESSION='ZSTD';
-- ALTER TABLE Cart COMPRESSION='ZSTD';
