<?php
// Global Helper Functions

/**
 * Safely output string to HTML
 */
function esc($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function format_currency($amount) {
    global $settings;
    $symbol = $settings['currency_symbol'] ?? '$';
    return $symbol . ' ' . number_format((float)$amount, 2);
}

/**
 * Load settings from database
 */
function load_settings($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

/**
 * Set flash message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Display and clear flash message
 */
function display_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $type = $flash['type'];
        $message = esc($flash['message']);
        
        $alertClass = "alert";
        if ($type === 'success') $alertClass .= " alert-success";
        elseif ($type === 'error') $alertClass .= " alert-danger";
        elseif ($type === 'warning') $alertClass .= " alert-warning";
        else $alertClass .= " alert-info";
        
        echo "<div class='{$alertClass}' id='flashMessage'>{$message}</div>";
        unset($_SESSION['flash']);
    }
}

/**
 * Log activity
 */
function log_activity($pdo, $user_id, $action, $description) {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $description]);
}
