CREATE TABLE Cart (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  ProductId INT(11) NOT NULL,
  Quantity INT(11) NOT NULL DEFAULT 1,
  DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_product (UserId, ProductId),
  INDEX idx_user_id (UserId),
  INDEX idx_product_id (ProductId)
);