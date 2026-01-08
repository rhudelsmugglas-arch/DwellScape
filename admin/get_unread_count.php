<?php
// Start session
if (!headers_sent()) {
    session_start();
} else {
    @session_start();
}
require_once '../config/database.php';
require_once '../config/auth.php';

// Check cookie-based authentication first, then fall back to session
$auth_user = verifyAuthToken();
if ($auth_user) {
    // Set session variables from cookie auth for compatibility
    $_SESSION['user_id'] = $auth_user['user_id'];
    $_SESSION['username'] = $auth_user['username'];
    $_SESSION['email'] = $auth_user['email'];
    $_SESSION['role'] = $auth_user['role'];
    $_SESSION['is_admin'] = $auth_user['is_admin'];
}

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['is_admin'])) {
    $is_admin = $_SESSION['is_admin'];
} elseif ($auth_user) {
    $is_admin = $auth_user['is_admin'] || $auth_user['role'] === 'admin';
}

header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

// Get unread count
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM suggestions WHERE status = 'unread'");
    $result = $stmt->fetch();
    $count = $result['count'] ?? 0;
    echo json_encode(['success' => true, 'count' => (int)$count]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'count' => 0]);
}
?>

