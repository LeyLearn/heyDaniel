CREATE TABLE ItemBoughtHistory (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  ProductId INT(11) NOT NULL,
  Quantity INT(11) NOT NULL,
  DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE CASCADE,
  FOREIGN KEY (ProductId) REFERENCES Products(Id) ON DELETE CASCADE,
  INDEX idx_user_id (UserId),
  INDEX idx_product_id (ProductId),
  INDEX idx_date_added (DateAdded)
);