CREATE TABLE CustomerPaymentMethod (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId LONGTEXT NOT NULL,
  PaymentMethod LONGTEXT NOT NULL,
  StripeCustomerId LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL,
  TimeAdded LONGTEXT NOT NULL
);

-- self-explanatory, tracks the payment methods that customers have added to their accounts. This can be used for processing payments, managing customer preferences, and providing a seamless checkout experience. The StripeCustomerId can be used to link the payment method to a Stripe customer for payment processing.

CREATE TABLE CustomerPaymentMethod (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  PaymentMethod VARCHAR(50) NOT NULL,
  StripeCustomerId VARCHAR(255) NOT NULL,
  DateAdded DATE NOT NULL,
  TimeAdded TIME NOT NULL
);

CREATE TABLE CustomerPaymentMethod (
  Id                    INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId                INT(11) NOT NULL,
  PaymentMethod         VARCHAR(50) NOT NULL,
  StripeCustomerId      VARCHAR(255) NOT NULL,
  StripePaymentIntentId VARCHAR(255) NULL,
  DateAdded             DATE NOT NULL,
  TimeAdded             TIME NOT NULL
);