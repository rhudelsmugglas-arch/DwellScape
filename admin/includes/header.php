<?php
// Admin Header Component
// This file should be included after session and database setup

// Handle logout
if (isset($_POST['logout'])) {
    // Ensure session is started
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Include auth.php with correct path (from admin/includes/ to config/)
    require_once '../config/auth.php';
    
    // Clear cookie-based auth
    clearAuthCookie();
    
    // Destroy session
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array(); // Clear session data
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    
    // Redirect to home page
    if (!headers_sent()) {
        header('Location: ../home.php');
        exit();
    } else {
        echo '<script>window.location.href = "../home.php";</script>';
        exit();
    }
}

// Check cookie-based authentication first, then fall back to session
if (!isset($auth_user)) {
    require_once '../config/auth.php';
    $auth_user = verifyAuthToken();
    if ($auth_user) {
        // Set session variables from cookie auth for compatibility
        $_SESSION['user_id'] = $auth_user['user_id'];
        $_SESSION['username'] = $auth_user['username'];
        $_SESSION['email'] = $auth_user['email'];
        $_SESSION['role'] = $auth_user['role'];
        $_SESSION['is_admin'] = $auth_user['is_admin'];
    }
}

// Redirect if not logged in - go to home page (which has login modal)
if (!isset($_SESSION['user_id']) && !$auth_user) {
    if (!headers_sent()) {
        header('Location: ../home.php');
        exit();
    } else {
        echo '<script>window.location.href = "../home.php";</script>';
        exit();
    }
}

// Check if user is admin (check both role and is_admin for compatibility)
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
        
        if (!$user || !$is_admin) {
            header('Location: ../dashboard.php');
            exit();
        }
        
        // Update session role if needed
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['role'] = 'admin';
            $_SESSION['is_admin'] = true;
        }
    } catch(PDOException $e) {
        header('Location: ../dashboard.php');
        exit();
    }
}

// Final check - redirect non-admin users
if (!$is_admin) {
    header('Location: ../dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Dashboard - Dwellscape</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main-content">
            <div class="admin-container">

