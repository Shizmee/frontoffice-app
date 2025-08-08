-- First, create excursion_types table if it doesn't exist
CREATE TABLE IF NOT EXISTS excursion_types (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default excursion types
INSERT INTO excursion_types (type_name) VALUES 
    ('Island Hopping'),
    ('Snorkeling'),
    ('Diving'),
    ('Fishing'),
    ('Sunset Cruise')
ON DUPLICATE KEY UPDATE type_name = VALUES(type_name);

-- Modify excursions table
ALTER TABLE excursions
    -- Drop columns we don't need
    DROP COLUMN IF EXISTS excursion_name,
    DROP COLUMN IF EXISTS num_guests,
    DROP COLUMN IF EXISTS location,
    
    -- Modify existing columns
    MODIFY COLUMN room_number VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    MODIFY COLUMN guest_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    MODIFY COLUMN excursion_type_id INT(11) NOT NULL,
    MODIFY COLUMN excursion_date DATETIME NOT NULL,
    MODIFY COLUMN excursion_time TIME DEFAULT NULL,
    MODIFY COLUMN num_persons INT(11) NOT NULL DEFAULT 1,
    MODIFY COLUMN notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    MODIFY COLUMN status ENUM('booked', 'cancelled', 'completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'booked',
    
    -- Add foreign key constraint
    ADD CONSTRAINT fk_excursion_type 
    FOREIGN KEY (excursion_type_id) 
    REFERENCES excursion_types(id) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE;