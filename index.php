<?php
// Simple redirect to login or dashboard
require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/pages/dashboard.php");
} else {
    header("Location: " . BASE_URL . "/login.php");
}
exit;
