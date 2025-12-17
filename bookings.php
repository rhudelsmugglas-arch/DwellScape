<?php
// Use cookie-based authentication instead of sessions
if (!ob_get_level()) ob_start();

require_once 'config/database.php';
require_once 'config/auth.php';

// Verify authentication token
$current_user = verifyAuthToken();

if (!$current_user) {
    header('Location: home.php');
    exit();
}

// Get user data from database
$user_gender = null;
$user_profile_picture = null;
try {
    $stmt = $pdo->prepare("SELECT gender, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$current_user['user_id']]);
    $user_data = $stmt->fetch();
    $user_gender = $user_data['gender'] ?? null;
    $user_profile_picture = $user_data['profile_picture'] ?? null;
} catch(PDOException $e) {
    // If error, default to null
}

// Determine profile image - prioritize uploaded picture
$profile_image = 'pictures/boy.png'; // default
if ($user_profile_picture && file_exists($user_profile_picture)) {
    $profile_image = $user_profile_picture;
} elseif ($user_gender && strtolower($user_gender) === 'female') {
    $profile_image = 'pictures/woman.png';
} elseif ($user_gender && strtolower($user_gender) === 'male') {
    $profile_image = 'pictures/boy.png';
}

if (isset($_POST['logout'])) {
    clearAuthCookie();
    header('Location: home.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Now - Dwellscape</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/image-fallback.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: transparent;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .header {
            background: linear-gradient(180deg, #f1e4d1 0%, #e4d3bc 60%, #d5bfa0 100%);
            background-color: #e4d3bc;
            box-shadow: 0 2px 10px rgba(122, 106, 79, 0.25);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0 20px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto !important;
            margin-top: 0 !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
            padding: 0 !important;
            position: relative;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 28px;
            font-weight: 800;
            color: #7a6a4f;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .logo:hover {
            transform: translateY(-2px) scale(1.02);
            color: #6b5f48;
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

        .logo:hover .logo-text { 
            color: #6b5f48;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        .nav-links a:active {
            background: linear-gradient(180deg, #f1e4d1 0%, #e4d3bc 60%, #d5bfa0 100%);
            background-color: #e4d3bc;
            color: #7a6a4f;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(122, 106, 79, 0.2);
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
            padding: 8px 14px;
            border-radius: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
        }

        .profile-btn:hover {
            background: rgba(195, 176, 145, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(195, 176, 145, 0.15);
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
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            border: 1px solid rgba(195, 176, 145, 0.1);
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        }

        .profile-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
            font-weight: 500;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: linear-gradient(90deg, rgba(195, 176, 145, 0.1) 0%, rgba(195, 176, 145, 0.05) 100%);
            color: #7a6a4f;
            padding-left: 22px;
        }

        .dropdown-item:hover i {
            color: #C3B091;
            transform: scale(1.1);
        }

        .dropdown-item i {
            margin-right: 12px;
            width: 18px;
            text-align: center;
            transition: all 0.2s ease;
            color: #6b7280;
        }

        .main-content {
            margin-top: 0;
            position: relative;
            z-index: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Hide main content when modal is active */
        body.modal-active .main-content {
            pointer-events: none;
            user-select: none;
            opacity: 0.3;
            transition: opacity 0.3s ease;
        }

        body.modal-active .main-content.modal-hidden {
            opacity: 0.2;
        }

        /* Animated Background Section for Booking */
        .booking-hero-section {
            position: relative;
            width: 100%;
            min-height: 100vh;
            /* Stretch edge-to-edge horizontally */
            margin: 0 !important;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            padding: 0 !important;
            padding-top: 80px !important;
            padding-bottom: 40px !important;
            top: 0 !important;
            left: 0 !important;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            z-index: 0;
        }

        .booking-hero-image {
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

        .booking-hero-overlay {
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
            .booking-hero-image {
                animation: none;
            }
        }

        .booking-hero-content {
            position: relative;
            z-index: 3;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 48px;
            font-weight: 800;
            color: #8B7355;
            margin-bottom: 40px;
            text-shadow: 
                -1px -1px 0 #000000,
                1px -1px 0 #000000,
                -1px 1px 0 #000000,
                1px 1px 0 #000000,
                0 4px 12px rgba(0, 0, 0, 0.4), 
                0 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
            animation: fadeInDown 0.8s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-subtitle {
            display: none;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .date-selector {
            background: white;
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), 0 2px 8px rgba(0, 0, 0, 0.05);
            max-width: 1400px;
            width: 95%;
            margin: 0 auto 30px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
            border: 1px solid rgba(195, 176, 145, 0.1);
            overflow: hidden;
        }

        .calendar-instructions {
            background: #8B7355;
            color: white;
            padding: 16px 28px;
            text-align: left;
            font-size: 14px;
            line-height: 1.6;
        }

        .calendar-instructions p {
            margin: 0;
            color: white;
        }

        .calendar-content {
            padding: 32px 60px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            box-sizing: border-box;
        }

        .calendar-header {
            text-align: center;
            margin-bottom: 20px;
            width: 100%;
        }

        .calendar-period {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-title-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-top: 2px;
        }

        .calendar-nav-btn {
            background: transparent;
            border: 2px solid #8B7355;
            color: #8B7355;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .calendar-nav-btn:hover {
            background: #8B7355;
            color: white;
            transform: scale(1.1);
        }

        .calendar-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #000000;
            display: inline-block;
            position: relative;
            min-width: 200px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 12px;
            margin: 0 auto 24px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        .calendar-date {
            border: 2px solid #ff8c42;
            border-radius: 10px;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            min-height: 90px;
            aspect-ratio: 1.2;
            box-sizing: border-box;
            width: 100%;
        }

        .calendar-date:hover:not(.booked) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 140, 66, 0.2);
        }

        .calendar-date.selected {
            background: #fff5e6;
            border-color: #ff8c42;
            border-width: 3px;
        }

        .calendar-date.booked {
            border-color: #ef4444;
            border-width: 2px;
            background: #fef2f2;
            cursor: not-allowed;
            opacity: 0.7;
            pointer-events: none;
        }

        .calendar-date.booked .month,
        .calendar-date.booked .day,
        .calendar-date.booked .weekday {
            color: #991b1b;
        }

        .calendar-date .month {
            font-size: 11px;
            color: #ff8c42;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .calendar-date .day {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .calendar-date .weekday {
            font-size: 11px;
            color: #6b7280;
        }

        .calendar-date .today-label {
            position: absolute;
            bottom: 3px;
            left: 50%;
            transform: translateX(-50%);
            background: #10b981;
            color: white;
            font-size: 8px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: 3px;
            white-space: nowrap;
        }

        .proceed-btn {
            background: #8B7355;
            color: white;
            padding: 10px 32px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .proceed-btn:hover {
            background: #7a6a4f;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 115, 85, 0.3);
        }

        .proceed-btn:disabled {
            background: #8B7355;
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .rooms-section {
            display: none;
            animation: fadeInUp 0.5s ease;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            min-height: 100vh;
            position: relative;
            z-index: 10;
        }

        .rooms-section.active {
            display: block;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .rooms-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .rooms-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .rooms-header p {
            color: #6b7280;
            font-size: 16px;
        }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 28px;
            margin-bottom: 60px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding: 30px 20px;
            align-items: start;
        }

        @media (min-width: 1200px) {
            .rooms-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .rooms-grid {
                grid-template-columns: 1fr;
                gap: 24px;
                padding: 20px 10px;
            }
        }

        .room-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            min-height: 620px;
        }

        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
            border-color: rgba(195, 176, 145, 0.3);
        }

        .room-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            display: block;
            flex-shrink: 0;
            transition: transform 0.4s ease;
        }

        .room-card:hover .room-image {
            transform: scale(1.05);
        }

        .room-content {
            padding: 24px;
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
            gap: 18px;
        }

        .room-title {
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            line-height: 1.3;
            letter-spacing: -0.3px;
        }

        .room-description {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .room-specs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 0;
            padding: 18px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4b5563;
            font-size: 13px;
            font-weight: 500;
        }

        .spec-item i {
            color: #C3B091;
            font-size: 16px;
        }

        .room-amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 0;
            min-height: 32px;
            max-height: 80px;
            overflow: hidden;
        }

        .amenity-tag {
            background: rgba(195, 176, 145, 0.12);
            color: #7a6a4f;
            padding: 5px 11px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            border: 1px solid rgba(195, 176, 145, 0.2);
        }

        .amenity-tag-more {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
            padding: 5px 11px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .room-footer {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: auto;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
        }

        .room-price-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .room-actions {
            display: flex;
            gap: 12px;
            width: 100%;
            margin-top: 4px;
        }

        .room-price {
            display: flex;
            flex-direction: column;
        }

        .price-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .price-amount {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .price-period {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .book-room-btn {
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(195, 176, 145, 0.35), 0 2px 6px rgba(122, 106, 79, 0.2);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            letter-spacing: 0.3px;
            flex: 1;
        }

        .book-room-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 30px rgba(195, 176, 145, 0.45), 0 4px 12px rgba(122, 106, 79, 0.25);
            background: linear-gradient(135deg, #9A8B6F 0%, #7a6a4f 100%);
        }

        .book-room-btn:active {
            transform: translateY(-1px) scale(0.98);
        }

        .book-room-btn i {
            transition: transform 0.3s ease;
        }

        .book-room-btn:hover i {
            transform: translateX(4px);
        }

        .view-details-btn {
            background: white;
            color: #7a6a4f;
            padding: 12px 20px;
            border: 2px solid #C3B091;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            justify-content: center;
        }

        .view-details-btn:hover {
            background: #f8f9fa;
            border-color: #9A8B6F;
            transform: translateY(-2px);
        }

        .book-room-btn {
            flex: 1;
        }

        .no-rooms {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .no-rooms i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .no-rooms h3 {
            font-size: 24px;
            color: #374151;
            margin-bottom: 12px;
        }

        .no-rooms p {
            font-size: 16px;
        }

        /* Room Details Modal */
        .room-details-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 24px;
            overflow-y: auto;
        }

        .room-details-modal-overlay.active {
            display: flex;
        }

        .room-details-modal {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            margin: auto;
        }

        .room-details-modal .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            font-size: 32px;
            color: #6b7280;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: all 0.3s;
        }

        .room-details-modal .close-modal:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .room-details-content {
            padding: 40px;
        }

        .room-details-header {
            margin-bottom: 30px;
        }

        .room-details-header h2 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .room-details-header p {
            color: #666;
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .room-details-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .room-details-images img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .room-details-images img:hover {
            transform: scale(1.05);
        }

        .room-details-specs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .room-details-spec-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .room-details-spec-item i {
            font-size: 24px;
            color: #C3B091;
        }

        .room-details-spec-item div {
            display: flex;
            flex-direction: column;
        }

        .room-details-spec-item strong {
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .room-details-spec-item span {
            color: #6b7280;
            font-size: 16px;
        }

        .room-details-amenities {
            margin-bottom: 30px;
        }

        .room-details-amenities h3 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .room-details-amenities-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .room-details-amenity-tag {
            background: rgba(195, 176, 145, 0.1);
            color: #7a6a4f;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .room-details-price {
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }

        .room-details-price h3 {
            font-size: 18px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .room-details-price .price-large {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .room-details-price .price-small {
            font-size: 16px;
            opacity: 0.9;
        }

        /* Notification Modal */
        .notification-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 24px;
            animation: fadeIn 0.3s ease;
        }

        .notification-modal-overlay.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .notification-modal {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: slideUp 0.3s ease;
            overflow-y: auto;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-modal-header {
            padding: 24px 28px 18px;
            text-align: center;
            border-bottom: 1px solid #f3f4f6;
        }

        .notification-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 24px;
        }

        .notification-icon.error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .notification-icon.success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .notification-icon.warning {
            background: rgba(251, 191, 36, 0.1);
            color: #fbbf24;
        }

        .notification-modal-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .notification-modal-body {
            padding: 20px 28px;
            text-align: center;
        }

        .notification-modal-body p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
        }

        .notification-modal-footer {
            padding: 18px 28px 24px;
            display: flex;
            gap: 12px;
        }

        /* Bookings List Modal Styles */
        .booking-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 24px;
        }

        .booking-modal-overlay.active {
            display: flex;
        }

        .bookings-list-modal {
            background: #ffffff;
            border-radius: 18px;
            width: min(95%, 1400px);
            max-height: 90vh;
            overflow-y: auto;
            padding: 36px;
            box-shadow: 0 40px 70px rgba(15, 23, 42, 0.18);
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 24px;
            right: 24px;
            background: none;
            border: none;
            font-size: 32px;
            color: #6b7280;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
            z-index: 1;
        }

        .close-modal:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .bookings-card {
            box-shadow: none;
            padding: 0;
        }

        .bookings-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .bookings-title-group h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .bookings-title-group p {
            color: #6b7280;
            font-size: 14px;
        }

        .bookings-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-field {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-field i {
            position: absolute;
            left: 16px;
            color: #9ca3af;
        }

        .search-field input {
            padding: 10px 16px 10px 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
        }

        .search-field input:focus {
            outline: none;
            border-color: #C3B091;
        }

        .bookings-actions select {
            padding: 10px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
        }

        .cta-button {
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cta-button:hover {
            opacity: 0.9;
        }

        .bookings-table-wrapper {
            overflow-x: auto;
            margin-bottom: 24px;
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bookings-table th {
            text-align: left;
            padding: 12px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .bookings-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #1f2937;
        }

        .bookings-table tbody tr:hover {
            background: #f9fafb;
        }


        .bookings-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 28px;
            flex-wrap: wrap;
            gap: 18px;
        }

        .pagination-button {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pagination-button:hover:not(:disabled) {
            border-color: #C3B091;
            color: #C3B091;
        }

        .pagination-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            font-size: 16px;
        }

        .modal-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(195, 176, 145, 0.35), 0 2px 8px rgba(122, 106, 79, 0.2);
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal-btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 30px rgba(195, 176, 145, 0.45), 0 4px 12px rgba(122, 106, 79, 0.25);
            background: linear-gradient(135deg, #9A8B6F 0%, #7a6a4f 100%);
        }

        .modal-btn-primary:active {
            transform: translateY(-1px) scale(0.98);
        }

        .modal-btn-secondary {
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal-btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .booking-confirmation-details {
            background: #f9fafb;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            text-align: left;
        }

        .booking-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 20px;
            margin-bottom: 16px;
        }

        .booking-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .booking-detail-row:last-child {
            border-bottom: none;
        }

        .booking-detail-label {
            color: #6b7280;
            font-size: 13px;
        }

        .booking-detail-value {
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
        }

        .booking-total {
            grid-column: 1 / -1;
            margin-top: 8px;
            padding-top: 12px;
            border-top: 2px solid #e5e7eb;
        }

        .booking-total .booking-detail-value {
            font-size: 18px;
            color: #C3B091;
        }

        .booking-terms-checkbox {
            margin-top: 14px;
            padding: 12px;
            background: #fef2f2;
            border-radius: 8px;
            border-left: 3px solid #ef4444;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            text-align: left;
        }

        .booking-terms-checkbox input[type="checkbox"] {
            margin-top: 2px;
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #ef4444;
            flex-shrink: 0;
        }

        .booking-terms-checkbox label {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.5;
            cursor: pointer;
            user-select: none;
            flex: 1;
        }

        .booking-terms-checkbox label strong {
            color: #991b1b;
        }

        .modal-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Terms and Conditions Modal */
        .terms-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 24px;
            overflow-y: auto;
        }

        .terms-modal-overlay.active {
            display: flex;
        }

        .terms-modal {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            margin: auto;
        }

        .terms-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            font-size: 32px;
            color: #6b7280;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: all 0.3s;
        }

        .terms-modal-close:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .terms-modal-header {
            padding: 30px 30px 20px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .terms-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dbeafe;
            border: 2px solid #93c5fd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            font-size: 20px;
        }

        .terms-modal-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .terms-modal-content {
            padding: 30px;
        }

        .terms-section {
            margin-bottom: 24px;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .terms-section-red {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .terms-section-blue {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .terms-section-white {
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }

        .terms-section-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .red-icon {
            background: #ef4444;
            color: white;
        }

        .blue-icon {
            background: #3b82f6;
            color: white;
        }

        .light-blue-icon {
            background: #dbeafe;
            color: #3b82f6;
        }

        .terms-section-content {
            flex: 1;
        }

        .terms-section-content h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .terms-section-red h3 {
            color: #991b1b;
        }

        .terms-section-blue h3 {
            color: #1e40af;
        }

        .terms-section-content ul {
            list-style: disc;
            padding-left: 20px;
            margin: 0;
        }

        .terms-section-red ul li {
            color: #991b1b;
            margin-bottom: 8px;
        }

        .terms-section-content p {
            color: #374151;
            line-height: 1.6;
            margin: 0;
        }

        .terms-warning-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 12px;
            margin: 12px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .terms-warning-icon {
            color: #f59e0b;
            font-size: 20px;
        }

        .terms-warning-text {
            color: #374151;
            font-weight: 600;
        }

        .terms-checkbox-wrapper {
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .terms-checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #3b82f6;
        }

        .terms-checkbox-wrapper label {
            color: #374151;
            cursor: pointer;
            font-size: 14px;
        }

        .terms-modal-footer {
            padding: 20px 30px 30px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            border-top: 1px solid #e5e7eb;
        }

        .terms-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .terms-btn-cancel {
            background: #ef4444;
            color: white;
        }

        .terms-btn-cancel:hover {
            background: #dc2626;
        }

        .terms-btn-accept {
            background: #9ca3af;
            color: #374151;
        }

        .terms-btn-accept:not(:disabled) {
            background: #3b82f6;
            color: white;
        }

        .terms-btn-accept:not(:disabled):hover {
            background: #2563eb;
        }

        .terms-btn-accept:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Date Range and Time Selection Modal */
        .date-time-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 24px;
        }

        .date-time-modal-overlay.active {
            display: flex;
        }

        .date-time-modal {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: slideUp 0.3s ease;
        }

        .date-time-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            font-size: 28px;
            color: #6b7280;
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: all 0.3s;
        }

        .date-time-modal-close:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .date-time-modal-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .date-time-modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .date-time-modal-content {
            padding: 24px;
        }

        .date-time-info {
            margin-bottom: 20px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-item label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
        }

        .info-item span {
            font-weight: 700;
            color: #1f2937;
            font-size: 14px;
        }

        .date-time-section {
            margin-bottom: 24px;
        }

        .date-time-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .date-picker-wrapper {
            width: 100%;
        }

        .date-picker-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            color: #1f2937;
            transition: all 0.3s ease;
        }

        .date-picker-input:focus {
            outline: none;
            border-color: #8B7355;
            box-shadow: 0 0 0 3px rgba(139, 115, 85, 0.1);
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .time-slot-btn {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .time-slot-btn:hover {
            border-color: #8B7355;
            background: #f9fafb;
        }

        .time-slot-btn.selected {
            background: #8B7355;
            border-color: #8B7355;
            color: white;
        }

        .date-time-modal-footer {
            padding: 16px 24px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            border-top: 1px solid #e5e7eb;
        }

        .date-time-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .date-time-btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }

        .date-time-btn-cancel:hover {
            background: #e5e7eb;
        }

        .date-time-btn-confirm {
            background: #8B7355;
            color: white;
        }

        .date-time-btn-confirm:hover:not(:disabled) {
            background: #7a6a4f;
        }

        .date-time-btn-confirm:disabled {
            background: #d1d5db;
            color: #9ca3af;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .booking-hero-section {
                min-height: 100vh;
                padding-top: 80px !important;
                padding-bottom: 40px !important;
            }

            .rooms-grid {
                grid-template-columns: 1fr;
            }

            .room-card {
                max-height: none;
            }

            .room-image {
                height: 220px;
            }

            .room-specs {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 42px;
            }

            .page-subtitle {
                font-size: 18px;
            }

            .date-selector {
                padding: 24px 16px;
                margin-bottom: 30px;
            }

            .calendar-grid {
                gap: 6px;
            }

            .calendar-date {
                padding: 8px 4px;
                min-height: 70px;
            }

            .calendar-date .day {
                font-size: 16px;
            }

            .calendar-date .month {
                font-size: 9px;
            }

            .calendar-date .weekday {
                font-size: 9px;
            }

            .date-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-btn {
                width: 100%;
                justify-content: center;
            }

            .rooms-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .room-footer {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }

            .book-room-btn {
                width: 100%;
                justify-content: center;
            }

            .terms-modal {
                max-width: 95%;
                max-height: 95vh;
            }

            .terms-modal-header {
                padding: 20px 20px 15px;
            }

            .terms-modal-content {
                padding: 20px;
            }

            .terms-section {
                flex-direction: column;
                padding: 16px;
            }

            .terms-modal-footer {
                padding: 15px 20px 20px;
                flex-direction: column;
            }

            .terms-btn {
                width: 100%;
            }

            .date-time-modal {
                max-width: 95%;
            }

            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }

            .date-time-modal-footer {
                flex-direction: column;
            }

            .date-time-btn {
                width: 100%;
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
                    <li><a href="dashboard.php#amenities">Amenities</a></li>
                    <li><a href="dashboard.php#gallery">Gallery</a></li>
                    <li><a href="dashboard.php#about">About</a></li>
                    <li><a href="dashboard.php#contact">Contact</a></li>
                    <li><a href="bookings.php" class="active">Book Now</a></li>
                </ul>
            </nav>

            <div class="profile-section">
                <button class="profile-btn" onclick="toggleProfileDropdown()">
                    <div class="profile-avatar">
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" onerror="this.style.display='none'; this.parentElement.innerHTML='<?php echo strtoupper(substr($current_user['username'], 0, 1)); ?>';">
                    </div>
                    <span class="profile-name"><?php echo htmlspecialchars($current_user['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-cog"></i>
                        Profile Settings
                    </a>
                    <a href="javascript:void(0)" class="dropdown-item" id="openBookingsListModalDropdown">
                        <i class="fas fa-calendar"></i>
                        Bookings
                    </a>
                    <a href="notifications.php" class="dropdown-item">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                    <form method="POST" class="dropdown-item" style="padding: 0;">
                        <button type="submit" name="logout" style="background: none; border: none; width: 100%; text-align: left; padding: 14px 18px; cursor: pointer; display: flex; align-items: center; color: #333; font-weight: 500; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; line-height: 1.5; transition: all 0.2s ease;" onmouseover="this.style.paddingLeft='22px'; this.style.background='linear-gradient(90deg, rgba(195, 176, 145, 0.1) 0%, rgba(195, 176, 145, 0.05) 100%)'; this.style.color='#7a6a4f';" onmouseout="this.style.paddingLeft='18px'; this.style.background='none'; this.style.color='#333';">
                            <i class="fas fa-sign-out-alt" style="margin-right: 12px; width: 18px; text-align: center; color: #6b7280;"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Animated Booking Hero Section -->
        <section class="booking-hero-section">
            <!-- Background image -->
            <img src="pictures/bookings.png" alt="Dwellscape Booking" class="booking-hero-image" style="display: block !important; visibility: visible !important; opacity: 1 !important;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop';">
            <div class="booking-hero-overlay"></div>
            
            <div class="booking-hero-content">
                <div class="page-header">
                    <h1 class="page-title">Booking/Schedules</h1>
                </div>

                <!-- Calendar Date Selector -->
                <div class="date-selector">
                    <div class="calendar-instructions">
                        <p>Choose a date to proceed to payment</p>
                        <p>* If an advance booking is cancelled, 50% of the payment will be charged.</p>
                    </div>
                    <div class="calendar-content">
                        <div class="calendar-header">
                            <div class="calendar-period">UPCOMING 28 DAYS</div>
                            <div class="calendar-title-wrapper">
                                <button class="calendar-nav-btn" id="prevMonthBtn">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <div class="calendar-title" id="calendarTitle">November-December 2025</div>
                                <button class="calendar-nav-btn" id="nextMonthBtn">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <div class="calendar-grid" id="calendarGrid">
                            <!-- Calendar dates will be dynamically generated -->
                        </div>
                        <button class="proceed-btn" id="proceedBtn" disabled>Proceed</button>
                    </div>
                </div>
            </div>
        </section>

        <div class="container">

            <!-- Available Rooms Section -->
            <div class="rooms-section" id="roomsSection">
                <div class="rooms-header">
                    <h2>Available Rooms</h2>
                    <p id="dateRangeText">Select dates to see available rooms</p>
                    </div>

                <div class="rooms-grid" id="roomsGrid">
                    <!-- Rooms will be dynamically inserted here -->
                            </div>
                            </div>
                        </div>
    </main>

    <!-- Room Details Modal -->
    <div class="room-details-modal-overlay" id="roomDetailsModal">
        <div class="room-details-modal">
            <button class="close-modal" onclick="closeRoomDetails()">&times;</button>
            <div id="roomDetailsContent">
                <!-- Room details will be inserted here -->
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="notification-modal-overlay" id="notificationModal">
        <div class="notification-modal">
            <div class="notification-modal-header">
                <div class="notification-icon" id="notificationIcon">
                    <i class="fas fa-info-circle"></i>
                                </div>
                <h3 id="notificationTitle">Notification</h3>
                                </div>
            <div class="notification-modal-body">
                <p id="notificationMessage"></p>
                <div id="bookingDetails" style="display: none;"></div>
                                </div>
            <div class="notification-modal-footer" id="notificationFooter">
                <button class="modal-btn modal-btn-primary" id="notificationConfirm">OK</button>
                                </div>
                            </div>
                        </div>

    <!-- Bookings List Modal -->
    <div class="booking-modal-overlay" id="bookingsListModal">
        <div class="bookings-list-modal">
            <button class="close-modal" id="closeBookingsListModal">&times;</button>
            <div class="bookings-card">
                <div class="bookings-header">
                    <div class="bookings-title-group">
                        <h2>Reservation List</h2>
                        <p>Monitor, filter, and manage every staycation booking in one place.</p>
                    </div>
                    <div class="bookings-actions">
                        <div class="search-field">
                            <i class="fas fa-search"></i>
                            <input type="text" id="bookingSearch" placeholder="Search guest or reference...">
                        </div>
                    </div>
                </div>

                <div class="bookings-table-wrapper">
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>Booking Date</th>
                                <th>Reservation Dates</th>
                                <th>Time</th>
                                <th>Guest</th>
                                <th>Suite</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody id="bookingTableBody">
                            <!-- Populated by script -->
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noBookingResults" style="display: none;">
                    No reservations match your filters. Try adjusting your search.
                </div>

                <div class="bookings-pagination">
                    <p id="bookingSummary" style="color: #6b7280; font-size: 14px;">Showing 0 reservations</p>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button class="pagination-button" id="prevPage"><i class="fas fa-chevron-left"></i> Prev</button>
                        <div id="paginationInfo" style="font-size: 14px; color: #6b7280;"></div>
                        <button class="pagination-button" id="nextPage">Next <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="terms-modal-overlay" id="termsModal">
        <div class="terms-modal">
            <button class="terms-modal-close" id="closeTermsModal">&times;</button>
            <div class="terms-modal-header">
                <div class="terms-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <h2>Terms and Conditions</h2>
            </div>
            
            <div class="terms-modal-content">
                <!-- Section 5: Failed or Declined Payments -->
                <div class="terms-section terms-section-red">
                    <div class="terms-section-icon red-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="terms-section-content">
                        <h3>1. Failed or Declined Payments</h3>
                        <ul>
                            <li>If a payment fails or is declined, the reservation will not be processed</li>
                            <li>Users are responsible for ensuring sufficient funds and correct payment details</li>
                            <li>Contact your bank if payment issues persist</li>
                        </ul>
                    </div>
                </div>

                <!-- Section 6: Refunds and Cancellation Policy -->
                <div class="terms-section terms-section-blue">
                    <div class="terms-section-icon blue-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="terms-section-content">
                        <h3>2. Refunds and Cancellation Policy</h3>
                        <div class="terms-warning-box">
                            <div class="terms-warning-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="terms-warning-text">
                                <strong>Important: Cancellation Policy</strong>
                            </div>
                        </div>
                        <p>All confirmed staycation reservations are non-refundable in full. In case of cancellation, only 50% of the total payment will be refunded. Please confirm your availability before booking.</p>
                    </div>
                </div>

                <!-- Agreement Required -->
                <div class="terms-section terms-section-white">
                    <div class="terms-section-icon light-blue-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="terms-section-content">
                        <h3>Agreement Required</h3>
                        <p>By clicking "Proceed", you acknowledge that you have read, understood, and agree to be bound by all the terms and conditions outlined above.</p>
                        <div class="terms-checkbox-wrapper">
                            <input type="checkbox" id="acceptTermsCheckbox">
                            <label for="acceptTermsCheckbox">I accept the terms and conditions</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="terms-modal-footer">
                <button class="terms-btn terms-btn-cancel" id="cancelTermsBtn">Cancel</button>
                <button class="terms-btn terms-btn-accept" id="acceptTermsBtn" disabled>Please Accept Terms First</button>
            </div>
        </div>
    </div>

    <!-- Date Range and Time Selection Modal -->
    <div class="date-time-modal-overlay" id="dateTimeModal">
        <div class="date-time-modal">
            <button class="date-time-modal-close" id="closeDateTimeModal">&times;</button>
            <div class="date-time-modal-header">
                <h3>Select Check-out Date & Time</h3>
            </div>
            
            <div class="date-time-modal-content">
                <div class="date-time-info">
                    <div class="info-item">
                        <label>Check-in Date:</label>
                        <span id="selectedCheckinDate"></span>
                    </div>
                </div>

                <div class="date-time-section">
                    <label class="date-time-label">Check-out Date:</label>
                    <div class="date-picker-wrapper">
                        <input type="date" id="checkoutDatePicker" class="date-picker-input" min="">
                    </div>
                </div>

                <div class="date-time-section">
                    <label class="date-time-label">Time (3pm - 10pm):</label>
                    <div class="time-slots">
                        <button class="time-slot-btn" data-time="15:00">3:00 PM</button>
                        <button class="time-slot-btn" data-time="16:00">4:00 PM</button>
                        <button class="time-slot-btn" data-time="17:00">5:00 PM</button>
                        <button class="time-slot-btn" data-time="18:00">6:00 PM</button>
                        <button class="time-slot-btn" data-time="19:00">7:00 PM</button>
                        <button class="time-slot-btn" data-time="20:00">8:00 PM</button>
                        <button class="time-slot-btn" data-time="21:00">9:00 PM</button>
                        <button class="time-slot-btn" data-time="22:00">10:00 PM</button>
                    </div>
                </div>
            </div>

            <div class="date-time-modal-footer">
                <button class="date-time-btn date-time-btn-cancel" id="cancelDateTimeBtn">Cancel</button>
                <button class="date-time-btn date-time-btn-confirm" id="confirmDateTimeBtn" disabled>Confirm</button>
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

        // Bookings List Modal Functionality
        let allBookings = [];
        let filteredBookings = [];
        const pageSize = 6;
        let currentPage = 1;

        const bookingsListModal = document.getElementById('bookingsListModal');
        const closeBookingsListBtn = document.getElementById('closeBookingsListModal');
        const openBookingsListDropdownBtn = document.getElementById('openBookingsListModalDropdown');
        const searchInput = document.getElementById('bookingSearch');
        const tableBody = document.getElementById('bookingTableBody');
        const noResults = document.getElementById('noBookingResults');
        const summaryText = document.getElementById('bookingSummary');
        const paginationInfo = document.getElementById('paginationInfo');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');

        function openBookingsListModal(e) {
            if (e) e.preventDefault();
            closeAllModals(); // Close any other open modals first
            if (bookingsListModal) {
                bookingsListModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                updateModalState();
                // Load bookings when modal opens
                loadBookings();
            }
        }

        function closeBookingsListModal() {
            if (bookingsListModal) {
                bookingsListModal.classList.remove('active');
            }
            updateModalState();
        }

        async function loadBookings() {
            try {
                const response = await fetch('api/get_bookings.php', {
                    credentials: 'include' // Include cookies for authentication
                });
                const result = await response.json();
                
                if (result.success && result.bookings) {
                    allBookings = result.bookings;
                    applyFilters();
                } else {
                    allBookings = [];
                    renderTable();
                }
            } catch (error) {
                console.error('Error loading bookings:', error);
                allBookings = [];
                renderTable();
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString + 'T00:00:00');
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
        }

        function formatReservationRange(checkIn, checkOut) {
            const checkInDate = formatDate(checkIn);
            const checkOutDate = formatDate(checkOut);
            return `${checkInDate} - ${checkOutDate}`;
        }

        function renderTable() {
            if (!tableBody) return;

            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            const pageBookings = filteredBookings.slice(start, end);
            const total = filteredBookings.length;
            const totalPages = Math.ceil(total / pageSize);

            tableBody.innerHTML = '';

            if (pageBookings.length === 0) {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
                pageBookings.forEach(booking => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${formatDate(booking.bookingDate)}</td>
                        <td>${formatReservationRange(booking.checkIn, booking.checkOut)}</td>
                        <td>${booking.time}</td>
                        <td>${booking.guest}</td>
                        <td>${booking.suite}</td>
                        <td>₱${(booking.totalPrice || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    `;
                    tableBody.appendChild(row);
                });
            }

            summaryText.textContent = `Showing ${start + 1}-${Math.min(end, total)} of ${total} reservations`;
            paginationInfo.textContent = totalPages > 0 ? `Page ${currentPage} of ${totalPages}` : '';
            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
        }

            function applyFilters() {
                const query = searchInput.value.trim().toLowerCase();

                filteredBookings = allBookings.filter(booking => {
                    const matchesSearch = !query ||
                        booking.guest.toLowerCase().includes(query) ||
                        booking.id.toLowerCase().includes(query) ||
                        booking.suite.toLowerCase().includes(query) ||
                        (booking.totalPrice && booking.totalPrice.toString().includes(query));
                    return matchesSearch;
                });

            currentPage = 1;
            renderTable();
        }

        // Event listeners
        if (openBookingsListDropdownBtn) {
            openBookingsListDropdownBtn.addEventListener('click', function(e) {
                openBookingsListModal(e);
                const dropdown = document.getElementById('profileDropdown');
                if (dropdown) {
                    dropdown.classList.remove('active');
                }
            });
        }

        if (closeBookingsListBtn) {
            closeBookingsListBtn.addEventListener('click', closeBookingsListModal);
        }

        if (bookingsListModal) {
            bookingsListModal.addEventListener('click', function(e) {
                if (e.target === bookingsListModal) {
                    closeBookingsListModal();
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }

        if (prevPageBtn) {
            prevPageBtn.addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });
        }

        if (nextPageBtn) {
            nextPageBtn.addEventListener('click', function() {
                const totalPages = Math.ceil(filteredBookings.length / pageSize);
                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }
            });
        }

        // Room data (will be populated from API)
        let rooms = [];
        let selectedDate = null;
        let selectedCheckin = null;
        let selectedCheckout = null;
        let selectedTime = null;
        let currentStartDate = new Date(); // Track the starting date for the calendar
        let bookedDates = []; // Store booked dates

        // Fetch booked dates from API
        async function fetchBookedDates() {
            try {
                const response = await fetch('api/get_booked_dates.php', {
                    credentials: 'include' // Include cookies for authentication
                });
                const result = await response.json();
                
                if (result.success && result.booked_dates) {
                    bookedDates = result.booked_dates;
                } else {
                    bookedDates = [];
                }
            } catch (error) {
                console.error('Error fetching booked dates:', error);
                bookedDates = [];
            }
        }

        // Calendar functionality
        async function generateCalendar(startDate = null) {
            // Fetch booked dates first
            await fetchBookedDates();
            
            const calendarGrid = document.getElementById('calendarGrid');
            const calendarTitle = document.getElementById('calendarTitle');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Use provided start date or default to today
            if (startDate) {
                currentStartDate = new Date(startDate);
            } else {
                currentStartDate = new Date(today);
            }
            currentStartDate.setHours(0, 0, 0, 0);
            
            const dates = [];
            
            // Generate next 28 days from start date
            for (let i = 0; i < 28; i++) {
                const date = new Date(currentStartDate);
                date.setDate(currentStartDate.getDate() + i);
                dates.push(new Date(date));
            }
            
            // Update calendar title
            const firstDate = dates[0];
            const lastDate = dates[dates.length - 1];
            const firstMonth = firstDate.toLocaleDateString('en-US', { month: 'long' });
            const lastMonth = lastDate.toLocaleDateString('en-US', { month: 'long' });
            const firstYear = firstDate.getFullYear();
            const lastYear = lastDate.getFullYear();
            
            if (firstMonth === lastMonth && firstYear === lastYear) {
                calendarTitle.textContent = `${firstMonth} ${firstYear}`;
            } else if (firstYear === lastYear) {
                calendarTitle.textContent = `${firstMonth}-${lastMonth} ${firstYear}`;
            } else {
                calendarTitle.textContent = `${firstMonth} ${firstYear}-${lastMonth} ${lastYear}`;
            }
            
            // Clear calendar grid
            calendarGrid.innerHTML = '';
            
            // Create date cells
            dates.forEach((date) => {
                const dateCell = document.createElement('div');
                // Format date as YYYY-MM-DD without timezone conversion
                const year = date.getFullYear();
                const monthNum = String(date.getMonth() + 1).padStart(2, '0');
                const dayNum = String(date.getDate()).padStart(2, '0');
                const dateString = `${year}-${monthNum}-${dayNum}`;
                dateCell.className = 'calendar-date';
                dateCell.dataset.date = dateString;
                
                // Check if this date is booked
                const isBooked = bookedDates.includes(dateString);
                
                if (isBooked) {
                    dateCell.classList.add('booked');
                }
                
                const month = date.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
                const day = date.getDate();
                const weekday = date.toLocaleDateString('en-US', { weekday: 'long' });
                
                // Check if this date is today
                const dateOnly = new Date(date);
                dateOnly.setHours(0, 0, 0, 0);
                const todayOnly = new Date(today);
                todayOnly.setHours(0, 0, 0, 0);
                const isToday = dateOnly.getTime() === todayOnly.getTime();
                
                dateCell.innerHTML = `
                    <div class="month">${month}</div>
                    <div class="day">${day}</div>
                    <div class="weekday">${weekday}</div>
                    ${isToday ? '<div class="today-label">TODAY</div>' : ''}
                `;
                
                // Add click handler only if not booked
                if (!isBooked) {
                    dateCell.addEventListener('click', function() {
                        const clickedDate = this.dataset.date;
                        
                        // Remove previous selection from all dates
                        document.querySelectorAll('.calendar-date').forEach(cell => {
                            cell.classList.remove('selected');
                        });
                        
                        // Add selection to clicked date
                        this.classList.add('selected');
                        selectedDate = clickedDate;
                        selectedCheckin = clickedDate; // Store check-in date
                        
                        // Show date-time selection modal
                        showDateTimeModal(clickedDate);
                    });
                }
                
                calendarGrid.appendChild(dateCell);
            });
        }
        
        // Navigation functions
        async function navigateMonth(direction) {
            const newDate = new Date(currentStartDate);
            newDate.setMonth(currentStartDate.getMonth() + direction);
            await generateCalendar(newDate);
        }
        
        // Initialize calendar on page load
        generateCalendar();
        
        // Add event listeners for navigation buttons
        document.getElementById('prevMonthBtn').addEventListener('click', function() {
            navigateMonth(-1);
        });
        
        document.getElementById('nextMonthBtn').addEventListener('click', function() {
            navigateMonth(1);
        });
        
        // Centralized Modal Management - Close all modals before opening a new one
        function closeAllModals() {
            // Close all modals by ID
            const modalIds = [
                'dateTimeModal',
                'termsModal',
                'notificationModal',
                'roomDetailsModal',
                'bookingsListModal'
            ];
            
            modalIds.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('active');
                }
            });
            
            // Also close by class selector (for safety)
            document.querySelectorAll('.date-time-modal-overlay.active, .terms-modal-overlay.active, .notification-modal-overlay.active, .room-details-modal-overlay.active, .booking-modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
            
            // Reset body overflow and remove modal-active class only if no modals are open
            const anyModalOpen = document.querySelector('.terms-modal-overlay.active, .date-time-modal-overlay.active, .notification-modal-overlay.active, .room-details-modal-overlay.active, .booking-modal-overlay.active');
            if (!anyModalOpen) {
                document.body.style.overflow = '';
                document.body.classList.remove('modal-active');
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.classList.remove('modal-hidden');
                }
            }
        }

        // Function to update modal state (add/remove classes for styling)
        function updateModalState() {
            const anyModalOpen = document.querySelector('.terms-modal-overlay.active, .date-time-modal-overlay.active, .notification-modal-overlay.active, .room-details-modal-overlay.active, .booking-modal-overlay.active');
            if (anyModalOpen) {
                document.body.classList.add('modal-active');
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.classList.add('modal-hidden');
                }
            } else {
                document.body.classList.remove('modal-active');
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.classList.remove('modal-hidden');
                }
            }
        }
        
        // Terms and Conditions Modal Functions
        function showTermsModal() {
            closeAllModals(); // Close any other open modals first
            const modal = document.getElementById('termsModal');
            if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
                updateModalState();
            }
        }

        function closeTermsModal() {
            const modal = document.getElementById('termsModal');
            if (modal) {
            modal.classList.remove('active');
            }
            updateModalState();
            // Reset checkbox
            const checkbox = document.getElementById('acceptTermsCheckbox');
            const acceptBtn = document.getElementById('acceptTermsBtn');
            if (checkbox) checkbox.checked = false;
            if (acceptBtn) {
                acceptBtn.disabled = true;
                acceptBtn.textContent = 'Please Accept Terms First';
            }
        }

        // Date-Time Selection Modal Functions
        function showDateTimeModal(checkinDate) {
            closeAllModals(); // Close any other open modals first
            const modal = document.getElementById('dateTimeModal');
            const checkinDateEl = document.getElementById('selectedCheckinDate');
            const checkoutDatePicker = document.getElementById('checkoutDatePicker');
            
            if (!modal || !checkinDateEl || !checkoutDatePicker) return;
            
            // Set check-in date (parse as local date to avoid timezone issues)
            const checkinParts = checkinDate.split('-');
            const checkin = new Date(parseInt(checkinParts[0]), parseInt(checkinParts[1]) - 1, parseInt(checkinParts[2]));
            checkinDateEl.textContent = checkin.toLocaleDateString('en-US', { 
                month: 'long', 
                day: 'numeric', 
                year: 'numeric' 
            });
            
            // Set minimum date for checkout (next day after check-in)
            const minCheckout = new Date(checkin);
            minCheckout.setDate(minCheckout.getDate() + 1);
            // Format as YYYY-MM-DD without timezone conversion
            const year = minCheckout.getFullYear();
            const month = String(minCheckout.getMonth() + 1).padStart(2, '0');
            const day = String(minCheckout.getDate()).padStart(2, '0');
            checkoutDatePicker.min = `${year}-${month}-${day}`;
            
            // Reset selections
            checkoutDatePicker.value = '';
            document.querySelectorAll('.time-slot-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            const confirmBtn = document.getElementById('confirmDateTimeBtn');
            if (confirmBtn) confirmBtn.disabled = true;
            
            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            updateModalState();
        }

        function closeDateTimeModal() {
            const modal = document.getElementById('dateTimeModal');
            if (modal) {
            modal.classList.remove('active');
            }
            updateModalState();
        }

        // Time slot selection
        document.querySelectorAll('.time-slot-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedTime = this.dataset.time;
                checkDateTimeSelection();
            });
        });

        // Checkout date change
        document.getElementById('checkoutDatePicker').addEventListener('change', function() {
            selectedCheckout = this.value;
            checkDateTimeSelection();
        });

        function checkDateTimeSelection() {
            const confirmBtn = document.getElementById('confirmDateTimeBtn');
            const checkoutDate = document.getElementById('checkoutDatePicker').value;
            const hasTime = document.querySelector('.time-slot-btn.selected') !== null;
            
            if (checkoutDate && hasTime) {
                confirmBtn.disabled = false;
            } else {
                confirmBtn.disabled = true;
            }
        }

        // Confirm date-time selection
        document.getElementById('confirmDateTimeBtn').addEventListener('click', function() {
            const checkoutDate = document.getElementById('checkoutDatePicker').value;
            const selectedTimeBtn = document.querySelector('.time-slot-btn.selected');
            
            if (checkoutDate && selectedTimeBtn) {
                selectedCheckin = selectedDate;
                selectedCheckout = checkoutDate;
                selectedTime = selectedTimeBtn.dataset.time;
                
                // Enable proceed button
                document.getElementById('proceedBtn').disabled = false;
                
                closeDateTimeModal();
            }
        });

        // Cancel date-time modal
        document.getElementById('cancelDateTimeBtn').addEventListener('click', function() {
            // Remove selection
            document.querySelectorAll('.calendar-date').forEach(cell => {
                cell.classList.remove('selected');
            });
            selectedDate = null;
            selectedCheckin = null;
            selectedCheckout = null;
            selectedTime = null;
            document.getElementById('proceedBtn').disabled = true;
            closeDateTimeModal();
        });

        document.getElementById('closeDateTimeModal').addEventListener('click', function() {
            // Remove selection
            document.querySelectorAll('.calendar-date').forEach(cell => {
                cell.classList.remove('selected');
            });
            selectedDate = null;
            selectedCheckin = null;
            selectedCheckout = null;
            selectedTime = null;
            document.getElementById('proceedBtn').disabled = true;
            closeDateTimeModal();
        });

        // Close modal when clicking overlay
        document.getElementById('dateTimeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                // Remove selection
                document.querySelectorAll('.calendar-date').forEach(cell => {
                    cell.classList.remove('selected');
                });
                selectedDate = null;
                selectedCheckin = null;
                selectedCheckout = null;
                selectedTime = null;
                document.getElementById('proceedBtn').disabled = true;
                closeDateTimeModal();
            }
        });

        // Proceed button handler - show Terms modal first
        document.getElementById('proceedBtn').addEventListener('click', function() {
            if (selectedCheckin && selectedCheckout && selectedTime) {
                showTermsModal();
            }
        });

        // Terms modal event listeners
        document.getElementById('closeTermsModal').addEventListener('click', closeTermsModal);
        document.getElementById('cancelTermsBtn').addEventListener('click', closeTermsModal);

        // Checkbox handler
        document.getElementById('acceptTermsCheckbox').addEventListener('change', function() {
            const acceptBtn = document.getElementById('acceptTermsBtn');
            if (this.checked) {
                acceptBtn.disabled = false;
                acceptBtn.textContent = 'Accept and Proceed';
            } else {
                acceptBtn.disabled = true;
                acceptBtn.textContent = 'Please Accept Terms First';
            }
        });

        // Accept terms button handler
        document.getElementById('acceptTermsBtn').addEventListener('click', function() {
            if (document.getElementById('acceptTermsCheckbox').checked && selectedCheckin && selectedCheckout && selectedTime) {
                closeTermsModal();
                
                // Use selected check-in, check-out, and time
                const checkin = selectedCheckin;
                const checkout = selectedCheckout;
                
                // Display rooms with selected dates and time
                displayRooms(checkin, checkout, selectedTime);
            }
        });

        // Close modal when clicking overlay
        document.getElementById('termsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTermsModal();
            }
        });

        // Format date for display (fix timezone issues)
        function formatDate(dateString) {
            // Parse date string as local date to avoid timezone conversion
            const parts = dateString.split('-');
            if (parts.length === 3) {
                const date = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric',
                    year: 'numeric'
                });
            }
            // Fallback for other formats
            const date = new Date(dateString + 'T12:00:00'); // Use noon to avoid timezone edge cases
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                year: 'numeric'
            });
        }

        // Calculate nights (fix timezone issues)
        function calculateNights(checkin, checkout) {
            // Parse dates as local dates to avoid timezone conversion
            const checkinParts = checkin.split('-');
            const checkoutParts = checkout.split('-');
            const checkinDate = new Date(parseInt(checkinParts[0]), parseInt(checkinParts[1]) - 1, parseInt(checkinParts[2]));
            const checkoutDate = new Date(parseInt(checkoutParts[0]), parseInt(checkoutParts[1]) - 1, parseInt(checkoutParts[2]));
            return Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
        }

        // Create room card HTML
        function createRoomCard(room, checkin, checkout) {
            const nights = calculateNights(checkin, checkout);
            const totalPrice = nights * room.pricePerNight;

            // Limit visible amenities to 3, show "+X more" if there are more
            const maxVisibleAmenities = 3;
            const visibleAmenities = room.amenities.slice(0, maxVisibleAmenities);
            const remainingCount = room.amenities.length - maxVisibleAmenities;
            const amenitiesHTML = visibleAmenities.map(amenity => 
                `<span class="amenity-tag">${amenity}</span>`
            ).join('') + (remainingCount > 0 ? 
                `<span class="amenity-tag amenity-tag-more">+${remainingCount} more</span>` : '');

            return `
                <div class="room-card" data-room-id="${room.id}">
                    <img src="${room.image}" alt="${room.name}" class="room-image" onerror="this.src='https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop'">
                    <div class="room-content">
                        <h3 class="room-title">${room.name}</h3>
                        ${room.description ? `<p class="room-description">${room.description}</p>` : ''}
                        <div class="room-specs">
                            <div class="spec-item">
                                <i class="fas fa-bed"></i>
                                <span>${room.bedrooms} ${room.bedrooms === 1 ? 'Bedroom' : 'Bedrooms'}</span>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-users"></i>
                                <span>Up to ${room.guests} Guests</span>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-bath"></i>
                                <span>${room.bathrooms} ${room.bathrooms === 1 ? 'Bathroom' : 'Bathrooms'}</span>
                            </div>
                            <div class="spec-item">
                                <i class="fas fa-ruler-combined"></i>
                                <span>${room.size}</span>
                            </div>
                        </div>
                        <div class="room-amenities">
                            ${amenitiesHTML}
                        </div>
                        <div class="room-footer">
                            <div class="room-price-info">
                                <span class="price-label">Total for ${nights} ${nights === 1 ? 'night' : 'nights'}</span>
                                <span class="price-amount">₱${totalPrice.toLocaleString('en-US')}</span>
                                <span class="price-period">₱${room.pricePerNight.toLocaleString('en-US')} per night</span>
                            </div>
                            <div class="room-actions">
                                <button class="view-details-btn" onclick="viewRoomDetails(${room.id}, '${checkin}', '${checkout}', ${nights})">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <button class="book-room-btn" onclick="bookRoom(${room.id}, '${checkin}', '${checkout}', ${nights}, ${totalPrice})">
                                    Book Now
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Fetch and display available rooms
        async function displayRooms(checkin, checkout, time = null) {
            const roomsSection = document.getElementById('roomsSection');
            const roomsGrid = document.getElementById('roomsGrid');
            const dateRangeText = document.getElementById('dateRangeText');
            
            // Update date range text with time if available
            let timeText = '';
            if (time) {
                const timeParts = time.split(':');
                const hour = parseInt(timeParts[0]);
                const minute = timeParts[1];
                const period = hour >= 12 ? 'PM' : 'AM';
                const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
                timeText = ` • ${displayHour}:${minute} ${period}`;
            }
            
            dateRangeText.textContent = `${formatDate(checkin)} - ${formatDate(checkout)} • ${calculateNights(checkin, checkout)} ${calculateNights(checkin, checkout) === 1 ? 'night' : 'nights'}${timeText}`;
            
            // Show loading state
            roomsGrid.innerHTML = '<div class="no-rooms" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #666;"></i><p style="margin-top: 16px;">Checking availability...</p></div>';
            roomsSection.classList.add('active');
            
            try {
                // Debug: Check if cookies are available
                console.log('Cookies before API call:', document.cookie);
                console.log('Auth token cookie exists:', document.cookie.indexOf('auth_token=') !== -1);
                
                // Fetch available rooms from API
                const response = await fetch('api/get_available_rooms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include', // Include cookies for authentication
                    body: JSON.stringify({
                        checkin: checkin,
                        checkout: checkout
                    })
                });
                
                console.log('Rooms API Response Status:', response.status);
                console.log('Rooms API Response Headers:', response.headers);
                
                // Check if response is OK
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('API Error Response:', errorText);
                    let errorData;
                    try {
                        errorData = JSON.parse(errorText);
                    } catch (e) {
                        errorData = { error: errorText || 'Unknown error' };
                    }
                    
                    if (response.status === 401) {
                        roomsGrid.innerHTML = '<div class="no-rooms"><p>Authentication failed. Please <a href="home.php">log in again</a>.</p></div>';
                    } else {
                        roomsGrid.innerHTML = '<div class="no-rooms"><p>Error loading rooms: ' + (errorData.error || 'Please try again') + '</p></div>';
                    }
                    rooms = [];
                    return;
                }
                
                const result = await response.json();
                console.log('Rooms API Result:', result);
                
                // Clear existing rooms
                roomsGrid.innerHTML = '';
                
                if (result.success && result.rooms && result.rooms.length > 0) {
                    // Store rooms for booking
                    rooms = result.rooms;
                    
                    // Add room cards
                    rooms.forEach(room => {
                        const roomCard = createRoomCard(room, checkin, checkout);
                        roomsGrid.insertAdjacentHTML('beforeend', roomCard);
                    });
                } else {
                    // No rooms available
                    roomsGrid.innerHTML = '<div class="no-rooms"><p>No rooms available for the selected dates. Please try different dates.</p></div>';
                    rooms = [];
                }
            } catch (error) {
                console.error('Error fetching rooms:', error);
                console.error('Error details:', error.message, error.stack);
                roomsGrid.innerHTML = '<div class="no-rooms"><p>Error loading rooms: ' + error.message + '. Please try again.</p></div>';
                rooms = [];
            }
            
            // Scroll to rooms section
            setTimeout(() => {
                roomsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }

        // Notification Modal Functions
        function showNotification(type, title, message, details = null, onConfirm = null, onCancel = null) {
            closeAllModals(); // Close any other open modals first
            const modal = document.getElementById('notificationModal');
            const icon = document.getElementById('notificationIcon');
            const titleEl = document.getElementById('notificationTitle');
            const messageEl = document.getElementById('notificationMessage');
            const detailsEl = document.getElementById('bookingDetails');
            const footer = document.getElementById('notificationFooter');
            
            // Set icon and type
            icon.className = 'notification-icon ' + type;
            const iconClass = type === 'error' ? 'fa-exclamation-circle' : 
                            type === 'success' ? 'fa-check-circle' : 
                            'fa-info-circle';
            icon.innerHTML = `<i class="fas ${iconClass}"></i>`;
            
            // Set content
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            // Show booking details if provided
            if (details) {
                detailsEl.style.display = 'block';
                detailsEl.innerHTML = `
                    <div class="booking-confirmation-details">
                        <div class="booking-details-grid">
                            <div class="booking-detail-row">
                                <span class="booking-detail-label">Room</span>
                                <span class="booking-detail-value">${details.roomName}</span>
                            </div>
                            <div class="booking-detail-row">
                                <span class="booking-detail-label">Nights</span>
                                <span class="booking-detail-value">${details.nights} ${details.nights === 1 ? 'night' : 'nights'}</span>
                            </div>
                            <div class="booking-detail-row">
                                <span class="booking-detail-label">Check-in</span>
                                <span class="booking-detail-value">${formatDate(details.checkin)}</span>
                            </div>
                            <div class="booking-detail-row">
                                <span class="booking-detail-label">Check-out</span>
                                <span class="booking-detail-value">${formatDate(details.checkout)}</span>
                            </div>
                            <div class="booking-detail-row booking-total">
                                <span class="booking-detail-label">Total Amount</span>
                                <span class="booking-detail-value">₱${details.totalPrice.toLocaleString('en-US')}</span>
                            </div>
                        </div>
                        <div class="booking-terms-checkbox">
                            <input type="checkbox" id="termsCheckbox" name="termsCheckbox">
                            <label for="termsCheckbox">
                                I understand that <strong>refunds and cancellations are not allowed</strong> after the transaction is completed. Once payment is processed, the booking is final and cannot be changed or cancelled.
                            </label>
                        </div>
                    </div>
                `;
            } else {
                detailsEl.style.display = 'none';
            }
            
            // Set up buttons
            footer.innerHTML = '';
            if (onCancel) {
                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'modal-btn modal-btn-secondary';
                cancelBtn.textContent = 'Cancel';
                cancelBtn.onclick = () => {
                    closeNotification();
                    if (onCancel) onCancel();
                };
                footer.appendChild(cancelBtn);
            }
            
            const confirmBtn = document.createElement('button');
            confirmBtn.className = 'modal-btn modal-btn-primary';
            confirmBtn.id = 'confirmBookingBtn';
            confirmBtn.textContent = onCancel ? 'Confirm' : 'OK';
            confirmBtn.disabled = details !== null; // Disable if booking details (requires checkbox)
            confirmBtn.onclick = () => {
                // Check if checkbox is checked when details are present
                if (details !== null) {
                    const termsCheckbox = document.getElementById('termsCheckbox');
                    if (!termsCheckbox || !termsCheckbox.checked) {
                        return; // Don't proceed if checkbox not checked
                    }
                }
                closeNotification();
                if (onConfirm) onConfirm();
            };
            footer.appendChild(confirmBtn);
            
            // Set up checkbox handler after button is created
            if (details !== null) {
                const termsCheckbox = document.getElementById('termsCheckbox');
                if (termsCheckbox && confirmBtn) {
                    termsCheckbox.addEventListener('change', function() {
                        confirmBtn.disabled = !this.checked;
                    });
                }
            }
            
            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeNotification() {
            const modal = document.getElementById('notificationModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal when clicking overlay
        document.getElementById('notificationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNotification();
            }
        });


        // Initiate PayMongo payment
        async function initiatePayment(bookingData) {
            try {
                // Show loading state
                showNotification(
                    'warning',
                    'Processing Payment',
                    'Please wait while we set up your payment...',
                    null,
                    null
                );

                // Create payment intent
                const response = await fetch('api/create_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include', // Include cookies for authentication
                    body: JSON.stringify({
                        amount: bookingData.totalPrice,
                        booking_data: bookingData
                    })
                });

                // Check if response is OK
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Network error occurred' }));
                    throw new Error(errorData.error || `HTTP ${response.status}: Failed to create payment`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Failed to create payment');
                }

                // Close loading modal
                closeNotification();

                // Open PayMongo checkout in new window/tab
                const payWindow = window.open(result.checkout_url, '_blank', 'width=800,height=600');
                
                // Poll for payment completion
                const checkPayment = setInterval(async function() {
                    try {
                        const checkResponse = await fetch('api/check_payment.php?payment_link_id=' + result.payment_link_id, {
                            credentials: 'include' // Include cookies for authentication
                        });
                        const checkResult = await checkResponse.json();
                        
                        if (checkResult.success && checkResult.paid) {
                            clearInterval(checkPayment);
                            payWindow.close();
                            // Redirect to success page
                            window.location.href = 'payment-success.php';
                        }
                    } catch (error) {
                        console.error('Error checking payment:', error);
                    }
                }, 3000); // Check every 3 seconds
                
                // Stop checking after 10 minutes
                setTimeout(function() {
                    clearInterval(checkPayment);
                }, 600000);

            } catch (error) {
                console.error('Payment error:', error);
                closeNotification();
                
                // Extract detailed error message
                let errorMessage = 'Failed to process payment. Please try again.';
                if (error.message) {
                    errorMessage = error.message;
                } else if (typeof error === 'string') {
                    errorMessage = error;
                }
                
                showNotification(
                    'error',
                    'Payment Error',
                    errorMessage,
                    null,
                    null
                );
            }
        }

        // Book room function
        async function bookRoom(roomId, checkin, checkout, nights, totalPrice) {
            const room = rooms.find(r => r.id === roomId);
            if (!room) {
                showNotification('error', 'Room Not Found', 'The selected room is no longer available.');
                return;
            }

            // Check room availability before proceeding
            try {
                const availabilityResponse = await fetch('api/check_room_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        room_id: roomId.toString(),
                        checkin: checkin,
                        checkout: checkout
                    })
                });

                const availabilityResult = await availabilityResponse.json();

                if (!availabilityResult.success || !availabilityResult.available) {
                    showNotification(
                        'error',
                        'Room Unavailable',
                        availabilityResult.message || 'This room is no longer available for the selected dates. Please select a different room or dates.'
                    );
                    // Reload available rooms
                    displayRooms(checkin, checkout);
                    return;
                }
            } catch (error) {
                console.error('Error checking availability:', error);
                showNotification('error', 'Availability Check Failed', 'Could not verify room availability. Please try again.');
                return;
            }

            // Create booking data
            const bookingData = {
                roomId: roomId,
                roomName: room.name,
                checkin: checkin,
                checkout: checkout,
                nights: nights,
                totalPrice: totalPrice,
                pricePerNight: room.pricePerNight
            };

            // Show confirmation modal with terms
            showNotification(
                'success',
                'Confirm Booking',
                'Please review your booking details and terms below:',
                bookingData,
                () => {
                    // On confirm - initiate PayMongo payment
                    initiatePayment(bookingData);
                },
                () => {
                    // On cancel
                    console.log('Booking cancelled');
                }
            );
        }

        // Room Details Modal Functions
        function viewRoomDetails(roomId, checkin, checkout, nights) {
            closeAllModals(); // Close any other open modals first
            // Find the room from the rooms array
            const room = rooms.find(r => r.id === roomId);
            if (!room) {
                console.error('Room not found');
                return;
            }
            
            const modal = document.getElementById('roomDetailsModal');
            const content = document.getElementById('roomDetailsContent');
            if (!modal || !content) return;
            
            const totalPrice = nights * room.pricePerNight;
            
            // Build images HTML
            let imagesHTML = '';
            if (room.images && room.images.length > 0) {
                imagesHTML = room.images.map(img => {
                    const imgSrc = img.startsWith('http') ? img : img;
                    return `<img src="${imgSrc}" alt="${room.name}" onerror="this.style.display='none'">`;
                }).join('');
            } else if (room.image) {
                const imgSrc = room.image.startsWith('http') ? room.image : room.image;
                imagesHTML = `<img src="${imgSrc}" alt="${room.name}" onerror="this.style.display='none'">`;
            }
            
            // Build amenities HTML
            const amenitiesHTML = room.amenities && room.amenities.length > 0
                ? room.amenities.map(amenity => `<span class="room-details-amenity-tag">${amenity}</span>`).join('')
                : '<p style="color: #6b7280;">No amenities listed</p>';
            
            content.innerHTML = `
                <div class="room-details-content">
                    <div class="room-details-header">
                        <h2>${room.name}</h2>
                        <p>${room.description || 'Experience comfort and luxury in this beautiful room.'}</p>
                    </div>
                    
                    ${imagesHTML ? `<div class="room-details-images">${imagesHTML}</div>` : ''}
                    
                    <div class="room-details-specs">
                        <div class="room-details-spec-item">
                            <i class="fas fa-bed"></i>
                            <div>
                                <strong>Bedrooms</strong>
                                <span>${room.bedrooms} ${room.bedrooms === 1 ? 'Bedroom' : 'Bedrooms'}</span>
                            </div>
                        </div>
                        <div class="room-details-spec-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <strong>Max Guests</strong>
                                <span>Up to ${room.guests} Guests</span>
                            </div>
                        </div>
                        <div class="room-details-spec-item">
                            <i class="fas fa-bath"></i>
                            <div>
                                <strong>Bathrooms</strong>
                                <span>${room.bathrooms} ${room.bathrooms === 1 ? 'Bathroom' : 'Bathrooms'}</span>
                            </div>
                        </div>
                        <div class="room-details-spec-item">
                            <i class="fas fa-ruler-combined"></i>
                            <div>
                                <strong>Size</strong>
                                <span>${room.size}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="room-details-amenities">
                        <h3>Guest Access</h3>
                        <div class="room-details-amenities-grid">
                            ${amenitiesHTML}
                        </div>
                    </div>
                    
                    <div class="room-details-price">
                        <h3>Total for ${nights} ${nights === 1 ? 'night' : 'nights'}</h3>
                        <div class="price-large">₱${totalPrice.toLocaleString('en-US')}</div>
                        <div class="price-small">₱${room.pricePerNight.toLocaleString('en-US')} per night</div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button class="book-room-btn" onclick="closeRoomDetails(); bookRoom(${room.id}, '${checkin}', '${checkout}', ${nights}, ${totalPrice});" style="max-width: 300px;">
                            Book Now
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            `;
            
            if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
                updateModalState();
            }
        }

        function closeRoomDetails() {
            const modal = document.getElementById('roomDetailsModal');
            if (modal) {
            modal.classList.remove('active');
            }
            updateModalState();
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('roomDetailsModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeRoomDetails();
                    }
                });
            }
            
            // Force logo images to display and handle corrupted files
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
            
            // Ensure booking background image displays properly
            const bookingHeroImage = document.querySelector('.booking-hero-image');
            if (bookingHeroImage) {
                // Force display
                bookingHeroImage.style.display = 'block';
                bookingHeroImage.style.visibility = 'visible';
                bookingHeroImage.style.opacity = '1';
                
                // Check if image file is valid (files < 1000 bytes are likely corrupted)
                fetch(bookingHeroImage.src, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            const contentLength = response.headers.get('content-length');
                            if (contentLength && parseInt(contentLength) < 1000) {
                                // File is too small (corrupted), use fallback immediately
                                // Booking background image is corrupted - using fallback (this is expected)
                                // console.warn('Booking background image appears corrupted (only ' + contentLength + ' bytes), using fallback');
                                const fallbackUrl = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop';
                                bookingHeroImage.src = fallbackUrl;
                                return;
                            }
                            // File seems valid, verify it loads
                            const testImg = new Image();
                            testImg.onload = function() {
                                console.log('Booking background image loaded successfully');
                            };
                            testImg.onerror = function() {
                                console.warn('Booking background image failed to load, using fallback');
                                bookingHeroImage.src = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop';
                            };
                            testImg.src = bookingHeroImage.src;
                        } else {
                            throw new Error('File not found');
                        }
                    })
                    .catch(() => {
                        // Can't check file or file doesn't exist, use fallback
                        console.warn('Cannot verify booking background image, using fallback');
                        bookingHeroImage.src = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop';
                    });
            }
        });
    </script>
</body>
</html>


