CREATE TABLE Products (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  Brand VARCHAR(255) NOT NULL,
  Name VARCHAR(500) NOT NULL,
  SKU VARCHAR(100) NOT NULL DEFAULT '',
  VendorId INT(11) NOT NULL DEFAULT 0,
  Size TEXT NOT NULL,
  Price DECIMAL(10,2) NOT NULL,
  isOnSale TINYINT(1) NOT NULL DEFAULT 0,
  SalePrice DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  isBogo TINYINT(1) NOT NULL DEFAULT 0,
  isFeatured TINYINT(1) NOT NULL DEFAULT 0,
  inStock TINYINT(1) NOT NULL DEFAULT 1,
  Picture TEXT NOT NULL,
  Description LONGTEXT NOT NULL,
  DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_brand (Brand),
  INDEX idx_name (Name)
);

CREATE TABLE ProductCategories (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  ProductId INT(11) NOT NULL,
  MainCategory VARCHAR(255) NOT NULL,
  SubCategory VARCHAR(255) NOT NULL,
  ThirdCategory VARCHAR(255) NOT NULL,
  ExtCategory VARCHAR(255) NOT NULL,
  FOREIGN KEY (ProductId) REFERENCES Products(Id) ON DELETE CASCADE,
  INDEX idx_main_category (MainCategory),
  INDEX idx_sub_category (SubCategory),
  INDEX idx_third_category (ThirdCategory),
  INDEX idx_ext_category (ExtCategory)
);

-- Sample products
INSERT INTO Products (Brand, Name, Size, Price, isOnSale, SalePrice, isBogo, inStock, Picture, Description) VALUES
('Apple', 'iPhone 15 Pro, 128GB', 0.128, 999.00, 0, 0.00, 0, 1, '/HeyDaniel/Assets/Build/iphone.png', 'The ultimate iPhone with a titanium design, A17 Pro chip, and a customizable Action button.'),
('Windex', 'Glass Cleaner, 23oz', 23.000, 4.29, 0, 0.00, 1, 1, '/HeyDaniel/Assets/Build/Windex.png', 'Streak-free shine on glass and multiple surfaces. Buy one, get one free.'),
('Gillette Venus', 'Comfortglide Razor, 2ct', 2.000, 9.99, 0, 0.00, 0, 1, '/HeyDaniel/Assets/Build/razor.png', 'Five-blade razor with a built-in shave gel bar for a smooth, comfortable shave.'),
('Insignia', 'Countertop Microwave, 0.9 cu ft', 0.900, 69.99, 0, 0.00, 0, 1, '/HeyDaniel/Assets/Build/Microwave.png', 'Compact 900-watt microwave with 10 power levels, perfect for small kitchens.'),
('Birds Eye', 'Steamfresh Cheesy Broccoli', 10.800, 3.49, 1, 2.79, 0, 1, '/HeyDaniel/Assets/Build/Frozen.png', 'Tender broccoli florets in a creamy cheese sauce, steams right in the bag.'),
('Fresh Farms', 'Organic Parsley Bunch', 1.000, 1.99, 0, 0.00, 0, 1, '/HeyDaniel/Assets/Build/parsley.png', 'Fresh, organically grown parsley bunch, picked and packed the same day.');

-- MySQL note: for a multi-row INSERT, LAST_INSERT_ID() returns the id of
-- the FIRST row inserted above, so the rest follow by simple offset.
SET @p1 = LAST_INSERT_ID();
SET @p2 = @p1 + 1;
SET @p3 = @p1 + 2;
SET @p4 = @p1 + 3;
SET @p5 = @p1 + 4;
SET @p6 = @p1 + 5;

-- Sample product categories (one per product above)
INSERT INTO ProductCategories (ProductId, MainCategory, SubCategory, ThirdCategory, ExtCategory) VALUES
(@p1, 'Electronics', 'Cell Phones', 'Smartphones', 'Apple'),
(@p2, 'Household', 'Cleaning Supplies', 'Glass & Surface Cleaners', 'Windex'),
(@p3, 'Beauty & Personal Care', 'Shaving', 'Razors', 'Gillette'),
(@p4, 'Electronics', 'Kitchen Appliances', 'Microwaves', 'Insignia'),
(@p5, 'Grocery', 'Frozen', 'Frozen Vegetables', 'Birds Eye'),
(@p6, 'Grocery', 'Produce', 'Fresh Herbs', 'Fresh Farms');
