<?php
require_once __DIR__ . '/../db.php';

try {
    // Check if table exists
    $tableQuery = "SHOW TABLES LIKE 'fo_bob'";
    $stmt = $pdo->query($tableQuery);
    $tableExists = $stmt->fetch();
    error_log("Table fo_bob exists: " . ($tableExists ? "Yes" : "No"));

    if ($tableExists) {
        // Get table structure
        $structureQuery = "DESCRIBE fo_bob";
        $stmt = $pdo->query($structureQuery);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Table structure: " . print_r($columns, true));

        // Get sample data
        $sampleQuery = "SELECT * FROM fo_bob LIMIT 1";
        $stmt = $pdo->query($sampleQuery);
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Sample data: " . print_r($sample, true));
    }
} catch (PDOException $e) {
    error_log("Error checking table: " . $e->getMessage());
}