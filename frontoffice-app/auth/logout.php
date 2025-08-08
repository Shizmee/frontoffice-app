<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if (isLoggedIn()) {
    logActivity($pdo, 'logout', 'auth', null, "User {$_SESSION['username']} logged out");
    session_destroy();
}

header('Location: login.php');
exit();
?>
