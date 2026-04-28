-- Create and use database
DROP DATABASE IF EXISTS pet_adoption;
CREATE DATABASE pet_adoption;
USE pet_adoption;

-- Users
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('adopter', 'shelter', 'admin'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Shelters
CREATE TABLE Shelters (
    shelter_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    location VARCHAR(100),
    contact_email VARCHAR(100)
);

-- PetTypes
CREATE TABLE PetTypes (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) UNIQUE NOT NULL
);

-- Pets
CREATE TABLE Pets (
    pet_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type_id INT,
    breed VARCHAR(50),
    age INT,
    gender ENUM('male','female'),
    description TEXT,
    shelter_id INT,
    status ENUM('available', 'adopted') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (type_id) REFERENCES PetTypes(type_id),
    FOREIGN KEY (shelter_id) REFERENCES Shelters(shelter_id)
);

-- Applications
CREATE TABLE Applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    pet_id INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    message TEXT,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (pet_id) REFERENCES Pets(pet_id)
);

-- PetImages
CREATE TABLE PetImages (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT,
    image_url VARCHAR(255),

    FOREIGN KEY (pet_id) REFERENCES Pets(pet_id)
);

-- Indexes 
CREATE INDEX idx_pet_type ON Pets(type_id);
CREATE INDEX idx_pet_status ON Pets(status);
CREATE INDEX idx_pet_shelter ON Pets(shelter_id);



INSERT INTO PetTypes (type_name)
VALUES ('Dog'), ('Cat'), ('Bird'), ('Rabbit');

INSERT INTO Shelters (name, location, contact_email)
VALUES 
('Happy Pets Shelter', 'Nicosia', 'happy@shelter.com'),
('Hope Shelter', 'Limassol', 'hope@shelter.com');

INSERT INTO Users (name, email, password, role)
VALUES 
('Hasnaa', 'hasnaa@email.com', '1234', 'adopter'),
('Ali', 'ali@email.com', '1234', 'adopter');

INSERT INTO Pets (name, type_id, breed, age, gender, description, shelter_id)
VALUES 
('Max', 1, 'Labrador', 2, 'male', 'Friendly dog', 1),
('Bella', 2, 'Persian', 3, 'female', 'Calm cat', 2);