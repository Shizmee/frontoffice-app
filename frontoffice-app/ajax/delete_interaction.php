<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    // Get guest name for activity log
    $stmt = $pdo->prepare("SELECT guest_name FROM guest_interactions WHERE id = ?");
    $stmt->execute([$id]);
    $guest = $stmt->fetch();
    
    // Delete the record
    $stmt = $pdo->prepare("DELETE FROM guest_interactions WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        // Log activity
        logActivity($pdo, 'delete', 'guest_interactions', $id, "Deleted guest interaction for {$guest['guest_name']}");
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete interaction');
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>