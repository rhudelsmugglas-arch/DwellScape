<?php
session_start();
require_once 'config/database.php';

// Redirect if not logged in - go to home page (which has login modal)
if (!isset($_SESSION['user_id'])) {
    if (!headers_sent()) {
        header('Location: home.php');
        exit();
    } else {
        echo '<script>window.location.href = "home.php";</script>';
        exit();
    }
}

$upload_message = '';
$upload_error = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $upload_dir = 'uploads/profile_pictures/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['profile_picture'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Check for upload errors
    if ($file_error === UPLOAD_ERR_OK) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($file_tmp);
        
        if (!in_array($file_type, $allowed_types)) {
            $upload_error = 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.';
        } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $upload_error = 'File size too large. Maximum size is 5MB.';
        } else {
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Delete old profile picture if exists
            try {
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $old_picture = $stmt->fetchColumn();
                if ($old_picture && file_exists($old_picture)) {
                    unlink($old_picture);
                }
            } catch(PDOException $e) {
                // Ignore error
            }
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Update database
                try {
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->execute([$upload_path, $_SESSION['user_id']]);
                    $upload_message = 'Profile picture updated successfully!';
                } catch(PDOException $e) {
                    $upload_error = 'Failed to update database.';
                    unlink($upload_path); // Delete file if database update fails
                }
            } else {
                $upload_error = 'Failed to upload file. Please try again.';
            }
        }
    } else {
        $upload_error = 'Upload error occurred. Please try again.';
    }
}

// Get user data from database
$user_gender = null;
$user_profile_picture = null;
$user_first_name = null;
$user_middle_initial = null;
$user_last_name = null;
try {
    $stmt = $pdo->prepare("SELECT gender, profile_picture, first_name, middle_initial, last_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    $user_gender = $user_data['gender'] ?? null;
    $user_profile_picture = $user_data['profile_picture'] ?? null;
    $user_first_name = $user_data['first_name'] ?? null;
    $user_middle_initial = $user_data['middle_initial'] ?? null;
    $user_last_name = $user_data['last_name'] ?? null;
} catch(PDOException $e) {
    // If error, default to null
}

// Build full name
$full_name = '';
if ($user_first_name || $user_last_name) {
    $name_parts = array_filter([$user_first_name, $user_middle_initial, $user_last_name]);
    $full_name = implode(' ', $name_parts);
}

// Determine profile image - prioritize uploaded picture
$profile_image = '../pictures/boy.png'; // default
if ($user_profile_picture && file_exists($user_profile_picture)) {
    $profile_image = $user_profile_picture;
} elseif ($user_gender && strtolower($user_gender) === 'female') {
    $profile_image = '../pictures/woman.png';
} elseif ($user_gender && strtolower($user_gender) === 'male') {
    $profile_image = '../pictures/boy.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Dwellscape</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f7f5f1;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0 20px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 28px;
            font-weight: 700;
            color: #7a6a4f;
            text-decoration: none;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            color: #7a6a4f;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: #C3B091;
        }

        .back-btn i {
            margin-right: 8px;
        }

        .main-content {
            margin-top: 100px;
            padding: 40px 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .profile-header-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 50px;
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-avatar-container {
            position: relative;
            display: inline-block;
            margin-right: 40px;
        }

        .profile-avatar-large {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 56px;
            font-weight: 700;
            box-shadow: 0 8px 25px rgba(195, 176, 145, 0.35);
            overflow: hidden;
        }

        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .upload-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            border: 4px solid white;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .upload-btn:hover {
            transform: scale(1.15);
            box-shadow: 0 6px 18px rgba(195, 176, 145, 0.4);
        }

        .upload-btn i {
            font-size: 20px;
        }

        .upload-form {
            display: none;
        }

        .upload-form input[type="file"] {
            display: none;
        }

        .profile-info h1 {
            font-size: 36px;
            color: #1f2937;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .profile-info p {
            color: #6b7280;
            font-size: 18px;
        }

        .message {
            padding: 14px 24px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .settings-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 50px;
            margin-bottom: 30px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
        }

        .settings-section {
            padding: 35px;
            background: linear-gradient(135deg, #fefbf6 0%, #f7f5f1 100%);
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .settings-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(195, 176, 145, 0.15);
            border-color: #C3B091;
        }

        .settings-section h2 {
            font-size: 22px;
            color: #1f2937;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            font-weight: 700;
        }

        .settings-section h2 i {
            margin-right: 12px;
            color: #C3B091;
            font-size: 24px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(195, 176, 145, 0.1);
            border-radius: 10px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #C3B091;
            box-shadow: 0 0 0 4px rgba(195, 176, 145, 0.15);
            background: #fefbf6;
        }

        .form-group input[readonly] {
            background: #f9fafb;
            cursor: not-allowed;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            padding: 20px;
            background: white;
            border-radius: 12px;
            border-left: 4px solid #C3B091;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .info-item label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-item .value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 500;
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px;
            background: white;
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .toggle-switch:hover {
            border-color: #C3B091;
            box-shadow: 0 2px 8px rgba(195, 176, 145, 0.1);
        }

        .toggle-switch label {
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            font-size: 15px;
        }

        .toggle-switch .switch {
            position: relative;
            width: 52px;
            height: 28px;
            background: #cbd5e1;
            border-radius: 14px;
            cursor: pointer;
            transition: background 0.3s ease;
            flex-shrink: 0;
        }

        .toggle-switch .switch.active {
            background: #C3B091;
        }

        .toggle-switch .switch::after {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: white;
            top: 3px;
            left: 3px;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch .switch.active::after {
            transform: translateX(24px);
        }

        .btn {
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(195, 176, 145, 0.25);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(195, 176, 145, 0.35);
        }

        .btn:active {
            transform: translateY(0);
        }

        @media (max-width: 1200px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-avatar-container {
                margin-right: 0;
                margin-bottom: 20px;
            }

            .profile-header-section,
            .settings-card {
                padding: 30px 20px;
            }

            .settings-section {
                padding: 25px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                <span>DWELLSCAPE <small>STAYCATION</small></span>
            </a>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>

            <!-- Profile Header Section -->
            <div class="profile-header-section">
                <?php if ($upload_message): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($upload_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($upload_error): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($upload_error); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-header">
                    <div class="profile-avatar-container">
                        <div class="profile-avatar-large">
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" onerror="this.style.display='none'; this.parentElement.innerHTML='<?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>';">
                        </div>
                        <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="document.getElementById('uploadForm').submit();">
                        </form>
                        <button type="button" class="upload-btn" onclick="document.getElementById('profile_picture').click();" title="Upload Profile Picture">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($full_name ?: $_SESSION['username']); ?></h1>
                        <p><?php echo htmlspecialchars($_SESSION['email'] ?? 'No email provided'); ?></p>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <div class="value"><?php echo htmlspecialchars($full_name ?: 'Not set'); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Username</label>
                        <div class="value"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Email Address</label>
                        <div class="value"><?php echo htmlspecialchars($_SESSION['email'] ?? 'Not set'); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Account Status</label>
                        <div class="value">Active</div>
                    </div>
                    <div class="info-item">
                        <label>Member Since</label>
                        <div class="value"><?php 
                            try {
                                $stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $created = $stmt->fetchColumn();
                                echo $created ? date('F Y', strtotime($created)) : date('F Y');
                            } catch(PDOException $e) {
                                echo date('F Y');
                            }
                        ?></div>
                    </div>
                </div>
            </div>

            <!-- Settings Sections -->
            <div class="settings-card">
                <div class="settings-grid">
                    <div class="settings-section">
                        <h2><i class="fas fa-user-cog"></i> Account Settings</h2>
                        <form>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_first_name ?? ''); ?>" placeholder="Enter your first name">
                            </div>
                            <div class="form-group">
                                <label>Middle Initial</label>
                                <input type="text" name="middle_initial" value="<?php echo htmlspecialchars($user_middle_initial ?? ''); ?>" placeholder="M.I." maxlength="1" style="text-transform: uppercase;">
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_last_name ?? ''); ?>" placeholder="Enter your last name">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" placeholder="Enter your phone number">
                            </div>
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h2><i class="fas fa-bell"></i> Notification Preferences</h2>
                        <div class="toggle-switch">
                            <label>Email Notifications</label>
                            <div class="switch active" onclick="toggleSwitch(this)"></div>
                        </div>
                        <div class="toggle-switch">
                            <label>SMS Notifications</label>
                            <div class="switch" onclick="toggleSwitch(this)"></div>
                        </div>
                        <div class="toggle-switch">
                            <label>Booking Reminders</label>
                            <div class="switch active" onclick="toggleSwitch(this)"></div>
                        </div>
                        <div class="toggle-switch">
                            <label>Promotional Emails</label>
                            <div class="switch" onclick="toggleSwitch(this)"></div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h2><i class="fas fa-lock"></i> Security</h2>
                        <form>
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" placeholder="Enter current password">
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" placeholder="Enter new password">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" placeholder="Confirm new password">
                            </div>
                            <button type="submit" class="btn">
                                <i class="fas fa-key"></i>
                                Update Password
                            </button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h2><i class="fas fa-palette"></i> Preferences</h2>
                        <form>
                            <div class="form-group">
                                <label>Language</label>
                                <select>
                                    <option>English</option>
                                    <option>Filipino</option>
                                    <option>Spanish</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Timezone</label>
                                <select>
                                    <option>Asia/Manila (GMT+8)</option>
                                    <option>UTC</option>
                                </select>
                            </div>
                            <button type="submit" class="btn">
                                <i class="fas fa-check"></i>
                                Save Preferences
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleSwitch(element) {
            element.classList.toggle('active');
        }
    </script>
</body>
</html>
