<?php
require_once 'includes/db.php';

try {
    // Drop table if exists
    $pdo->exec("DROP TABLE IF EXISTS buggy_log");

    // Create the buggy_log table
    $sql = "CREATE TABLE buggy_log (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        time TIME NOT NULL,
        call_from VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        caller_name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        guest_request TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        concern_department VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        comments TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        status_followup VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        time_done TIME DEFAULT NULL,
        fup_with_guest VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
        fup_time TIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    $pdo->exec($sql);
    echo "Table 'buggy_log' created successfully!";

} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}
?>