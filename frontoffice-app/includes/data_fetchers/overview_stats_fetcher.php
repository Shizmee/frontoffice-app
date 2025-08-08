<?php
require_once __DIR__ . '/../db.php';

function getOverviewStats($date) {
    global $pdo;
    $stats = [
        'occupancy' => 0,
        'occ_rooms' => 0,
        'arr_rooms' => 0,
        'arrival_pax' => 0,
        'dep_rooms' => 0,
        'departure_pax' => 0,
        'inhouse_pax' => 0
    ];

    try {
        // 1. Get Occupancy, Arrival Rooms, Departure Rooms, and Guests from forecast table
        $stmt = $pdo->prepare("
            SELECT 
                PER_DEF_OCC as occupancy,
                ARRIVAL_ROOMS as arr_rooms,
                DEPARTURE_ROOMS as dep_rooms,
                GUESTS as inhouse_pax
            FROM forecast 
            WHERE DATE(CONSIDERED_DATE) = ?
        ");
        $stmt->execute([$date]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['occupancy'] = floatval($row['occupancy']);
            $stats['arr_rooms'] = intval($row['arr_rooms']);
            $stats['dep_rooms'] = intval($row['dep_rooms']);
            $stats['inhouse_pax'] = intval($row['inhouse_pax']);
        }

        // 2. Get Occupied Rooms from room_type
        $stmt = $pdo->prepare("
            SELECT OCCUPIED_ROOMS 
            FROM room_type 
            WHERE DATE(BUSINESS_DATE) = ?
        ");
        $stmt->execute([$date]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['occ_rooms'] = intval($row['OCCUPIED_ROOMS']);
        }

        // 3. Get Arrival Pax (from fo_bob for RESERVED and PROSPECT)
        $stmt = $pdo->prepare("
            SELECT SUM(COALESCE(Adults, 0) + COALESCE(Children, 0)) as arrival_pax
            FROM fo_bob 
            WHERE DATE(Arrival_Date) = ?
            AND Resv_Status IN ('RESERVED', 'PROSPECT')
        ");
        $stmt->execute([$date]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['arrival_pax'] = intval($row['arrival_pax']);
        }

        // 4. Get Departure Pax (from fo_bob for CHECKED IN and DUE IN)
        $stmt = $pdo->prepare("
            SELECT SUM(COALESCE(Adults, 0) + COALESCE(Children, 0)) as departure_pax
            FROM fo_bob 
            WHERE DATE(Departure_Date) = ?
            AND Resv_Status IN ('CHECKED IN', 'DUE IN', 'CHECKED-IN', 'CHECK IN', 'CHECKIN', 'CHECK-IN')
        ");
        $stmt->execute([$date]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['departure_pax'] = intval($row['departure_pax']);
        }

        return $stats;

    } catch (PDOException $e) {
        error_log("Error fetching overview stats: " . $e->getMessage());
        return $stats;
    }
}

/**
 * Fetches overview stats for a range of dates
 * 
 * @param string $start_date Start date in Y-m-d format
 * @param string $end_date End date in Y-m-d format
 * @return array Array of stats indexed by date
 */
function getOverviewStatsRange($start_date, $end_date) {
    $stats = [];
    $current_date = new DateTime($start_date);
    $end = new DateTime($end_date);

    while ($current_date <= $end) {
        $date_str = $current_date->format('Y-m-d');
        $stats[$date_str] = getOverviewStats($date_str);
        $current_date->modify('+1 day');
    }

    return $stats;
}