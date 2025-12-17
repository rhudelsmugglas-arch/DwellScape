<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../home.php');
    exit();
}

$page_title = 'Users Management';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE username LIKE ? OR email LIKE ?";
    $search_params = ["%$search%", "%$search%"];
}

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM users $search_condition";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($search_params);
    $total_users = $stmt->fetch()['total'];
    $total_pages = ceil($total_users / $per_page);
    
    // Get users with pagination
    $sql = "SELECT id, username, email, created_at, is_admin, role 
            FROM users 
            $search_condition
            ORDER BY created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($search_params);
    $all_users = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    $all_users = [];
    $total_users = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> Users Management</h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / Users
    </div>
</div>

<div class="content-section">
    <div class="section-header">
        <h2><i class="fas fa-users"></i> All Users</h2>
        <div class="section-actions">
            <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; min-width: 200px; max-width: 300px; flex: 1;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($search)): ?>
                <a href="admin_users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="table-container">
        <table class="table-wrapper">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_users)): ?>
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No users found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($all_users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php 
                            $user_role = !empty($user['role']) ? $user['role'] : (($user['is_admin'] ?? false) ? 'admin' : 'user');
                            if ($user_role === 'admin'): ?>
                                <span class="badge badge-admin">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-info">User</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <a href="#" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
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
        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
            Next <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px; color: #666; font-size: 14px;">
        Showing <?php echo count($all_users); ?> of <?php echo number_format($total_users); ?> users
    </div>
</div>

<?php include 'includes/footer.php'; ?>

