<?php
// Authentication & Core Bootstrap

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/functions.php';

// Initialize global PDO connection
$pdo = getDBConnection();

// Load global settings
$settings = load_settings($pdo);

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect to login if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

// Redirect if already logged in
function redirect_if_logged_in() {
    if (is_logged_in()) {
        header("Location: " . BASE_URL . "/pages/dashboard.php");
        exit;
    }
}

// Check role permissions
function has_permission($allowed_roles) {
    if (!is_logged_in()) return false;
    $current_role = $_SESSION['user_role'] ?? '';
    if ($current_role === 'admin') return true; // Admin has all access
    return in_array($current_role, $allowed_roles);
}

// Enforce permission
function require_permission($allowed_roles) {
    if (!has_permission($allowed_roles)) {
        http_response_code(403);
        die("403 Forbidden. You do not have permission to access this page.");
    }
}

// Auto-run login check unless on login page itself
$current_script = basename($_SERVER['SCRIPT_NAME']);
if ($current_script !== 'login.php') {
    require_login();
}
