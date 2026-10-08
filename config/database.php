<?php
// Database configuration
// Change these values based on your InfinityFree credentials or local setup

define('DB_HOST', 'sql113.infinityfree.com');
define('DB_NAME', 'if0_42179293_pos');
define('DB_USER', 'if0_42179293');
define('DB_PASS', '3dY78nvWmnW');

function getDBConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // In production, do not output the raw error message to the user
        // Log it and show a generic message
        die("Database Connection Failed. Please check config/database.php");
    }
}
