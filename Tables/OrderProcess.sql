CREATE TABLE Process (
  Id             INT(11)    AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId         INT(11)    NOT NULL,
  ProductId      INT(11)    NOT NULL,
  OrderId        INT(11)    NULL,
  Quantity       INT(11)    NOT NULL,
  QuantityFound  INT(11)    NULL,
  isStocked      TINYINT(1) NOT NULL DEFAULT 1,
  DateAdded      DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  isMissing TINYINT(1) NOT NULL DEFAULT 0,
  HandlerId      INT(11)    NOT NULL DEFAULT 0,
  isClosed       TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (UserId) REFERENCES Users(Id),
  FOREIGN KEY (ProductId) REFERENCES Products(Id),
  FOREIGN KEY (OrderId) REFERENCES OrderSent(Id),
  UNIQUE KEY idx_user_product_order (UserId, ProductId, OrderId),
  INDEX idx_user_id (UserId),
  INDEX idx_product_id (ProductId),
  INDEX idx_order_id (OrderId),
  INDEX idx_date_added (DateAdded)
);

