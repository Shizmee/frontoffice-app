<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['room_no'])) {
    echo json_encode(['success' => false, 'message' => 'Room number is required']);
    exit;
}

try {
    $room_no = $_GET['room_no'];
    
    $sql = "SELECT 
            Guest_Name,
            Room_No,
            Arrival_Date,
            Departure_Date,
            Room_Nights as no_of_nights,
            TA_Name as booking_agency,
            Meal_Plan,
            Adults,
            Children,
            Country,
            Room_Type,
            VIP,
            Comments,
            Special,
            Resv_Status
            FROM fo_bob 
            WHERE Room_No = :room_no 
            AND Arrival_Date <= CURDATE() 
            AND Departure_Date >= CURDATE()
            AND UPPER(Resv_Status) = 'CHECKED IN'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['room_no' => $room_no]);
    
    if ($guest = $stmt->fetch()) {
        echo json_encode([
            'success' => true,
            'data' => [
                'guest_name' => $guest['Guest_Name'],
                'room_no' => $guest['Room_No'],
                'booking_agency' => $guest['booking_agency'],
                'arrival' => $guest['Arrival_Date'],
                'departure' => $guest['Departure_Date'],
                'no_of_nights' => $guest['no_of_nights'],
                'meal_plan' => $guest['Meal_Plan'],
                'adults' => $guest['Adults'],
                'children' => $guest['Children'],
                'country' => $guest['Country'],
                'room_type' => $guest['Room_Type'],
                'vip' => $guest['VIP'],
                'comments' => $guest['Comments'],
                'special' => $guest['Special']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Guest not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>