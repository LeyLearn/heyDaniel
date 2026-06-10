CREATE TABLE ItemView (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId LONGTEXT NOT NULL,
  ProductId LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL
);
-- self-explanatory, tracks when a user views an item. can be used for analytics, recommendations, etc.
