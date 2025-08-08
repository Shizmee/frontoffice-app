<?php
require_once __DIR__ . '/../db.php';

/**
 * Fetches all in-house guests (checked-in guests for current day)
 * 
 * @return array Array containing guests data and totals
 */
function getInHouseGuests() {
    global $pdo;
    
    try {
        // Debug: Print current date for verification
        error_log("Fetching in-house guests for date: " . date('Y-m-d'));
        
        // Debug: Print table structure
        $tableCheck = $pdo->query("DESCRIBE fo_bob");
        error_log("fo_bob table columns:");
        while ($col = $tableCheck->fetch(PDO::FETCH_ASSOC)) {
            error_log(print_r($col, true));
        }
        
        // Debug: Check if any records exist
        $checkData = $pdo->query("SELECT COUNT(*) as count FROM fo_bob");
        $count = $checkData->fetch(PDO::FETCH_ASSOC)['count'];
        error_log("Total records in fo_bob: " . $count);
        
        // Debug: Check raw data sample
        $sampleData = $pdo->query("SELECT * FROM fo_bob LIMIT 1");
        $sample = $sampleData->fetch(PDO::FETCH_ASSOC);
        error_log("Sample record: " . print_r($sample, true));
        
        // Debug: Check date ranges and status values
        $dateCheck = $pdo->query("
            SELECT 
                MIN(Arrival_Date) as min_arrival,
                MAX(Arrival_Date) as max_arrival,
                MIN(Departure_Date) as min_departure,
                MAX(Departure_Date) as max_departure,
                GROUP_CONCAT(DISTINCT Resv_Status) as statuses
            FROM fo_bob
        ");
        $dates = $dateCheck->fetch(PDO::FETCH_ASSOC);
        error_log("Date ranges and statuses: " . print_r($dates, true));

        // Debug: Check current date records
        $currentCheck = $pdo->query("
            SELECT 
                Room_No,
                Guest_Name,
                Resv_Status,
                Arrival_Date,
                Departure_Date,
                Adults,
                Children,
                (COALESCE(Adults, 0) + COALESCE(Children, 0)) as total_pax
            FROM fo_bob 
            WHERE DATE(Arrival_Date) <= CURDATE() 
            AND DATE(Departure_Date) >= CURDATE()
        ");
        $currentRecords = $currentCheck->fetchAll(PDO::FETCH_ASSOC);
        error_log("Current date records: " . print_r($currentRecords, true));

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
                CONCAT(
                    COALESCE(f.ARR_INT_FLT, ''),
                    ' ',
                    COALESCE(DATE_FORMAT(f.ARR_INT_TIME, '%H:%i'), '')
                ) as arr_int,
                CONCAT(
                    COALESCE(f.DEP_INT_FLT, ''),
                    ' ',
                    COALESCE(DATE_FORMAT(f.DEP_INT_TIME, '%H:%i'), '')
                ) as dep_int,
                f.Resv_Status as resv_status
            FROM fo_bob f
            WHERE 
                (
                    f.Resv_Status = 'CHECKED IN' OR 
                    f.Resv_Status = 'DUE OUT' OR 
                    f.Resv_Status = 'RESERVED' OR
                    f.Resv_Status = 'PROSPECT' OR
                    f.Resv_Status = 'DUE IN'
                )
                AND DATE(f.Arrival_Date) <= ?
                AND DATE(f.Departure_Date) >= ?
                AND (COALESCE(f.Adults, 0) + COALESCE(f.Children, 0)) > 0
            ORDER BY f.Arrival_Date ASC, f.Room_No ASC";
            
        // Debug: Also run a count query to verify data exists
        $countQuery = "SELECT COUNT(*) as total FROM fo_bob WHERE DATE(Arrival_Date) <= CURDATE() AND DATE(Departure_Date) >= CURDATE()";
        $countStmt = $pdo->query($countQuery);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        error_log("Total possible records (ignoring status): " . $totalCount);
        
        // Debug: Print the query
        error_log("Query: " . $query);
        
        error_log("Running query: " . $query);
        $stmt = $pdo->prepare($query);
        $selectedDate = isset($_SESSION['selected_report_date']) ? $_SESSION['selected_report_date'] : date('Y-m-d', strtotime('tomorrow'));
        $stmt->execute([$selectedDate, $selectedDate]);
        
        $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Query results count: " . count($rawResults));
        
        // Process results to group by room
        $processedResults = [];
        $currentRoom = null;
        $currentRecord = null;
        
        foreach ($rawResults as $row) {
            if ($currentRoom !== $row['room_no']) {
                if ($currentRecord) {
                    $processedResults[] = $currentRecord;
                }
                $currentRoom = $row['room_no'];
                $currentRecord = $row;
                $currentRecord['guest_names'] = [$row['guest_name']];
                unset($currentRecord['guest_name']);
            } else {
                $currentRecord['guest_names'][] = $row['guest_name'];
            }
        }
        
        if ($currentRecord) {
            $processedResults[] = $currentRecord;
        }
        
        $results = $processedResults;
        
        error_log("Processed results count: " . count($results));
        if (count($results) > 0) {
            error_log("First processed result: " . print_r($results[0], true));
        }
        if (!empty($results)) {
            error_log("First result: " . print_r($results[0], true));
        }
        
        return processGuestResults($results);
        
    } catch (PDOException $e) {
        error_log("Error fetching in-house guests: " . $e->getMessage());
        error_log("SQL State: " . $e->errorInfo[0]);
        error_log("Error Code: " . $e->errorInfo[1]);
        error_log("Error Message: " . $e->errorInfo[2]);
        return getEmptyGuestResult();
    }
}

/**
 * Processes guest query results and calculates totals
 * 
 * @param array $guests Array of guest records
 * @return array Processed results with guests and totals
 */
function processGuestResults($guests) {
    $totals = [
        'adult' => 0,
        'child' => 0
    ];
    
    foreach ($guests as $guest) {
        $totals['adult'] += intval($guest['adult']);
        $totals['child'] += intval($guest['child']);
    }
    
    // Debug: Print totals
    error_log("Calculated totals: " . print_r($totals, true));
    
    return [
        'guests' => $guests,
        'totals' => $totals
    ];
}

/**
 * Returns empty guest result structure
 * 
 * @return array Empty guest result with zero totals
 */
function getEmptyGuestResult() {
    return [
        'guests' => [],
        'totals' => ['adult' => 0, 'child' => 0]
    ];
}

// Debug: Test database connection
try {
    global $pdo;
    $pdo->query("SELECT 1");
    error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
}