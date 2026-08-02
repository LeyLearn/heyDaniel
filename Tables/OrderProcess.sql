-- Line items for EVERY order, same-day or standard (OrderTracking used to
-- hold standard orders' items separately but was dropped) - plus permanent
-- buy-again history and the "Recently Bought" slider source. Rows persist
-- forever, so every read/write must be scoped to OrderId, never just
-- UserId+ProductId (see getActiveSameDayOrderId()'s comment).
-- QuantityFound: NULL = not yet picked, 0 = out of stock, full = found,
-- in between = partial (see processContent()). setItemPickStatus()
-- (Components.php) is THE write path - it also keeps isStocked in step,
-- stamps HandlerId, notifies the customer on every status transition
-- (found/partial/out_of_stock), and pings the WS daemon. The shopper
-- dashboard must call it rather than updating these columns directly;
-- nothing calls it yet since that dashboard isn't built.
-- ClaimStatus: 'none' (default), or a customer's post-delivery claim on
-- this item - 'missing', 'expired', 'bad_quality'. Read by orderDetails()/
-- the order-history badge; no claim-filing flow exists yet (dashboard).
CREATE TABLE Process (
  Id             INT(11)     AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId         INT(11)     NOT NULL,
  ProductId      INT(11)     NOT NULL,
  OrderId        INT(11)     NULL,
  Quantity       INT(11)     NOT NULL,
  QuantityFound  INT(11)     NULL,
  isStocked      TINYINT(1)  NOT NULL DEFAULT 1,
  DateAdded      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ClaimStatus    VARCHAR(20) NOT NULL DEFAULT 'none',
  HandlerId      INT(11)     NOT NULL DEFAULT 0,
  FOREIGN KEY (UserId) REFERENCES Users(Id),
  FOREIGN KEY (ProductId) REFERENCES Products(Id),
  FOREIGN KEY (OrderId) REFERENCES OrderSent(Id),
  UNIQUE KEY idx_user_product_order (UserId, ProductId, OrderId),
  INDEX idx_user_id (UserId),
  INDEX idx_product_id (ProductId),
  INDEX idx_order_id (OrderId),
  INDEX idx_date_added (DateAdded)
);

