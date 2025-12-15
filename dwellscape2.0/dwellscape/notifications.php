<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Dwellscape</title>
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
            max-width: 900px;
            margin: 0 auto;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .notifications-header h1 {
            font-size: 32px;
            color: #1f2937;
        }

        .mark-all-read {
            background: #C3B091;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mark-all-read:hover {
            background: #9A8B6F;
            transform: translateY(-2px);
        }

        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: flex-start;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .notification-item.unread {
            border-left-color: #C3B091;
            background: #fefbf6;
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .notification-icon.booking {
            background: linear-gradient(135deg, #C3B091 0%, #9A8B6F 100%);
            color: white;
        }

        .notification-icon.promotion {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .notification-icon.system {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .notification-item.unread .notification-title {
            color: #7a6a4f;
        }

        .notification-message {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .notification-time {
            font-size: 12px;
            color: #9ca3af;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            margin-left: 15px;
        }

        .action-btn {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .action-btn:hover {
            color: #C3B091;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            font-size: 24px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #9ca3af;
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

            <div class="notifications-header">
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <button class="mark-all-read" onclick="markAllRead()">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </button>
            </div>

            <div id="notificationsList">
                <div class="notification-item unread">
                    <div class="notification-icon booking">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">Booking Confirmed</div>
                        <div class="notification-message">Your staycation booking has been confirmed for March 15-17, 2024.</div>
                        <div class="notification-time">2 hours ago</div>
                    </div>
                    <div class="notification-actions">
                        <button class="action-btn" onclick="markAsRead(this)">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>

                <div class="notification-item unread">
                    <div class="notification-icon promotion">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">Special Promotion</div>
                        <div class="notification-message">Get 20% off on your next booking! Use code STAY20 at checkout.</div>
                        <div class="notification-time">1 day ago</div>
                    </div>
                    <div class="notification-actions">
                        <button class="action-btn" onclick="markAsRead(this)">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>

                <div class="notification-item">
                    <div class="notification-icon system">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">Welcome to Dwellscape!</div>
                        <div class="notification-message">Thank you for joining us. Start exploring amazing staycation options.</div>
                        <div class="notification-time">3 days ago</div>
                    </div>
                    <div class="notification-actions">
                        <button class="action-btn" onclick="markAsRead(this)">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>

                <div class="notification-item">
                    <div class="notification-icon booking">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">Reminder: Upcoming Stay</div>
                        <div class="notification-message">Don't forget your staycation starts tomorrow!</div>
                        <div class="notification-time">1 week ago</div>
                    </div>
                    <div class="notification-actions">
                        <button class="action-btn" onclick="markAsRead(this)">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function markAsRead(button) {
            const notification = button.closest('.notification-item');
            notification.classList.remove('unread');
            button.style.display = 'none';
        }

        function markAllRead() {
            const notifications = document.querySelectorAll('.notification-item.unread');
            notifications.forEach(notification => {
                notification.classList.remove('unread');
                const actionBtn = notification.querySelector('.action-btn');
                if (actionBtn) actionBtn.style.display = 'none';
            });
        }
    </script>
</body>
</html>

