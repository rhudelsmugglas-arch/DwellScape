<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

// Get user data from database
$user_gender = null;
$user_profile_picture = null;
try {
    $stmt = $pdo->prepare("SELECT gender, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    $user_gender = $user_data['gender'] ?? null;
    $user_profile_picture = $user_data['profile_picture'] ?? null;
} catch(PDOException $e) {
    // If error, default to null
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
    <title>Contact - Dwellscape</title>
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
            transition: transform 0.2s ease;
        }

        .logo .brand-mark {
            display: inline-flex;
            align-items: center;
            margin-right: 10px;
        }

        .logo .brand-mark img {
            height: 26px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .logo .logo-fallback {
            display: none;
            height: 26px;
            width: 26px;
        }

        .logo .logo-text {
            display: inline-block;
            font-weight: 800;
            letter-spacing: 0.4px;
            color: #7a6a4f;
            font-size: 21px;
            line-height: 1;
            margin-right: 0;
            white-space: nowrap;
        }

        .logo .logo-text small {
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 3px;
            margin-left: 8px;
            color: #9A8B6F;
        }

        .logo:hover .logo-text { color: #6b5f48; }

        .header-content nav {
            margin-left: 10px;
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
            position: relative;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #9A8B6F;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #C3B091;
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }

        .profile-section {
            position: relative;
            margin-left: 20px;
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

        .profile-btn:hover {
            background: rgba(195, 176, 145, 0.15);
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
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-name {
            font-weight: 500;
            margin-right: 8px;
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            font-weight: 500;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
        }

        .dropdown-item i {
            margin-right: 10px;
            width: 16px;
        }

        .main-content {
            margin-top: 80px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .page-title {
            font-size: 48px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
        }

        .contact-form {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .contact-form h3 {
            color: #1f2937;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #C3B091;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .cta-button {
            background: #C3B091;
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            width: 100%;
            justify-content: center;
        }

        .cta-button:hover {
            background: #9A8B6F;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(195, 176, 145, 0.3);
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .info-card h3 {
            color: #1f2937;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .info-content h4 {
            color: #1f2937;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-content p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }

        .map-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            height: 300px;
            margin-top: 30px;
        }

        .map-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .page-title {
                font-size: 36px;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo" aria-label="Dwellscape Staycation">
                <span class="brand-mark">
                    <img src="assets/img/dwellscape-logo.png" alt="Dwellscape logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                    <svg class="logo-fallback" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z" fill="none" stroke="#7a6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="logo-text">DWELLSCAPE <small>STAYCATION</small></span>
            </a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="dashboard.php#virtual-view">Virtual View</a></li>
                    <li><a href="dashboard.php#home">Home</a></li>
                    <li><a href="dashboard.php#amenities">Amenities</a></li>
                    <li><a href="dashboard.php#gallery">Gallery</a></li>
                    <li><a href="dashboard.php#about">About</a></li>
                    <li><a href="dashboard.php#contact" class="active">Contact</a></li>
                    <li><a href="bookings.php">Book Now</a></li>
                </ul>
            </nav>

            <div class="profile-section">
                <button class="profile-btn" onclick="toggleProfileDropdown()">
                    <div class="profile-avatar">
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" onerror="this.style.display='none'; this.parentElement.innerHTML='<?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>';">
                    </div>
                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="dashboard.php" class="dropdown-item">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    <a href="bookings.php" class="dropdown-item">
                        <i class="fas fa-calendar"></i>
                        Bookings
                    </a>
                    <form method="POST" class="dropdown-item" style="padding: 0;">
                        <button type="submit" name="logout" style="background: none; border: none; width: 100%; text-align: left; padding: 12px 16px; cursor: pointer; display: flex; align-items: center; color: #333; font-weight: 500; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; line-height: 1.5;">
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
                <h1 class="page-title">Contact Us</h1>
                <p class="page-subtitle">Get in touch with us. We're here to help make your staycation perfect.</p>
            </div>

            <div class="contact-grid">
                <div class="contact-form">
                    <h3>Send us a message</h3>
                    <form>
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        <button type="submit" class="cta-button">
                            Send Message <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

                <div class="contact-info">
                    <div class="info-card">
                        <h3>Contact Details</h3>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <h4>Email</h4>
                                <p>dwellscapestaycation@gmail.com</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-content">
                                <h4>Phone</h4>
                                <p>09091231231</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>Address</h4>
                                <p>South Residences, Las Pinas City, Metro Manila</p>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>Business Hours</h3>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h4>Monday - Friday</h4>
                                <p>9:00 AM - 6:00 PM</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h4>Saturday - Sunday</h4>
                                <p>10:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                    </div>

                    <div class="map-container">
                        <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=1200&auto=format&fit=crop" alt="Map location">
                    </div>
                </div>
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

