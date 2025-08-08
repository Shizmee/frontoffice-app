<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate input
    if (!$username || !$currentPassword || !$newPassword || !$confirmPassword) {
        $_SESSION['error'] = "All fields are required.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "New password and confirmation do not match.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    if (strlen($newPassword) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Verify current user and password
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ? AND id = ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $user['id']]);
    
    // Log the activity
    logActivity($pdo, 'update', 'auth', $user['id'], 'Password changed');
    
    $_SESSION['success'] = "Password has been changed successfully.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header('Location: /frontoffice-app/pages/dashboard.php');
    exit();
}
?>
