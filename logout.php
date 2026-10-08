<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/app.php';

// Log activity if logged in
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/functions.php';
    $pdo = getDBConnection();
    log_activity($pdo, $_SESSION['user_id'], 'Logout', 'User logged out.');
}

// Destroy session
$_SESSION = [];
session_destroy();

header("Location: " . BASE_URL . "/login.php");
exit;
