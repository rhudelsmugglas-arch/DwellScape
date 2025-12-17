<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../home.php');
    exit();
}

$page_title = 'Transactions Management';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM transactions";
    $stmt = $pdo->query($count_sql);
    $total_transactions = $stmt->fetch()['total'];
    $total_pages = ceil($total_transactions / $per_page);
    
    // Get transactions with pagination
    $sql = "SELECT t.*, b.booking_id, b.room_name, u.username, u.email 
            FROM transactions t 
            LEFT JOIN bookings b ON t.booking_id = b.id 
            LEFT JOIN users u ON t.user_id = u.id 
            ORDER BY t.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $all_transactions = $stmt->fetchAll();
    
    // Get summary statistics
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions");
    $total_revenue = $stmt->fetch()['total'] ?? 0;
    
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    $all_transactions = [];
    $total_transactions = 0;
    $total_pages = 0;
    $total_revenue = 0;
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-credit-card"></i> Transactions Management</h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / Transactions
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="margin-bottom: 30px;">
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

<div class="content-section">
    <div class="section-header">
        <h2><i class="fas fa-credit-card"></i> Recent Transactions</h2>
    </div>
    
    <div class="table-container">
        <table class="table-wrapper">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Booking ID</th>
                    <th>User</th>
                    <th>Room</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Payment Method</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_transactions)): ?>
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No transactions found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($all_transactions as $transaction): ?>
                    <tr>
                        <td><strong><?php echo $transaction['id']; ?></strong></td>
                        <td><?php echo $transaction['booking_id'] ?? 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($transaction['username'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transaction['room_name'] ?? 'N/A'); ?></td>
                        <td class="currency"><strong>₱<?php echo number_format($transaction['amount'], 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($transaction['currency']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['payment_method'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
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
        Showing <?php echo count($all_transactions); ?> of <?php echo number_format($total_transactions); ?> transactions
    </div>
</div>

<?php include 'includes/footer.php'; ?>

