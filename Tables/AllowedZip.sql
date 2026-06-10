CREATE TABLE AllowedZip (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  Zipcode LONGTEXT NOT NULL,
  SpotName LONGTEXT NOT NULL
);

-- This table is used to store allowed zip codes and their corresponding spot names. It can be used for various purposes such as validating user input, providing location-based services, or managing delivery areas.
-- i don't really recall what's the need for spot name.