CREATE TABLE Holidays (
    Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,  
    Name LONGTEXT NOT NULL,         
    StartDate LONGTEXT NOT NULL,           
    EndDate LONGTEXT NOT NULL,            
    PrimaryImage LONGTEXT NOT NULL, 
    SecondaryImage LONGTEXT NOT NULL, 
    ThirdImage LONGTEXT NOT NULL,               
    PrimarySlogan LONGTEXT NOT NULL, 
    SecondarySlogan LONGTEXT NOT NULL,
    HeaderPrimarySlogan LONGTEXT NOT NULL, 
    HeaderSecondarySlogan LONGTEXT NOT NULL,
    AdsImage LONGTEXT NOT NULL,
    AdSlogan LONGTEXT NOT NULL,
    AdSecondarySlogan LONGTEXT NOT NULL,
    AdColorPrimary LONGTEXT NOT NULL,       
    AdColorSecondary LONGTEXT NOT NULL    
);