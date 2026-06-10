-- ReviewStatus can be 'pending', 'approved', 'rejected'. This allows for moderation of reviews before they are visible to the public.
CREATE TABLE ItemReview (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId LONGTEXT NOT NULL,
  ProductId LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL,
  Stars LONGTEXT NOT NULL,
  Expectation LONGTEXT NOT NULL,
  ReviewTitle LONGTEXT NOT NULL,
  Review LONGTEXT NOT NULL,
  ReviewStatus LONGTEXT NOT NULL
);
