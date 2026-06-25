CREATE TABLE CustomerPaymentMethod (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  PaymentMethod VARCHAR(50) NOT NULL,
  StripeCustomerId VARCHAR(255) NOT NULL,
  StripePaymentIntentId VARCHAR(255) NULL,
  DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE CASCADE,
  INDEX idx_user_id (UserId),
  INDEX idx_stripe_customer_id (StripeCustomerId)
);