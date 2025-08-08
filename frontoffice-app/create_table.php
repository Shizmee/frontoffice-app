<?php
require_once 'includes/db.php';

try {
    // Drop the existing table to recreate with correct structure
    $pdo->exec("DROP TABLE IF EXISTS guest_interactions");

    $sql = "CREATE TABLE IF NOT EXISTS guest_interactions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        entry_date DATE DEFAULT CURRENT_DATE,
        guest_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        room_no VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        booking_agency VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        arrival DATE DEFAULT NULL,
        departure DATE DEFAULT NULL,
        no_of_nights INT(11) DEFAULT NULL,
        time TIME DEFAULT NULL,
        house_status VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        guest_comments TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        associate_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        incident TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        department VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        follow_up_by VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        recovery_action TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        guest_satisfaction_level VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    $pdo->exec($sql);
    echo "Table guest_interactions recreated successfully!";
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}
?>