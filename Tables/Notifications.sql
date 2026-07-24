CREATE TABLE Notifications (
  Id        INT(11)      AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId    INT(11)      NOT NULL,
  Type      VARCHAR(50)  NOT NULL,
  Title     VARCHAR(255) NOT NULL,
  Body      TEXT         NOT NULL DEFAULT '',
  IsRead    TINYINT(1)   NOT NULL DEFAULT 0,
  DateAdded DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (UserId) REFERENCES Users(Id),
  INDEX idx_user_id (UserId),
  INDEX idx_date_added (DateAdded)
);
