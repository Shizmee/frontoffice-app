CREATE TABLE IF NOT EXISTS excursions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    excursion_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    num_guests INT(11) DEFAULT 1,
    guest_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    room_number VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    excursion_type_id INT(11) NOT NULL,
    excursion_date DATETIME NOT NULL,
    excursion_time TIME DEFAULT NULL,
    location VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    num_persons INT(11) NOT NULL,
    notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    status ENUM('booked', 'cancelled', 'completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'booked',
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;