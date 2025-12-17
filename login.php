<?php
// Ensure output buffering is enabled at server level
ini_set('output_buffering', 'On');
ini_set('implicit_flush', 'Off');

// Start output buffering if not already started
if (!ob_get_level()) {
    ob_start();
}

// Clear any existing output that might have been sent
if (ob_get_length() > 0) {
    ob_clean();
}

// Start session (suppress warning if headers already sent, but try to prevent it)
if (!headers_sent()) {
    session_start();
} else {
    // If headers already sent, try to start session anyway
    @session_start();
}
require_once 'config/database.php';

$error_message = '';
$success_message = '';

// Check for signup success message
if (isset($_SESSION['signup_success'])) {
    $success_message = $_SESSION['signup_success'];
    unset($_SESSION['signup_success']); // Clear the message after displaying
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Check user role and redirect accordingly
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/admin.php');
    } else {
    header('Location: dashboard.php');
    }
    exit();
}

$forgot_password_error = '';
$forgot_password_success = '';

// Handle forgot password form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $forgot_password_error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $forgot_password_error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token in database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
                $stmt->execute([$reset_token, $reset_token_expires, $email]);
                
                // In a real application, you would send an email here
                // For demo purposes, we'll show the reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $reset_token;
                $forgot_password_success = "Password reset link has been generated. In a real application, this would be sent to your email. For demo purposes, here's your reset link: <br><br><a href='$reset_link' target='_blank' style='color: #C3B091; text-decoration: underline;'>$reset_link</a>";
            } else {
                $forgot_password_error = 'No account found with that email address.';
            }
        } catch (PDOException $e) {
            $forgot_password_error = 'Database error. Please try again.';
        }
    }
    
    // Return JSON response for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => !empty($forgot_password_success),
            'error' => $forgot_password_error,
            'message' => $forgot_password_success
        ]);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['forgot_password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, password, is_admin, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // ONLY username 'admin' can be admin - all other users are regular users
                $is_admin_username = (strtolower(trim($username)) === 'admin');
                
                // Determine user role: ONLY 'admin' username can be admin
                if ($is_admin_username) {
                    $user_role = 'admin';
                } else {
                    $user_role = 'user';
                }
                
                // Force update user role in database based on username only
                if ($is_admin_username) {
                    try {
                        // Set admin user as admin
                        $update_stmt = $pdo->prepare("UPDATE users SET role = 'admin', is_admin = 1 WHERE id = ?");
                        $update_stmt->execute([$user['id']]);
                        $user_role = 'admin'; // Ensure it's set to admin
                    } catch(PDOException $e) {
                        // Ignore update error, continue with login
                    }
                } else {
                    try {
                        // Force all other users to be regular users (including kuysrhod)
                        $update_stmt = $pdo->prepare("UPDATE users SET role = 'user', is_admin = 0 WHERE id = ?");
                        $update_stmt->execute([$user['id']]);
                        $user_role = 'user'; // Ensure it's set to user
                    } catch(PDOException $e) {
                        // Ignore update error, continue with login
                    }
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user_role;
                $_SESSION['is_admin'] = ($user_role === 'admin');
                
                // Check if AJAX request (from modal)
                // Check multiple ways to detect AJAX request
                $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
                          (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
                
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    $redirect_url = ($user_role === 'admin') ? 'admin/admin.php' : 'dashboard.php';
                    // Ensure we always send redirect URL and role
                    $response = [
                        'success' => true, 
                        'redirect' => $redirect_url, 
                        'role' => $user_role, 
                        'username' => $user['username'],
                        'is_admin' => ($user_role === 'admin')
                    ];
                    echo json_encode($response);
                    exit();
                }
                
                // Redirect admin to admin page, regular users to dashboard
                if ($user_role === 'admin') {
                    header('Location: admin/admin.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error. Please try again.';
        }
    }
    
    // Check if AJAX request and return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && !empty($error_message)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error_message]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Auth System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .auth-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .auth-modal-overlay.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-modal {
            background: #fafafa;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            padding: 48px 40px;
            width: 90%;
            max-width: 420px;
            position: relative;
            animation: slideUp 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        .auth-modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-modal-header h1 {
            color: #000000;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .auth-modal-header p {
            color: #2d3748;
            font-size: 16px;
            font-weight: 500;
        }

        .auth-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.08);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            font-size: 20px;
            color: #4a5568;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .auth-modal-close:hover {
            background: rgba(0, 0, 0, 0.15);
            color: #000000;
        }

        .auth-modal .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .auth-modal .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #000000;
            font-weight: 700;
            font-size: 15px;
        }

        .auth-modal .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .auth-modal .input-icon {
            position: absolute;
            left: 14px;
            color: #4a5568;
            font-size: 18px;
            z-index: 2;
        }

        .auth-modal .form-group input {
            width: 100%;
            padding: 16px 18px 16px 50px;
            border: 2.5px solid #cbd5e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s ease;
            background: #ffffff;
            color: #2d3748;
        }

        .auth-modal .form-group input::placeholder {
            color: #718096;
            font-weight: 400;
        }

        .auth-modal .form-group input:focus {
            outline: none;
            border-color: #7a6a4f;
            border-width: 3px;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(122, 106, 79, 0.2), 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .auth-modal .btn {
            width: 100%;
            padding: 18px 20px;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .auth-modal .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(195, 176, 145, 0.45);
            background: linear-gradient(135deg, #9A8B6F 0%, #7a6a4f 100%);
        }

        .auth-modal .error-message {
            background: #fff5f5;
            color: #c53030;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .auth-modal .success-message {
            background: #f0fff4;
            color: #2f855a;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .auth-modal .auth-links {
            text-align: center;
            margin-top: 24px;
        }

        .auth-modal .auth-links a {
            color: #4a5568;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: color 0.3s ease;
        }

        .auth-modal .auth-links a:hover {
            color: #7a6a4f;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Dwellscape Staycation</h1>
            <p>Sign in to your account</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
            </div>

            <div class="form-group password-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>

        <div class="auth-links">
            <a href="javascript:void(0)" onclick="openForgotPasswordModal()">Forgot your password?</a>
            <br><br>
            <a href="signup.php">Don't have an account? Sign up</a>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="auth-modal-overlay" id="forgotPasswordModal">
        <div class="auth-modal">
            <button class="auth-modal-close" onclick="closeForgotPasswordModal()">&times;</button>
            <div class="auth-modal-header">
                <h1>Forgot Password</h1>
                <p>Enter your email to reset your password</p>
            </div>
            <div class="error-message" id="forgotPasswordError" style="display: <?php echo !empty($forgot_password_error) ? 'block' : 'none'; ?>;">
                <?php echo htmlspecialchars($forgot_password_error); ?>
            </div>
            <div class="success-message" id="forgotPasswordSuccess" style="display: <?php echo !empty($forgot_password_success) ? 'block' : 'none'; ?>;">
                <?php echo $forgot_password_success; ?>
            </div>
            <form method="POST" id="forgotPasswordForm" action="">
                <input type="hidden" name="forgot_password" value="1">
                <div class="form-group">
                    <label for="forgotEmail">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="forgotEmail" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>
            <div class="auth-links">
                <a href="javascript:void(0)" onclick="closeForgotPasswordModal()">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        function openForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.remove('active');
            document.body.style.overflow = '';
            // Reset form and messages
            document.getElementById('forgotPasswordForm').reset();
            document.getElementById('forgotPasswordError').style.display = 'none';
            document.getElementById('forgotPasswordSuccess').style.display = 'none';
        }

        // Handle form submission via AJAX
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const errorDiv = document.getElementById('forgotPasswordError');
            const successDiv = document.getElementById('forgotPasswordSuccess');
            
            // Hide previous messages
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            fetch('login.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successDiv.innerHTML = data.message;
                    successDiv.style.display = 'block';
                    document.getElementById('forgotPasswordForm').reset();
                } else {
                    errorDiv.textContent = data.error;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            });
        });

        // Close modal when clicking outside
        document.getElementById('forgotPasswordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeForgotPasswordModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeForgotPasswordModal();
            }
        });
    </script>
</body>
</html>