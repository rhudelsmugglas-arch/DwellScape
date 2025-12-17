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

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (!headers_sent()) {
        header('Location: dashboard.php');
        exit();
    } else {
        // Fallback: Use JavaScript redirect if headers already sent
        echo '<script>window.location.href = "dashboard.php";</script>';
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_initial = trim($_POST['middle_initial'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $birthday = $_POST['birthday'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) || empty($birthday) || empty($gender)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (empty($birthday)) {
        $error_message = 'Please enter your birthday.';
    } else {
        // Validate age - must be at least 7 years old
        $birthday_date = new DateTime($birthday);
        $today = new DateTime();
        $age = $today->diff($birthday_date)->y;
        
        if ($age < 7) {
            $error_message = 'You must be at least 7 years old to create an account.';
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = 'Email already exists. Please use a different email.';
                } else {
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error_message = 'Username already exists. Please choose a different username.';
                    } else {
                        // Create new user with 'user' role
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Ensure middle_initial is empty string if not provided
                        if (empty($middle_initial)) {
                            $middle_initial = '';
                        }
                        
                        // Validate and format birthday
                        $birthday_formatted = $birthday;
                        if (!empty($birthday)) {
                            $birthday_date = DateTime::createFromFormat('Y-m-d', $birthday);
                            if ($birthday_date) {
                                $birthday_formatted = $birthday_date->format('Y-m-d');
                            }
                        }
                        
                        // Add name fields to database insert
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, middle_initial, last_name, birthday, gender, role, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user', 0)");
                        $stmt->execute([$username, $email, $hashed_password, $first_name, $middle_initial ?: null, $last_name, $birthday_formatted ?: null, $gender]);
                        
                        // Check if AJAX request (from modal)
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            if (!headers_sent()) {
                                header('Content-Type: application/json');
                            }
                            echo json_encode(['success' => true, 'message' => 'Account created successfully! You can now login.']);
                            exit();
                        }
                        
                        // Redirect to login page with success message
                        $_SESSION['signup_success'] = 'Account created successfully! You can now login.';
                        if (!headers_sent()) {
                            header('Location: login.php');
                            exit();
                        } else {
                            // Fallback: Use JavaScript redirect if headers already sent
                            echo '<script>window.location.href = "login.php";</script>';
                            exit();
                        }
                    }
                }
            } catch (PDOException $e) {
                // Log the actual error for debugging
                error_log("Signup error: " . $e->getMessage());
                error_log("Signup error trace: " . $e->getTraceAsString());
                
                // Return more specific error message
                $error_message = 'Database error: ' . $e->getMessage();
                
                // For AJAX requests, return JSON with error details
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    if (!headers_sent()) {
                        header('Content-Type: application/json');
                    }
                    echo json_encode([
                        'success' => false, 
                        'error' => 'An error occurred. Please try again.',
                        'debug' => $e->getMessage() // Remove this in production
                    ]);
                    exit();
                }
            }
        }
    }
    
    // Check if AJAX request and return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && !empty($error_message)) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
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
    <title>Sign Up - Dwellscape</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #ffffff;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 8px;
            height: 100%;
            background: linear-gradient(180deg, #C3B091 0%, #9A8B6F 100%);
            z-index: 1;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            right: 0;
            width: 8px;
            height: 100%;
            background: linear-gradient(180deg, #C3B091 0%, #9A8B6F 100%);
            z-index: 1;
        }

        .auth-container {
            max-width: 900px;
            width: 100%;
            padding: 60px 80px;
            background: #ffffff;
            box-shadow: none;
            border-radius: 0;
            border: none;
            position: relative;
            z-index: 2;
        }

        .auth-container::before {
            display: none;
        }

        .auth-header {
            text-align: left;
            margin-bottom: 40px;
        }

        .auth-header h1 {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .auth-header p {
            font-size: 16px;
            color: #6b7280;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
            z-index: 2;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #ffffff;
            font-weight: 400;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%239ca3af' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 45px;
            padding-left: 20px;
            cursor: pointer;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #C3B091;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(195, 176, 145, 0.1);
        }

        .form-group input:focus ~ .input-icon {
            color: #C3B091;
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
        }

        .password-toggle:hover {
            color: #C3B091;
        }

        .form-group.password-group .input-with-icon input {
            padding-right: 50px;
        }

        .btn-primary {
            width: 100%;
            padding: 18px 32px;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(195, 176, 145, 0.25);
            margin-top: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(195, 176, 145, 0.35);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .error-message,
        .success-message {
            grid-column: 1 / -1;
            margin-bottom: 24px;
        }

        .auth-links {
            text-align: center;
            margin-top: 24px;
        }

        .auth-links a {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .auth-links a strong {
            color: #374151;
            font-weight: 600;
        }

        .auth-links a:hover {
            color: #C3B091;
        }

        .auth-links a:hover strong {
            color: #9A8B6F;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .auth-container {
                padding: 40px 30px;
            }

            body::before,
            body::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Join us today</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name">First Name <span style="color: #dc3545;">*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="middle_initial">Middle Initial</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="middle_initial" name="middle_initial" placeholder="M.I." maxlength="1" style="text-transform: uppercase;">
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name <span style="color: #dc3545;">*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-at input-icon"></i>
                        <input type="text" id="username" name="username" placeholder="Choose a username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="form-group password-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group password-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="birthday">Birthday</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar input-icon"></i>
                        <input type="date" id="birthday" name="birthday" max="<?php echo date('Y-m-d', strtotime('-7 years')); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <div class="input-with-icon">
                        <select id="gender" name="gender" required>
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary">CREATE ACCOUNT</button>
        </form>

        <div class="auth-links">
            <a href="login.php">Already have an account? <strong>Sign in</strong></a>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId === 'password' ? 'toggleIcon1' : 'toggleIcon2');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>