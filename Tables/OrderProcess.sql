CREATE TABLE Process (
  Id             INT(11)    AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId         INT(11)    NOT NULL,
  ProductId      INT(11)    NOT NULL,
  Quantity       INT(11)    NOT NULL,
  isStocked      TINYINT(1) NOT NULL DEFAULT 1,
  DateAdded      DATE       NOT NULL,
  TimeAdded      TIME       NOT NULL,
  NeverDelivered TINYINT(1) NOT NULL DEFAULT 0,
  HandlerId      INT(11)    NOT NULL DEFAULT 0,
  isClosed       TINYINT(1) NOT NULL DEFAULT 0
);
