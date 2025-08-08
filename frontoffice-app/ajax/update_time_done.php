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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        throw new Exception('Invalid ID');
    }
    
    $id = $data['id'];
    $time_done = date('H:i:s');
    
    $stmt = $pdo->prepare("UPDATE buggy_log SET time_done = ? WHERE id = ?");
    if (!$stmt->execute([$time_done, $id])) {
        throw new Exception('Failed to update time');
    }
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT time_done FROM buggy_log WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        throw new Exception('Failed to verify update');
    }
    
    echo json_encode([
        'success' => true,
        'time' => date('H:i', strtotime($result['time_done']))
    ]);

} catch (Exception $e) {
    error_log("Error in update_time_done.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>