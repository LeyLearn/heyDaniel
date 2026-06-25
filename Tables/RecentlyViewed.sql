CREATE TABLE RecentlyViewed (
  Id              INT(11)      AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId          INT(11)      NOT NULL,
  ProductId       INT(11)      NOT NULL,
  DeviceSignature VARCHAR(255) NOT NULL,
  DateViewed      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (UserId),
  INDEX idx_product_id (ProductId),
  INDEX idx_date_viewed (DateViewed)
);
