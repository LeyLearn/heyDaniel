CREATE TABLE PassWordResetCode (
  Id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  Email LONGTEXT NOT NULL,
  Code LONGTEXT NOT NULL,
  SentIn LONGTEXT NOT NULL,
  ExpiredAt LONGTEXT NOT NULL
);
-- self-explanatory, tracks password reset codes sent to users. can be used for validating password reset requests, ensuring security, and managing the password reset process.