CREATE TABLE Process (
  Id             INT(11)    AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId         INT(11)    NOT NULL,
  ProductId      INT(11)    NOT NULL,
  Quantity       INT(11)    NOT NULL,
  isStocked      TINYINT(1) NOT NULL DEFAULT 1,
  DateAdded      DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NeverDelivered TINYINT(1) NOT NULL DEFAULT 0,
  HandlerId      INT(11)    NOT NULL DEFAULT 0,
  isClosed       TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_user_id (UserId),
  INDEX idx_product_id (ProductId),
  INDEX idx_date_added (DateAdded)
);
