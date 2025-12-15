<?php
session_start();
require_once 'config/database.php';

// Redirect if not logged in
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
if ($user_profile_picture && file_exists($user_profile_picture)) {
    $profile_image = $user_profile_picture;
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
    <title>Virtual View - Dwellscape</title>
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
            position: relative;
        }
        
        /* Virtual View Background Section */
        .virtual-hero-section {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            overflow: hidden;
        }
        
        .virtual-hero-image {
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            object-position: center center;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 1;
            margin: 0 !important;
            padding: 0 !important;
            filter: brightness(1.05) contrast(1.15) saturate(1.2);
            animation: heroZoom 30s ease-in-out infinite;
        }
        
        @keyframes heroZoom {
            0%   { transform: scale(1.05); }
            50%  { transform: scale(1.1); }
            100% { transform: scale(1.05); }
        }
        
        .virtual-hero-overlay {
            position: fixed;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw;
            height: 100vh;
            background:
                radial-gradient(circle at 20% 20%, rgba(0, 0, 0, 0.2) 0%, transparent 55%),
                radial-gradient(circle at 80% 80%, rgba(0, 0, 0, 0.2) 0%, transparent 55%),
                linear-gradient(135deg, rgba(0, 0, 0, 0.35) 0%, rgba(0, 0, 0, 0.15) 100%);
            z-index: 2;
            margin: 0 !important;
            padding: 0 !important;
            pointer-events: none;
        }
        
        /* Respect user's motion preferences */
        @media (prefers-reduced-motion: reduce) {
            .virtual-hero-image {
                animation: none;
            }
        }

        /* Header Styles */
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
            position: relative;
            z-index: 3;
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

        .virtual-tour-container {
            background: #f7f5f1;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 40px;
        }

        .embed-16x9 {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: #000;
        }

        .embed-16x9 iframe,
        .embed-16x9 img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
            object-fit: cover;
        }

        .tour-info {
            margin-top: 30px;
            text-align: center;
        }

        .tour-info h3 {
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .tour-info p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .page-title {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
                    <li><a href="dashboard.php#virtual-view" class="active">Virtual View</a></li>
                    <li><a href="dashboard.php#home">Home</a></li>
                    <li><a href="dashboard.php#amenities">Amenities</a></li>
                    <li><a href="dashboard.php#gallery">Gallery</a></li>
                    <li><a href="dashboard.php#about">About</a></li>
                    <li><a href="dashboard.php#contact">Contact</a></li>
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

    <!-- Virtual View Background -->
    <section class="virtual-hero-section">
        <img src="pictures/virtual.jpg" alt="Dwellscape Virtual View" class="virtual-hero-image" style="display: block !important; visibility: visible !important; opacity: 1 !important;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop';">
        <div class="virtual-hero-overlay"></div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title" style="color: #ffffff; text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);">Virtual View</h1>
                <p class="page-subtitle" style="color: #ffffff; text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);">Experience our properties in stunning 360° virtual tours. Explore every corner before you book.</p>
            </div>

            <div class="virtual-tour-container" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
                <div class="embed-16x9">
                    <img src="pictures/virtual.jpg" alt="Virtual tour preview" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1200&auto=format&fit=crop';">
                </div>
                <div class="tour-info">
                    <h3>360° Property Tour</h3>
                    <p>Take a virtual walkthrough of our premium staycation spaces. Click and drag to explore, or use the controls to navigate through different rooms and areas.</p>
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
        });
        
        // Ensure virtual background image displays properly
        document.addEventListener('DOMContentLoaded', function() {
            const virtualHeroImage = document.querySelector('.virtual-hero-image');
            if (virtualHeroImage) {
                // Force display
                virtualHeroImage.style.display = 'block';
                virtualHeroImage.style.visibility = 'visible';
                virtualHeroImage.style.opacity = '1';
                
                // Check if image file is valid (files < 1000 bytes are likely corrupted)
                fetch(virtualHeroImage.src, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            const contentLength = response.headers.get('content-length');
                            if (contentLength && parseInt(contentLength) < 1000) {
                                // File is too small (corrupted), use fallback immediately
                                // Virtual background image is corrupted - using fallback (this is expected)
                                // console.warn('Virtual background image appears corrupted (only ' + contentLength + ' bytes), using fallback');
                                const fallbackUrl = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop';
                                virtualHeroImage.src = fallbackUrl;
                                return;
                            }
                            // File seems valid, verify it loads
                            const testImg = new Image();
                            testImg.onload = function() {
                                console.log('Virtual background image loaded successfully');
                            };
                            testImg.onerror = function() {
                                // Virtual background image failed to load - using fallback (this is expected)
                                // console.warn('Virtual background image failed to load, using fallback');
                                virtualHeroImage.src = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop';
                            };
                            testImg.src = virtualHeroImage.src;
                        } else {
                            throw new Error('File not found');
                        }
                    })
                    .catch(() => {
                        // Can't check file or file doesn't exist, use fallback
                        // Cannot verify virtual background image - using fallback (this is expected)
                        // console.warn('Cannot verify virtual background image, using fallback');
                        virtualHeroImage.src = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop';
                    });
            }
        });
    </script>
</body>
</html>

