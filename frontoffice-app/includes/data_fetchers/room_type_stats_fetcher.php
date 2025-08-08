<?php
require_once __DIR__ . '/../db.php';

function getRoomTypeStats($date) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            WITH RoomData AS (
                SELECT 
                    CASE 
                        WHEN MARKET_CODE IN ('PSRV', 'SRV') THEN 'SRV'
                        WHEN MARKET_CODE IN ('PSBV', 'SBV') THEN 'SBV'
                        WHEN MARKET_CODE IN ('PBVW', 'PSRVW') THEN 'PBVW'
                        ELSE MARKET_CODE 
                    END as room_type,
                    OCCUPIED_ROOMS,
                    AVAILABLE_ROOMS
                FROM room_type 
                WHERE DATE(BUSINESS_DATE) = ?
                AND MARKET_CODE NOT IN ('0', 'Hotel Availability')
            )
            SELECT 
                room_type,
                SUM(OCCUPIED_ROOMS) as occupied,
                SUM(AVAILABLE_ROOMS) as available
            FROM RoomData
            WHERE room_type IN ('SRV', 'SBV', 'PBVW')
            GROUP BY room_type
            ORDER BY 
                CASE room_type
                    WHEN 'SRV' THEN 1
                    WHEN 'SBV' THEN 2
                    WHEN 'PBVW' THEN 3
                    ELSE 4
                END
        ");
        $stmt->execute([$date]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate totals
        $totals = [
            'occupied' => 0,
            'available' => 0
        ];
        foreach ($results as $row) {
            $totals['occupied'] += $row['occupied'];
            $totals['available'] += $row['available'];
        }
        
        return [
            'stats' => $results,
            'totals' => $totals
        ];
    } catch (PDOException $e) {
        error_log("Error fetching room type stats: " . $e->getMessage());
        return ['stats' => [], 'totals' => ['occupied' => 0, 'available' => 0]];
    }
}
?>