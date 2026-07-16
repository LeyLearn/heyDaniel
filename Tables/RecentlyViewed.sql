CREATE TABLE RecentlyViewed (
  Id              INT(11)      AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId          INT(11)      NOT NULL,
  ProductId       INT(11)      NOT NULL,
  DeviceSignature VARCHAR(255) NOT NULL,
  DateViewed      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE CASCADE,
  FOREIGN KEY (ProductId) REFERENCES Products(Id) ON DELETE CASCADE,
  INDEX idx_user_id (UserId),
  INDEX idx_product_id (ProductId),
  INDEX idx_date_viewed (DateViewed)
);

-- Sample recently-viewed rows for the sample user (see Users.sql) and two
-- of the sample products (see Product.sql). Looked up by name/email rather
-- than session variables so this runs fine even in a separate session,
-- as long as Users.sql and Product.sql have already been run.
INSERT INTO RecentlyViewed (UserId, ProductId, DeviceSignature) VALUES
(
    (SELECT Id FROM Users WHERE Email = 'leydave01@gmail.com'),
    (SELECT Id FROM Products WHERE Brand = 'Apple' AND Name = 'iPhone 15 Pro, 128GB'),
    '072ccfde8726f9b26e05340319274f85bf26e00edac01899d193d6c1bbd46f79'
),
(
    (SELECT Id FROM Users WHERE Email = 'leydave01@gmail.com'),
    (SELECT Id FROM Products WHERE Brand = 'Windex' AND Name = 'Glass Cleaner, 23oz'),
    '072ccfde8726f9b26e05340319274f85bf26e00edac01899d193d6c1bbd46f79'
);
