<?php
require_once __DIR__ . '/../db.php';

/**
 * Gets occupancy statistics for a specific date
 * 
 * @param string $date Date in Y-m-d format
 * @return array Array containing rooms, guests, and occupancy percentage
 */
function getOccupancyStats($date) {
    global $pdo;
    
    try {
        // Get room counts from room_type table
        $roomStmt = $pdo->prepare("
            SELECT 
                SUM(OCCUPIED_ROOMS) as occupied_rooms,
                SUM(AVAILABLE_ROOMS) as available_rooms
            FROM room_type 
            WHERE DATE(BUSINESS_DATE) = ?
            AND MARKET_CODE NOT IN ('0', 'Hotel Availability')
        ");
        $roomStmt->execute([$date]);
        $roomData = $roomStmt->fetch(PDO::FETCH_ASSOC) ?: ['occupied_rooms' => 0, 'available_rooms' => 0];
        
        // Get guest count from forecast table
        $guestStmt = $pdo->prepare("
            SELECT GUESTS as total_guests
            FROM forecast 
            WHERE DATE(CONSIDERED_DATE) = ?
        ");
        $guestStmt->execute([$date]);
        $guestData = $guestStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_guests' => 0];
        
        // Calculate occupancy percentage
        $totalRooms = intval($roomData['occupied_rooms']) + intval($roomData['available_rooms']);
        $occupancyPercentage = $totalRooms > 0 
            ? (intval($roomData['occupied_rooms']) / $totalRooms) * 100 
            : 0;
        
        return [
            'rooms' => intval($roomData['occupied_rooms']),
            'total_guests' => intval($guestData['total_guests']),
            'occupancy_percentage' => $occupancyPercentage,
            'total_rooms' => $totalRooms
        ];
    } catch (PDOException $e) {
        error_log("Error fetching occupancy stats: " . $e->getMessage());
        return [
            'rooms' => 0,
            'total_guests' => 0,
            'occupancy_percentage' => 0,
            'total_rooms' => 0
        ];
    }
}

/**
 * Gets occupancy statistics for today and previous day
 * 
 * @param string $selectedDate Date in Y-m-d format
 * @return array Array containing today's and previous day's stats
 */
function getDailyOccupancyStats($selectedDate) {
    $previousDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
    
    return [
        'today' => getOccupancyStats($selectedDate),
        'previous_day' => getOccupancyStats($previousDate)
    ];
}