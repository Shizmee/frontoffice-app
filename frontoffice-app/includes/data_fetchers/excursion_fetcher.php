<?php
require_once __DIR__ . '/../db.php';

/**
 * Fetches excursions for a specific date
 * 
 * @param string $date Date in Y-m-d format
 * @return array Array of excursions
 */
function getExcursions($date) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.room_number,
                e.guest_name,
                et.type_name as excursion_type,
                TIME_FORMAT(e.excursion_time, '%H:%i') as time,
                e.num_persons,
                e.notes as remarks,
                e.date as excursion_date
            FROM excursions e
            INNER JOIN excursion_types et ON e.excursion_type_id = et.id
            WHERE DATE(e.date) = ?
            AND e.status = 'booked'
            ORDER BY e.excursion_time ASC, e.room_number ASC
        ");
        
        $stmt->execute([$date]);
        $excursions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Fetching excursions for date: " . $date);
        error_log("Found " . count($excursions) . " excursions");
        error_log("Excursions data: " . print_r($excursions, true));
        
        return $excursions;
        
    } catch (PDOException $e) {
        error_log("Error fetching excursions: " . $e->getMessage());
        error_log("SQL State: " . $e->errorInfo[0]);
        error_log("Error Code: " . $e->errorInfo[1]);
        error_log("Message: " . $e->errorInfo[2]);
        return [];
    }
}