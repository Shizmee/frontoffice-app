<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid ID');
    }

    $id = intval($_GET['id']);
    
    $stmt = $pdo->prepare("SELECT * FROM buggy_log WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entry) {
        throw new Exception('Entry not found');
    }
    
    // Format times for display
    if ($entry['time']) {
        $entry['time'] = date('H:i', strtotime($entry['time']));
    }
    if ($entry['time_done']) {
        $entry['time_done'] = date('H:i', strtotime($entry['time_done']));
    }
    if ($entry['fup_time']) {
        $entry['fup_time'] = date('H:i', strtotime($entry['fup_time']));
    }
    
    echo json_encode([
        'success' => true,
        'id' => $entry['id'],
        'call_from' => $entry['call_from'],
        'caller_name' => $entry['caller_name'],
        'guest_request' => $entry['guest_request'],
        'concern_department' => $entry['concern_department'],
        'comments' => $entry['comments'],
        'status_followup' => $entry['status_followup'],
        'time' => $entry['time'],
        'time_done' => $entry['time_done'],
        'fup_with_guest' => $entry['fup_with_guest'],
        'fup_time' => $entry['fup_time']
    ]);

} catch (Exception $e) {
    error_log("Error in get_entry.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>