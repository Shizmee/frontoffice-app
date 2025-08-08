<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: /frontoffice-app/auth/login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isManager() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'manager';
}
function isSupervisor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor';
}
function isTeamMember() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'team_member';
}

function logActivity($pdo, $action, $module, $record_id = null, $desc = '') {
    $sql = "INSERT INTO activity_logs (user_id, action, module, record_id, description) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $module,
        $record_id,
        $desc
    ]);
}
?>