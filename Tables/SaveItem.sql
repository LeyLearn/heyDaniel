CREATE TABLE Saved (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT NOT NULL,
  ProductId INT NOT NULL,
  DateAdded LONGTEXT NOT NULL
);

-- self-explanatory, tracks the items that a user has saved for later. can be used for wishlists, favorites, etc.