<?php
require_once __DIR__ . '/../db.php';
require_once 'inhouse_fetcher.php'; // For using common functions

function getArrivingGuests() {
    global $pdo;
    
    try {
        // Debug: Print current date for verification
        error_log("Fetching arriving guests for date: " . date('Y-m-d'));
        
        $query = "
 SELECT 
                f.Room_No as room_no,
                f.Guest_Name as guest_name,
                f.Meal_Plan as meal_plan,
                f.Adults as adult,
                f.Children as child,
                f.TA_Name as ta_name,
                f.Room_Nights as room_night,
                f.VIP as vip,
                f.Room_Type as room_type,
                f.Country as country,
                f.Special as sprq,
                f.Comments as comments,
                DATE_FORMAT(f.Arrival_Date, '%d-%m-%Y') as arr_date,
                DATE_FORMAT(f.Departure_Date, '%d-%m-%Y') as dep_date,
                COALESCE(f.ARR_INT_FLT, '') as arr_int,
                COALESCE(DATE_FORMAT(f.DEP_DOM_TIME, '%H:%i'), '') as dom_time_to_go,
                COALESCE(f.DEP_INT_FLT, '') as dep_int,
                f.Resv_Status as resv_status
            FROM fo_bob f
            WHERE 
                (
                    f.Resv_Status = 'RESERVED' OR 
                    f.Resv_Status = 'PROSPECT' 
                )
                AND DATE(f.Arrival_Date) = ?
                AND (COALESCE(f.Adults, 0) + COALESCE(f.Children, 0)) > 0
            ORDER BY f.ARR_DOM_TIME ASC, f.Room_No ASC";
        
        error_log("Running query: " . $query);
        $stmt = $pdo->prepare($query);
        $selectedDate = isset($_SESSION['selected_report_date']) ? $_SESSION['selected_report_date'] : date('Y-m-d', strtotime('tomorrow'));
        $stmt->execute([$selectedDate]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Query results count: " . count($results));
        
        // Debug room numbers and dom_time_to_go
        foreach ($results as $result) {
            error_log("Room number found: " . print_r($result['room_no'], true));
            error_log("DOM TIME TO GO found: " . print_r($result['dom_time_to_go'] ?? 'not set', true));
            error_log("Full result: " . print_r($result, true));
        }
        
        error_log("Processed results count: " . count($results));
        if (count($results) > 0) {
            error_log("First processed result: " . print_r($results[0], true));
        }
        
        error_log("Results before processing: " . print_r($results, true));
        $processed = processGuestResults($results);
        error_log("Results after processing: " . print_r($processed, true));
        return $processed;
        
    } catch (PDOException $e) {
        error_log("Error fetching arriving guests: " . $e->getMessage());
        return getEmptyGuestResult();
    }
}