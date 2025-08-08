<?php
require_once __DIR__ . '/../db.php';

function getTopNationalities() {
    global $pdo;
    
    try {
        $query = "
            SELECT 
                COALESCE(NULLIF(TRIM(Country), ''), 'Unknown') as country,
                SUM(COALESCE(Adults, 0) + COALESCE(Children, 0)) as guest_count
            FROM fo_bob
            WHERE 
                (
                    Resv_Status = 'CHECKED IN' OR 
                    Resv_Status = 'CHECKED-IN' OR 
                    Resv_Status = 'CHECK IN' OR
                    Resv_Status = 'CHECKIN' OR
                    Resv_Status = 'CHECK-IN'
                )
                AND DATE(Arrival_Date) <= ?
                AND DATE(Departure_Date) >= ?
                AND (COALESCE(Adults, 0) + COALESCE(Children, 0)) > 0
            GROUP BY country
            ORDER BY guest_count DESC
            LIMIT 10";
            
        $stmt = $pdo->prepare($query);
        $selectedDate = isset($_SESSION['selected_report_date']) ? $_SESSION['selected_report_date'] : date('Y-m-d', strtotime('tomorrow'));
        $stmt->execute([$selectedDate, $selectedDate]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total
        $total = array_sum(array_column($results, 'guest_count'));
        
        return [
            'nationalities' => $results,
            'total' => $total
        ];
        
    } catch (PDOException $e) {
        error_log("Error fetching top nationalities: " . $e->getMessage());
        return [
            'nationalities' => [],
            'total' => 0
        ];
    }
}