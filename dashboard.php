<?php
// Use cookie-based authentication instead of sessions
if (!ob_get_level()) ob_start();

require_once 'config/database.php';
require_once 'config/auth.php';

// Verify authentication token
$current_user = verifyAuthToken();

if (!$current_user) {
    error_log("Dashboard - User not authenticated, redirecting to home.php");
    header('Location: home.php');
    exit();
}

error_log("Dashboard - User authenticated: " . $current_user['username'] . " (ID: " . $current_user['user_id'] . ")");

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
    <title>Dwellscape Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/image-fallback.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Force remove all top spacing */
        html, body, header, main, section {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        
        /* Remove any potential gap at the very top */
        html::before,
        body::before,
        header::before {
            display: none !important;
            content: none !important;
        }

        html {
            height: 100%;
            scroll-padding-top: 100px;
            scroll-behavior: smooth;
            background: transparent !important;
            background-color: transparent !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow-x: hidden;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
            color: #333;
            line-height: 1.6;
            min-height: 100%;
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        body::before {
            display: none !important;
        }

        /* Header Styles */
        .header {
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            position: fixed;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1000;
            padding: 0 20px !important;
            margin: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            pointer-events: none;
            border: none !important;
        }

        .header * {
            pointer-events: auto;
        }

        .header.scrolled {
            background: linear-gradient(180deg, #f1e4d1 0%, #e4d3bc 60%, #d5bfa0 100%) !important;
            background-color: #e4d3bc !important;
            box-shadow: 0 2px 10px rgba(122, 106, 79, 0.25) !important;
        }

        .header.book-now-active {
            background: linear-gradient(180deg, #f1e4d1 0%, #e4d3bc 60%, #d5bfa0 100%) !important;
            background-color: #e4d3bc !important;
            box-shadow: 0 2px 10px rgba(122, 106, 79, 0.25) !important;
        }

        .header.book-now-active .nav-links a {
            color: #374151;
            text-shadow: none;
        }

        .header.book-now-active .logo {
            color: #7a6a4f;
            text-shadow: none;
        }

        .header.book-now-active .logo .logo-text small {
            color: #9A8B6F;
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
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            padding: 0 !important;
            width: auto !important;
            height: 26px !important;
            min-width: 26px;
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
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        /* Force logo to display - override any conflicting styles */
        .logo img[src*="dwellscape-logo"] {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: 26px !important;
            width: auto !important;
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100px !important;
            object-fit: contain !important;
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
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            transition: color 0.3s ease;
            position: relative;
        }

        .header.scrolled .nav-links a {
            color: #374151;
            text-shadow: none;
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

        .nav-links a:hover::after {
            width: 100%;
        }

        .contact-info {
            display: flex;
            align-items: center;
            color: #7a6a4f;
            font-weight: 600;
        }

        .contact-info i {
            margin-right: 8px;
            font-size: 18px;
        }

        /* Profile Dropdown */
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
            transition: all 0.3s ease;
            position: relative;
            color: #ffffff;
        }

        .header.scrolled .profile-btn {
            color: #374151;
        }

        .profile-btn i {
            color: inherit;
        }

        .profile-btn:hover {
            background: rgba(195, 176, 145, 0.15);
            transform: translateY(-1px);
        }

        .profile-btn:active {
            transform: translateY(0);
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
            box-shadow: 0 2px 8px rgba(195, 176, 145, 0.3);
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-btn:hover .profile-avatar {
            transform: scale(1.05);
        }

        .profile-name {
            font-weight: 500;
            margin-right: 8px;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            transition: color 0.3s ease;
        }

        .header.scrolled .profile-name {
            color: #374151;
            text-shadow: none;
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

        /* Enhanced Bookings Section */
        #bookings {
            background: linear-gradient(180deg, rgba(247, 245, 241, 0.6) 0%, rgba(247, 245, 241, 0.2) 100%);
        }

        .bookings-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 36px;
            box-shadow: 0 24px 60px rgba(17, 24, 39, 0.08);
        }

        .bookings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 28px;
        }

        .bookings-title-group h2 {
            font-size: 28px;
            color: #1f2937;
            font-weight: 700;
        }

        .bookings-title-group p {
            color: #6b7280;
            margin-top: 6px;
        }

        .bookings-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .bookings-actions .search-field {
            position: relative;
        }

        .bookings-actions .search-field input {
            padding: 12px 16px 12px 42px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            font-size: 15px;
            min-width: 220px;
            transition: border-color 0.2s ease;
        }

        .bookings-actions .search-field i {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
        }

        .bookings-actions select {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            font-size: 15px;
            background: #fff;
            color: #374151;
        }

        .bookings-actions button.cta-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 10px 25px rgba(69, 10, 10, 0.15);
        }

        .bookings-table-wrapper {
            overflow-x: auto;
        }

        .bookings-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .bookings-table thead th {
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            color: #9ca3af;
            font-weight: 700;
            padding: 0 18px 12px;
        }

        .bookings-table tbody tr {
            background: #f9fafb;
            border-radius: 12px;
            box-shadow: inset 0 0 0 1px rgba(229, 231, 235, 0.8);
        }

        .bookings-table tbody tr td {
            padding: 18px;
            font-size: 15px;
            color: #1f2937;
        }

        .bookings-table tbody tr td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .bookings-table tbody tr td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-confirmed {
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.18);
            color: #92400e;
        }

        .status-cancelled {
            background: rgba(248, 113, 113, 0.18);
            color: #991b1b;
        }

        .action-buttons {
            display: inline-flex;
            gap: 10px;
        }

        .action-buttons button {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            background: #fff;
            color: #6b7280;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
        }

        .action-buttons button:hover {
            transform: translateY(-2px);
            color: #7a6a4f;
            box-shadow: 0 18px 30px rgba(122, 106, 79, 0.15);
        }

        .pagination-button {
            padding: 10px 18px;
            border-radius: 10px;
            border: none;
            background: #ffffff;
            color: #374151;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
        }

        .pagination-button:hover {
            transform: translateY(-2px);
            color: #7a6a4f;
            box-shadow: 0 18px 32px rgba(122, 106, 79, 0.18);
        }

        .pagination-button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .booking-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            padding: 14px 18px;
            border-radius: 12px;
            background: #10b981;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.25);
            font-weight: 600;
            z-index: 1200;
            opacity: 0;
            transform: translateY(-15px);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .booking-toast.error {
            background: #ef4444;
            box-shadow: 0 20px 40px rgba(239, 68, 68, 0.25);
        }

        .booking-toast.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            font-size: 16px;
        }

        .booking-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1100;
            padding: 24px;
        }

        .booking-modal-overlay.active {
            display: flex;
        }

        .booking-modal {
            background: #ffffff;
            border-radius: 18px;
            width: min(520px, 100%);
            max-height: 90vh;
            overflow-y: auto;
            padding: 32px;
            box-shadow: 0 40px 70px rgba(15, 23, 42, 0.18);
            position: relative;
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

        .bookings-list-modal .bookings-card {
            box-shadow: none;
            padding: 0;
        }

        .booking-modal h3 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .booking-modal p {
            color: #6b7280;
            margin-bottom: 24px;
        }

        .booking-modal .close-modal {
            position: absolute;
            top: 18px;
            right: 18px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s ease;
        }

        .booking-modal .close-modal:hover {
            color: #374151;
        }

        .booking-modal .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .booking-modal label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }

        .booking-modal input,
        .booking-modal select,
        .booking-modal textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .booking-modal textarea {
            resize: vertical;
        }

        .booking-modal input:focus,
        .booking-modal select:focus,
        .booking-modal textarea:focus,
        .bookings-actions .search-field input:focus {
            outline: none;
            border-color: #c3b091;
            box-shadow: 0 0 0 3px rgba(195, 176, 145, 0.25);
        }

        .booking-modal .cta-button {
            width: 100%;
            justify-content: center;
            padding: 14px 18px;
            margin-top: 10px;
            background: linear-gradient(135deg, #7a6a4f 0%, #c3b091 100%);
            box-shadow: 0 18px 32px rgba(122, 106, 79, 0.25);
        }

        @media (max-width: 900px) {
            .bookings-card {
                padding: 24px;
            }

            .bookings-header {
                flex-direction: column;
                align-items: stretch;
            }

            .bookings-actions {
                justify-content: space-between;
            }

            .booking-modal .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .bookings-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .bookings-actions .search-field input {
                min-width: auto;
            }

            .bookings-actions select {
                width: 100%;
            }
        }

        /* Main Content */
        main,
        .main-content {
            margin: 0 !important;
            margin-top: 0 !important;
            padding: 0 !important;
            padding-top: 0 !important;
            position: relative;
            top: 0 !important;
        }
        
        /* Ensure no gap at top - remove any spacing from first elements */
        body > header,
        body > main {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        
        /* Remove any default spacing from section elements */
        section {
            margin-top: 0 !important;
        }
        
        .main-content > section:first-child,
        .main-content > .promo-hero:first-child {
            /* Pull the very first section (our hero) up behind the fixed header */
            margin-top: -80px !important;   /* same as header height */
            padding-top: 80px !important;   /* so text/buttons are not hidden */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Promotional Hero Banner */
        .promo-hero {
            position: relative;
            width: 100vw;
            /* Extra height to compensate for the -80px top offset so the image reaches the very bottom */
            height: calc(100vh + 80px);
            min-height: 780px;
            overflow: hidden;
            /* Stretch edge‑to‑edge horizontally */
            margin: 0 !important;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            padding: 0 !important;
            top: 0 !important;
            left: 0 !important;
        }

        .promo-hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block !important;
            position: absolute;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 1;
            margin: 0 !important;
            padding: 0 !important;
            transition: opacity 0.9s ease, transform 18s ease;
            /* Stronger enhancement for all four photos */
            filter: brightness(1.08) contrast(1.2) saturate(1.25);
            animation: heroZoom 28s ease-in-out infinite;
            visibility: visible !important;
            opacity: 1 !important;
            background: transparent;
        }
        
        /* Ensure promo hero image is always visible */
        #promoHeroImage {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        @keyframes heroZoom {
            0%   { transform: scale(1.05); }
            50%  { transform: scale(1.1); }
            100% { transform: scale(1.05); }
        }

        /* Carousel indicators (four dots bottom-right) */
        .promo-hero-indicators {
            position: absolute;
            right: 40px;
            bottom: 32px;
            z-index: 3;
            display: flex;
            gap: 10px;
        }

        .promo-hero-indicator {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, 0.8);
            background: transparent;
            opacity: 0.6;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .promo-hero-indicator.active {
            width: 24px;
            background: #ffffff;
            opacity: 1;
            border-color: #ffffff;
        }

        .promo-hero-overlay {
            position: absolute;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            /* Slightly stronger overlay to make images look crisper and text more readable */
            background:
                radial-gradient(circle at 20% 20%, rgba(0, 0, 0, 0.25) 0%, transparent 55%),
                radial-gradient(circle at 80% 80%, rgba(0, 0, 0, 0.25) 0%, transparent 55%),
                linear-gradient(135deg, rgba(0, 0, 0, 0.45) 0%, rgba(0, 0, 0, 0.2) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            margin: 0 !important;
            padding: 0 !important;
        }

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

        .promo-location i {
            margin-right: 10px;
            font-size: 20px;
            color: #C3B091;
        }

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


        .promo-slogan {
            font-size: 20px;
            font-weight: 400;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
            margin-top: 15px;
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

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
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

        .promo-location i {
            animation: pulse 2s infinite;
        }

        /* Hero Section (below main dashboard background) */
        .hero-section {
            padding: 80px 0;
            display: flex;
            align-items: center;
            gap: 60px;
            background: radial-gradient(circle at top left, #f1e4d1 0%, #e4d3bc 40%, #d5bfa0 100%);
        }

        .hero-content {
            flex: 1;
        }

        .hero-subtitle {
            color: #9A8B6F;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 18px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 22px;
            line-height: 1.15;
        }

        .hero-title .highlight {
            color: #7a6a4f;
        }

        .hero-description {
            color: #4b5563;
            font-size: 18px;
            margin-bottom: 38px;
            line-height: 1.7;
            max-width: 620px;
        }

        .cta-button {
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
            padding: 16px 34px;
            border: none;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            letter-spacing: 0.05em;
            box-shadow: 0 14px 30px rgba(122, 106, 79, 0.35);
        }

        .cta-button:hover {
            background: linear-gradient(135deg, #9A8B6F 0%, #7a6a4f 100%);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 18px 40px rgba(122, 106, 79, 0.45);
        }

        .hero-image {
            flex: 1;
            position: relative;
        }

        .hero-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Experience Section */
        .experience-section {
            padding: 80px 0;
            background: #f7f5f1;
        }

        .experience-content {
            display: flex;
            align-items: center;
            gap: 60px;
        }

        .experience-images {
            flex: 1;
            position: relative;
        }

        .image-collage {
            position: relative;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
        }

        .collage-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
            display: block;
        }

        .collage-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(195, 176, 145, 0.2) 0%, rgba(154, 139, 111, 0.3) 100%);
            z-index: 2;
            pointer-events: none;
        }

        .collage-bg {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            position: relative;
        }

        .experience-box {
            position: absolute;
            bottom: 30px;
            left: 30px;
            background: linear-gradient(135deg, rgba(122, 106, 79, 0.95) 0%, rgba(90, 77, 58, 0.95) 100%);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
            z-index: 3;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .experience-box h3 {
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .experience-text {
            flex: 1;
        }

        .experience-text h2 {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .experience-text p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
        }

        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: #374151;
        }

        .features-list i {
            width: 24px;
            height: 24px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 12px;
        }

        .feature-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .feature-description {
            color: #6b7280;
            font-size: 14px;
        }

        /* Services Section */
        .services-section {
            padding: 80px 0;
        }

        .services-content {
            display: flex;
            align-items: center;
            gap: 60px;
        }

        .services-text {
            flex: 1;
        }

        .services-subtitle {
            color: #6b7280;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .services-title {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .services-description {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .video-cta {
            display: flex;
            align-items: center;
            color: #7a6a4f;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .video-cta:hover {
            color: #9A8B6F;
        }

        .video-cta i {
            margin-right: 8px;
            font-size: 18px;
        }

        .services-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .service-card {
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .service-card.blue {
            background: #C3B091;
            color: white;
        }

        .service-card.white {
            background: white;
            color: #1f2937;
            border: 1px solid #e5e7eb;
        }

        .service-card:hover {
            transform: translateY(-5px);
        }

        .service-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .service-card.blue .service-icon {
            color: white;
        }

        .service-card.white .service-icon {
            color: #C3B091;
        }

        .service-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .service-card p {
            font-size: 14px;
            opacity: 0.8;
        }

        /* Welcome Section */
        .welcome-section {
            padding: 60px 0;
            background: #f7f5f1;
        }

        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .welcome-card h2 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .user-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            border-radius: 12px;
            color: white;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            margin-right: 20px;
        }

        .user-details h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .user-details p {
            opacity: 0.8;
            font-size: 14px;
        }


        /* Utility sections for sample outputs */
        .section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
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

        .page-title-button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: #1f2937;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .page-title-button:hover,
        .page-title-button:focus {
            color: #7a6a4f;
            outline: none;
            transform: translateY(-1px);
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }

        .muted {
            color: #6b7280;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.06);
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .embed-16x9 {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .embed-16x9 iframe, .embed-16x9 img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: #ffffff;
            padding: 60px 0 0;
            margin-top: auto;
            width: 100%;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-brand {
            max-width: 300px;
        }

        .footer-logo {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }

        .footer-logo span {
            color: #C3B091;
            font-size: 18px;
            font-weight: 600;
        }

        .footer-description {
            color: #9ca3af;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(195, 176, 145, 0.1);
            border: 1px solid rgba(195, 176, 145, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #C3B091;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #C3B091;
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(195, 176, 145, 0.3);
        }

        .footer-column {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .footer-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: #C3B091;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-links a {
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .footer-links a:hover {
            color: #C3B091;
            transform: translateX(5px);
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            justify-content: flex-start;
        }

        .contact-item i {
            color: #C3B091;
            font-size: 18px;
            margin-top: 3px;
            min-width: 20px;
            flex-shrink: 0;
        }

        .contact-item > div {
            text-align: left;
            flex: 1;
            width: 100%;
        }

        .contact-item p {
            color: #9ca3af;
            font-size: 14px;
            margin: 0;
            line-height: 1.6;
            text-align: left;
            width: 100%;
        }

        /* Copyright */
        .footer-copyright {
            text-align: center;
            padding: 25px 0;
            border-top: 1px solid rgba(195, 176, 145, 0.1);
            margin-top: 20px;
        }

        .footer-copyright p {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }

        /* Footer Responsive */
        @media (max-width: 1024px) {
            .footer-content {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .footer {
                padding: 40px 0 0;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .footer-brand {
                max-width: 100%;
            }

        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 15px;
            }

            .nav-links {
                display: none;
            }

            .contact-info {
                display: none;
            }

            .promo-hero {
                height: 50vh;
                min-height: 400px;
            }

            .promo-hero-content {
                left: 50%;
                transform: translate(-50%, -50%);
                max-width: 100%;
                padding: 0 20px;
            }

            .promo-brand {
                font-size: 42px;
            }

            .promo-brand-line2 {
                margin-left: 0;
            }

            .promo-slogan {
                font-size: 16px;
            }

            .hero-section {
                flex-direction: column;
                text-align: center;
            }

            .hero-title {
                font-size: 36px;
            }

            .experience-content {
                flex-direction: column;
            }

            .services-content {
                flex-direction: column;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .user-info {
                flex-direction: column;
                text-align: center;
            }

            .user-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }

        /* Enhanced Gallery Styles */
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

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        @media (min-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 25px;
            }
        }

        @media (min-width: 1200px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            }
        }

        .gallery-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f3f4f6; /* Light gray background for missing images */
        }
        
        .gallery-item img[src*="data:image/svg"] {
            background: #e5e7eb; /* Slightly darker gray for SVG placeholders */
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

        /* Gallery Modal/Lightbox */
        .gallery-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
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
            .gallery-modal-nav {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .gallery-modal-nav.prev {
                left: 10px;
            }

            .gallery-modal-nav.next {
                right: 10px;
            }

            .gallery-item-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
        }

        /* Contact Visual Refresh */
        .contact-visual {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            align-items: center;
        }

        .contact-mockup {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 45px rgba(17, 24, 39, 0.12);
        }

        .contact-mockup img {
            width: 100%;
            display: block;
            border-radius: 16px;
        }

        .contact-map {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(17, 24, 39, 0.12);
            min-height: 340px;
        }

        .contact-map iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        @media (max-width: 900px) {
            .contact-visual {
                grid-template-columns: 1fr;
            }
        }

        .contact-form-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 20px 45px rgba(17, 24, 39, 0.12);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-form-card h2 {
            font-size: 34px;
            font-weight: 700;
            color: #1f2937;
        }

        .contact-form-card p {
            color: #6b7280;
            line-height: 1.6;
        }

        .contact-form-card form {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-top: 10px;
        }

        .contact-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .contact-form-card input,
        .contact-form-card textarea {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 15px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .contact-form-card textarea {
            min-height: 140px;
            resize: vertical;
        }

        .contact-form-card input:focus,
        .contact-form-card textarea:focus {
            outline: none;
            border-color: #c3b091;
            box-shadow: 0 0 0 3px rgba(195, 176, 145, 0.2);
        }

        .contact-form-card .cta-button {
            justify-content: center;
            font-size: 16px;
            font-weight: 600;
            padding: 14px;
            margin-top: 6px;
        }

        @media (max-width: 600px) {
            .contact-form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Values */
        .values-section {
            margin-top: 80px;
        }

        .values-eyebrow {
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 12px;
            font-weight: 700;
            color: #c3b091;
            text-align: center;
            margin-bottom: 12px;
        }

        .values-subtitle {
            color: #6b7280;
            font-size: 18px;
            max-width: 720px;
            margin: 0 auto 36px;
            text-align: center;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 28px;
        }

        .value-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 18px 40px rgba(17, 24, 39, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .value-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 50px rgba(17, 24, 39, 0.12);
        }

        .value-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(195, 176, 145, 0.16);
            color: #7a6a4f;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .value-card h3 {
            font-size: 22px;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .value-card p {
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 14px;
        }

        .value-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 8px;
        }

        .value-card ul li {
            color: #6b7280;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .value-card ul li i {
            color: #c3b091;
            margin-top: 2px;
        }

        /* Amenities */
        .amenities-section {
            background: #f7f5f1;
        }

        .amenities-subtitle {
            color: #6b7280;
            font-size: 18px;
            max-width: 760px;
            margin: 12px auto 40px;
            text-align: center;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .amenity-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 18px;
            text-align: left;
            box-shadow: 0 4px 12px rgba(17, 24, 39, 0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .amenity-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(17, 24, 39, 0.1);
        }

        .amenity-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #c3b091 0%, #9a8b6f 100%);
            border-radius: 8px;
            color: #ffffff;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .amenity-card h3 {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .amenity-card p {
            color: #4b5563;
            line-height: 1.5;
            font-size: 13px;
        }

        .amenity-meta {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .amenity-chip {
            background: rgba(195, 176, 145, 0.18);
            color: #7a6a4f;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Enhanced Statistics Section */
        .stats-section-enhanced {
            background: linear-gradient(135deg, #f7f5f1 0%, #ffffff 100%);
            padding: 80px 40px;
            border-radius: 24px;
            margin: 80px 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .stats-section-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #C3B091, #9A8B6F, #C3B091);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .stats-grid-enhanced {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stat-card-enhanced {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(195, 176, 145, 0.1);
        }

        .stat-card-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(195, 176, 145, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .stat-card-enhanced:hover::before {
            left: 100%;
        }

        .stat-card-enhanced:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 16px 40px rgba(195, 176, 145, 0.25);
            border-color: rgba(195, 176, 145, 0.3);
        }

        .stat-icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s ease;
            position: relative;
            box-shadow: 0 8px 20px rgba(195, 176, 145, 0.3);
        }

        .stat-card-enhanced:hover .stat-icon-wrapper {
            transform: rotate(5deg) scale(1.1);
            box-shadow: 0 12px 30px rgba(195, 176, 145, 0.4);
        }

        .stat-icon {
            font-size: 36px;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .stat-card-enhanced:hover .stat-icon {
            transform: scale(1.15);
        }

        .stat-number {
            font-size: 56px;
            font-weight: 800;
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            line-height: 1;
            transition: all 0.3s ease;
        }

        .stat-card-enhanced:hover .stat-number {
            transform: scale(1.05);
        }

        .stat-label {
            color: #4b5563;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin: 0;
            transition: color 0.3s ease;
        }

        .stat-card-enhanced:hover .stat-label {
            color: #7a6a4f;
        }

        @media (max-width: 768px) {
            .stats-section-enhanced {
                padding: 60px 20px;
                margin: 60px 0;
            }

            .stats-grid-enhanced {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .stat-card-enhanced {
                padding: 30px 20px;
            }

            .stat-icon-wrapper {
                width: 70px;
                height: 70px;
                margin-bottom: 20px;
            }

            .stat-icon {
                font-size: 30px;
            }

            .stat-number {
                font-size: 42px;
            }

            .stat-label {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid-enhanced {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="logo" aria-label="Dwellscape Staycation">
                <span class="brand-mark" style="display: inline-flex !important; visibility: visible !important; align-items: center;">
                    <img src="assets/img/dwellscape-logo.png" alt="Dwellscape logo" loading="eager" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: 26px; width: auto; max-width: 100px; object-fit: contain; background: transparent; border: none; padding: 0; margin: 0; margin-right: 10px;" onerror="console.error('Logo failed to load:', this.src); this.style.display='none'; const fallback = this.nextElementSibling; if(fallback && fallback.classList.contains('logo-fallback')) { fallback.style.display='inline-block'; fallback.style.visibility='visible'; }">
                    <svg class="logo-fallback" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="display: none; height: 26px; width: 26px; vertical-align: middle; background: transparent;">
                        <path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z" fill="none" stroke="#7a6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="logo-text">DWELLSCAPE <small>STAYCATION</small></span>
            </a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="#virtual-view">Virtual View</a></li>
                    <li><a href="#amenities">Amenities</a></li>
                    <li><a href="#gallery">Gallery</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="bookings.php">Book Now</a></li>
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Promotional Hero Banner -->
        <section class="promo-hero">
            <!-- Main background image for the hero slider (all images are your own from /htdocs/pictures) -->
            <img src="pictures/dashboard1.png" alt="Dwellscape South Residences" class="promo-hero-image" id="promoHeroImage" style="display: block !important; visibility: visible !important; opacity: 1 !important;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop';">
            <div class="promo-hero-overlay"></div>
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
            </div>

            <!-- Carousel indicators (4 dots) -->
            <div class="promo-hero-indicators" id="promoHeroIndicators">
                <button type="button" class="promo-hero-indicator active" data-index="0" aria-label="Slide 1"></button>
                <button type="button" class="promo-hero-indicator" data-index="1" aria-label="Slide 2"></button>
                <button type="button" class="promo-hero-indicator" data-index="2" aria-label="Slide 3"></button>
                <button type="button" class="promo-hero-indicator" data-index="3" aria-label="Slide 4"></button>
            </div>
        </section>

        <!-- Home Section -->
        <section class="hero-section" id="home">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-subtitle">Relax. Unwind. Stay in Style.</div>
                    <h1 class="hero-title">
                        Discover your perfect <span class="highlight">staycation escape</span>
                    </h1>
                    <p class="hero-description">
                        Welcome to Dwellscape, your home away from home. Enjoy curated spaces,
                        premium amenities, and seamless bookings—crafted for memorable stays.
                    </p>
                    <a href="bookings.php" class="cta-button">
                        Book Now <i class="fas fa-calendar"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Experience Section -->
        <section class="experience-section" id="company">
            <div class="container">
                <div class="experience-content">
                    <div class="experience-images">
                        <div class="image-collage">
                            <img src="pictures/dashboard2.png" alt="Luxury Staycation Experience" class="collage-image" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1200&auto=format&fit=crop';">
                            <div class="collage-overlay"></div>
                            <div class="experience-box">
                                <h3>2 Years Of Experience</h3>
                            </div>
                        </div>
                    </div>
                    <div class="experience-text">
                        <h2>Thoughtfully designed spaces for every getaway</h2>
                        <p>
                            From city escapes to beachfront retreats, we curate stylish, comfortable stays
                            with the amenities you love—so you can focus on making memories.
                        </p>
                        <ul class="features-list">
                            <li>
                                <i class="fas fa-check"></i>
                                <div>
                                    <div class="feature-title">Prime Locations</div>
                                    <div class="feature-description">Close to dining, shopping, and attractions</div>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-star"></i>
                                <div>
                                    <div class="feature-title">Hotel-level Comfort</div>
                                    <div class="feature-description">Premium bedding, fast Wi‑Fi, and smart TVs</div>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-signature"></i>
                                <div>
                                    <div class="feature-title">Seamless Booking</div>
                                    <div class="feature-description">Easy, secure, and responsive support</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Virtual View Section -->
        <section class="section" id="virtual-view" style="background: #f7f5f1;">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">Virtual View</h1>
                    <p class="page-subtitle">Experience our properties in stunning 360° virtual tours. Explore every corner before you book.</p>
                </div>

                <div class="virtual-tour-container" style="background: white; border-radius: 12px; padding: 40px; margin-bottom: 40px;">
                    <div class="embed-16x9" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                        <iframe 
                            src="https://app.lapentor.com/sphere/room-tour-8?scene=695b6b6031ba7a23620807ac" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; border-radius: 12px;"
                            allowfullscreen
                            allow="vr; accelerometer; gyroscope; autoplay"
                            title="360° Virtual Tour - Dwellscape Staycation">
                        </iframe>
                    </div>
                    <div class="tour-info" style="margin-top: 30px; text-align: center;">
                        <h3 style="color: #1f2937; font-size: 24px; margin-bottom: 15px;">360° Property Tour</h3>
                        <p style="color: #6b7280; font-size: 16px; line-height: 1.6;">Take a virtual walkthrough of our premium staycation spaces. Click and drag to explore, or use the controls to navigate through different rooms and areas.</p>
                    </div>
                </div>
            </div>
        </section>

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
                                    <th>Guests</th>
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

                    <div class="bookings-pagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 28px; flex-wrap: wrap; gap: 18px;">
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

            <div class="booking-modal-overlay" id="bookingModal">
                <div class="booking-modal">
                    <button class="close-modal" id="closeBookingModal">&times;</button>
                    <h3>New Reservation</h3>
                    <p>Complete the details below to secure a staycation schedule for your guest.</p>
                    <form id="bookingModalForm">
                        <div class="form-row">
                            <div>
                                <label for="modalGuestName">Guest Name</label>
                                <input type="text" id="modalGuestName" name="guestName" placeholder="e.g. Maria Reyes" required>
                            </div>
                            <div>
                                <label for="modalContact">Contact Number</label>
                                <input type="tel" id="modalContact" name="contact" placeholder="09XX-XXX-XXXX" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="modalCheckIn">Check-in Date</label>
                                <input type="date" id="modalCheckIn" name="checkIn" required>
                            </div>
                            <div>
                                <label for="modalCheckOut">Check-out Date</label>
                                <input type="date" id="modalCheckOut" name="checkOut" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="modalTime">Arrival Time</label>
                                <input type="time" id="modalTime" name="time" required>
                            </div>
                            <div>
                                <label for="modalGuests">Guests</label>
                                <select id="modalGuests" name="guests" required>
                                    <option value="">Select</option>
                                    <option value="2">2 guests</option>
                                    <option value="4">4 guests</option>
                                    <option value="6">6 guests</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="modalSuite">Suite Type</label>
                                <select id="modalSuite" name="suite" required>
                                    <option value="">Select suite</option>
                                    <option value="1 Bedroom Suite">1 Bedroom Suite</option>
                                    <option value="2 Bedroom Premium">2 Bedroom Premium</option>
                                    <option value="Family Loft">Family Loft</option>
                                </select>
                            </div>
                            <div>
                                <label for="modalStatus">Status</label>
                                <select id="modalStatus" name="status" required>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="modalNotes">Special Requests</label>
                            <textarea id="modalNotes" name="notes" rows="3" placeholder="Early check-in, celebration setup, etc."></textarea>
                        </div>
                        <button type="submit" class="cta-button">Submit Reservation</button>
                    </form>
                </div>
            </div>

        <!-- Amenities Section -->
        <section class="section amenities-section" id="amenities">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">Amenities</h1>
                    <p class="amenities-subtitle">Whether you are planning a family retreat or a remote-work escape, every suite is stocked with thoughtful amenities so you can travel light and live well.</p>
                </div>

                <div class="amenities-grid">
                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-snowflake"></i>
                        </div>
                        <h3>Air Conditioning</h3>
                        <p>Split-type AC units for optimal comfort.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-wifi"></i>
                        </div>
                        <h3>Unlimited WiFi</h3>
                        <p>High-speed internet for work and streaming.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-tv"></i>
                        </div>
                        <h3>Smart TV</h3>
                        <p>60" & 42" TVs with Netflix and YouTube.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-microphone"></i>
                        </div>
                        <h3>Karaoke Speaker</h3>
                        <p>Bluetooth karaoke for music sessions.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <h3>Queen Bed</h3>
                        <p>Master bedroom with queen-size bed.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <h3>Double Bed</h3>
                        <p>Additional double bed for extra guests.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <h3>Free Coffee</h3>
                        <p>Complimentary coffee available.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <h3>Honesty Store</h3>
                        <p>Snacks, drinks, and essentials available.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-wine-bottle"></i>
                        </div>
                        <h3>Wines</h3>
                        <p>Selection of wines for purchase.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-cocktail"></i>
                        </div>
                        <h3>Softdrinks</h3>
                        <p>Variety of soft drinks available.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-cookie-bite"></i>
                        </div>
                        <h3>Snacks</h3>
                        <p>Assorted snacks for purchase.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3>Cup Noodles</h3>
                        <p>Quick meal options available.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <h3>Canned Goods</h3>
                        <p>Canned goods for cooking needs.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-tint"></i>
                        </div>
                        <h3>Bottled Water</h3>
                        <p>Hydration available for convenience.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-spa"></i>
                        </div>
                        <h3>Free Towels</h3>
                        <p>Face, hand, and bath towels provided.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-fan"></i>
                        </div>
                        <h3>Hair Dryer</h3>
                        <p>Complimentary hair dryer available.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-pump-soap"></i>
                        </div>
                        <h3>Free Toiletries</h3>
                        <p>Essential toiletries provided.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-soap"></i>
                        </div>
                        <h3>Dish Soap</h3>
                        <p>Free dish soap for cleaning.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-shower"></i>
                        </div>
                        <h3>Rain Shower</h3>
                        <p>Hot and cold rain shower.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3>Full Kitchen</h3>
                        <p>Fully equipped kitchen with essentials.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-snowflake"></i>
                        </div>
                        <h3>Refrigerator</h3>
                        <p>Keep food and drinks fresh.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-cube"></i>
                        </div>
                        <h3>Microwave</h3>
                        <p>Quick meal heating available.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-fire-alt"></i>
                        </div>
                        <h3>Hot Plate</h3>
                        <p>Electric hot plate for cooking.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-mug-hot"></i>
                        </div>
                        <h3>Electric Kettle</h3>
                        <p>Quick water boiling for tea/coffee.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-circle"></i>
                        </div>
                        <h3>Rice Cooker</h3>
                        <p>Perfect rice preparation included.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-utensil-spoon"></i>
                        </div>
                        <h3>Kitchen Utensils</h3>
                        <p>Complete set for all cooking needs.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-pepper-hot"></i>
                        </div>
                        <h3>Condiments</h3>
                        <p>Basic condiments for cooking.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-basketball-ball"></i>
                        </div>
                        <h3>Basketball</h3>
                        <p>Basketball equipment for activities.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-circle"></i>
                        </div>
                        <h3>Billiards</h3>
                        <p>Billiards table for entertainment.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-dice"></i>
                        </div>
                        <h3>Board Games</h3>
                        <p>Variety of board games available.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h3>Card Games</h3>
                        <p>Card decks for your enjoyment.</p>
                    </article>

                    <article class="amenity-card">
                        <div class="amenity-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <h3>Dumbbells</h3>
                        <p>Fitness equipment for workouts.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section class="section" id="gallery">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">Gallery</h1>
                    <p class="page-subtitle">Explore our beautiful properties and spaces. See what makes Dwellscape the perfect staycation choice.</p>
                </div>

                <div class="gallery-filters" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; justify-content: center;">
                    <button class="gallery-filter-btn active" onclick="filterGallery('all')" style="padding: 10px 20px; background: #C3B091; border: 2px solid #C3B091; border-radius: 25px; color: white; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">All</button>
                    <button class="gallery-filter-btn" onclick="filterGallery('living-room')" style="padding: 10px 20px; background: white; border: 2px solid #e5e7eb; border-radius: 25px; color: #6b7280; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">Living Room</button>
                    <button class="gallery-filter-btn" onclick="filterGallery('kitchen')" style="padding: 10px 20px; background: white; border: 2px solid #e5e7eb; border-radius: 25px; color: #6b7280; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">Full Kitchen</button>
                    <button class="gallery-filter-btn" onclick="filterGallery('dining')" style="padding: 10px 20px; background: white; border: 2px solid #e5e7eb; border-radius: 25px; color: #6b7280; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">Dining Area</button>
                    <button class="gallery-filter-btn" onclick="filterGallery('bedroom1')" style="padding: 10px 20px; background: white; border: 2px solid #e5e7eb; border-radius: 25px; color: #6b7280; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">Bedroom 1</button>
                    <button class="gallery-filter-btn" onclick="filterGallery('bedroom2')" style="padding: 10px 20px; background: white; border: 2px solid #e5e7eb; border-radius: 25px; color: #6b7280; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">Bedroom 2</button>
                    <button class="gallery-filter-btn" onclick="filterGallery('bathroom')" style="padding: 10px 20px; background: white; border: 2px solid #e5e7eb; border-radius: 25px; color: #6b7280; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">Full Bathroom</button>
                    <button class="gallery-filter-btn" onclick="filterGallery('pool')" style="padding: 10px 20px; background: white; border: 2px solid #e5e7eb; border-radius: 25px; color: #6b7280; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">Pool</button>
                    <button class="gallery-filter-btn" onclick="filterGallery('activity')" style="padding: 10px 20px; background: white; border: 2px solid #e5e7eb; border-radius: 25px; color: #6b7280; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-size: 14px;">Activity Area</button>
                </div>

                <div class="gallery-grid">
                    <?php
                    // Get active gallery images from database
                    try {
                        $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC");
                        $gallery_images = $stmt->fetchAll();
                        
                        if (empty($gallery_images)) {
                            echo '<p style="grid-column: 1 / -1; text-align: center; color: #666; padding: 40px;">No gallery images available. <a href="add_all_gallery_images.php" style="color: #C3B091; text-decoration: underline;">Click here to restore images</a></p>';
                        } else {
                            foreach ($gallery_images as $image):
                                // Normalize image path
                                $raw_url = trim($image['image_url'] ?? '');
                                
                                if (empty($raw_url)) {
                                    continue; // Skip empty URLs
                                }
                                
                                // Extract filename from any path format
                                $filename = basename($raw_url);
                                
                                // Resolve path to web-accessible URL (dashboard.php is at root, so pictures/ is at pictures/)
                                $image_url = '';
                                
                                // Already a full URL → leave as is
                                if (preg_match('~^https?://~i', $raw_url)) {
                                    $image_url = $raw_url;
                                }
                                // Paths that start with pictures/ (relative to app root)
                                elseif (preg_match('~^pictures/~i', $raw_url)) {
                                    // From dashboard.php (root), pictures folder is at pictures/
                                    $image_url = 'pictures/' . $filename;
                                }
                                // Paths that start with ../pictures/ (from admin or subdirectories)
                                elseif (preg_match('~^\.\./pictures/~i', $raw_url)) {
                                    // Convert to relative path from root
                                    $image_url = 'pictures/' . $filename;
                                }
                                // Paths that start with uploads/
                                elseif (preg_match('~^uploads/~i', $raw_url)) {
                                    $image_url = $raw_url;
                                }
                                // Paths that start with ../uploads/
                                elseif (preg_match('~^\.\./uploads/~i', $raw_url)) {
                                    // Convert to relative path from root
                                    $image_url = str_replace('../uploads/', 'uploads/', $raw_url);
                                }
                                // If it's a Windows absolute path, extract filename
                                elseif (preg_match('~[\\\\/]pictures[\\\\/](.+)$~i', $raw_url, $m)) {
                                    $image_url = 'pictures/' . str_replace('\\', '/', $m[1]);
                                }
                                elseif (preg_match('~[\\\\/]uploads[\\\\/](.+)$~i', $raw_url, $m)) {
                                    $image_url = 'uploads/' . str_replace('\\', '/', $m[1]);
                                }
                                // Plain filename → assume it's in pictures folder
                                elseif (!empty($filename) && preg_match('~\.(jpg|jpeg|png|gif|webp|avif)$~i', $filename)) {
                                    $image_url = 'pictures/' . $filename;
                                }
                                // Fallback: try to extract filename
                                else {
                                    $image_url = 'pictures/' . $filename;
                                }
                                
                                // Verify file exists and try alternative extensions if needed
                                if (!preg_match('~^https?://~i', $image_url)) {
                                    // Get local file path
                                    $local_path = str_replace('pictures/', __DIR__ . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR, $image_url);
                                    $local_path = str_replace('uploads/', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR, $local_path);
                                    
                                    // Normalize path separators
                                    $local_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $local_path);
                                    
                                    if (!file_exists($local_path)) {
                                        // Try alternative extensions (AVIF → PNG, etc.)
                                        $base_path = pathinfo($local_path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($local_path, PATHINFO_FILENAME);
                                        $extensions = ['avif', 'png', 'jpg', 'jpeg', 'webp', 'gif'];
                                        $found = false;
                                        
                                        foreach ($extensions as $ext) {
                                            $test_path = $base_path . '.' . $ext;
                                            if (file_exists($test_path)) {
                                                // Update image_url to use found extension
                                                $found_filename = pathinfo($local_path, PATHINFO_FILENAME) . '.' . $ext;
                                                $image_url = 'pictures/' . $found_filename;
                                                $found = true;
                                                break;
                                            }
                                        }
                                        
                                        // Also try case-insensitive filename matching
                                        if (!$found) {
                                            $pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
                                            if (is_dir($pictures_dir)) {
                                                $files = scandir($pictures_dir);
                                                $base_name = pathinfo($filename, PATHINFO_FILENAME);
                                                foreach ($files as $file) {
                                                    if ($file !== '.' && $file !== '..') {
                                                        $file_base = pathinfo($file, PATHINFO_FILENAME);
                                                        if (strcasecmp($base_name, $file_base) === 0) {
                                                            $image_url = 'pictures/' . $file;
                                                            $found = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                $image_url = htmlspecialchars(str_replace('\\', '/', $image_url));
                                $title = htmlspecialchars($image['title']);
                                $description = htmlspecialchars($image['description'] ?? '');
                                $category = htmlspecialchars($image['category']);
                                $alt_text = htmlspecialchars($image['title'] . ' - ' . $description);
                            ?>
                            <div class="gallery-item" data-category="<?php echo $category; ?>" onclick="openGalleryModal('<?php echo $image_url; ?>', '<?php echo $title; ?>', '<?php echo $description; ?>')">
                                <img src="<?php echo $image_url; ?>" alt="<?php echo $alt_text; ?>" onerror="handleImageError(this, '<?php echo addslashes($image_url); ?>')" loading="lazy">
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
        </section>

        <!-- Gallery Modal/Lightbox -->
        <div id="galleryModal" class="gallery-modal" onclick="if(event.target === this) closeGalleryModal()">
            <div class="gallery-modal-content">
                <button class="gallery-modal-close" onclick="closeGalleryModal()" title="Close (ESC)">&times;</button>
                <button class="gallery-modal-nav prev" onclick="navigateGallery(-1)" title="Previous (←)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="gallery-modal-nav next" onclick="navigateGallery(1)" title="Next (→)">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <img id="modalImage" class="gallery-modal-image" src="" alt="">
                <div class="gallery-modal-info">
                    <div class="gallery-modal-info-title" id="modalTitle"></div>
                    <div class="gallery-modal-info-category" id="modalCategory"></div>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <section class="section" id="about" style="background: #f7f5f1;">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">About Dwellscape</h1>
                    <p class="page-subtitle">Your trusted partner in creating memorable staycation experiences.</p>
                </div>

                <div class="about-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 60px; align-items: center;">
                    <div class="about-text">
                        <h2 style="font-size: 36px; font-weight: 700; color: #1f2937; margin-bottom: 20px;">Thoughtfully designed spaces for every getaway</h2>
                        <p style="color: #6b7280; font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                            From city escapes to beachfront retreats, we curate stylish, comfortable stays
                            with the amenities you love—so you can focus on making memories.
                        </p>
                        <p style="color: #6b7280; font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                            Dwellscape Staycation is dedicated to providing exceptional staycation experiences
                            at South Residences, Las Pinas City. We believe that everyone deserves a perfect
                            escape, whether it's a weekend getaway or an extended stay.
                        </p>
                        <p style="color: #6b7280; font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                            Our properties are carefully selected and maintained to ensure the highest standards
                            of comfort, cleanliness, and style. We combine modern amenities with thoughtful
                            design to create spaces that feel like home.
                        </p>
                    </div>
                    <div class="about-image" style="position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);">
                        <img src="pictures/virtual.jpg" alt="About Dwellscape" style="width: 100%; height: 400px; object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1200&auto=format&fit=crop';">
                    </div>
                </div>

                <div class="stats-section-enhanced">
                    <div class="stats-grid-enhanced">
                        <div class="stat-card-enhanced" data-value="2" data-suffix="">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-calendar-alt stat-icon"></i>
                            </div>
                            <h3 class="stat-number">2</h3>
                            <p class="stat-label">Years of Experience</p>
                        </div>
                        <div class="stat-card-enhanced" data-value="500" data-suffix="+">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-users stat-icon"></i>
                            </div>
                            <h3 class="stat-number">500+</h3>
                            <p class="stat-label">Happy Guests</p>
                        </div>
                        <div class="stat-card-enhanced" data-value="5" data-suffix="">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-building stat-icon"></i>
                            </div>
                            <h3 class="stat-number">5</h3>
                            <p class="stat-label">Properties</p>
                        </div>
                        <div class="stat-card-enhanced" data-value="98" data-suffix="%">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-star stat-icon"></i>
                            </div>
                            <h3 class="stat-number">98%</h3>
                            <p class="stat-label">Guest Satisfaction</p>
                        </div>
                    </div>
                </div>

                <div class="values-section">
                    <div class="values-eyebrow">What guides us</div>
                    <h2 class="page-title" style="text-align: center;">Our Values</h2>
                    <p class="values-subtitle">Every stay is built on a promise: thoughtful design, heartfelt hospitality, and seamless experiences that let you make the most of every moment.</p>
                    <div class="values-grid">
                        <article class="value-card">
                            <span class="value-badge"><i class="fas fa-heart"></i> Comfort First</span>
                            <h3>Guest-Ready Comfort</h3>
                            <p>We prepare each suite with hotel-grade touches and curated essentials so you can switch off the moment you arrive.</p>
                            <ul>
                                <li><i class="fas fa-check"></i> Ultra-soft linens, bespoke pillows, and aromatherapy welcome kits</li>
                                <li><i class="fas fa-check"></i> Climate control, blackout shades, and acoustic insulation for deep rest</li>
                            </ul>
                        </article>
                        <article class="value-card">
                            <span class="value-badge"><i class="fas fa-magic"></i> Quality Service</span>
                            <h3>Hospitality on Your Terms</h3>
                            <p>Our concierge-style support is always a tap away—before, during, and after your stay—to make every request effortless.</p>
                            <ul>
                                <li><i class="fas fa-check"></i> 24/7 guest messaging with lightning-fast response times</li>
                                <li><i class="fas fa-check"></i> Personalized itineraries and local insider recommendations</li>
                            </ul>
                        </article>
                        <article class="value-card">
                            <span class="value-badge"><i class="fas fa-home"></i> Design-Led Spaces</span>
                            <h3>Beautiful, Purposeful Interiors</h3>
                            <p>Each residence is thoughtfully styled to balance aesthetics and functionality, encouraging connection and calm.</p>
                            <ul>
                                <li><i class="fas fa-check"></i> Multi-zone layouts for lounging, working, and dining together</li>
                                <li><i class="fas fa-check"></i> Locally sourced art and decor that celebrate Manila's creative culture</li>
                            </ul>
                        </article>
                        <article class="value-card">
                            <span class="value-badge"><i class="fas fa-shield-alt"></i> Trust & Safety</span>
                            <h3>Security You Can Feel</h3>
                            <p>Your peace of mind is non-negotiable. From smart access to proactive maintenance, we keep every detail safeguarded.</p>
                            <ul>
                                <li><i class="fas fa-check"></i> Secure digital check-in with rotating smart locks</li>
                                <li><i class="fas fa-check"></i> Professional housekeeping and on-call maintenance team</li>
                            </ul>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="section" id="contact">
            <div class="container">
                <div class="page-header">
                    <h1 class="page-title">Contact Us</h1>
                    <p class="page-subtitle">Share your plans, ask questions, or let us know how we can make your stay unforgettable.</p>
                </div>

                <div class="contact-visual">
                    <div class="contact-form-card">
                        <h2>Message Us</h2>
                        <p>Fill out the form and our staycation specialists will reach out shortly with tailored recommendations and exclusive offers.</p>
                        <form id="contactMessageForm">
                            <div class="contact-form-row">
                                <input type="text" id="contactFirstName" name="firstName" placeholder="First name" required>
                                <input type="email" id="contactEmail" name="email" placeholder="Email address" required>
                            </div>
                            <textarea id="contactMessage" name="message" placeholder="Message" required></textarea>
                            <button type="submit" class="cta-button" id="contactSendButton">
                                Send Message <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                    <div class="contact-map">
                        <iframe
                            title="Dwellscape Location"
                            src="https://www.google.com/maps?q=South%20Residences%2C%20Las%20Pinas%20City%2C%20Metro%20Manila&output=embed"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Brand Section -->
                <div class="footer-brand">
                    <h3 class="footer-logo">DWELLSCAPE <span>STAYCATION</span></h3>
                    <p class="footer-description">Escape the ordinary and embrace the extra ordinary at home. Your perfect staycation experience awaits.</p>
                    <div class="social-links">
                        <a href="https://web.facebook.com/profile.php?id=61560289265086" class="social-link" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.tiktok.com/@dwellscape.stayca" class="social-link" aria-label="TikTok" target="_blank" rel="noopener noreferrer"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-column">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="bookings.php">Book Now</a></li>
                        <li><a href="#gallery">Gallery</a></li>
                        <li><a href="#amenities">Amenities</a></li>
                        <li><a href="#virtual-view">Virtual View</a></li>
                    </ul>
                </div>

                <!-- About -->
                <div class="footer-column">
                    <h4 class="footer-title">About</h4>
                    <ul class="footer-links">
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="privacy-policy.php">Privacy Policy</a></li>
                        <li><a href="terms-conditions.php">Terms & Conditions</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="footer-column">
                    <h4 class="footer-title">Contact Us</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <p>South Residences</p>
                                <p>Las Pinas City, Metro Manila</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <p>dwellscapestaycation@gmail.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <p>09091231231</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="footer-copyright">
                <p>&copy; <?php echo date('Y'); ?> Dwellscape Staycation. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `booking-toast ${type}`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i> ${message}`;
            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.add('visible');
            });

            setTimeout(() => {
                toast.classList.remove('visible');
                toast.addEventListener('transitionend', () => toast.remove(), { once: true });
            }, 3200);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const profileSection = document.querySelector('.profile-section');
            const dropdown = document.getElementById('profileDropdown');
            
            if (!profileSection.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Enhanced Bookings Management
        document.addEventListener('DOMContentLoaded', function() {
            let bookings = [];
            let filteredBookings = [];
            
            const tableBody = document.getElementById('bookingTableBody');
            const searchInput = document.getElementById('bookingSearch');
            const statusFilter = document.getElementById('statusFilter');
            const noResultsMessage = document.getElementById('noBookingResults');
            const summaryText = document.getElementById('bookingSummary');
            const paginationInfo = document.getElementById('paginationInfo');
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');
            const openModalBtn = document.getElementById('openBookingModal');
            const modalOverlay = document.getElementById('bookingModal');
            const closeModalBtn = document.getElementById('closeBookingModal');
            const bookingForm = document.getElementById('bookingModalForm');
            
            // Bookings List Modal handlers
            const bookingsListModal = document.getElementById('bookingsListModal');
            const closeBookingsListBtn = document.getElementById('closeBookingsListModal');
            
            // Load bookings from database
            async function loadBookings() {
                try {
                    const response = await fetch('api/get_bookings.php', {
                        credentials: 'include' // Include cookies for authentication
                    });
                    const result = await response.json();
                    
                    if (result.success && result.bookings) {
                        bookings = result.bookings;
                        applyFilters();
                    } else {
                        bookings = [];
                        filteredBookings = [];
                        renderTable();
                    }
                } catch (error) {
                    console.error('Error loading bookings:', error);
                    bookings = [];
                    filteredBookings = [];
                    renderTable();
                }
            }
            
            function openBookingsListModal(e) {
                if (e) e.preventDefault();
                if (bookingsListModal) {
                    bookingsListModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    // Load bookings when modal opens
                    loadBookings();
                }
            }
            
            
            const openBookingsListNavBtn = document.getElementById('openBookingsListModalNav');
            if (openBookingsListNavBtn) {
                openBookingsListNavBtn.addEventListener('click', openBookingsListModal);
            }
            
            const openBookingsListDropdownBtn = document.getElementById('openBookingsListModalDropdown');
            if (openBookingsListDropdownBtn) {
                openBookingsListDropdownBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openBookingsListModal(e);
                    // Close dropdown when opening modal
                    const dropdown = document.getElementById('profileDropdown');
                    if (dropdown) {
                        dropdown.classList.remove('active');
                    }
                });
            }
            
            if (closeBookingsListBtn && bookingsListModal) {
                closeBookingsListBtn.addEventListener('click', function() {
                    bookingsListModal.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }
            
            // Close bookings list modal when clicking outside
            if (bookingsListModal) {
                bookingsListModal.addEventListener('click', function(e) {
                    if (e.target === bookingsListModal) {
                        bookingsListModal.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            }
            const checkInInput = document.getElementById('modalCheckIn');
            const checkOutInput = document.getElementById('modalCheckOut');
            const modalTimeInput = document.getElementById('modalTime');
            const contactMessageForm = document.getElementById('contactMessageForm');
            const pageSize = 6;
            let currentPage = 1;

            function formatDate(dateString) {
                const date = new Date(dateString + 'T00:00:00');
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric'
                });
            }

            function formatReservationRange(checkIn, checkOut) {
                const start = new Date(checkIn + 'T00:00:00');
                const end = new Date(checkOut + 'T00:00:00');

                if (start.toDateString() === end.toDateString()) {
                    return start.toLocaleDateString('en-US', {
                        month: 'short',
                        day: '2-digit',
                        year: 'numeric'
                    });
                }

                const sameMonthAndYear =
                    start.getMonth() === end.getMonth() &&
                    start.getFullYear() === end.getFullYear();

                if (sameMonthAndYear) {
                    const month = start.toLocaleDateString('en-US', { month: 'short' });
                    const startDay = String(start.getDate()).padStart(2, '0');
                    const endDay = String(end.getDate()).padStart(2, '0');
                    return `${month} ${startDay}-${endDay}, ${start.getFullYear()}`;
                }

                const sameYear = start.getFullYear() === end.getFullYear();
                const startFormat = start.toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: sameYear ? undefined : 'numeric'
                }).replace(', ', ' ');
                const endFormat = end.toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric'
                }).replace(', ', ' ');

                return `${startFormat} - ${endFormat}`;
            }

            function formatTime(timeString) {
                const [hours, minutes] = timeString.split(':').map(Number);
                const date = new Date();
                date.setHours(hours, minutes, 0);
                return date.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }

            function renderTable() {
                const total = filteredBookings.length;
                const totalPages = Math.max(1, Math.ceil(total / pageSize));
                currentPage = Math.min(Math.max(currentPage, 1), totalPages);
                const start = (currentPage - 1) * pageSize;
                const pageItems = filteredBookings.slice(start, start + pageSize);

                tableBody.innerHTML = '';

                if (total === 0) {
                    noResultsMessage.style.display = 'block';
                    summaryText.textContent = 'No reservations to display';
                    paginationInfo.textContent = '';
                    prevPageBtn.disabled = true;
                    nextPageBtn.disabled = true;
                    return;
                }

                noResultsMessage.style.display = 'none';

                pageItems.forEach(booking => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${formatDate(booking.bookingDate)}</td>
                        <td>${formatReservationRange(booking.checkIn, booking.checkOut)}</td>
                        <td>${formatTime(booking.time)}</td>
                        <td>
                            <div style="font-weight: 600; color: #111827;">${booking.guest}</div>
                            <div style="font-size: 13px; color: #6b7280; margin-top: 2px;">${booking.id}</div>
                        </td>
                        <td>${booking.guests} pax</td>
                        <td>${booking.suite}</td>
                        <td>₱${(booking.totalPrice || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    `;
                    tableBody.appendChild(row);
                });

                const end = Math.min(start + pageItems.length, total);
                summaryText.textContent = `Showing ${start + 1}-${end} of ${total} reservations`;
                paginationInfo.textContent = `Page ${currentPage} of ${totalPages}`;

                prevPageBtn.disabled = currentPage === 1;
                nextPageBtn.disabled = currentPage === totalPages;
            }

            function applyFilters() {
                const query = searchInput.value.trim().toLowerCase();

                filteredBookings = bookings.filter(booking => {
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

            function openModal() {
                const today = new Date().toISOString().split('T')[0];
                checkInInput.setAttribute('min', today);
                checkOutInput.setAttribute('min', today);
                modalTimeInput.value = modalTimeInput.value || '14:00';
                modalOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modalOverlay.classList.remove('active');
                document.body.style.overflow = '';
                bookingForm.reset();
            }

            searchInput.addEventListener('input', applyFilters);

            prevPageBtn.addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });

            nextPageBtn.addEventListener('click', function() {
                const totalPages = Math.max(1, Math.ceil(filteredBookings.length / pageSize));
                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }
            });

            // Action buttons removed - replaced with Payment Method column

            if (openModalBtn) {
                openModalBtn.addEventListener('click', openModal);
            }
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', closeModal);
            }
            modalOverlay.addEventListener('click', function(event) {
                if (event.target === modalOverlay) {
                    closeModal();
                }
            });

            checkInInput.addEventListener('change', function() {
                if (this.value) {
                    checkOutInput.min = this.value;
                    if (checkOutInput.value && checkOutInput.value < this.value) {
                        checkOutInput.value = this.value;
                    }
                }
            });

            bookingForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const formData = new FormData(bookingForm);
                const checkIn = formData.get('checkIn');
                const checkOut = formData.get('checkOut');

                if (new Date(checkOut) <= new Date(checkIn)) {
                    showToast('Check-out date should be after check-in date.', 'error');
                    return;
                }

                const newBooking = {
                    id: `DS-${Math.floor(1000 + Math.random() * 9000)}`,
                    bookingDate: new Date().toISOString().split('T')[0],
                    checkIn,
                    checkOut,
                    time: formData.get('time'),
                    guest: formData.get('guestName'),
                    guests: parseInt(formData.get('guests'), 10),
                    suite: formData.get('suite'),
                    status: formData.get('status'),
                    contact: formData.get('contact'),
                    notes: formData.get('notes')
                };

                bookings.unshift(newBooking);
                applyFilters();
                closeModal();
                showToast('Reservation successfully added!');
            });

            if (contactMessageForm) {
                contactMessageForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    const firstName = document.getElementById('contactFirstName').value.trim();
                    const email = document.getElementById('contactEmail').value.trim();
                    const message = document.getElementById('contactMessage').value.trim();
                    const submitButton = document.getElementById('contactSendButton');

                    if (!firstName || !email || !message) {
                        showToast('Please complete all fields.', 'error');
                        return;
                    }

                    // Disable button and show loading state
                    submitButton.disabled = true;
                    const originalText = submitButton.innerHTML;
                    submitButton.innerHTML = 'Sending... <i class="fas fa-spinner fa-spin"></i>';

                    // Send data to server via AJAX
                    const formData = new FormData();
                    formData.append('firstName', firstName);
                    formData.append('email', email);
                    formData.append('message', message);

                    fetch('api/save_suggestion.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        // Check if response is OK
                        if (!response.ok) {
                            // Even if response is not OK, show success as user requested
                            // since they confirmed messages are being saved
                            return { success: true, message: 'Message sent successfully!' };
                        }
                        // Try to parse JSON response
                        return response.text().then(text => {
                            text = text.trim();
                            try {
                                const data = JSON.parse(text);
                                // If we got valid JSON, use it
                                // But always show success message as user requested
                                return { success: true, message: 'Message sent successfully!' };
                            } catch (e) {
                                // If JSON parsing fails, but we got a response, assume success
                                console.log('Response received (non-JSON):', text);
                                return { success: true, message: 'Message sent successfully!' };
                            }
                        });
                    })
                    .then(data => {
                        // Always show success message as user requested
                        showToast('Message sent successfully!', 'success');
                        contactMessageForm.reset();
                    })
                    .catch(error => {
                        console.error('Network error:', error);
                        // Even on network error, show success as user requested
                        showToast('Message sent successfully!', 'success');
                        contactMessageForm.reset();
                    })
                    .finally(() => {
                        // Re-enable button
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                    });
                });
            }
        });

        // Handle image errors - hide gallery items if images fail to load (no error messages)
        function handleImageError(img, originalSrc) {
            // Immediately hide the entire gallery item
            const galleryItem = img.closest('.gallery-item');
            if (galleryItem) {
                galleryItem.style.display = 'none';
                galleryItem.style.visibility = 'hidden';
                galleryItem.style.opacity = '0';
            }
            // Prevent any further error handling
            img.onerror = null;
        }

        // Gallery Filter Function
        function filterGallery(category) {
            // Update active button
            document.querySelectorAll('.gallery-filter-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'white';
                btn.style.borderColor = '#e5e7eb';
                btn.style.color = '#6b7280';
            });
            event.target.classList.add('active');
            event.target.style.background = '#C3B091';
            event.target.style.borderColor = '#C3B091';
            event.target.style.color = 'white';

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

        // Gallery Modal Functions
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

        // Close modal with ESC key and arrow navigation
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('galleryModal');
            if (modal && modal.classList.contains('active')) {
                if (e.key === 'Escape') {
                    closeGalleryModal();
                } else if (e.key === 'ArrowLeft') {
                    navigateGallery(-1);
                } else if (e.key === 'ArrowRight') {
                    navigateGallery(1);
                }
            }
        });

        // Handle navbar background on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            const promoHero = document.querySelector('.promo-hero');
            
            if (header && promoHero) {
                // Get the height of the hero section
                const heroHeight = promoHero.offsetHeight;
                
                // Add scrolled class when we've scrolled past the hero section
                if (window.scrollY > heroHeight - 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            } else if (header) {
                // Fallback: use a fixed pixel value if hero section not found
                if (window.scrollY > 600) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }
        });

        // Add beige background to header when Book Now button is pressed
        document.addEventListener('DOMContentLoaded', function() {
            const bookNowLinks = document.querySelectorAll('.nav-links a[href="bookings.php"]');
            const header = document.querySelector('.header');
            
            bookNowLinks.forEach(function(link) {
                link.addEventListener('mousedown', function() {
                    if (header) {
                        header.classList.add('book-now-active');
                    }
                });
                
                link.addEventListener('mouseup', function() {
                    // Keep the class briefly, then let scroll handler manage it
                    setTimeout(function() {
                        if (header && window.scrollY <= 100) {
                            header.classList.remove('book-now-active');
                        }
                    }, 150);
                });
                
                link.addEventListener('click', function() {
                    if (header) {
                        header.classList.add('book-now-active');
                    }
                });
            });
        });

        // =============================
        // Promo hero background carousel
        // =============================
        document.addEventListener('DOMContentLoaded', function () {
            const heroImage = document.getElementById('promoHeroImage');
            const indicatorsContainer = document.getElementById('promoHeroIndicators');
            if (!heroImage || !indicatorsContainer) return;

            // Add or change URLs here for your hero background slides
            // Try local images first, fallback to placeholders
            // Order: 1) Building, 2) Pool, 3) Playground, 4) Billiards room
            const heroSlides = [
                { local: 'pictures/dashboard1.png', fallback: 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?q=80&w=1920&auto=format&fit=crop' },
                { local: 'pictures/dashboard2.png', fallback: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop' },
                { local: 'pictures/dashboard3.png', fallback: 'https://images.unsplash.com/photo-1559827260-d06e58bcb0e0?q=80&w=1920&auto=format&fit=crop' },
                { local: 'pictures/dashboard4.png', fallback: 'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?q=80&w=1920&auto=format&fit=crop' }
            ];

            let currentSlide = 0;
            const indicators = Array.from(indicatorsContainer.querySelectorAll('.promo-hero-indicator'));

            function setActiveSlide(index) {
                currentSlide = index;
                
                // Force image to be visible
                heroImage.style.display = 'block';
                heroImage.style.visibility = 'visible';
                heroImage.style.opacity = '0';
                
                setTimeout(() => {
                    const slide = heroSlides[currentSlide];
                    
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
                                const img = new Image();
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
                            const promoHero = document.querySelector('.promo-hero');
                            const overlay = document.querySelector('.promo-hero-overlay');
                            if (promoHero) {
                                promoHero.style.background = 'linear-gradient(135deg, #7a6a4f 0%, #5a4d3a 50%, #4a3f2f 100%)';
                            }
                            if (overlay) {
                                overlay.style.background = 'linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 100%)';
                            }
                        };
                        fallbackImg.src = slide.fallback;
                    }
                }, 200);

                indicators.forEach((dot, i) => {
                    if (i === currentSlide) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }

            // Initialize first slide immediately
            setActiveSlide(0);
            
            // Force logo images to display - always show the image first
            document.addEventListener('DOMContentLoaded', function() {
                const logoImages = document.querySelectorAll('img[src*="dwellscape-logo"]');
                logoImages.forEach(function(img) {
                    // Always force display the image first - don't hide it
                    img.style.setProperty('display', 'block', 'important');
                    img.style.setProperty('visibility', 'visible', 'important');
                    img.style.setProperty('opacity', '1', 'important');
                    img.style.setProperty('background', 'transparent', 'important');
                    img.style.setProperty('border', 'none', 'important');
                    img.style.setProperty('padding', '0', 'important');
                    img.style.setProperty('margin', '0 10px 0 0', 'important');
                    
                    // Ensure fallback is hidden initially
                    const fallback = img.nextElementSibling;
                    if (fallback && fallback.classList.contains('logo-fallback')) {
                        fallback.style.setProperty('display', 'none', 'important');
                        fallback.style.setProperty('visibility', 'hidden', 'important');
                        fallback.style.setProperty('opacity', '0', 'important');
                        fallback.style.setProperty('background', 'transparent', 'important');
                    }
                    
                    // Only show fallback if image truly fails to load
                    img.onerror = function() {
                        console.error('Logo image failed to load:', this.src);
                        this.style.setProperty('display', 'none', 'important');
                        this.style.setProperty('visibility', 'hidden', 'important');
                        this.style.setProperty('opacity', '0', 'important');
                        const fallback = this.nextElementSibling;
                        if (fallback && fallback.classList.contains('logo-fallback')) {
                            fallback.style.setProperty('display', 'inline-block', 'important');
                            fallback.style.setProperty('visibility', 'visible', 'important');
                            fallback.style.setProperty('opacity', '1', 'important');
                        }
                    };
                    
                    // Verify image loads successfully
                    img.onload = function() {
                        console.log('Logo image loaded successfully:', this.src);
                        // Ensure image is visible
                        this.style.setProperty('display', 'block', 'important');
                        this.style.setProperty('visibility', 'visible', 'important');
                        this.style.setProperty('opacity', '1', 'important');
                        // Hide fallback if image loads
                        const fallback = this.nextElementSibling;
                        if (fallback && fallback.classList.contains('logo-fallback')) {
                            fallback.style.setProperty('display', 'none', 'important');
                        }
                    };
                });
            });
            
            // Auto-slide every 4 seconds
            let sliderTimer = setInterval(() => {
                const next = (currentSlide + 1) % heroSlides.length;
                setActiveSlide(next);
            }, 4000);

            // Allow clicking the dots to change slide
            indicators.forEach((dot, i) => {
                dot.addEventListener('click', () => {
                    setActiveSlide(i);
                    // Reset timer so the user has time to view selected slide
                    clearInterval(sliderTimer);
                    sliderTimer = setInterval(() => {
                        const next = (currentSlide + 1) % heroSlides.length;
                        setActiveSlide(next);
                    }, 3000);
                });
            });
        });

        // Enhanced Statistics Counter Animation
        function animateCounter(element, target, suffix = '', duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16); // 60fps
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                
                element.textContent = Math.floor(current) + suffix;
            }, 16);
        }

        // Intersection Observer for stats animation
        let statsObserver;
        
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card-enhanced');
            
            // Initialize all numbers to 0
            statCards.forEach(card => {
                const numberElement = card.querySelector('.stat-number');
                const suffix = card.getAttribute('data-suffix') || '';
                numberElement.textContent = '0' + suffix;
            });
            
            // Create observer
            statsObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                        const card = entry.target;
                        const numberElement = card.querySelector('.stat-number');
                        const value = parseInt(card.getAttribute('data-value'));
                        const suffix = card.getAttribute('data-suffix') || '';
                        
                        // Mark as animated
                        card.classList.add('animated');
                        
                        // Animate counter
                        animateCounter(numberElement, value, suffix);
                        
                        // Stop observing this card
                        statsObserver.unobserve(card);
                    }
                });
            }, {
                threshold: 0.3,
                rootMargin: '0px 0px -50px 0px'
            });

            // Observe all stat cards
            statCards.forEach(card => {
                statsObserver.observe(card);
            });
        });
    </script>
  </body>
</html>