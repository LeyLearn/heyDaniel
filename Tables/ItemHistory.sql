CREATE TABLE ItemBoughtHistory (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  ProductId INT(11) NOT NULL,
  Quantity INT(11) NOT NULL,
  HandlerId INT(11) NOT NULL,
  DateAdded DATETIME NOT NULL,
  TimeAdded DATETIME NOT NULL
);

-- This table is used to store the history of items that a user has bought. It includes the user ID, product ID, quantity of the product, handler ID (which could be the ID of the person or system that processed the order), and the date and time when the item was added to the history. This information can be used for order tracking, analytics, and customer service purposes.