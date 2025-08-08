<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['module']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$id = $input['id'];
$module = $input['module'];
$status = $input['status'];

try {
    // Start transaction
    $pdo->beginTransaction();

    switch ($module) {
        case 'guest_calls':
            $validStatuses = ['open', 'in_progress', 'closed'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status for guest calls');
            }
            $sql = "UPDATE guest_calls SET status = ? WHERE id = ?";
            break;

        case 'guest_interactions':
            $validStatuses = ['pending', 'in_progress', 'resolved'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status for guest interactions');
            }
            $sql = "UPDATE guest_interactions SET status = ? WHERE id = ?";
            break;

        case 'excursions':
            $validStatuses = ['booked', 'confirmed', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status for excursions');
            }
            $sql = "UPDATE excursions SET status = ? WHERE id = ?";
            break;

        case 'dinner_reservations':
            $validStatuses = ['confirmed', 'seated', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status for dinner reservations');
            }
            $sql = "UPDATE dinner_reservations SET status = ? WHERE id = ?";
            break;

        default:
            throw new Exception('Invalid module');
    }

    // Update status
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $id]);

    // Log the activity
    logActivity(
        $pdo,
        'update',
        $module,
        $id,
        "Updated status to: $status"
    );

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
