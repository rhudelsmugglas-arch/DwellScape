<?php
// Use cookie-based authentication instead of sessions
// No need for output buffering or session configuration

require_once 'config/database.php';
require_once 'config/auth.php';

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
            
            // Use cookie-based authentication instead of sessions
            $auth_set = setAuthCookie($user['id'], $user['username'], $user['email'], $user_role, $is_admin);
            
            if ($auth_set) {
                error_log("Login - Auth cookie set successfully for user: " . $user['username']);
            } else {
                error_log("Login - WARNING: Failed to set auth cookie");
            }
            
            // Return JSON response
            $redirect_url = $is_admin ? 'admin/admin.php' : 'dashboard.php';
            $response = [
                'success' => true,
                'redirect' => $redirect_url,
                'role' => $user_role,
                'username' => $user['username']
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
