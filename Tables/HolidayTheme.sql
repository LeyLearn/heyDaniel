CREATE TABLE Themes  (
    Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,  
    Name VARCHAR(255) NOT NULL,         
    StartDate DATETIME NOT NULL,           
    EndDate DATETIME NOT NULL,            
    PrimaryImage VARCHAR(255) NOT NULL, 
    SecondaryImage VARCHAR(255) NOT NULL, 
    ThirdImage VARCHAR(255) NOT NULL,               
    PrimarySlogan VARCHAR(255) NOT NULL, 
    SecondarySlogan VARCHAR(255) NOT NULL,
    HeaderPrimarySlogan VARCHAR(255) NOT NULL, 
    HeaderSecondarySlogan VARCHAR(255) NOT NULL,
    AdsImage VARCHAR(255) NOT NULL,
    AdSlogan VARCHAR(255) NOT NULL,
    AdSecondarySlogan VARCHAR(255) NOT NULL,
    AdColorPrimary VARCHAR(255) NOT NULL,       
    AdColorSecondary VARCHAR(255) NOT NULL    
);

-- updated version with more flexible design to allow for more assets without needing to alter the table structure. This way we can easily add new types of assets in the future without needing to change the database schema.
CREATE TABLE Themes (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    StartDate DATETIME NOT NULL,
    EndDate DATETIME NOT NULL
);

CREATE TABLE ThemeAssets (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    ThemeId INT NOT NULL,
    AssetKey VARCHAR(100) NOT NULL,  -- e.g. 'primary_image', 'ad_slogan'
    AssetValue TEXT NOT NULL,
    FOREIGN KEY (ThemeId) REFERENCES Themes(Id)
);