<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - Dwellscape</title>
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
                <h1 class="page-title">Terms & Conditions</h1>
                <p class="page-subtitle">Please read these terms carefully before using our services.</p>
            </div>

            <div class="content-section">
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing and using Dwellscape Staycation services, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to these terms, please do not use our services.</p>
            </div>

            <div class="content-section">
                <h2>2. Booking and Reservations</h2>
                <h3>2.1 Booking Process</h3>
                <p>All bookings are subject to availability and confirmation. You must provide accurate information when making a reservation.</p>
                
                <h3>2.2 Payment Terms</h3>
                <p>Payment is required at the time of booking unless otherwise specified. We accept major credit cards and other payment methods as indicated on our platform.</p>
                
                <h3>2.3 Cancellation Policy</h3>
                <p>Cancellations must be made at least 48 hours before the scheduled check-in time to receive a full refund. Cancellations made less than 48 hours before check-in may be subject to fees.</p>
            </div>

            <div class="content-section">
                <h2>3. Use of Services</h2>
                <p>You agree to use our services only for lawful purposes and in accordance with these Terms. You agree not to:</p>
                <ul>
                    <li>Use the services in any way that violates any applicable law or regulation</li>
                    <li>Interfere with or disrupt the services or servers</li>
                    <li>Attempt to gain unauthorized access to any portion of the services</li>
                    <li>Use the services to transmit any harmful or malicious code</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>4. Property Rules and Regulations</h2>
                <p>Guests are expected to:</p>
                <ul>
                    <li>Respect the property and other guests</li>
                    <li>Follow all house rules provided at check-in</li>
                    <li>Report any damages or issues immediately</li>
                    <li>Comply with local laws and regulations</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>5. Liability and Indemnification</h2>
                <p>Dwellscape Staycation is not liable for any damages, losses, or injuries that may occur during your stay. You agree to indemnify and hold harmless Dwellscape Staycation from any claims arising from your use of our services.</p>
            </div>

            <div class="content-section">
                <h2>6. Intellectual Property</h2>
                <p>All content on this platform, including text, graphics, logos, and software, is the property of Dwellscape Staycation and is protected by copyright and other intellectual property laws.</p>
            </div>

            <div class="content-section">
                <h2>7. Modifications to Terms</h2>
                <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Your continued use of our services constitutes acceptance of the modified terms.</p>
            </div>

            <div class="content-section">
                <h2>8. Contact Information</h2>
                <p>For questions about these Terms & Conditions, please contact us at:</p>
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

