<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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
if ($user_profile_picture && !empty($user_profile_picture)) {
    // Check if file exists (normalize path)
    $profile_path = $user_profile_picture;
    // If path doesn't start with http or /, it's relative - check from script directory
    if (!preg_match('/^(https?:\/\/|\/)/', $profile_path)) {
        $absolute_path = __DIR__ . '/' . $profile_path;
        if (file_exists($absolute_path)) {
            $profile_image = $profile_path;
        } else {
            // Fallback to gender-based image
            if ($user_gender && strtolower($user_gender) === 'female') {
                $profile_image = '../pictures/woman.png';
            } elseif ($user_gender && strtolower($user_gender) === 'male') {
                $profile_image = '../pictures/boy.png';
            }
        }
    } else {
        $profile_image = $profile_path;
    }
} elseif ($user_gender && strtolower($user_gender) === 'female') {
    $profile_image = '../pictures/woman.png';
} elseif ($user_gender && strtolower($user_gender) === 'male') {
    $profile_image = '../pictures/boy.png';
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
    <title>About - Dwellscape</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/image-fallback.js"></script>
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
            display: inline-flex !important;
            align-items: center;
            margin-right: 10px;
            visibility: visible !important;
        }

        .logo .brand-mark img {
            height: 26px;
            width: auto;
            object-fit: contain;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative;
            z-index: 10;
        }
        
        /* Force logo to display - override any conflicting styles */
        .logo img[src*="dwellscape-logo"] {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: 26px !important;
            width: auto !important;
        }

        .logo .logo-fallback {
            display: none;
            height: 26px;
            width: 26px;
            visibility: visible !important;
        }
        
        /* Show SVG fallback when image fails */
        .logo .brand-mark:has(img[style*="display: none"]) .logo-fallback,
        .logo img[style*="display: none"] + .logo-fallback {
            display: inline-block !important;
            visibility: visible !important;
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
            position: relative;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .profile-avatar .fallback-letter {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .profile-avatar img:not([style*="display: none"]) ~ .fallback-letter {
            display: none;
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

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-bottom: 60px;
            align-items: center;
        }

        .about-text h2 {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .about-text p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .about-image {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .about-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .stats-section {
            background: #f7f5f1;
            padding: 60px 0;
            border-radius: 12px;
            margin: 60px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 48px;
            font-weight: 700;
            color: #C3B091;
            margin-bottom: 10px;
        }

        .stat-item p {
            color: #6b7280;
            font-size: 16px;
            font-weight: 500;
        }

        .values-section {
            margin-top: 60px;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .value-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
        }

        .value-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .value-card h3 {
            color: #1f2937;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .value-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .page-title {
                font-size: 36px;
            }

            .about-content {
                grid-template-columns: 1fr;
            }

            .values-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo" aria-label="Dwellscape Staycation">
                <span class="brand-mark" style="display: inline-flex !important; visibility: visible !important;">
                    <img src="assets/img/dwellscape-logo.png" alt="Dwellscape logo" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: 26px; width: auto; max-width: 100px; object-fit: contain;" onerror="console.error('Logo failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                    <svg class="logo-fallback" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="display: none;">
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
                    <li><a href="dashboard.php#about" class="active">About</a></li>
                    <li><a href="dashboard.php#contact">Contact</a></li>
                    <li><a href="bookings.php">Book Now</a></li>
                </ul>
            </nav>

            <div class="profile-section">
                <button class="profile-btn" onclick="toggleProfileDropdown()">
                    <div class="profile-avatar" id="profileAvatar">
                        <?php 
                        // Simple approach: always show first letter as fallback
                        $first_letter = strtoupper(substr($_SESSION['username'], 0, 1));
                        $image_src = htmlspecialchars($profile_image);
                        ?>
                        <img src="<?php echo $image_src; ?>" 
                             alt="Profile" 
                             style="display: none;"
                             onload="this.style.display='flex'; const fallbackOnload = this.parentElement.querySelector('.fallback-letter'); if(fallbackOnload) fallbackOnload.style.display='none';"
                             onerror="this.style.display='none'; const fallbackOnerror = this.parentElement.querySelector('.fallback-letter'); if(fallbackOnerror) fallbackOnerror.style.display='flex';">
                        <span class="fallback-letter" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;"><?php echo $first_letter; ?></span>
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
                <h1 class="page-title">About Dwellscape</h1>
                <p class="page-subtitle">Your trusted partner in creating memorable staycation experiences.</p>
            </div>

            <div class="about-content">
                <div class="about-text">
                    <h2>Thoughtfully designed spaces for every getaway</h2>
                    <p>
                        From city escapes to beachfront retreats, we curate stylish, comfortable stays
                        with the amenities you love—so you can focus on making memories.
                    </p>
                    <p>
                        Dwellscape Staycation is dedicated to providing exceptional staycation experiences
                        at South Residences, Las Pinas City. We believe that everyone deserves a perfect
                        escape, whether it's a weekend getaway or an extended stay.
                    </p>
                    <p>
                        Our properties are carefully selected and maintained to ensure the highest standards
                        of comfort, cleanliness, and style. We combine modern amenities with thoughtful
                        design to create spaces that feel like home.
                    </p>
                </div>
                <div class="about-image">
                    <img src="pictures/virtual.jpg" alt="About Dwellscape" style="display: block !important; visibility: visible !important; opacity: 1 !important; width: 100%; height: 400px; object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1200&auto=format&fit=crop';">
                </div>
            </div>

            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-item">
                        <h3>2</h3>
                        <p>Years of Experience</p>
                    </div>
                    <div class="stat-item">
                        <h3>500+</h3>
                        <p>Happy Guests</p>
                    </div>
                    <div class="stat-item">
                        <h3>5</h3>
                        <p>Properties</p>
                    </div>
                    <div class="stat-item">
                        <h3>98%</h3>
                        <p>Guest Satisfaction</p>
                    </div>
                </div>
            </div>

            <div class="values-section">
                <h2 class="page-title" style="text-align: center; margin-bottom: 20px;">Our Values</h2>
                <div class="values-grid">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Comfort First</h3>
                        <p>We prioritize your comfort and ensure every stay feels like home with premium amenities and thoughtful touches.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>Quality Service</h3>
                        <p>Dedicated to providing exceptional service and maintaining the highest standards in hospitality.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Trust & Safety</h3>
                        <p>Your safety and security are our top priorities with 24/7 monitoring and secure access.</p>
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
        
        // Force logo images to display and handle corrupted files
        document.addEventListener('DOMContentLoaded', function() {
            const logoImages = document.querySelectorAll('img[src*="dwellscape-logo"]');
            logoImages.forEach(function(img) {
                // Force display initially
                img.style.display = 'block';
                img.style.visibility = 'visible';
                img.style.opacity = '1';
                
                // Check if logo file is valid (files < 1000 bytes are likely corrupted)
                fetch(img.src, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            const contentLength = response.headers.get('content-length');
                            if (contentLength && parseInt(contentLength) < 1000) {
                                // File is too small (corrupted), show SVG fallback
                                // Logo file is corrupted - showing SVG fallback (this is expected)
                                // console.warn('Logo file appears corrupted (only ' + contentLength + ' bytes), showing SVG fallback');
                                const fallback = img.nextElementSibling;
                                if (fallback && fallback.classList.contains('logo-fallback')) {
                                    img.style.display = 'none';
                                    fallback.style.display = 'inline-block';
                                    fallback.style.visibility = 'visible';
                                }
                                return;
                            }
                            // File seems valid, try to load it
                            const testImg = new Image();
                            testImg.onload = function() {
                                img.src = img.src; // Reload if needed
                                console.log('Logo image loaded successfully:', img.src);
                            };
                            testImg.onerror = function() {
                                console.error('Logo image failed to load:', img.src);
                                const fallback = img.nextElementSibling;
                                if (fallback && fallback.classList.contains('logo-fallback')) {
                                    img.style.display = 'none';
                                    fallback.style.display = 'inline-block';
                                    fallback.style.visibility = 'visible';
                                }
                            };
                            testImg.src = img.src;
                        } else {
                            throw new Error('File not found');
                        }
                    })
                    .catch(() => {
                        // Can't check file or file doesn't exist, try to load anyway
                        const testImg = new Image();
                        testImg.onload = function() {
                            console.log('Logo image loaded:', img.src);
                        };
                        testImg.onerror = function() {
                            console.error('Logo image failed to load:', img.src);
                            const fallback = img.nextElementSibling;
                            if (fallback && fallback.classList.contains('logo-fallback')) {
                                img.style.display = 'none';
                                fallback.style.display = 'inline-block';
                                fallback.style.visibility = 'visible';
                            }
                        };
                        testImg.src = img.src;
                    });
            });
            
            // Ensure about image displays properly
            const aboutImage = document.querySelector('.about-image img');
            if (aboutImage) {
                aboutImage.style.display = 'block';
                aboutImage.style.visibility = 'visible';
                aboutImage.style.opacity = '1';
                
                // Check if image file is valid
                fetch(aboutImage.src, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            const contentLength = response.headers.get('content-length');
                            if (contentLength && parseInt(contentLength) < 1000) {
                                // File is corrupted, use fallback
                                // About image is corrupted - using fallback (this is expected)
                                // console.warn('About image appears corrupted, using fallback');
                                aboutImage.src = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1200&auto=format&fit=crop';
                            }
                        } else {
                            // File not found, use fallback
                            aboutImage.src = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1200&auto=format&fit=crop';
                        }
                    })
                    .catch(() => {
                        // Can't check, try fallback
                        const testImg = new Image();
                        testImg.onerror = function() {
                            aboutImage.src = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1200&auto=format&fit=crop';
                        };
                        testImg.src = aboutImage.src;
                    });
            }
        });
    </script>
</body>
</html>

