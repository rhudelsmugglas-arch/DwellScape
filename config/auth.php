<?php
/**
 * Cookie-based Authentication System
 * Bypasses PHP sessions by using secure cookies with database tokens
 */

require_once __DIR__ . '/database.php';

/**
 * Generate a secure authentication token
 */
function generateAuthToken() {
    return bin2hex(random_bytes(32)); // 64 character hex string
}

/**
 * Create authentication token and set cookie
 */
function setAuthCookie($user_id, $username, $email, $role, $is_admin) {
    global $pdo;
    
    try {
        // Generate token
        $token = generateAuthToken();
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database
        $stmt = $pdo->prepare("
            INSERT INTO auth_tokens (user_id, token, expires_at, created_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE token = ?, expires_at = ?, created_at = NOW()
        ");
        $stmt->execute([$user_id, $token, date('Y-m-d H:i:s', $expires), $token, date('Y-m-d H:i:s', $expires)]);
        
        // Detect HTTPS
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                   (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                   (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
        
        // Set secure cookie
        setcookie('auth_token', $token, $expires, '/', '', $is_https, true); // httponly=true, secure based on HTTPS
        
        // Also set user info in cookie (for quick access, but verify token on each request)
        $user_data = json_encode([
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'is_admin' => $is_admin
        ]);
        setcookie('user_data', base64_encode($user_data), $expires, '/', '', $is_https, false); // Not httponly for JS access
        
        return true;
    } catch(PDOException $e) {
        error_log("Auth token creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify authentication token and return user data
 */
function verifyAuthToken() {
    global $pdo;
    
    if (!isset($_COOKIE['auth_token'])) {
        return null;
    }
    
    $token = $_COOKIE['auth_token'];
    
    try {
        // Verify token exists and hasn't expired
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.role, u.is_admin 
            FROM auth_tokens at
            JOIN users u ON at.user_id = u.id
            WHERE at.token = ? AND at.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Update last activity
            $update_stmt = $pdo->prepare("UPDATE auth_tokens SET last_used = NOW() WHERE token = ?");
            $update_stmt->execute([$token]);
            
            return [
                'user_id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'is_admin' => (bool)$user['is_admin']
            ];
        }
        
        // Token invalid or expired - clear cookie
        setcookie('auth_token', '', time() - 3600, '/');
        setcookie('user_data', '', time() - 3600, '/');
        
        return null;
    } catch(PDOException $e) {
        error_log("Auth token verification error: " . $e->getMessage());
        return null;
    }
}

/**
 * Clear authentication cookie and token
 */
function clearAuthCookie() {
    global $pdo;
    
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
        
        // Delete token from database
        try {
            $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE token = ?");
            $stmt->execute([$token]);
        } catch(PDOException $e) {
            error_log("Auth token deletion error: " . $e->getMessage());
        }
    }
    
    // Clear cookies
    setcookie('auth_token', '', time() - 3600, '/');
    setcookie('user_data', '', time() - 3600, '/');
}

/**
 * Get current authenticated user (convenience function)
 */
function getCurrentUser() {
    return verifyAuthToken();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return verifyAuthToken() !== null;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    $user = verifyAuthToken();
    return $user && ($user['is_admin'] || $user['role'] === 'admin');
}

// Create auth_tokens table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auth_tokens (
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(64) PRIMARY KEY,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            last_used DATETIME NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch(PDOException $e) {
    // Table might already exist, ignore
}

