<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    // Generate a proper bcrypt hash for 'password123'
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    
    // Update the first user in the database
    $stmt = $pdo->prepare("UPDATE users SET email = 'admin@admin.com', password_hash = ? WHERE id = 1");
    $stmt->execute([$hash]);
    
    echo "<h1>Database Reset Successful!</h1>";
    echo "<p>Your login has been fixed.</p>";
    echo "<p><strong>Email:</strong> admin@admin.com</p>";
    echo "<p><strong>Password:</strong> password123</p>";
    echo '<p><a href="login.php">Click here to go to the login page</a></p>';
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
