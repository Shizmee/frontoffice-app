<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid ID');
    }

    $stmt = $pdo->prepare("SELECT * FROM call_log WHERE id = ?");
    if (!$stmt->execute([$_GET['id']])) {
        throw new Exception('Failed to fetch entry');
    }

    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$entry) {
        throw new Exception('Entry not found');
    }

    echo json_encode(['success' => true, 'data' => $entry]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}