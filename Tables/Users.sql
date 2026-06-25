CREATE TABLE Users (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  Name VARCHAR(255) NOT NULL,
  Email VARCHAR(255) NOT NULL,
  Password LONGTEXT NOT NULL,
  Phone VARCHAR(20) NOT NULL,
  Credits DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  IsMember TINYINT(1) NOT NULL DEFAULT 0,
  TimeRegister  TINYTEXT NOT NULL,
  TimeMembership  TINYTEXT NOT NULL,
  IsActive TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE Tokens (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    Token TEXT NOT NULL,
    Type TEXT NOT NULL,
    ExpiresAt DATETIME NOT NULL,
    DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- self-explanatory, stores user information. can be used for authentication, personalization, order processing, etc.
-- updated version to allow multiple addresses per user.
CREATE TABLE UserAddresses (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId int(11) NOT NULL,
  Address VARCHAR(255) NOT NULL,
  Apt VARCHAR(100) NOT NULL,
  City VARCHAR(100) NOT NULL,
  State VARCHAR(100) NOT NULL,
  ZipCode TEXT NOT NULL,
  Coordinate TEXT NOT NULL,
  GateCode TEXT NOT NULL,
  Note LONGTEXT NOT NULL,
);
