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

$page_title = 'Gallery Management';

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        // Get image data to delete file
        $stmt = $pdo->prepare("SELECT image_url FROM gallery_images WHERE id = ?");
        $stmt->execute([$delete_id]);
        $image_to_delete = $stmt->fetch();
        
        if ($image_to_delete) {
            // Delete local file if it exists
            $image_url = $image_to_delete['image_url'];
            
            // Handle different path formats
            $file_path = null;
            if (preg_match('~^https?://~i', $image_url)) {
                // Skip deletion for external URLs
            } elseif (preg_match('~[\\\\/]uploads[\\\\/](gallery[\\\\/].+)$~i', $image_url, $m)) {
                $file_path = '../uploads/' . str_replace('\\', '/', $m[1]);
            } elseif (preg_match('~^uploads/~i', $image_url)) {
                $file_path = '../' . $image_url;
            } elseif (preg_match('~^\.\./uploads/~i', $image_url)) {
                $file_path = $image_url;
            } elseif (preg_match('~[\\\\/]pictures[\\\\/](.+)$~i', $image_url, $m)) {
                $file_path = '../pictures/' . str_replace('\\', '/', $m[1]);
            } elseif (preg_match('~^pictures/~i', $image_url)) {
                $file_path = '../' . $image_url;
            } elseif (preg_match('~^\.\./pictures/~i', $image_url)) {
                $file_path = $image_url;
            }
            
            if ($file_path && file_exists($file_path)) {
                @unlink($file_path);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM gallery_images WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            header("Location: admin_gallery.php?deleted=1");
            exit();
        }
    } catch(PDOException $e) {
        header("Location: admin_gallery.php?error=delete_failed");
        exit();
    }
}

// Handle toggle active status
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $toggle_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE gallery_images SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$toggle_id]);
        header("Location: admin_gallery.php?toggled=1");
        exit();
    } catch(PDOException $e) {
        header("Location: admin_gallery.php?error=toggle_failed");
        exit();
    }
}

// Check for success/error messages
$success_message = '';
$error_message = '';
if (isset($_GET['deleted'])) {
    $success_message = 'Image deleted successfully!';
}
if (isset($_GET['toggled'])) {
    $success_message = 'Image status updated successfully!';
}
if (isset($_GET['error'])) {
    $error_message = 'Operation failed. Please try again.';
}

// Get filter
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Get all gallery images
try {
    if ($category_filter === 'all') {
        $stmt = $pdo->query("SELECT * FROM gallery_images ORDER BY display_order ASC, created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE category = ? ORDER BY display_order ASC, created_at DESC");
        $stmt->execute([$category_filter]);
    }
    $gallery_images = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    $gallery_images = [];
}

// Get unique categories for filter
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM gallery_images ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $categories = [];
}

include 'includes/header.php';
?>

<?php if ($success_message): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h1><i class="fas fa-images"></i> Gallery Management</h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / Gallery
    </div>
</div>

<div class="content-section">
    <div class="section-header">
        <h2><i class="fas fa-images"></i> All Gallery Images</h2>
        <div class="section-actions">
            <a href="admin_gallery_manage.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Image
            </a>
        </div>
    </div>
    
    <!-- Category Filter -->
    <div class="filter-section" style="margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <label for="category" style="font-weight: 600; color: #333;">Filter by Category:</label>
            <select name="category" id="category" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $cat))); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    
    <?php if (empty($gallery_images)): ?>
    <div class="empty-state">
        <i class="fas fa-images"></i>
        <p>No gallery images found</p>
        <a href="admin_gallery_manage.php" class="btn btn-primary mt-20">Add First Image</a>
    </div>
    <?php else: ?>
    <div class="gallery-grid-admin">
        <?php foreach ($gallery_images as $image): 
            // Normalize image path so local files in /pictures or /uploads/gallery work correctly
            $raw_url = trim($image['image_url'] ?? '');

            // Already a full URL → leave as is
            if (preg_match('~^https?://~i', $raw_url)) {
                // no change - keep as is
            }
            // If it's a Windows absolute path (e.g., C:\xampp\htdocs\pictures\img.jpg),
            // strip everything up to /pictures or /uploads to convert it to a web path.
            elseif (preg_match('~[\\\\/]pictures[\\\\/](.+)$~i', $raw_url, $m)) {
                $raw_url = '../pictures/' . str_replace('\\', '/', $m[1]);
            }
            elseif (preg_match('~[\\\\/]uploads[\\\\/](gallery[\\\\/].+)$~i', $raw_url, $m)) {
                $raw_url = '../uploads/' . str_replace('\\', '/', $m[1]);
            }
            // Paths that already point to /pictures (absolute)
            elseif (preg_match('~^/pictures/~i', $raw_url)) {
                // Convert to relative path
                $raw_url = '..' . $raw_url;
            }
            // Paths that start with pictures/ (relative)
            elseif (preg_match('~^pictures/~i', $raw_url)) {
                // from admin/admin_gallery.php go two levels up then /pictures
                $raw_url = '../' . $raw_url;
            }
            // Paths that start with ../pictures/
            elseif (preg_match('~^\.\./pictures/~i', $raw_url)) {
                // Already correct relative path
                // no change
            }
            // Paths that already point to uploads/gallery relative to app root
            elseif (preg_match('~^uploads/~i', $raw_url)) {
                // make it relative
                $raw_url = '../' . $raw_url;
            }
            // Paths that start with ../uploads/
            elseif (preg_match('~^\.\./uploads/~i', $raw_url)) {
                // Already correct relative path
                // no change
            }
            // Plain filename → assume it lives in /pictures
            elseif (!empty($raw_url) && !preg_match('~[\\\\/]~', $raw_url)) {
                $raw_url = '../pictures/' . $raw_url;
            }
            // If it contains pictures but doesn't match above patterns
            elseif (!empty($raw_url) && stripos($raw_url, 'pictures') !== false) {
                // Try to extract filename and prepend ../pictures/
                if (preg_match('~([^\\\\/]+\.(jpg|jpeg|png|gif|webp|avif))$~i', $raw_url, $m)) {
                    $raw_url = '../pictures/' . $m[1];
                }
            }

            $image_url = htmlspecialchars(str_replace('\\', '/', $raw_url));
        ?>
        <div class="gallery-item-admin">
            <div class="gallery-item-image">
                <img src="<?php echo $image_url; ?>" 
                     alt="<?php echo htmlspecialchars($image['title']); ?>"
                     onerror="this.src='https://via.placeholder.com/300x200?text=Image+Not+Found'">
                <?php if (!$image['is_active']): ?>
                <div class="inactive-badge">Inactive</div>
                <?php endif; ?>
            </div>
            <div class="gallery-item-info">
                <h3><?php echo htmlspecialchars($image['title']); ?></h3>
                <p class="gallery-category">
                    <i class="fas fa-tag"></i> 
                    <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $image['category']))); ?>
                </p>
                <?php if (!empty($image['description'])): ?>
                <p class="gallery-description"><?php echo htmlspecialchars($image['description']); ?></p>
                <?php endif; ?>
                <div class="gallery-item-actions">
                    <a href="admin_gallery_manage.php?id=<?php echo $image['id']; ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="admin_gallery.php?toggle=1&id=<?php echo $image['id']; ?>" 
                       class="btn btn-<?php echo $image['is_active'] ? 'warning' : 'success'; ?> btn-sm"
                       onclick="return confirm('Are you sure you want to <?php echo $image['is_active'] ? 'deactivate' : 'activate'; ?> this image?');">
                        <i class="fas fa-<?php echo $image['is_active'] ? 'eye-slash' : 'eye'; ?>"></i> 
                        <?php echo $image['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </a>
                    <a href="admin_gallery.php?delete=1&id=<?php echo $image['id']; ?>" 
                       class="btn btn-danger btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this image? This action cannot be undone.');">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.gallery-grid-admin {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.gallery-item-admin {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.gallery-item-admin:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.gallery-item-image {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: #f5f5f5;
}

.gallery-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.inactive-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
}

.gallery-item-info {
    padding: 15px;
}

.gallery-item-info h3 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 16px;
}

.gallery-category {
    color: #7a6a4f;
    font-size: 14px;
    margin: 5px 0;
    font-weight: 500;
}

.gallery-description {
    color: #666;
    font-size: 13px;
    margin: 8px 0;
    line-height: 1.4;
}

.gallery-item-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.btn-warning {
    background: #ffc107;
    color: #333;
}

.btn-warning:hover {
    background: #e0a800;
}

.filter-section {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?php include 'includes/footer.php'; ?>

