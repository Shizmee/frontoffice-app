<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/meal_count_calculator.php';
require_once __DIR__ . '/inhouse_fetcher.php';
require_once __DIR__ . '/departure_fetcher.php';
require_once __DIR__ . '/occupancy_stats_fetcher.php';

// Define valid check-in statuses once
function getValidCheckInStatuses() {
    return ["CHECKED IN", "RESERVED", "DUE OUT", "PROSPECT", "CHECK-IN"];
}

// Convert statuses to SQL IN clause and params
function buildStatusCondition($prefix = 'Resv_Status') {
    $statuses = getValidCheckInStatuses();
    $conditions = implode(' OR ', array_map(fn($s) => "$prefix = ?", $statuses));
    return [$conditions, $statuses];
}

/**
 * Get meal counts for the selected date
 * Falls back to dynamic calculation if not found
 */
function getMealCounts() {
    $selectedDate = isset($_SESSION['selected_report_date']) 
        ? $_SESSION['selected_report_date'] 
        : date('Y-m-d', strtotime('tomorrow'));

    error_log('Meal fetcher: selectedDate=' . $selectedDate);

    global $pdo;

    // Try to get from precomputed table
    $query = "SELECT BFADULT, BFCHILD, LNADULT, LNCHLD, DNADULT, DNCHLD 
              FROM meal_daily_summary 
              WHERE meal_date = ? 
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$selectedDate]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        error_log('Meal fetcher: Found in meal_daily_summary for ' . $selectedDate);
    } else {
        error_log('Meal fetcher: Not found in summary, calculating dynamically...');
        return calculateMealCountsForDate($selectedDate);
    }

    return [
        'Breakfast' => [
            'adult' => (int)($row['BFADULT'] ?? 0),
            'child'  => (int)($row['BFCHILD'] ?? 0),
            'total'  => (int)($row['BFADULT'] ?? 0) + (int)($row['BFCHILD'] ?? 0)
        ],
        'Lunch' => [
            'adult' => (int)($row['LNADULT'] ?? 0),
            'child'  => (int)($row['LNCHLD'] ?? 0),
            'total'  => (int)($row['LNADULT'] ?? 0) + (int)($row['LNCHLD'] ?? 0)
        ],
        'Dinner' => [
            'adult' => (int)($row['DNADULT'] ?? 0),
            'child'  => (int)($row['DNCHLD'] ?? 0),
            'total'  => (int)($row['DNADULT'] ?? 0) + (int)($row['DNCHLD'] ?? 0)
        ]
    ];
}

/**
 * Dynamically calculate meal counts for a given date
 */
function calculateMealCountsForDate($date) {
    $dateObj = new DateTime($date);
    $yesterday = $dateObj->modify('-1 day')->format('Y-m-d');
    $dateObj->modify('+1 day'); // Reset

    // Dinner: in-house on $date
    $dinner = getInHouseGuestsForDate($date);
    $dinnerAdults = $dinner['totals']['adult'];
    $dinnerChildren = $dinner['totals']['child'];

    // Breakfast & Lunch: based on yesterday's in-house minus early departures today
    $prevDayInHouse = getInHouseGuestsForDate($yesterday);
    $bfDeduct = getDepartingBeforeTime($date, '06:00');
    $lunchDeduct = getDepartingBeforeTime($date, '12:30');

    $breakfastAdults = max(0, $prevDayInHouse['totals']['adult'] - $bfDeduct['adult']);
    $breakfastChildren = max(0, $prevDayInHouse['totals']['child'] - $bfDeduct['child']);

    $lunchAdults = max(0, $prevDayInHouse['totals']['adult'] - $lunchDeduct['adult']);
    $lunchChildren = max(0, $prevDayInHouse['totals']['child'] - $lunchDeduct['child']);

    return [
        'Breakfast' => [
            'adult' => $breakfastAdults,
            'child' => $breakfastChildren,
            'total' => $breakfastAdults + $breakfastChildren
        ],
        'Lunch' => [
            'adult' => $lunchAdults,
            'child' => $lunchChildren,
            'total' => $lunchAdults + $lunchChildren
        ],
        'Dinner' => [
            'adult' => $dinnerAdults,
            'child' => $dinnerChildren,
            'total' => $dinnerAdults + $dinnerChildren
        ]
    ];
}

/**
 * Get total in-house guests for a date
 */
function getInHouseGuestsForDate($date) {
    global $pdo;
    [$statusCond, $statuses] = buildStatusCondition();

    $query = "
        SELECT 
            COALESCE(SUM(Adults), 0) as adult,
            COALESCE(SUM(Children), 0) as child
        FROM fo_bob
        WHERE ($statusCond)
          AND DATE(Arrival_Date) <= ?
          AND DATE(Departure_Date) >= ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($statuses, [$date, $date]));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'totals' => [
            'adult' => (int)$row['adult'],
            'child' => (int)$row['child']
        ]
    ];
}

/**
 * Get departing guests before a given time (e.g., 06:00 or 12:30)
 */
function getDepartingBeforeTime($date, $time) {
    global $pdo;
    [$statusCond, $statuses] = buildStatusCondition();

    $query = "
        SELECT 
            COALESCE(SUM(Adults), 0) as adult,
            COALESCE(SUM(Children), 0) as child
        FROM fo_bob
        WHERE ($statusCond)
          AND DATE(Departure_Date) = ?
          AND DEP_DOM_TIME < ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($statuses, [$date, $time]));

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'adult' => (int)$row['adult'],
        'child' => (int)$row['child']
    ];
}

/**
 * Get full list of departing guests with DOM time (for display)
 */
function getDepartingGuestsWithDomTime($date) {
    global $pdo;
    [$statusCond, $statuses] = buildStatusCondition();

    $query = "
        SELECT 
            COALESCE(Adults, 0) as adult,
            COALESCE(Children, 0) as child,
            COALESCE(DATE_FORMAT(DEP_DOM_TIME, '%H:%i'), '00:00') as dom_time_to_go
        FROM fo_bob
        WHERE ($statusCond)
          AND DATE(Departure_Date) = ?
          AND (Adults > 0 OR Children > 0)
        ORDER BY DEP_DOM_TIME
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($statuses, [$date]));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Optional: Add a function to trigger recalculation for a range of dates
function refreshMealSummaryForMonth($baseDate = null) {
    $baseDate = $baseDate ? new DateTime($baseDate) : new DateTime();
    $start = clone $baseDate;
    $start->modify('first day of this month');
    $end = clone $baseDate;
    $end->modify('last day of next month');

    $current = clone $start;
    while ($current <= $end) {
        $dateStr = $current->format('Y-m-d');
        error_log("Recalculating meal counts for $dateStr");
        $counts = calculateMealCountsForDate($dateStr);

        global $pdo;
        $insert = "
            INSERT INTO meal_daily_summary 
            (meal_date, BFADULT, BFCHILD, LNADULT, LNCHLD, DNADULT, DNCHLD)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                BFADULT = VALUES(BFADULT),
                BFCHILD = VALUES(BFCHILD),
                LNADULT = VALUES(LNADULT),
                LNCHLD = VALUES(LNCHLD),
                DNADULT = VALUES(DNADULT),
                DNCHLD = VALUES(DNCHLD),
                updated_at = CURRENT_TIMESTAMP
        ";
        $stmt = $pdo->prepare($insert);
        $stmt->execute([
            $dateStr,
            $counts['Breakfast']['adult'],
            $counts['Breakfast']['child'],
            $counts['Lunch']['adult'],
            $counts['Lunch']['child'],
            $counts['Dinner']['adult'],
            $counts['Dinner']['child']
        ]);

        $current->modify('+1 day');
    }
}