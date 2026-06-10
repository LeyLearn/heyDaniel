CREATE TABLE Process (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId LONGTEXT NOT NULL,
  ProductId LONGTEXT NOT NULL,
  Quantity LONGTEXT NOT NULL,
  isStocked LONGTEXT NOT NULL,
  DateAdded LONGTEXT NOT NULL,
  TimeAdded LONGTEXT NOT NULL,
  NeverDelivered LONGTEXT NOT NULL,
  HandlerId LONGTEXT NOT NULL
);

-- This table is used to store the order processing information. It includes the user ID, product ID, quantity of the product, whether the product is stocked, the date and time when the order was added, whether the order was never delivered, and the handler ID (which could be the ID of the person or system that processed the order). This information can be used for order tracking, inventory management, and customer service purposes.