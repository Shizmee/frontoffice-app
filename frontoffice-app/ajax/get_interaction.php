<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM guest_interactions WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    if ($row = $stmt->fetch()) {
        echo json_encode($row);
    } else {
        echo json_encode(['success' => false, 'message' => 'Interaction not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>