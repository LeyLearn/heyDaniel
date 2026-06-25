CREATE TABLE Tokens (
  Id        INT(11)     AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId    INT(11)     NOT NULL,
  Token     TEXT        NOT NULL,
  Type      VARCHAR(50) NOT NULL,
  ExpiresAt DATETIME    NOT NULL,
  DateAdded DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (UserId) REFERENCES Users(Id),
  INDEX idx_user_id (UserId),
  INDEX idx_expires_at (ExpiresAt)
);
