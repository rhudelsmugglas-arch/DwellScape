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
    <title>Gallery - Dwellscape</title>
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

        /* Gallery Styles */
        .gallery-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .gallery-filter-btn {
            padding: 10px 20px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            color: #6b7280;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .gallery-filter-btn:hover,
        .gallery-filter-btn.active {
            background: #C3B091;
            border-color: #C3B091;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(195, 176, 145, 0.3);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .gallery-item {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            background: white;
            opacity: 1;
            transform: scale(1);
        }

        .gallery-item:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 32px rgba(195, 176, 145, 0.25);
        }

        .gallery-item:hover .gallery-item-overlay {
            opacity: 1;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.5), transparent);
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }

        .gallery-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .gallery-item-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
            padding: 25px;
            color: white;
            transition: all 0.4s ease;
            opacity: 0.95;
        }

        .gallery-item-overlay::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(195, 176, 145, 0.2) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .gallery-item:hover .gallery-item-overlay::before {
            opacity: 1;
        }

        .gallery-item-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .gallery-item-category {
            font-size: 14px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }

        .gallery-item-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.4s ease;
            z-index: 2;
        }

        .gallery-item:hover .gallery-item-icon {
            opacity: 1;
            transform: scale(1);
        }

        /* Modal/Lightbox Styles */
        .gallery-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s ease;
            overflow: auto;
        }

        .gallery-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .gallery-modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90vh;
            margin: auto;
            animation: zoomIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes zoomIn {
            from { transform: scale(0.7); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .gallery-modal-image {
            width: 100%;
            height: auto;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.6);
            transition: transform 0.3s ease;
        }

        .gallery-modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-size: 24px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .gallery-modal-nav:hover {
            background: rgba(195, 176, 145, 0.8);
            border-color: rgba(195, 176, 145, 0.9);
            transform: translateY(-50%) scale(1.1);
        }

        .gallery-modal-nav.prev {
            left: 20px;
        }

        .gallery-modal-nav.next {
            right: 20px;
        }

        .gallery-modal-info {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-align: center;
            z-index: 10;
            display: none;
        }

        .gallery-modal-info-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .gallery-modal-info-category {
            font-size: 14px;
            opacity: 0.9;
        }

        .gallery-modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #fff;
            font-size: 40px;
            font-weight: 300;
            cursor: pointer;
            transition: color 0.3s ease;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            padding: 0;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            z-index: 10;
        }

        .gallery-modal-close:hover {
            color: #C3B091;
            background: rgba(0, 0, 0, 0.7);
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .page-title {
                font-size: 36px;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .gallery-modal-content {
                max-width: 95%;
                max-height: 85vh;
            }

            .gallery-modal-close {
                top: 5px;
                right: 5px;
                font-size: 35px;
                width: 40px;
                height: 40px;
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
                    <li><a href="dashboard.php#gallery" class="active">Gallery</a></li>
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
                <h1 class="page-title">Gallery</h1>
                <p class="page-subtitle">Explore our beautiful properties and spaces. See what makes Dwellscape the perfect staycation choice.</p>
            </div>

            <div class="gallery-filters">
                <button class="gallery-filter-btn active" onclick="filterGallery('all')">All</button>
                <button class="gallery-filter-btn" onclick="filterGallery('living-room')">Living Room</button>
                <button class="gallery-filter-btn" onclick="filterGallery('kitchen')">Full Kitchen</button>
                <button class="gallery-filter-btn" onclick="filterGallery('dining')">Dining Area</button>
                <button class="gallery-filter-btn" onclick="filterGallery('bedroom1')">Bedroom 1</button>
                <button class="gallery-filter-btn" onclick="filterGallery('bedroom2')">Bedroom 2</button>
                <button class="gallery-filter-btn" onclick="filterGallery('bathroom')">Full Bathroom</button>
                <button class="gallery-filter-btn" onclick="filterGallery('pool')">Pool</button>
                <button class="gallery-filter-btn" onclick="filterGallery('activity')">Activity Area</button>
            </div>

            <div class="gallery-grid">
                <?php
                // Get active gallery images from database
                try {
                    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC");
                    $gallery_images = $stmt->fetchAll();
                    
                    // Debug: Check if query returns results
                    // Uncomment for debugging: var_dump($gallery_images);
                    
                    if (empty($gallery_images)) {
                        echo '<p style="grid-column: 1 / -1; text-align: center; color: #666; padding: 40px;">No gallery images available. <a href="check_and_add_gallery.php" style="color: #C3B091; text-decoration: underline;">Click here to restore images</a></p>';
                    } else {
                        foreach ($gallery_images as $image):
                            // Normalize image path so local files in /pictures or /uploads/gallery work correctly
                            $raw_url = trim($image['image_url'] ?? '');

                            // Already a full URL → leave as is
                            if (preg_match('~^https?://~i', $raw_url)) {
                                // no change - keep as is
                            }
                            // If it's a Windows absolute path (e.g., C:\xampp\htdocs\pictures\img.jpg),
                            // strip everything up to /pictures or /uploads to convert it to a web path.
                            elseif (preg_match('~[\\\\/]pictures[\\\\/](.+)$~i', $raw_url, $m)) {
                                $raw_url = '../pictures/' . str_replace('\\', '/', $m[1]);
                            }
                            elseif (preg_match('~[\\\\/]uploads[\\\\/](gallery[\\\\/].+)$~i', $raw_url, $m)) {
                                $raw_url = '../uploads/' . str_replace('\\', '/', $m[1]);
                            }
                            // Paths that already point to /pictures (absolute)
                            elseif (preg_match('~^/pictures/~i', $raw_url)) {
                                // Convert to relative path
                                $raw_url = '..' . $raw_url;
                            }
                            // Paths that start with pictures/ (relative)
                            elseif (preg_match('~^pictures/~i', $raw_url)) {
                                // from gallery.php go one level up then /pictures
                                $raw_url = '../' . $raw_url;
                            }
                            // Paths that start with ../pictures/
                            elseif (preg_match('~^\.\./pictures/~i', $raw_url)) {
                                // Already correct relative path
                                // no change
                            }
                            // Paths that already point to uploads/gallery relative to app root
                            elseif (preg_match('~^uploads/~i', $raw_url)) {
                                // make it relative
                                $raw_url = '../' . $raw_url;
                            }
                            // Plain filename → assume it lives in /pictures
                            elseif (!empty($raw_url) && !preg_match('~[\\\\/]~', $raw_url)) {
                                $raw_url = '../pictures/' . $raw_url;
                            }
                            // If it contains pictures but doesn't match above patterns
                            elseif (!empty($raw_url) && stripos($raw_url, 'pictures') !== false) {
                                // Try to extract filename and prepend ../pictures/
                                if (preg_match('~([^\\\\/]+\.(jpg|jpeg|png|gif|webp|avif))$~i', $raw_url, $m)) {
                                    $raw_url = '../pictures/' . $m[1];
                                }
                            }

                            $image_url = htmlspecialchars(str_replace('\\', '/', $raw_url));
                            $title = htmlspecialchars($image['title']);
                            $description = htmlspecialchars($image['description'] ?? '');
                            $category = htmlspecialchars($image['category']);
                            $alt_text = htmlspecialchars($image['title'] . ' - ' . $description);
                        ?>
                        <div class="gallery-item" data-category="<?php echo $category; ?>" onclick="openGalleryModal('<?php echo $image_url; ?>', '<?php echo $title; ?>', '<?php echo $description; ?>')">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo $alt_text; ?>" onerror="this.src='https://via.placeholder.com/400x300?text=Image+Not+Found'">
                            <div class="gallery-item-icon">
                                <i class="fas fa-expand"></i>
                            </div>
                            <div class="gallery-item-overlay">
                                <div class="gallery-item-title"><?php echo $title; ?></div>
                                <div class="gallery-item-category"><?php echo $description; ?></div>
                            </div>
                        </div>
                        <?php
                        endforeach;
                    }
                } catch(PDOException $e) {
                    echo '<p style="grid-column: 1 / -1; text-align: center; color: #dc3545; padding: 40px;">Error loading gallery images.</p>';
                }
                ?>
            </div>
        </div>
    </main>

    <!-- Gallery Modal/Lightbox -->
    <div id="galleryModal" class="gallery-modal" onclick="if(event.target === this) closeGalleryModal()">
        <div class="gallery-modal-content">
            <button class="gallery-modal-close" onclick="closeGalleryModal()" title="Close (ESC)">&times;</button>
            <button class="gallery-modal-nav prev" onclick="navigateGallery(-1)" title="Previous">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="gallery-modal-nav next" onclick="navigateGallery(1)" title="Next">
                <i class="fas fa-chevron-right"></i>
            </button>
            <img id="modalImage" class="gallery-modal-image" src="" alt="">
            <div class="gallery-modal-info">
                <div class="gallery-modal-info-title" id="modalTitle"></div>
                <div class="gallery-modal-info-category" id="modalCategory"></div>
            </div>
        </div>
    </div>

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

        // Gallery Filter Function
        function filterGallery(category) {
            // Update active button
            document.querySelectorAll('.gallery-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filter gallery items
            const items = document.querySelectorAll('.gallery-item');
            items.forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, 10);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        }

        // Gallery data for navigation
        let currentImageIndex = 0;
        const galleryImages = [];

        // Initialize gallery images array
        document.addEventListener('DOMContentLoaded', function() {
            const galleryItems = document.querySelectorAll('.gallery-item');
            galleryItems.forEach((item, index) => {
                const img = item.querySelector('img');
                const title = item.querySelector('.gallery-item-title').textContent;
                const category = item.querySelector('.gallery-item-category').textContent;
                
                galleryImages.push({
                    src: img.src,
                    alt: img.alt,
                    title: title,
                    category: category
                });
            });
        });

        // Gallery Modal Functions
        function openGalleryModal(imageSrc, title, category) {
            const modal = document.getElementById('galleryModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalCategory = document.getElementById('modalCategory');

            // Find current index
            currentImageIndex = galleryImages.findIndex(img => img.src === imageSrc || img.title === title);
            if (currentImageIndex === -1) currentImageIndex = 0;

            modalImage.src = imageSrc;
            modalImage.alt = title;
            modalTitle.textContent = title;
            modalCategory.textContent = category;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeGalleryModal() {
            const modal = document.getElementById('galleryModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function navigateGallery(direction) {
            currentImageIndex += direction;
            
            if (currentImageIndex < 0) {
                currentImageIndex = galleryImages.length - 1;
            } else if (currentImageIndex >= galleryImages.length) {
                currentImageIndex = 0;
            }

            const image = galleryImages[currentImageIndex];
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalCategory = document.getElementById('modalCategory');

            modalImage.src = image.src;
            modalImage.alt = image.alt;
            modalTitle.textContent = image.title;
            modalCategory.textContent = image.category;
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('galleryModal');
            if (modal.classList.contains('active')) {
                if (e.key === 'Escape') {
                    closeGalleryModal();
                } else if (e.key === 'ArrowLeft') {
                    navigateGallery(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateGallery(1);
                }
            }
        });
    </script>
</body>
</html>

