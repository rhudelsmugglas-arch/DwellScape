<!-- Admin Sidebar Component -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="admin.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_users.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_users.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_bookings.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_bookings.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Recent Bookings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_transactions.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_transactions.php') ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>Recent Transactions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_rooms.php" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['admin_rooms.php', 'admin_room_details.php', 'admin_room_manage.php'])) ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_suggestions.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_suggestions.php') ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>View Suggestions</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info-sidebar">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <form method="POST">
            <button type="submit" name="logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>
</aside>

