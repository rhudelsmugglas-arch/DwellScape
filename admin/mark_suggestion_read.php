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

// Redirect if not logged in
if (!isset($_SESSION['user_id']) && !$auth_user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['is_admin'])) {
    $is_admin = $_SESSION['is_admin'];
} elseif ($auth_user) {
    $is_admin = $auth_user['is_admin'] || $auth_user['role'] === 'admin';
} else {
    try {
        $stmt = $pdo->prepare("SELECT is_admin, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $is_admin = ($user['role'] === 'admin') || ($user['is_admin'] ?? false);
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error checking authorization']);
        exit();
    }
}

if (!$is_admin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Mark suggestion as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $suggestion_id = (int)$_POST['id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE suggestions SET status = 'read' WHERE id = ?");
        $stmt->execute([$suggestion_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating suggestion']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

