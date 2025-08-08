<?php
require_once 'includes/db.php';

try {
    // First add the column
    $sql = "ALTER TABLE guest_interactions 
            ADD COLUMN IF NOT EXISTS entry_date DATE DEFAULT CURRENT_DATE AFTER id";
    $pdo->exec($sql);
    
    // Then update existing records to have today's date if entry_date is null
    $sql = "UPDATE guest_interactions 
            SET entry_date = CURRENT_DATE 
            WHERE entry_date IS NULL";
    $pdo->exec($sql);
    
    echo "Table altered and entry dates updated successfully!";
} catch (PDOException $e) {
    die("Error updating table: " . $e->getMessage());
}
?>