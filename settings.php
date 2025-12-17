<?php
session_start();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Dwellscape</title>
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
            padding: 30px;
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
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(195, 176, 145, 0.1);
            border-radius: 8px;
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

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: #d1d5db;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 1200px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .settings-card {
                padding: 30px 20px;
            }

            .settings-section {
                padding: 20px;
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
                                <label>Full Name</label>
                                <input type="text" placeholder="Enter your full name">
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

