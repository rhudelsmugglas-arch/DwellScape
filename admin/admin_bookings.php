<?php
// Start session
if (!headers_sent()) {
    session_start();
} else {
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

// Redirect if not logged in
if (!isset($_SESSION['user_id']) && !$auth_user) {
    header('Location: ../home.php');
    exit();
}

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['is_admin'])) {
    $is_admin = $_SESSION['is_admin'];
} elseif ($auth_user) {
    $is_admin = $auth_user['is_admin'] || $auth_user['role'] === 'admin';
} else {
    try {
        $stmt = $pdo->prepare("SELECT is_admin, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $is_admin = ($user['role'] === 'admin') || ($user['is_admin'] ?? false);
    } catch(PDOException $e) {
        // Error checking
    }
}

// Redirect non-admin users
if (!$is_admin) {
    header('Location: ../dashboard.php');
    exit();
}

$page_title = 'Bookings Management';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM bookings";
    $stmt = $pdo->query($count_sql);
    $total_bookings = $stmt->fetch()['total'];
    $total_pages = ceil($total_bookings / $per_page);
    
    // Get bookings with pagination
    $sql = "SELECT b.*, u.username, u.email 
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id 
            ORDER BY b.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $all_bookings = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    $all_bookings = [];
    $total_bookings = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-calendar-check"></i> Bookings Management</h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / Bookings
    </div>
</div>

<div class="content-section">
    <div class="section-header">
        <h2><i class="fas fa-calendar-check"></i> Recent Bookings</h2>
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
                    <th>Price/Night</th>
                    <th>Total Price</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_bookings)): ?>
                <tr>
                    <td colspan="9" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No bookings found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($all_bookings as $booking): ?>
                    <tr>
                        <td><strong><?php echo $booking['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($booking['username'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?></td>
                        <td class="text-center"><?php echo $booking['nights']; ?></td>
                        <td class="currency">₱<?php echo number_format($booking['price_per_night'], 2); ?></td>
                        <td class="currency"><strong>₱<?php echo number_format($booking['total_price'], 2); ?></strong></td>
                        <td><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>">
            Next <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px; color: #666; font-size: 14px;">
        Showing <?php echo count($all_bookings); ?> of <?php echo number_format($total_bookings); ?> bookings
    </div>
</div>

<?php include 'includes/footer.php'; ?>

