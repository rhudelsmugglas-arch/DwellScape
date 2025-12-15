<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Check if user is admin
try {
    $stmt = $pdo->prepare("SELECT is_admin, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    $is_admin = ($user['role'] === 'admin') || ($user['is_admin'] ?? false);
    
    if (!$user || !$is_admin) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit();
    }
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error checking authorization']);
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

