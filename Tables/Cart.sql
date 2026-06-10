CREATE TABLE Cart (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId LONGTEXT NOT NULL,
  ProductId LONGTEXT NOT NULL,
  Quantity LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL
);

-- This table is used to store the items that a user has added to their shopping cart. It includes the user ID, product ID, quantity of the product, and the date it was added to the cart. This information can be used to display the user's cart contents, calculate totals, and manage the checkout process.