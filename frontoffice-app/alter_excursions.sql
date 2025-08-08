-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS=0;

-- Drop and recreate tables
DROP TABLE IF EXISTS excursions;
DROP TABLE IF EXISTS excursion_types;

-- Create excursion_types table
CREATE TABLE excursion_types (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default excursion types
INSERT INTO excursion_types (type_name) VALUES 
    ('Addu City Tour'),
    ('Big Game Fishing'),
    ('End of the Day Dolphin Cruise'),
    ('Local Island Tour'),
    ('Lucky Dolphin Cruise'),
    ('Morning Fishing'),
    ('Private Trip Tour'),
    ('Snorkeling Explorer'),
    ('Sunset Cocktail Cruise'),
    ('Sunset Fishing');

-- Create excursions table
CREATE TABLE excursions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    guest_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    excursion_type_id INT(11) NOT NULL,
    date DATE NOT NULL,
    excursion_date DATETIME NOT NULL,
    excursion_time TIME DEFAULT NULL,
    num_persons INT(11) NOT NULL DEFAULT 1,
    notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    status ENUM('booked', 'cancelled', 'completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'booked',
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_excursion_type 
        FOREIGN KEY (excursion_type_id) 
        REFERENCES excursion_types(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;