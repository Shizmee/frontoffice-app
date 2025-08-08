<?php
error_log("Request received at save_excursion.php");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content type: " . $_SERVER['CONTENT_TYPE'] ?? 'not set');
error_log("Raw input: " . file_get_contents('php://input'));

require_once '../includes/db.php';
require_once '../includes/auth.php';

// Ensure user is authenticated
requireAuth();

// Set proper content type for JSON response
header('Content-Type: application/json');

try {
    // Get and validate JSON input
    $input = file_get_contents('php://input');
    if (!$input) {
        throw new Exception('No input data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    // Debug log
    error_log("Received data: " . print_r($data, true));

    // Validate required fields
    $required_fields = ['room_number', 'guest_name', 'excursion_type_id', 'date', 'time', 'num_persons'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing_fields[] = $field;
        }
    }
    if (!empty($missing_fields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
    }

    // Validate numeric fields
    if (!is_numeric($data['num_persons']) || $data['num_persons'] < 1) {
        throw new Exception('Invalid number of persons');
    }
    if (!is_numeric($data['excursion_type_id'])) {
        throw new Exception('Invalid excursion type');
    }

    // Validate date and time
    if (!strtotime($data['date'])) {
        throw new Exception('Invalid date format');
    }
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['time'])) {
        throw new Exception('Invalid time format');
    }

    // Format dates
    $date = date('Y-m-d', strtotime($data['date']));
    $excursion_date = date('Y-m-d H:i:s', strtotime($data['date'] . ' ' . $data['time']));
    $excursion_time = date('H:i:s', strtotime($data['time']));

    // Debug log
    error_log("Formatted dates - date: $date, excursion_date: $excursion_date, time: $excursion_time");

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO excursions (
            room_number,
            guest_name,
            excursion_type_id,
            date,
            excursion_date,
            excursion_time,
            num_persons,
            notes,
            status,
            created_by
        ) VALUES (
            :room_number,
            :guest_name,
            :excursion_type_id,
            :date,
            :excursion_date,
            :excursion_time,
            :num_persons,
            :notes,
            'booked',
            :created_by
        )
    ");

    $params = [
        'room_number' => $data['room_number'],
        'guest_name' => $data['guest_name'],
        'excursion_type_id' => intval($data['excursion_type_id']),
        'date' => $date,
        'excursion_date' => $excursion_date,
        'excursion_time' => $excursion_time,
        'num_persons' => intval($data['num_persons']),
        'notes' => $data['notes'] ?? null,
        'created_by' => $_SESSION['user_id'] ?? 1
    ];

    // Debug log
    error_log("SQL parameters: " . print_r($params, true));

    $result = $stmt->execute($params);

    if (!$result) {
        throw new Exception('Database error: ' . implode(' ', $stmt->errorInfo()));
    }

    $excursionId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Excursion saved successfully',
        'id' => $excursionId
    ]);

} catch (Exception $e) {
    error_log("Error saving excursion: " . $e->getMessage());
    error_log("Input data: " . print_r($data ?? [], true));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}