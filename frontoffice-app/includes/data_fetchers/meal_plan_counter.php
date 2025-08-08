<?php
require_once __DIR__ . '/../db.php';

function getMealPlanCounts() {
    global $pdo;
    
    try {
        // Get In-house meal plan counts
        $inhouseQuery = "
            SELECT 
                UPPER(TRIM(Meal_Plan)) as Meal_Plan,
                SUM(COALESCE(Adults, 0) + COALESCE(Children, 0)) as pax_count
            FROM fo_bob
            WHERE 
                UPPER(Resv_Status) IN (
                    'CHECKED IN', 'CHECKED-IN', 'CHECK IN', 'CHECKIN', 'CHECK-IN',
                    'CHECKED_IN', 'CHECK_IN', 'CHECK', 'IN'
                )
                AND DATE(Arrival_Date) <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND DATE(Departure_Date) >= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            GROUP BY Meal_Plan";
        
        // Get Arrival meal plan counts
        $arrivalQuery = "
            SELECT 
                UPPER(TRIM(Meal_Plan)) as Meal_Plan,
                SUM(COALESCE(Adults, 0) + COALESCE(Children, 0)) as pax_count
            FROM fo_bob
            WHERE UPPER(Resv_Status) IN (
                'RESERVED', 'PROSPECT', 'DUE IN', 'DUE-IN', 'DUEIN',
                'WALK IN', 'WALK-IN', 'WALKIN', 'NEW', 'PENDING'
            )
            AND DATE(Arrival_Date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            GROUP BY Meal_Plan";
        
        // Get Departure meal plan counts
        $departureQuery = "
            SELECT 
                UPPER(TRIM(Meal_Plan)) as Meal_Plan,
                SUM(COALESCE(Adults, 0) + COALESCE(Children, 0)) as pax_count
            FROM fo_bob
            WHERE 
                UPPER(Resv_Status) IN (
                    'CHECKED IN', 'CHECKED-IN', 'CHECK IN', 'CHECKIN', 'CHECK-IN',
                    'CHECKED_IN', 'CHECK_IN', 'CHECK', 'IN'
                )
                AND DATE(Departure_Date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            GROUP BY Meal_Plan";

        // Debug log queries and current date
        error_log("Current Date: " . date('Y-m-d'));
        error_log("Tomorrow's Date: " . date('Y-m-d', strtotime('tomorrow')));
        error_log("Inhouse Query: " . $inhouseQuery);
        error_log("Arrival Query: " . $arrivalQuery);
        error_log("Departure Query: " . $departureQuery);
        
        // Debug: Check if we have any data at all
        $checkQuery = "SELECT COUNT(*) as total, 
                             GROUP_CONCAT(DISTINCT Meal_Plan) as meal_plans,
                             GROUP_CONCAT(DISTINCT Resv_Status) as statuses,
                             MIN(Arrival_Date) as min_arrival,
                             MAX(Arrival_Date) as max_arrival,
                             MIN(Departure_Date) as min_departure,
                             MAX(Departure_Date) as max_departure
                      FROM fo_bob";
        $checkStmt = $pdo->query($checkQuery);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        error_log("Database Overview:");
        error_log(print_r($checkResult, true));

        // Execute queries
        $inhouseStmt = $pdo->query($inhouseQuery);
        $arrivalStmt = $pdo->query($arrivalQuery);
        $departureStmt = $pdo->query($departureQuery);

        // Debug log results
        error_log("Inhouse Results:");
        while ($row = $inhouseStmt->fetch(PDO::FETCH_ASSOC)) {
            error_log(print_r($row, true));
        }
        $inhouseStmt = $pdo->query($inhouseQuery); // Reset the statement

        error_log("Arrival Results:");
        while ($row = $arrivalStmt->fetch(PDO::FETCH_ASSOC)) {
            error_log(print_r($row, true));
        }
        $arrivalStmt = $pdo->query($arrivalQuery); // Reset the statement

        error_log("Departure Results:");
        while ($row = $departureStmt->fetch(PDO::FETCH_ASSOC)) {
            error_log(print_r($row, true));
        }
        $departureStmt = $pdo->query($departureQuery); // Reset the statement

        // Initialize counts array
        $mealPlans = ['BB', 'HB', 'FB', 'AI'];
        $counts = [
            'INH' => array_fill_keys($mealPlans, 0),
            'ARR' => array_fill_keys($mealPlans, 0),
            'DEP' => array_fill_keys($mealPlans, 0),
            'Total' => array_fill_keys($mealPlans, 0)
        ];

        // Process inhouse results
        while ($row = $inhouseStmt->fetch(PDO::FETCH_ASSOC)) {
            $mealPlan = strtoupper(trim($row['Meal_Plan']));
            if (in_array($mealPlan, $mealPlans)) {
                $counts['INH'][$mealPlan] = (int)$row['pax_count'];
            }
        }

        // Process arrival results
        while ($row = $arrivalStmt->fetch(PDO::FETCH_ASSOC)) {
            $mealPlan = strtoupper(trim($row['Meal_Plan']));
            if (in_array($mealPlan, $mealPlans)) {
                $counts['ARR'][$mealPlan] = (int)$row['pax_count'];
            }
        }

        // Process departure results
        while ($row = $departureStmt->fetch(PDO::FETCH_ASSOC)) {
            $mealPlan = strtoupper(trim($row['Meal_Plan']));
            if (in_array($mealPlan, $mealPlans)) {
                $counts['DEP'][$mealPlan] = (int)$row['pax_count'];
            }
        }

        // Calculate totals
        foreach ($mealPlans as $plan) {
            $counts['Total'][$plan] = $counts['INH'][$plan] + $counts['ARR'][$plan] - $counts['DEP'][$plan];
        }

        return $counts;

    } catch (PDOException $e) {
        error_log("Error fetching meal plan counts: " . $e->getMessage());
        return [
            'INH' => array_fill_keys($mealPlans, 0),
            'ARR' => array_fill_keys($mealPlans, 0),
            'DEP' => array_fill_keys($mealPlans, 0),
            'Total' => array_fill_keys($mealPlans, 0)
        ];
    }
}