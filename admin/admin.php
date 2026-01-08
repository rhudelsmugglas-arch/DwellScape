<?php
// Ensure output buffering is enabled at server level
ini_set('output_buffering', 'On');
ini_set('implicit_flush', 'Off');

// Start output buffering if not already started
if (!ob_get_level()) {
    ob_start();
}

// Clear any existing output that might have been sent
if (ob_get_length() > 0) {
    ob_clean();
}

// Start session (suppress warning if headers already sent, but try to prevent it)
if (!headers_sent()) {
    session_start();
} else {
    // If headers already sent, try to start session anyway
    @session_start();
}
require_once '../config/database.php';
require_once '../config/auth.php';

// Check cookie-based authentication first, then fall back to session
$auth_user = verifyAuthToken();
if ($auth_user) {
    // Set session variables from cookie auth for compatibility
    $_SESSION['user_id'] = $auth_user['user_id'];
    $_SESSION['username'] = $auth_user['username'];
    $_SESSION['email'] = $auth_user['email'];
    $_SESSION['role'] = $auth_user['role'];
    $_SESSION['is_admin'] = $auth_user['is_admin'];
}

// Redirect if not logged in - go to home page (which has login modal)
if (!isset($_SESSION['user_id']) && !$auth_user) {
    if (!headers_sent()) {
        header('Location: ../home.php');
        exit();
    } else {
        echo '<script>window.location.href = "../home.php";</script>';
        exit();
    }
}

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['is_admin'])) {
    $is_admin = $_SESSION['is_admin'];
} elseif ($auth_user) {
    $is_admin = $auth_user['is_admin'] || $auth_user['role'] === 'admin';
} else {
    // Check database
    try {
        $stmt = $pdo->prepare("SELECT is_admin, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $is_admin = ($user['role'] === 'admin') || ($user['is_admin'] ?? false);
    } catch(PDOException $e) {
        // Error checking
    }
}

// Redirect non-admin users to dashboard
if (!$is_admin) {
    if (!headers_sent()) {
        header('Location: ../dashboard.php');
        exit();
    } else {
        echo '<script>window.location.href = "../dashboard.php";</script>';
        exit();
    }
}

$page_title = 'Dashboard';

// Get statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_users = $stmt->fetch()['count'];
    
    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings");
    $total_bookings = $stmt->fetch()['count'];
    
    // Total transactions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions");
    $total_transactions = $stmt->fetch()['count'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions");
    $total_revenue = $stmt->fetch()['total'] ?? 0;
    
    // Recent bookings (last 5)
    $stmt = $pdo->query("
        SELECT b.*, u.username 
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id 
        ORDER BY b.created_at DESC 
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll();
    
    // Recent transactions (last 5)
    $stmt = $pdo->query("
        SELECT t.*, b.booking_id, u.username 
        FROM transactions t 
        LEFT JOIN bookings b ON t.booking_id = b.id 
        LEFT JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC 
        LIMIT 5
    ");
    $recent_transactions = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    // Initialize variables to prevent errors
    $total_users = 0;
    $total_bookings = 0;
    $total_transactions = 0;
    $total_revenue = 0;
    $recent_bookings = [];
    $recent_transactions = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Dashboard Overview</h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / Dashboard
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-users"></i></div>
        <h3>Total Users</h3>
        <div class="value"><?php echo number_format($total_users); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="icon"><i class="fas fa-calendar-check"></i></div>
        <h3>Total Bookings</h3>
        <div class="value"><?php echo number_format($total_bookings); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="icon"><i class="fas fa-credit-card"></i></div>
        <h3>Total Transactions</h3>
        <div class="value"><?php echo number_format($total_transactions); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        <h3>Total Revenue</h3>
        <div class="value currency">₱<?php echo number_format($total_revenue, 2); ?></div>
    </div>
</div>

<!-- Recent Bookings Section -->
<div class="content-section" style="margin-top: 30px;">
    <div class="section-header">
        <h2><i class="fas fa-calendar-check"></i> Recent Bookings</h2>
        <a href="admin_bookings.php" class="btn btn-secondary">View All</a>
    </div>
    
    <div class="table-container">
        <table class="table-wrapper">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Nights</th>
                    <th>Total Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_bookings)): ?>
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No bookings found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $booking): ?>
                    <tr>
                        <td><strong><?php echo $booking['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($booking['username'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?></td>
                        <td class="text-center"><?php echo $booking['nights']; ?></td>
                        <td class="currency"><strong>₱<?php echo number_format($booking['total_price'], 2); ?></strong></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($booking['payment_status']); ?>">
                                <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Transactions Section -->
<div class="content-section" style="margin-top: 30px;">
    <div class="section-header">
        <h2><i class="fas fa-credit-card"></i> Recent Transactions</h2>
        <a href="admin_transactions.php" class="btn btn-secondary">View All</a>
    </div>
    
    <div class="table-container">
        <table class="table-wrapper">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Booking ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_transactions)): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No transactions found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recent_transactions as $transaction): ?>
                    <tr>
                        <td><strong><?php echo $transaction['id']; ?></strong></td>
                        <td><?php echo $transaction['booking_id'] ?? 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($transaction['username'] ?? 'N/A'); ?></td>
                        <td class="currency"><strong>₱<?php echo number_format($transaction['amount'], 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($transaction['payment_method'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($transaction['payment_status']); ?>">
                                <?php echo ucfirst($transaction['payment_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.content-section {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.section-header h2 {
    color: #7a6a4f;
    font-size: 20px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.table-container {
    overflow-x: auto;
}

.table-wrapper {
    width: 100%;
    border-collapse: collapse;
}

.table-wrapper thead {
    background: #f8f9fa;
}

.table-wrapper th {
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}

.table-wrapper td {
    padding: 12px 15px;
    border-bottom: 1px solid #dee2e6;
}

.table-wrapper tbody tr:hover {
    background: #f8f9fa;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 10px;
    display: block;
}

.empty-state p {
    margin: 0;
    font-size: 16px;
}

.text-center {
    text-align: center;
}

.currency {
    text-align: right;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.paid {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.failed {
    background: #f8d7da;
    color: #721c24;
}

.btn {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}
</style>

</div>

<?php include 'includes/footer.php'; ?>

