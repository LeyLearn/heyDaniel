CREATE TABLE ProductImages (
    Id        INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
    ProductId INT NOT NULL,
    ImageUrl  LONGTEXT NOT NULL,
    INDEX(ProductsId)
);

-- self-explanatory, stores the images associated with each product. can be used to display product images on the website, manage product listings, etc.