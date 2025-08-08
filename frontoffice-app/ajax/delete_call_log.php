<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        throw new Exception('Invalid ID');
    }

    $stmt = $pdo->prepare("DELETE FROM call_log WHERE id = ?");
    if (!$stmt->execute([$data['id']])) {
        throw new Exception('Failed to delete entry');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}