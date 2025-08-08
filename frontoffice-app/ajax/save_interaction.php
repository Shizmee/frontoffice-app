<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Required fields
$required_fields = [
    'guest_name',
    'room_no',
    'booking_agency',
    'arrival',
    'departure',
    'no_of_nights',
    'time',
    'house_status',
    'guest_comments'
];

// Check required fields
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

$id = isset($data['interaction_id']) ? intval($data['interaction_id']) : null;

try {
    // Debug log
    error_log("Saving interaction data: " . json_encode($data));
    
    if ($id) {
        // Update existing record
        $sql = "UPDATE guest_interactions SET 
                entry_date = CURRENT_DATE,
                guest_name = :guest_name,
                room_no = :room_no,
                booking_agency = :booking_agency,
                arrival = :arrival,
                departure = :departure,
                no_of_nights = :no_of_nights,
                time = :time,
                house_status = :house_status,
                guest_comments = :guest_comments,
                associate_name = :associate_name,
                incident = :incident,
                department = :department,
                follow_up_by = :follow_up_by,
                recovery_action = :recovery_action,
                guest_satisfaction_level = :guest_satisfaction_level
                WHERE id = :id";
        
        $params = [
            'guest_name' => $data['guest_name'],
            'room_no' => $data['room_no'],
            'booking_agency' => $data['booking_agency'],
            'arrival' => $data['arrival'],
            'departure' => $data['departure'],
            'no_of_nights' => $data['no_of_nights'],
            'time' => $data['time'],
            'house_status' => $data['house_status'],
            'guest_comments' => $data['guest_comments'],
            'associate_name' => $data['associate_name'] ?? null,
            'incident' => $data['incident'] ?? null,
            'department' => $data['department'] ?? null,
            'follow_up_by' => $data['follow_up_by'] ?? null,
            'recovery_action' => $data['recovery_action'] ?? null,
            'guest_satisfaction_level' => $data['guest_satisfaction_level'] ?? null,
            'id' => $id
        ];
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        // Log activity
        logActivity($pdo, 'update', 'guest_interactions', $id, "Updated guest interaction for {$data['guest_name']}");
    } else {
        // Insert new record
        $sql = "INSERT INTO guest_interactions (
                entry_date, guest_name, room_no, booking_agency, arrival, 
                departure, no_of_nights, time, house_status,
                guest_comments, associate_name, incident, department,
                follow_up_by, recovery_action, guest_satisfaction_level)
                VALUES (
                CURRENT_DATE, :guest_name, :room_no, :booking_agency, :arrival,
                :departure, :no_of_nights, :time, :house_status,
                :guest_comments, :associate_name, :incident, :department,
                :follow_up_by, :recovery_action, :guest_satisfaction_level)";
        
        $params = [
            'guest_name' => $data['guest_name'],
            'room_no' => $data['room_no'],
            'booking_agency' => $data['booking_agency'],
            'arrival' => $data['arrival'],
            'departure' => $data['departure'],
            'no_of_nights' => $data['no_of_nights'],
            'time' => $data['time'],
            'house_status' => $data['house_status'],
            'guest_comments' => $data['guest_comments'],
            'associate_name' => $data['associate_name'] ?? null,
            'incident' => $data['incident'] ?? null,
            'department' => $data['department'] ?? null,
            'follow_up_by' => $data['follow_up_by'] ?? null,
            'recovery_action' => $data['recovery_action'] ?? null,
            'guest_satisfaction_level' => $data['guest_satisfaction_level'] ?? null
        ];
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        // Debug log
        if (!$result) {
            error_log("SQL Error: " . json_encode($stmt->errorInfo()));
        } else {
            error_log("Successfully saved new interaction");
            $id = $pdo->lastInsertId();
            error_log("New interaction ID: " . $id);
        }
        
        // Log activity
        logActivity($pdo, 'create', 'guest_interactions', $id, "Created guest interaction for {$data['guest_name']}");
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>