<?php
/**
 * Script to create admin account
 * Run this file once to create the admin user
 * Username: admin
 * Password: Admin123!
 */

require_once 'config/database.php';

$admin_username = 'admin';
$admin_password = 'Admin123!';
$admin_email = 'admin@dwellscape.com';

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR is_admin = 1");
    $stmt->execute([$admin_username]);
    $existing_admin = $stmt->fetch();
    
    if ($existing_admin) {
        // Update existing admin password and role
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, email = ?, is_admin = 1, role = 'admin' WHERE username = ? OR id = ?");
        $stmt->execute([$hashed_password, $admin_email, $admin_username, $existing_admin['id']]);
        echo "<h2>Admin account updated successfully!</h2>";
        echo "<p>Username: <strong>$admin_username</strong></p>";
        echo "<p>Password: <strong>$admin_password</strong></p>";
        echo "<p>Email: <strong>$admin_email</strong></p>";
        echo "<p>Role: <strong>admin</strong></p>";
        echo "<p><a href='../home.php'>Go to Login</a></p>";
    } else {
        // Create new admin account with admin role
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin, role) VALUES (?, ?, ?, 1, 'admin')");
        $stmt->execute([$admin_username, $admin_email, $hashed_password]);
        
        echo "<h2>Admin account created successfully!</h2>";
        echo "<p>Username: <strong>$admin_username</strong></p>";
        echo "<p>Password: <strong>$admin_password</strong></p>";
        echo "<p>Email: <strong>$admin_email</strong></p>";
        echo "<p>Role: <strong>admin</strong></p>";
        echo "<p><a href='../home.php'>Go to Login</a></p>";
    }
} catch(PDOException $e) {
    echo "<h2>Error creating admin account</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

