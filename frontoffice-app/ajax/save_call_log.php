<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid data received');
    }

    $fields = [
        'guest_name', 'room_no', 'booking_agency', 'arrival', 'departure',
        'no_of_nights', 'time', 'house_status', 'guest_comments', 'associate_name',
        'incident', 'department', 'follow_up_by', 'recovery_action', 'guest_satisfaction_level'
    ];

    $params = [];
    foreach ($fields as $field) {
        $params[$field] = $data[$field] ?? null;
    }

    if (isset($data['entry_id'])) {
        // Update existing entry
        $sql = "UPDATE call_log SET 
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
        $params['id'] = $data['entry_id'];
    } else {
        // Insert new entry
        $sql = "INSERT INTO call_log (
                guest_name, room_no, booking_agency, arrival, departure,
                no_of_nights, time, house_status, guest_comments, associate_name,
                incident, department, follow_up_by, recovery_action, guest_satisfaction_level)
                VALUES (
                :guest_name, :room_no, :booking_agency, :arrival, :departure,
                :no_of_nights, :time, :house_status, :guest_comments, :associate_name,
                :incident, :department, :follow_up_by, :recovery_action, :guest_satisfaction_level)";
    }

    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) {
        throw new Exception('Failed to save entry');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}