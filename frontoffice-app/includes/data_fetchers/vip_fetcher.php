<?php
require_once __DIR__ . '/../db.php';
require_once 'inhouse_fetcher.php'; // For using common functions

function getVIPInHouseGuests() {
    global $pdo;
    
    try {
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
                COALESCE(DATE_FORMAT(f.ARR_DOM_TIME, '%H:%i'), '') as dom_time_to_go,
                COALESCE(f.DEP_INT_FLT, '') as dep_int
            FROM fo_bob f
            WHERE 
                (
                    f.Resv_Status = 'CHECKED IN' OR 
                    f.Resv_Status = 'CHECKED-IN' OR 
                    f.Resv_Status = 'CHECK IN' OR
                    f.Resv_Status = 'CHECKIN' OR
                    f.Resv_Status = 'CHECK-IN'
                )
                AND DATE(f.Arrival_Date) <= ?
                AND DATE(f.Departure_Date) >= ?
                AND (COALESCE(f.Adults, 0) + COALESCE(f.Children, 0)) > 0
                AND (
                    NULLIF(TRIM(f.VIP), '') IS NOT NULL 
                    OR LOWER(f.Comments) LIKE '%vip%'
                    OR LOWER(f.Special) LIKE '%vip%'
                )
            ORDER BY f.Room_No ASC";
        
        $stmt = $pdo->prepare($query);
        $selectedDate = isset($_SESSION['selected_report_date']) ? $_SESSION['selected_report_date'] : date('Y-m-d', strtotime('tomorrow'));
        $stmt->execute([$selectedDate, $selectedDate]);
        
        return processGuestResults($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } catch (PDOException $e) {
        error_log("Error fetching VIP in-house guests: " . $e->getMessage());
        return getEmptyGuestResult();
    }
}

function getVIPArrivingGuests() {
    global $pdo;
    
    try {
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
                COALESCE(DATE_FORMAT(f.ARR_DOM_TIME, '%H:%i'), '') as dom_time_to_go,
                COALESCE(f.DEP_INT_FLT, '') as dep_int,
                f.Resv_Status as resv_status
            FROM fo_bob f
            WHERE (
                f.Resv_Status = 'RESERVED' OR
                f.Resv_Status = 'PROSPECT' OR
                f.Resv_Status = 'DUE IN' OR
                f.Resv_Status = 'WALK-IN'
                )
                AND DATE(f.Arrival_Date) = ?
                AND (COALESCE(f.Adults, 0) + COALESCE(f.Children, 0)) > 0
                AND (
                    NULLIF(TRIM(f.VIP), '') IS NOT NULL 
                    OR LOWER(f.Comments) LIKE '%vip%'
                    OR LOWER(f.Special) LIKE '%vip%'
                )
            ORDER BY 
                CASE 
                    WHEN TIME(f.ARR_DOM_TIME) BETWEEN '00:00:00' AND '05:59:59' THEN 1 
                    ELSE 0 
                END ASC,
                f.ARR_DOM_TIME ASC,
                f.Room_No ASC";
        
        $stmt = $pdo->prepare($query);
        $selectedDate = isset($_SESSION['selected_report_date']) ? $_SESSION['selected_report_date'] : date('Y-m-d', strtotime('tomorrow'));
        $stmt->execute([$selectedDate]);
        
        return processGuestResults($stmt->fetchAll(PDO::FETCH_ASSOC));
        
    } catch (PDOException $e) {
        error_log("Error fetching VIP arriving guests: " . $e->getMessage());
        return getEmptyGuestResult();
    }
}