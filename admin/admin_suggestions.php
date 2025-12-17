<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../home.php');
    exit();
}

$page_title = 'Suggestions Management';

// Create suggestions table if it doesn't exist
try {
    $createSuggestionsTable = "
    CREATE TABLE IF NOT EXISTS suggestions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    $pdo->exec($createSuggestionsTable);
} catch(PDOException $e) {
    // Table might already exist, ignore error
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE name LIKE ? OR email LIKE ? OR message LIKE ?";
    $search_params = ["%$search%", "%$search%", "%$search%"];
}

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM suggestions $search_condition";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($search_params);
    $total_suggestions = $stmt->fetch()['total'];
    $total_pages = ceil($total_suggestions / $per_page);
    
    // Get suggestions with pagination
    $sql = "SELECT id, name, email, message, status, created_at 
            FROM suggestions 
            $search_condition
            ORDER BY created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($search_params);
    $all_suggestions = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    $all_suggestions = [];
    $total_suggestions = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-envelope"></i> Suggestions Management</h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / Suggestions
    </div>
</div>

<div class="content-section">
    <div class="section-header">
        <h2><i class="fas fa-envelope"></i> All Suggestions</h2>
        <div class="section-actions">
            <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" placeholder="Search suggestions..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; min-width: 200px; max-width: 300px; flex: 1;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($search)): ?>
                <a href="admin_suggestions.php" class="btn btn-secondary">
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
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_suggestions)): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No suggestions found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($all_suggestions as $suggestion): ?>
                    <tr>
                        <td><?php echo $suggestion['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($suggestion['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($suggestion['email']); ?></td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($suggestion['message']); ?>">
                            <?php echo htmlspecialchars(substr($suggestion['message'], 0, 100)) . (strlen($suggestion['message']) > 100 ? '...' : ''); ?>
                        </td>
                        <td>
                            <?php 
                            if ($suggestion['status'] === 'read'): ?>
                                <span class="badge badge-success">Read</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Unread</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($suggestion['created_at'])); ?></td>
                        <td>
                            <a href="#" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" onclick="viewSuggestion(<?php echo $suggestion['id']; ?>, '<?php echo htmlspecialchars($suggestion['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($suggestion['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($suggestion['message'], ENT_QUOTES); ?>', '<?php echo $suggestion['status']; ?>'); return false;">
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
        Showing <?php echo count($all_suggestions); ?> of <?php echo number_format($total_suggestions); ?> suggestions
    </div>
</div>

<!-- Modal for viewing suggestion -->
<div id="suggestionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 10px; padding: 30px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; position: relative;">
        <button onclick="closeSuggestionModal()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
        <h2 style="margin-bottom: 20px; color: #2c3e50;"><i class="fas fa-envelope"></i> Suggestion Details</h2>
        <div id="modalContent">
            <div style="margin-bottom: 15px;">
                <strong style="color: #666;">Name:</strong>
                <p id="modalName" style="margin-top: 5px; color: #2c3e50;"></p>
            </div>
            <div style="margin-bottom: 15px;">
                <strong style="color: #666;">Email:</strong>
                <p id="modalEmail" style="margin-top: 5px; color: #2c3e50;"></p>
            </div>
            <div style="margin-bottom: 15px;">
                <strong style="color: #666;">Message:</strong>
                <p id="modalMessage" style="margin-top: 5px; color: #2c3e50; white-space: pre-wrap; line-height: 1.6;"></p>
            </div>
            <div style="margin-bottom: 15px;">
                <strong style="color: #666;">Status:</strong>
                <p id="modalStatus" style="margin-top: 5px;"></p>
            </div>
        </div>
    </div>
</div>

<script>
function viewSuggestion(id, name, email, message, status) {
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalEmail').textContent = email;
    document.getElementById('modalMessage').textContent = message;
    
    const statusElement = document.getElementById('modalStatus');
    if (status === 'read') {
        statusElement.innerHTML = '<span class="badge badge-success">Read</span>';
    } else {
        statusElement.innerHTML = '<span class="badge badge-warning">Unread</span>';
        // Mark as read
        fetch('mark_suggestion_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        }).catch(err => console.error('Error:', err));
    }
    
    document.getElementById('suggestionModal').style.display = 'flex';
}

function closeSuggestionModal() {
    document.getElementById('suggestionModal').style.display = 'none';
    // Reload page to update status
    window.location.reload();
}

// Close modal when clicking outside
document.getElementById('suggestionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuggestionModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>

