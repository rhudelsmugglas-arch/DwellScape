<?php
// Start output buffering FIRST to catch any BOM/whitespace
if (!ob_get_level()) {
    ob_start();
}

// Configure session cookie parameters BEFORE session_start()
// Detect HTTPS (Railway uses HTTPS, but check headers for proxy)
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_secure', $is_https ? '1' : '0'); // Set based on actual HTTPS status
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', ''); // Empty for current domain

require_once 'config/database.php';
require_once 'config/session_handler.php';

// Use database session handler (file sessions don't work on Railway)
$session_handler = new DatabaseSessionHandler($pdo);
session_set_save_handler($session_handler, true);

// Start session
session_start();

error_log("Login - Session started using database handler");
error_log("Login - Session ID: " . session_id());
error_log("Login - Session status: " . session_status() . " (2=PHP_SESSION_ACTIVE)");

// If GET request, redirect to home
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: home.php');
    exit();
}

// Handle forgot password
if (isset($_POST['forgot_password'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $response = ['success' => false, 'error' => 'Please enter your email address.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['success' => false, 'error' => 'Please enter a valid email address.'];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $reset_token = bin2hex(random_bytes(32));
                $reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
                $stmt->execute([$reset_token, $reset_token_expires, $email]);
                
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $reset_token;
                $response = [
                    'success' => true,
                    'message' => "Password reset link: <a href='$reset_link' target='_blank'>$reset_link</a>"
                ];
            } else {
                $response = ['success' => false, 'error' => 'No account found with that email address.'];
            }
        } catch (PDOException $e) {
            $response = ['success' => false, 'error' => 'Database error. Please try again.'];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle login
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $response = ['success' => false, 'error' => 'Please fill in all fields.'];
} else {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set role: only 'admin' username is admin
            $is_admin = (strtolower(trim($username)) === 'admin');
            $user_role = $is_admin ? 'admin' : 'user';
            
            // Update role in database
            try {
                $update_stmt = $pdo->prepare("UPDATE users SET role = ?, is_admin = ? WHERE id = ?");
                $update_stmt->execute([$user_role, $is_admin ? 1 : 0, $user['id']]);
            } catch(PDOException $e) {
                // Ignore update error
            }
            
            // Verify session is active before setting data
            if (session_status() !== PHP_SESSION_ACTIVE) {
                error_log("Login - ERROR: Session not active before setting data! Status: " . session_status());
                session_start();
                error_log("Login - Session restarted, status: " . session_status());
            }
            
            // Ensure session is active before setting data
            if (session_status() !== PHP_SESSION_ACTIVE) {
                error_log("Login - Session not active before setting data, starting...");
                session_start();
            }
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user_role;
            $_SESSION['is_admin'] = $is_admin;
            
            // Get session ID (must exist now)
            $session_id = session_id();
            if (empty($session_id)) {
                error_log("Login - ERROR: Session ID is empty after setting data!");
                // Generate a session ID if it doesn't exist
                if (function_exists('session_create_id')) {
                    $session_id = session_create_id();
                    session_id($session_id);
                    session_start();
                }
            }
            
            $session_name = session_name();
            $cookie_params = session_get_cookie_params();
            
            error_log("Login - Session ID: " . ($session_id ?: 'EMPTY'));
            error_log("Login - Session name: " . $session_name);
            error_log("Login - Session status: " . session_status() . " (2=PHP_SESSION_ACTIVE)");
            error_log("Login - User ID in session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
            error_log("Login - All session data: " . json_encode($_SESSION ?? []));
            
            // Force session write to database
            if ($session_id) {
                session_write_close();
                error_log("Login - Session written to database, ID: " . $session_id);
                
                // Restart session with same ID
                session_id($session_id);
                session_start();
                
                // Verify data was read back
                error_log("Login - After restart - Session ID: " . session_id());
                error_log("Login - After restart - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
                error_log("Login - After restart - Session data: " . json_encode($_SESSION ?? []));
            } else {
                error_log("Login - CRITICAL: Cannot write session - no session ID!");
            }
            
            // Return JSON response
            $redirect_url = $is_admin ? 'admin/admin.php' : 'dashboard.php';
            $response = [
                'success' => true,
                'redirect' => $redirect_url,
                'role' => $user_role,
                'username' => $user['username'],
                'session_id' => $session_id // Include for debugging
            ];
        } else {
            $response = ['success' => false, 'error' => 'Invalid username or password.'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'error' => 'Database error. Please try again.'];
    }
}

// Set headers AFTER setting cookies
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
}

echo json_encode($response);
exit();
