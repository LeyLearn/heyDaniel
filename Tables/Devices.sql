CREATE TABLE Devices (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  DeviceSignature LONGTEXT NOT NULL,
  DeviceType LONGTEXT NOT NULL,
  ZipCode LONGTEXT NOT NULL,
  UserAddress LONGTEXT NOT NULL,
  isActive LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL
);
-- this code is updated due to LONGTEXT stores up to 4GB per field. waste of space.
CREATE TABLE Devices (
  Id          INT(11)      AUTO_INCREMENT PRIMARY KEY NOT NULL,
  DeviceSignature VARCHAR(255) NOT NULL,
  DeviceType  VARCHAR(100) NOT NULL,
  ZipCode     CHAR(10)     NOT NULL,
  isSameDayEligible TINYINT(1) NOT NULL DEFAULT 0,
  isActive    TINYINT(1)   NOT NULL DEFAULT 1,
  DateAdded   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);