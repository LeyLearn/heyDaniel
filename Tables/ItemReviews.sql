-- ReviewStatus: 'pending', 'approved', 'rejected'
-- HandlerId: Employees.Id of whoever approved/rejected it; 0 = not yet handled
-- (no FK to Employees yet - that table is still a stub pending the dashboard build)
CREATE TABLE ItemReviews (
  Id           INT(11)      AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId       INT(11)      NOT NULL,
  ProductId    INT(11)      NOT NULL,
  Stars        TINYINT(1)   NOT NULL,
  Expectation  TINYINT(1)   NOT NULL,
  ReviewTitle  VARCHAR(255) NOT NULL,
  Review       LONGTEXT     NOT NULL,
  ReviewStatus VARCHAR(20)  NOT NULL DEFAULT 'pending',
  HandlerId    INT(11)      NOT NULL DEFAULT 0,
  DateAdded    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (UserId)    REFERENCES Users(Id),
  FOREIGN KEY (ProductId) REFERENCES Products(Id),
  INDEX idx_product_id (ProductId),
  INDEX idx_user_id (UserId),
  INDEX idx_stars (Stars)
);
