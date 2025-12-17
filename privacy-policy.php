<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: home.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Dwellscape</title>
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
            background: #ffffff;
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

        .logo .logo-text {
            font-weight: 800;
            letter-spacing: 0.4px;
            color: #7a6a4f;
            font-size: 21px;
        }

        .logo .logo-text small {
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 3px;
            margin-left: 8px;
            color: #9A8B6F;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 24px;
        }

        .nav-links a {
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #9A8B6F;
        }

        .profile-section {
            position: relative;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 25px;
            transition: background 0.3s ease;
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 10px;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
        }

        .profile-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .main-content {
            margin-top: 80px;
            padding: 60px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-title {
            font-size: 42px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 16px;
        }

        .content-section {
            margin-bottom: 40px;
        }

        .content-section h2 {
            font-size: 28px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #C3B091;
        }

        .content-section h3 {
            font-size: 20px;
            font-weight: 600;
            color: #374151;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .content-section p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .content-section ul {
            margin-left: 30px;
            margin-bottom: 20px;
        }

        .content-section li {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .last-updated {
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                <span class="logo-text">DWELLSCAPE <small>STAYCATION</small></span>
            </a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="virtual-view.php">Virtual View</a></li>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="bookings.php">Bookings</a></li>
                    <li><a href="amenities.php">Amenities</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>

            <div class="profile-section">
                <button class="profile-btn" onclick="toggleProfileDropdown()">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    <a href="notifications.php" class="dropdown-item">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                    <form method="POST" class="dropdown-item" style="padding: 0;">
                        <button type="submit" name="logout" style="background: none; border: none; width: 100%; text-align: left; padding: 12px 16px; cursor: pointer; display: flex; align-items: center;">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Privacy Policy</h1>
                <p class="page-subtitle">Your privacy is important to us. This policy explains how we collect, use, and protect your information.</p>
            </div>

            <div class="content-section">
                <h2>1. Information We Collect</h2>
                <p>We collect information that you provide directly to us when you:</p>
                <ul>
                    <li>Create an account or profile</li>
                    <li>Make a booking or reservation</li>
                    <li>Contact us for customer support</li>
                    <li>Subscribe to our newsletter or marketing communications</li>
                    <li>Participate in surveys or promotions</li>
                </ul>
                <p>The types of information we may collect include your name, email address, phone number, payment information, and booking preferences.</p>
            </div>

            <div class="content-section">
                <h2>2. How We Use Your Information</h2>
                <p>We use the information we collect to:</p>
                <ul>
                    <li>Process and manage your bookings</li>
                    <li>Communicate with you about your reservations</li>
                    <li>Send you marketing communications (with your consent)</li>
                    <li>Improve our services and user experience</li>
                    <li>Comply with legal obligations</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>3. Information Sharing</h2>
                <p>We do not sell your personal information. We may share your information only in the following circumstances:</p>
                <ul>
                    <li>With service providers who assist us in operating our platform</li>
                    <li>When required by law or to protect our rights</li>
                    <li>In connection with a business transfer or merger</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>4. Data Security</h2>
                <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
            </div>

            <div class="content-section">
                <h2>5. Your Rights</h2>
                <p>You have the right to:</p>
                <ul>
                    <li>Access your personal information</li>
                    <li>Correct inaccurate data</li>
                    <li>Request deletion of your data</li>
                    <li>Opt-out of marketing communications</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>6. Cookies and Tracking</h2>
                <p>We use cookies and similar tracking technologies to enhance your experience, analyze usage, and assist with marketing efforts. You can control cookie preferences through your browser settings.</p>
            </div>

            <div class="content-section">
                <h2>7. Contact Us</h2>
                <p>If you have questions about this Privacy Policy, please contact us at:</p>
                <p><strong>Email:</strong> dwellscapestaycation@gmail.com<br>
                <strong>Phone:</strong> 09091231231</p>
            </div>

            <div class="last-updated">
                <p>Last Updated: <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
    </main>

    <script>
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const profileSection = document.querySelector('.profile-section');
            const dropdown = document.getElementById('profileDropdown');
            
            if (!profileSection.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>

