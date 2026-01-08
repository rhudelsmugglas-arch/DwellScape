<?php
// Start output buffering FIRST to catch any BOM/whitespace
if (!ob_get_level()) {
    ob_start();
}

// Configure session cookie parameters BEFORE session_start()
// Detect HTTPS (Railway uses HTTPS, but check headers for proxy)
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_secure', $is_https ? '1' : '0'); // Set based on actual HTTPS status
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', ''); // Empty for current domain

// Load database first
require_once 'config/database.php';

// Set up database session handler BEFORE any output
require_once 'config/session_handler.php';
$session_handler = new DatabaseSessionHandler($pdo);
session_set_save_handler($session_handler, true);

// Start session
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_gender = null;
$user_profile_picture = null;
$profile_image = 'pictures/boy.png'; // default - will fallback to placeholder if missing

// Get user data from database if logged in
if ($is_logged_in) {
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
                    $profile_image = 'pictures/woman.png';
                } elseif ($user_gender && strtolower($user_gender) === 'male') {
                    $profile_image = 'pictures/boy.png';
                }
            }
        } else {
            $profile_image = $profile_path;
        }
    } elseif ($user_gender && strtolower($user_gender) === 'female') {
        $profile_image = 'pictures/woman.png';
    } elseif ($user_gender && strtolower($user_gender) === 'male') {
        $profile_image = 'pictures/boy.png';
    }
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
    <title>Home - Dwellscape</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/image-fallback.js"></script>
    <style>
        /* Override external CSS that might interfere */
        body {
            background: transparent !important;
            background-color: transparent !important;
            /* Allow background images to display */
        }
        
        body::before {
            display: none !important;
        }
        
        /* Ensure hero section doesn't inherit green background from external CSS */
        /* Hero section background - allow images to display */
        .hero-section {
            background: #1a1a1a; /* Dark background as fallback only */
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            background: transparent !important;
            background-color: transparent !important;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: transparent !important;
            background-color: transparent !important;
            /* Allow background images to display */
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
            padding: 0 !important;
            display: block !important;
            align-items: normal !important;
            justify-content: normal !important;
            min-height: auto !important;
        }

        body::before {
            display: none !important;
        }

        .header {
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0 20px;
            pointer-events: none;
        }

        .header * {
            pointer-events: auto;
        }

        .header.scrolled {
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
        }

        .header-content {
            background: transparent !important;
            background-color: transparent !important;
        }

        .header nav,
        .header .nav-links,
        .header .nav-links li,
        .header .nav-links a {
            background: transparent !important;
            background-color: transparent !important;
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
            color: #ffffff;
            text-decoration: none;
            transition: transform 0.2s ease, color 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header.scrolled .logo {
            color: #7a6a4f;
            text-shadow: none;
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
            color: inherit;
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
            color: #C3B091;
        }

        .header.scrolled .logo .logo-text small {
            color: #9A8B6F;
        }

        .logo:hover .logo-text { 
            opacity: 0.9;
        }

        .header-content nav {
            margin-left: 10px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 24px;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header.scrolled .nav-links a {
            color: #374151;
            text-shadow: none;
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

        .dropdown-item i {
            margin-right: 10px;
            width: 16px;
        }

        .main-content {
            margin-top: 0;
            position: relative;
        }

        /* Hero Section with Background Image */
        .hero-section {
            position: relative;
            width: 100vw;
            height: 100vh;
            min-height: 700px;
            overflow: hidden;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            margin-top: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            background: #1a1a1a; /* Dark background as fallback */
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 1;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            transition: opacity 0.9s ease, transform 18s ease;
            filter: brightness(1.08) contrast(1.2) saturate(1.25);
            background: transparent;
        }
        
        /* Ensure hero image is always visible */
        #homeHeroImage {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.1) 100%);
            z-index: 2;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .hero-content,
        .promo-hero-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            z-index: 3;
            max-width: 800px;
            width: 100%;
            text-align: center;
            padding: 0 20px;
        }

        .hero-location,
        .promo-location {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: 500;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            animation: fadeInDown 1s ease-out;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        .hero-location i,
        .promo-location i {
            margin-right: 10px;
            font-size: 20px;
            color: #C3B091;
            animation: pulse 2s infinite;
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .hero-title,
        .promo-brand {
            font-size: 72px;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 10px;
            text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.7);
            letter-spacing: -2px;
            animation: fadeInUp 1.2s ease-out 0.3s;
            opacity: 0;
            animation-fill-mode: forwards;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero-title .highlight {
            color: #C3B091;
            background: linear-gradient(90deg, #C3B091 0%, #9A8B6F 50%, #C3B091 100%);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description,
        .promo-slogan {
            color: rgba(255, 255, 255, 0.95);
            font-size: 20px;
            font-weight: 400;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
            margin-top: 15px;
            margin-bottom: 40px;
            line-height: 1.4;
            animation: fadeInUp 1s ease-out 0.6s;
            opacity: 0;
            animation-fill-mode: forwards;
            text-align: center;
        }

        /* Text Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }

        .promo-brand-line1 {
            display: block;
            background: linear-gradient(90deg, #C3B091 0%, #9A8B6F 50%, #C3B091 100%);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s infinite;
            position: relative;
            margin-bottom: 5px;
        }

        .promo-brand-line1::after {
            content: 'DWELLSCAPE';
            position: absolute;
            left: 0;
            top: 0;
            -webkit-text-stroke: 2px white;
            text-stroke: 2px white;
            -webkit-text-fill-color: transparent;
            z-index: -1;
        }

        .promo-brand-line2 {
            display: block;
            background: linear-gradient(90deg, #C3B091 0%, #9A8B6F 50%, #C3B091 100%);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s infinite 0.5s;
            position: relative;
        }

        .promo-brand-line2::after {
            content: 'STAYCATION';
            position: absolute;
            left: 0;
            top: 0;
            -webkit-text-stroke: 2px white;
            text-stroke: 2px white;
            -webkit-text-fill-color: transparent;
            z-index: -1;
        }

        .cta-button {
            background: linear-gradient(135deg, #7a6a4f 0%, #5a4d3a 100%);
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
            box-shadow: 0 4px 15px rgba(90, 77, 58, 0.4);
        }

        .cta-button:hover {
            background: linear-gradient(135deg, #5a4d3a 0%, #4a3f2f 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(90, 77, 58, 0.5);
        }

        .cta-button:disabled {
            background: rgba(195, 176, 145, 0.5);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: 20px;
        }

        .auth-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .auth-btn-login {
            background: transparent;
            color: #ffffff;
            border: 2px solid rgba(255, 255, 255, 0.8);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .auth-btn-login:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: #ffffff;
        }

        .auth-btn-signup {
            background: #C3B091;
            color: white;
            border: 2px solid #C3B091;
        }

        .auth-btn-signup:hover {
            background: #9A8B6F;
            border-color: #9A8B6F;
        }

        .header.scrolled .auth-btn-login {
            color: #374151;
            border-color: #C3B091;
            text-shadow: none;
        }

        .header.scrolled .auth-btn-login:hover {
            background: rgba(195, 176, 145, 0.1);
        }


        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero-section {
                min-height: 600px;
            }

            .hero-title {
                font-size: 42px;
            }

            .hero-description {
                font-size: 16px;
            }
        }

        /* Modal Styles */
        .auth-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
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
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideUp 0.3s ease;
        }

        .auth-modal.signup-modal {
            max-width: 650px;
            padding: 40px 45px;
            max-height: 95vh;
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
            font-size: 22px;
            color: #2d3748;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            font-weight: 600;
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
            letter-spacing: 0.2px;
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

        .auth-modal .form-group input,
        .auth-modal .form-group select {
            width: 100%;
            padding: 16px 18px 16px 50px;
            border: 2.5px solid #cbd5e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            font-weight: 500;
            color: #000000;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .auth-modal .form-group input::placeholder {
            color: #718096;
            font-weight: 400;
        }

        .auth-modal .form-group input:focus,
        .auth-modal .form-group select:focus {
            outline: none;
            border-color: #7a6a4f;
            border-width: 3px;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(122, 106, 79, 0.2), 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .auth-modal .form-group input:focus + .input-icon,
        .auth-modal .form-group select:focus + .input-icon {
            color: #C3B091;
        }

        .auth-modal .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #4a5568;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .auth-modal .password-toggle:hover {
            background: rgba(195, 176, 145, 0.15);
            color: #7a6a4f;
        }

        .auth-modal .btn {
            width: 100%;
            padding: 18px 20px;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(195, 176, 145, 0.35);
            letter-spacing: 0.5px;
        }

        .auth-modal .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(195, 176, 145, 0.45);
            background: linear-gradient(135deg, #9A8B6F 0%, #7a6a4f 100%);
        }

        .auth-modal .btn:active {
            transform: translateY(0);
        }

        .auth-modal .error-message {
            background: #fff5f5;
            color: #c53030;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #fc8181;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.5;
        }

        .auth-modal .success-message {
            background: #f0fff4;
            color: #2f855a;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #68d391;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.5;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .signup-modal .form-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .signup-modal .form-group {
            margin-bottom: 20px;
        }

        .signup-modal .auth-modal-header {
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .signup-modal {
                max-width: 95%;
                padding: 35px 30px;
            }
        }

        @media (max-width: 768px) {
            .auth-modal {
                padding: 40px 30px;
                max-width: 95%;
            }

            .signup-modal {
                padding: 30px 25px;
            }

            .form-grid,
            .signup-modal .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <?php if ($is_logged_in): ?>
            <a href="dashboard.php" class="logo" aria-label="Dwellscape Staycation">
                <span class="brand-mark" style="display: inline-flex !important; visibility: visible !important;">
                    <img src="assets/img/dwellscape-logo.png" alt="Dwellscape logo" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: 26px; width: auto; max-width: 100px; object-fit: contain;" onerror="console.error('Logo failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                    <svg class="logo-fallback" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="display: none; height: 26px; width: 26px; vertical-align: middle;">
                        <path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z" fill="none" stroke="#7a6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="logo-text">DWELLSCAPE <small>STAYCATION</small></span>
            </a>
            <?php else: ?>
            <div class="logo" style="cursor: default; pointer-events: none;">
                <span class="brand-mark" style="display: inline-flex !important; visibility: visible !important;">
                    <img src="assets/img/dwellscape-logo.png" alt="Dwellscape logo" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: 26px; width: auto; max-width: 100px; object-fit: contain;" onerror="console.error('Logo failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                    <svg class="logo-fallback" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="display: none; height: 26px; width: 26px; vertical-align: middle;">
                        <path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z" fill="none" stroke="#7a6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="logo-text">DWELLSCAPE <small>STAYCATION</small></span>
            </div>
            <?php endif; ?>
            
            <?php if ($is_logged_in): ?>
            <nav>
                <ul class="nav-links">
                    <li><a href="dashboard.php#virtual-view">Virtual View</a></li>
                    <li><a href="dashboard.php#home" class="active">Home</a></li>
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
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" onerror="this.onerror=null; this.src='<?php echo ($user_gender && strtolower($user_gender) === 'female') ? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop' : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop'; ?>';">
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
                    <form method="POST" class="dropdown-item" style="padding: 0;">
                        <button type="submit" name="logout" style="background: none; border: none; width: 100%; text-align: left; padding: 12px 16px; cursor: pointer; display: flex; align-items: center;">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="auth-buttons">
                <a href="javascript:void(0)" class="auth-btn auth-btn-login" onclick="openLoginModal()">Login</a>
                <a href="javascript:void(0)" class="auth-btn auth-btn-signup" onclick="openSignupModal()">Sign Up</a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="main-content">
        <section class="hero-section">
            <img src="pictures/dashboard1.png" alt="Dwellscape South Residences" class="hero-background" id="homeHeroImage" style="display: block !important; visibility: visible !important; opacity: 1 !important;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop';">
            <div class="hero-overlay"></div>
            <div class="promo-hero-content">
                <div class="promo-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>South Residences, Las Pinas City, Metro Manila</span>
                </div>
                <h1 class="promo-brand">
                    <span class="promo-brand-line1">DWELLSCAPE</span>
                    <span class="promo-brand-line2">STAYCATION</span>
                </h1>
                <p class="promo-slogan">Escape the ordinary and embrace the extra ordinary at home.</p>
                <?php if ($is_logged_in): ?>
                <a href="bookings.php" class="cta-button">
                    Book Now <i class="fas fa-arrow-right"></i>
                </a>
                <?php else: ?>
                <a href="javascript:void(0)" class="cta-button" onclick="openLoginModal();">
                    Book Now <i class="fas fa-arrow-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Login Modal -->
    <div class="auth-modal-overlay" id="loginModal">
        <div class="auth-modal">
            <button class="auth-modal-close" onclick="closeLoginModal()">&times;</button>
            <div class="auth-modal-header">
                <h1>Dwellscape Staycation</h1>
                <p>Sign in to your account</p>
            </div>
            <div class="error-message" id="loginError" style="display: none;"></div>
            <form method="POST" id="loginForm" action="login.php">
                <div class="form-group">
                    <label for="loginUsername">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="loginUsername" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                <div class="form-group password-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="toggleLoginPassword()">
                            <i class="fas fa-eye" id="loginToggleIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Sign In</button>
            </form>
            <div class="auth-links">
                <a href="javascript:void(0)" onclick="closeLoginModal(); openForgotPasswordModal();">Forgot your password?</a>
                <br><br>
                <a href="javascript:void(0)" onclick="closeLoginModal(); openSignupModal();">Don't have an account? Sign up</a>
            </div>
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
            <div class="error-message" id="forgotPasswordError" style="display: none;"></div>
            <div class="success-message" id="forgotPasswordSuccess" style="display: none;"></div>
            <form method="POST" id="forgotPasswordForm" action="forgot-password.php">
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
                <a href="javascript:void(0)" onclick="closeForgotPasswordModal(); openLoginModal();">Back to Login</a>
            </div>
        </div>
    </div>

    <!-- Signup Modal -->
    <div class="auth-modal-overlay" id="signupModal">
        <div class="auth-modal signup-modal">
            <button class="auth-modal-close" onclick="closeSignupModal()">&times;</button>
            <div class="auth-modal-header">
                <h1>Create Account</h1>
                <p>Join Dwellscape Staycation today</p>
            </div>
            <div class="error-message" id="signupError" style="display: none;"></div>
            <div class="success-message" id="signupSuccess" style="display: none;"></div>
            <form method="POST" id="signupForm" action="signup.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="signupFirstName">First Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="signupFirstName" name="first_name" placeholder="First name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="signupMiddleInitial">Middle Initial</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="signupMiddleInitial" name="middle_initial" placeholder="M.I." maxlength="1" style="text-transform: uppercase;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="signupLastName">Last Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="signupLastName" name="last_name" placeholder="Last name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="signupUsername">Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-at input-icon"></i>
                            <input type="text" id="signupUsername" name="username" placeholder="Enter username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="signupEmail">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="signupEmail" name="email" placeholder="Enter email" required>
                        </div>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group password-group">
                        <label for="signupPassword">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="signupPassword" name="password" placeholder="Enter password" required>
                            <button type="button" class="password-toggle" onclick="toggleSignupPassword()">
                                <i class="fas fa-eye" id="signupToggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group password-group">
                        <label for="signupConfirmPassword">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="signupConfirmPassword" name="confirm_password" placeholder="Confirm password" required>
                            <button type="button" class="password-toggle" onclick="toggleSignupConfirmPassword()">
                                <i class="fas fa-eye" id="signupConfirmToggleIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="signupBirthday">Birthday</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar input-icon"></i>
                            <input type="date" id="signupBirthday" name="birthday" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="signupGender">Gender</label>
                        <div class="input-with-icon">
                            <i class="fas fa-venus-mars input-icon"></i>
                            <select id="signupGender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            <div class="auth-links">
                <a href="javascript:void(0)" onclick="closeSignupModal(); openLoginModal();">Already have an account? Sign in</a>
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
            
            if (profileSection && !profileSection.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Handle navbar background on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // =============================
        // Home hero background carousel
        // =============================
        document.addEventListener('DOMContentLoaded', function () {
            const heroImage = document.getElementById('homeHeroImage');
            if (!heroImage) return;

            // Try local images first, fallback to placeholders
            const heroSlides = [
                { local: 'pictures/dashboard1.png', fallback: 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop' },
                { local: 'pictures/dashboard2.png', fallback: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop' },
                { local: 'pictures/dashboard3.png', fallback: 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?q=80&w=1920&auto=format&fit=crop' },
                { local: 'pictures/dashboard4.png', fallback: 'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?q=80&w=1920&auto=format&fit=crop' }
            ];

            let currentSlide = 0;

            function setActiveSlide(index) {
                currentSlide = index;
                heroImage.style.opacity = '0';
                
                setTimeout(() => {
                    const slide = heroSlides[currentSlide];
                    const img = new Image();
                    
                    // Ensure hero section has proper background
                    const heroSection = document.querySelector('.hero-section');
                    if (heroSection) {
                        heroSection.style.background = '#1a1a1a'; // Dark background as fallback
                    }
                    heroImage.style.display = 'block';
                    
                    // Check if local image is valid (files < 1000 bytes are likely corrupted)
                    fetch(slide.local, { method: 'HEAD' })
                        .then(response => {
                            if (response.ok) {
                                const contentLength = response.headers.get('content-length');
                                if (contentLength && parseInt(contentLength) < 1000) {
                                    // File is too small (corrupted), skip to fallback
                                    throw new Error('File too small (corrupted)');
                                }
                                // File seems valid, try to load it
                                img.onload = function() {
                                    heroImage.src = slide.local;
                                    heroImage.style.opacity = '1';
                                    heroImage.style.display = 'block';
                                    heroImage.style.visibility = 'visible';
                                };
                                img.onerror = function() {
                                    loadFallbackImage();
                                };
                                img.src = slide.local;
                            } else {
                                throw new Error('File not found');
                            }
                        })
                        .catch(() => {
                            // File doesn't exist or is corrupted, use fallback immediately
                            loadFallbackImage();
                        });
                    
                    function loadFallbackImage() {
                        const fallbackImg = new Image();
                        fallbackImg.onload = function() {
                            heroImage.src = slide.fallback;
                            heroImage.style.opacity = '1';
                            heroImage.style.display = 'block';
                            heroImage.style.visibility = 'visible';
                        };
                        fallbackImg.onerror = function() {
                            // If both fail, use gradient background (brown/beige theme, NOT green)
                            heroImage.style.display = 'none';
                            if (heroSection) {
                                heroSection.style.background = 'linear-gradient(135deg, #7a6a4f 0%, #5a4d3a 50%, #4a3f2f 100%)';
                            }
                            const overlay = document.querySelector('.hero-overlay');
                            if (overlay) {
                                overlay.style.background = 'linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%)';
                            }
                        };
                        fallbackImg.src = slide.fallback;
                    }
                }, 200);
            }
            
            // Initialize first slide immediately
            setActiveSlide(0);
            
            // Force logo images to display and handle corrupted files
            document.addEventListener('DOMContentLoaded', function() {
                const logoImages = document.querySelectorAll('img[src*="dwellscape-logo"]');
                logoImages.forEach(function(img) {
                    // Force display
                    img.style.display = 'block';
                    img.style.visibility = 'visible';
                    img.style.opacity = '1';
                    
                    // Check if logo file is valid (not corrupted)
                    fetch(img.src, { method: 'HEAD' })
                        .then(response => {
                            if (response.ok) {
                                return response.headers.get('content-length');
                            }
                            throw new Error('File not found');
                        })
                        .then(contentLength => {
                            // If file is too small (likely corrupted), use a placeholder or SVG
                            if (parseInt(contentLength) < 1000) {
                                // Logo file is corrupted - showing SVG fallback (this is expected)
                                // console.warn('Logo file appears corrupted (' + contentLength + ' bytes), showing SVG fallback immediately');
                                img.style.display = 'none';
                                img.style.visibility = 'hidden';
                                img.style.opacity = '0';
                                const fallback = img.nextElementSibling;
                                if (fallback && fallback.classList.contains('logo-fallback')) {
                                    fallback.style.display = 'inline-block';
                                    fallback.style.visibility = 'visible';
                                    fallback.style.opacity = '1';
                                }
                                return;
                            } else {
                                // File seems valid, try to load it
                                const testImg = new Image();
                                testImg.onload = function() {
                                    img.src = img.src; // Reload if needed
                                    console.log('Logo image loaded successfully:', img.src);
                                };
                                testImg.onerror = function() {
                                    console.error('Logo image failed to load:', img.src);
                                    img.style.display = 'none';
                                    img.style.visibility = 'hidden';
                                    img.style.opacity = '0';
                                    const fallback = img.nextElementSibling;
                                    if (fallback && fallback.classList.contains('logo-fallback')) {
                                        fallback.style.display = 'inline-block';
                                        fallback.style.visibility = 'visible';
                                        fallback.style.opacity = '1';
                                    }
                                };
                                testImg.src = img.src;
                            }
                        })
                        .catch(() => {
                            // Can't check file, try to load anyway
                            const testImg = new Image();
                            testImg.onload = function() {
                                console.log('Logo image loaded:', img.src);
                            };
                            testImg.onerror = function() {
                                console.error('Logo image failed to load:', img.src);
                                img.style.display = 'none';
                                img.style.visibility = 'hidden';
                                img.style.opacity = '0';
                                const fallback = img.nextElementSibling;
                                if (fallback && fallback.classList.contains('logo-fallback')) {
                                    fallback.style.display = 'inline-block';
                                    fallback.style.visibility = 'visible';
                                    fallback.style.opacity = '1';
                                }
                            };
                            testImg.src = img.src;
                        });
                });
            });

            setInterval(() => {
                const next = (currentSlide + 1) % heroSlides.length;
                setActiveSlide(next);
            }, 4000);
        });

        // Modal Functions
        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLoginModal() {
            document.getElementById('loginModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('loginForm').reset();
            document.getElementById('loginError').style.display = 'none';
        }

        function openSignupModal() {
            document.getElementById('signupModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSignupModal() {
            document.getElementById('signupModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('signupForm').reset();
            document.getElementById('signupError').style.display = 'none';
        }

        function openForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.remove('active');
            document.body.style.overflow = '';
            document.getElementById('forgotPasswordForm').reset();
            document.getElementById('forgotPasswordError').style.display = 'none';
            document.getElementById('forgotPasswordSuccess').style.display = 'none';
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('auth-modal-overlay')) {
                closeLoginModal();
                closeSignupModal();
                closeForgotPasswordModal();
            }
        });

        // Close modals with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeLoginModal();
                closeSignupModal();
                closeForgotPasswordModal();
            }
        });

        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const errorDiv = document.getElementById('loginError');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            errorDiv.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing in...';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'login.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.withCredentials = true; // Ensure cookies are sent with AJAX request
            xhr.onload = function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign In';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('Login response:', response); // Debug log
                        if (response.success) {
                            // If cookie needs to be set via JavaScript (headers already sent)
                            if (response.auth_token) {
                                const expires = new Date(response.auth_expires * 1000);
                                const isSecure = window.location.protocol === 'https:';
                                
                                // Set cookie via JavaScript (cannot be HttpOnly when set via JS)
                                // Note: HttpOnly cookies cannot be set via JavaScript, so this is less secure
                                // but necessary when PHP headers are already sent
                                const cookieString = `auth_token=${response.auth_token}; expires=${expires.toUTCString()}; path=/; ${isSecure ? 'secure; ' : ''}SameSite=Lax`;
                                document.cookie = cookieString;
                                console.log('Cookie set via JavaScript:', cookieString);
                                console.log('Cookie expires:', expires.toUTCString());
                                console.log('All cookies after setting:', document.cookie);
                            }
                            
                            // Use the redirect URL from response, or determine based on role
                            let redirectUrl = response.redirect;
                            console.log('Response redirect:', response.redirect);
                            console.log('Response role:', response.role);
                            
                            if (!redirectUrl && response.role === 'admin') {
                                redirectUrl = 'admin/admin.php';
                            } else if (!redirectUrl) {
                                redirectUrl = 'dashboard.php';
                            }
                            
                            // Force admin redirect if role is admin
                            if (response.role === 'admin') {
                                redirectUrl = 'admin/admin.php';
                            }
                            
                            console.log('Final redirect URL:', redirectUrl);
                            
                            // Longer delay to ensure cookie is processed by browser
                            // Cookies set via JavaScript need time to be stored
                            setTimeout(function() {
                                // Verify cookie was set before redirecting
                                const cookies = document.cookie;
                                console.log('Cookies before redirect:', cookies);
                                
                                if (cookies.indexOf('auth_token=') === -1) {
                                    console.error('WARNING: auth_token cookie not found!');
                                    // Try setting again
                                    if (response.auth_token) {
                                        const expires = new Date(response.auth_expires * 1000);
                                        const isSecure = window.location.protocol === 'https:';
                                        document.cookie = `auth_token=${response.auth_token}; expires=${expires.toUTCString()}; path=/; ${isSecure ? 'secure; ' : ''}SameSite=Lax`;
                                        console.log('Retried setting cookie');
                                    }
                                }
                                
                                // Use window.location.href for full page reload with cookies
                                window.location.href = redirectUrl;
                            }, 300); // Increased delay to 300ms
                        } else {
                            // Show error message - no redirect
                            errorDiv.textContent = response.error || 'Invalid username or password.';
                            errorDiv.style.display = 'block';
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Sign In';
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e, 'Response:', xhr.responseText); // Debug log
                        // Not JSON, might be HTML redirect - try to parse
                        if (xhr.responseText.includes('admin')) {
                            window.location.href = 'admin/admin.php';
                        } else if (xhr.responseText.includes('dashboard')) {
                            window.location.href = 'dashboard.php';
                        } else {
                            errorDiv.textContent = 'Invalid username or password.';
                            errorDiv.style.display = 'block';
                        }
                    }
                } else {
                    errorDiv.textContent = 'An error occurred. Please try again.';
                    errorDiv.style.display = 'block';
                }
            };
            xhr.onerror = function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign In';
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            };
            xhr.send(formData);
        });

        // Signup form submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const errorDiv = document.getElementById('signupError');
            const successDiv = document.getElementById('signupSuccess');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating Account...';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'signup.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Account';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            successDiv.textContent = response.message || 'Account created successfully! You can now login.';
                            successDiv.style.display = 'block';
                            errorDiv.style.display = 'none';
                            setTimeout(() => {
                                closeSignupModal();
                                openLoginModal();
                                successDiv.style.display = 'none';
                            }, 2000);
                        } else {
                            errorDiv.textContent = response.error || 'An error occurred. Please try again.';
                            errorDiv.style.display = 'block';
                            successDiv.style.display = 'none';
                        }
                    } catch (e) {
                        // Not JSON, might be HTML - try to parse
                        if (xhr.responseText.includes('successfully') || xhr.responseText.includes('login')) {
                            successDiv.textContent = 'Account created successfully! You can now login.';
                            successDiv.style.display = 'block';
                            setTimeout(() => {
                                closeSignupModal();
                                openLoginModal();
                                successDiv.style.display = 'none';
                            }, 2000);
                        } else {
                            errorDiv.textContent = 'An error occurred. Please try again.';
                            errorDiv.style.display = 'block';
                        }
                    }
                } else {
                    errorDiv.textContent = 'An error occurred. Please try again.';
                    errorDiv.style.display = 'block';
                }
            };
            xhr.onerror = function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Account';
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            };
            xhr.send(formData);
        });

        // Forgot password form submission
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const errorDiv = document.getElementById('forgotPasswordError');
            const successDiv = document.getElementById('forgotPasswordSuccess');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'forgot-password.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Reset Link';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            successDiv.innerHTML = response.message || 'Password reset link has been sent to your email.';
                            successDiv.style.display = 'block';
                            errorDiv.style.display = 'none';
                            document.getElementById('forgotPasswordForm').reset();
                        } else {
                            errorDiv.textContent = response.error || 'An error occurred. Please try again.';
                            errorDiv.style.display = 'block';
                            successDiv.style.display = 'none';
                        }
                    } catch (e) {
                        // Not JSON, might be HTML - check for success indicators
                        if (xhr.responseText.includes('reset link') || xhr.responseText.includes('success')) {
                            successDiv.innerHTML = 'Password reset link has been generated. Check your email or the page for the reset link.';
                            successDiv.style.display = 'block';
                            errorDiv.style.display = 'none';
                        } else {
                            errorDiv.textContent = 'An error occurred. Please try again.';
                            errorDiv.style.display = 'block';
                        }
                    }
                } else {
                    errorDiv.textContent = 'An error occurred. Please try again.';
                    errorDiv.style.display = 'block';
                }
            };
            xhr.onerror = function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Reset Link';
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            };
            xhr.send(formData);
        });

        // Password toggle functions
        function toggleLoginPassword() {
            const passwordInput = document.getElementById('loginPassword');
            const toggleIcon = document.getElementById('loginToggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        function toggleSignupPassword() {
            const passwordInput = document.getElementById('signupPassword');
            const toggleIcon = document.getElementById('signupToggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        function toggleSignupConfirmPassword() {
            const passwordInput = document.getElementById('signupConfirmPassword');
            const toggleIcon = document.getElementById('signupConfirmToggleIcon');
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

