-- ReviewStatus: 'pending', 'approved', 'rejected'
CREATE TABLE ItemReviews (
  Id           INT(11)      AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId       INT(11)      NOT NULL,
  ProductId    INT(11)      NOT NULL,
  Stars        TINYINT(1)   NOT NULL,
  Expectation  TINYINT(1)   NOT NULL,
  ReviewTitle  VARCHAR(255) NOT NULL,
  Review       LONGTEXT     NOT NULL,
  ReviewStatus VARCHAR(20)  NOT NULL DEFAULT 'pending',
  DateAdded    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (UserId)    REFERENCES Users(Id),
  FOREIGN KEY (ProductId) REFERENCES Products(Id)
);
