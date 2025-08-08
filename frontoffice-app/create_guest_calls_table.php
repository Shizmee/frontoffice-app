<?php
require_once 'includes/db.php';

try {
    // Drop table if exists
    $pdo->exec("DROP TABLE IF EXISTS guest_calls");

    // Create the guest_calls table
    $sql = "CREATE TABLE guest_calls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        entry_date DATE DEFAULT CURRENT_DATE,
        guest_name VARCHAR(255) NOT NULL,
        room_no VARCHAR(50) NOT NULL,
        booking_agency VARCHAR(255) NOT NULL,
        arrival DATE NOT NULL,
        departure DATE NOT NULL,
        no_of_nights INT NOT NULL,
        time TIME NOT NULL,
        house_status VARCHAR(50) NOT NULL,
        call_type VARCHAR(255) NOT NULL,
        call_details TEXT NOT NULL,
        action_taken TEXT,
        follow_up_by VARCHAR(255),
        status VARCHAR(50) DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "Table 'guest_calls' created successfully!";

} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}
?>