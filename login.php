<?php
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

session_start();
require_once 'config/database.php';

// If GET request or not AJAX POST, handle redirect
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: home.php');
    exit();
}

// Check if this is a regular form POST (not AJAX)
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

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
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user_role;
            $_SESSION['is_admin'] = $is_admin;
            
            // Regenerate session ID for security (PHP automatically copies session data)
            session_regenerate_id(true);
            
            // Get session info for debugging
            $session_id = session_id();
            $session_name = session_name();
            $cookie_params = session_get_cookie_params();
            
            // Debug: Log session status
            error_log("Login - Session ID: " . $session_id);
            error_log("Login - Session name: " . $session_name);
            error_log("Login - User ID in session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
            error_log("Login - All session data: " . json_encode($_SESSION ?? []));
            error_log("Login - Session cookie params: " . json_encode($cookie_params));
            error_log("Login - Cookies received: " . json_encode($_COOKIE ?? []));
            error_log("Login - HTTPS detected: " . ($is_https ? 'YES' : 'NO'));
            
            // Session will be automatically written when script ends
            // PHP will send Set-Cookie header automatically
            
            // Handle response based on request type
            $redirect_url = $is_admin ? 'admin/admin.php' : 'dashboard.php';
            
            if ($is_ajax) {
                // AJAX request - return JSON
                $response = [
                    'success' => true,
                    'redirect' => $redirect_url,
                    'role' => $user_role,
                    'username' => $user['username'],
                    'session_id' => $session_id
                ];
            } else {
                // Regular form POST - redirect directly (session cookie will be sent)
                header('Location: ' . $redirect_url);
                exit();
            }
        } else {
            $response = ['success' => false, 'error' => 'Invalid username or password.'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'error' => 'Database error. Please try again.'];
    }
}

// Set headers and send response
if ($is_ajax) {
    // AJAX request - return JSON
    if (!headers_sent()) {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
    echo json_encode($response);
} else {
    // Regular form POST - should have redirected already, but fallback for errors
    if (isset($response) && !$response['success']) {
        // Error case - redirect back to home with error
        header('Location: home.php?error=' . urlencode($response['error'] ?? 'Login failed'));
    } else {
        // Should not reach here if login succeeded
        header('Location: home.php');
    }
}
exit();
