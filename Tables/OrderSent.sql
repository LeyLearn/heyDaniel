CREATE TABLE OrderSent (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId LONGTEXT NOT NULL,
  ItemQuantity LONGTEXT NOT NULL,
  OrderRevenue LONGTEXT NOT NULL,
  FinalOrderRevenue LONGTEXT NOT NULL,
  OrderLiability LONGTEXT NOT NULL,
  HandlerId LONGTEXT NOT NULL,
  OrderStatus LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL,
  TimeAdded LONGTEXT NOT NULL,
  TimeDelivered LONGTEXT NOT NULL,
  isRated LONGTEXT NOT NULL,
  isTipped LONGTEXT NOT NULL,
  isSameDay LONGTEXT NOT NULL,
  isClosed LONGTEXT NOT NULL
);

CREATE TABLE Process (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId LONGTEXT NOT NULL,
  ProductId LONGTEXT NOT NULL,
  Quantity LONGTEXT NOT NULL,
  isStocked LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL,
  TimeAdded LONGTEXT NOT NULL,
  isMissing LONGTEXT NOT NULL,
  HandlerId LONGTEXT NOT NULL
);

CREATE TABLE orderTracking (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId LONGTEXT NOT NULL,
  ProductId LONGTEXT NOT NULL,
  Quantity LONGTEXT NOT NULL,
  isStocked LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL,
  TimeAdded LONGTEXT NOT NULL,
  isMissing LONGTEXT NOT NULL,
  TrackingStatus LONGTEXT NOT NULL
);

-- self-explanatory, tracks when an order is sent. can be used for analytics, notifications, etc.
CREATE TABLE OrderSent (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  ItemQuantity INT(11) NOT NULL,
  OrderRevenue DECIMAL(10,2) NOT NULL,
  FinalOrderRevenue DECIMAL(10,2) NOT NULL,
  OrderLiability DECIMAL(10,2) NOT NULL,
  HandlerId INT(11) NOT NULL,
  OrderStatus TINYINT(1) NOT NULL DEFAULT 0,
  DateAdded DATE NOT NULL,
  TimeAdded TIME NOT NULL,
  TimeDelivered TIME NULL,
  isRated TINYINT(1) NOT NULL DEFAULT 0,
  isTipped TINYINT(1) NOT NULL DEFAULT 0,
  isSameDay TINYINT(1) NOT NULL DEFAULT 0,
  isClosed TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE Process (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  ProductId INT(11) NOT NULL,
  Quantity INT(11) NOT NULL,
  isStocked TINYINT(1) NOT NULL DEFAULT 0,
  DateAdded DATE NOT NULL,
  TimeAdded TIME NOT NULL,
  isMissing TINYINT(1) NOT NULL DEFAULT 0,
  HandlerId INT(11) NOT NULL
);

CREATE TABLE orderTracking (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  ProductId INT(11) NOT NULL,
  Quantity INT(11) NOT NULL,
  isStocked TINYINT(1) NOT NULL DEFAULT 0,
  DateAdded DATE NOT NULL,
  TimeAdded TIME NOT NULL,
  isMissing TINYINT(1) NOT NULL DEFAULT 0,
  TrackingStatus TINYINT(1) NOT NULL DEFAULT 0
);

--  more refining 
CREATE TABLE OrderSent (
  Id                    INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId                INT(11) NOT NULL,
  ItemQuantity          INT(11) NOT NULL,
  OrderRevenue          DECIMAL(10,2) NOT NULL,
  FinalOrderRevenue     DECIMAL(10,2) NOT NULL,
  OrderLiability        DECIMAL(10,2) NOT NULL,
  HandlerId             INT(11) NOT NULL DEFAULT 0,
  StripePaymentIntentId VARCHAR(255) NULL,
  TipAmount             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  OrderStatus           TINYINT(1) NOT NULL DEFAULT 0,
  DateAdded             DATE NOT NULL,
  TimeAdded             TIME NOT NULL,
  TimeDelivered         TIME NULL,
  isRated               TINYINT(1) NOT NULL DEFAULT 0,
  isTipped              TINYINT(1) NOT NULL DEFAULT 0,
  isSameDay             TINYINT(1) NOT NULL DEFAULT 0,
  isClosed              TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE Process (
  Id          INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId      INT(11) NOT NULL,
  ProductId   INT(11) NOT NULL,
  Quantity    INT(11) NOT NULL,
  isStocked   TINYINT(1) NOT NULL DEFAULT 1,
  DateAdded   DATE NOT NULL,
  TimeAdded   TIME NOT NULL,
  isMissing   TINYINT(1) NOT NULL DEFAULT 0,
  HandlerId   INT(11) NOT NULL DEFAULT 0
);

CREATE TABLE orderTracking (
  Id             INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId         INT(11) NOT NULL,
  ProductId      INT(11) NOT NULL,
  Quantity       INT(11) NOT NULL,
  isStocked      TINYINT(1) NOT NULL DEFAULT 1,
  DateAdded      DATE NOT NULL,
  TimeAdded      TIME NOT NULL,
  isMissing      TINYINT(1) NOT NULL DEFAULT 0,
  TrackingStatus TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE UserAddresses (
  Id       int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId   int(11) NOT NULL,
  Address  VARCHAR(255) NOT NULL,
  Apt      VARCHAR(100) NOT NULL,
  City     VARCHAR(100) NOT NULL,
  State    VARCHAR(100) NOT NULL,
  ZipCode  TEXT NOT NULL,
  LatnLong TEXT NOT NULL,
  GateCode TEXT NOT NULL,
  Note     LONGTEXT NOT NULL,
  Phone    VARCHAR(20) NOT NULL,
  FOREIGN KEY (UserId) REFERENCES Users(Id)
);