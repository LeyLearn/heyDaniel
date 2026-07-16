CREATE TABLE Users (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  Name VARCHAR(255) NOT NULL,
  Email VARCHAR(255) NOT NULL UNIQUE,
  Password TEXT NULL,
  Credits DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  IsMember TINYINT(1) NOT NULL DEFAULT 0,
  TimeRegister DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TimeMembership DATETIME NULL,
  IsActive TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_email (Email),
  INDEX idx_is_active (IsActive)
);

-- Sample user (Password left NULL — just a placeholder id for seed data
-- like RecentlyViewed to reference).
INSERT INTO Users (Name, Email) VALUES
('Sample User', 'sample.user@heydaniel.test');
