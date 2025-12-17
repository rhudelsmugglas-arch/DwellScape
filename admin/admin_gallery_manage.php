<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../home.php');
    exit();
}

$page_title = 'Manage Gallery Image';

$is_edit = false;
$image_id = null;
$image = null;
$success_message = '';
$error_message = '';

// Check if editing existing image
if (isset($_GET['id'])) {
    $image_id = (int)$_GET['id'];
    $is_edit = true;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch();
        
        if (!$image) {
            header('Location: admin_gallery.php');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: admin_gallery.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle file upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $upload_dir = '../uploads/gallery/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $new_filename = 'gallery_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                // Delete old file if editing and it's a local file
                if ($is_edit && !empty($image['image_url']) && strpos($image['image_url'], 'uploads/') === 0) {
                    $old_file = '../' . $image['image_url'];
                    if (file_exists($old_file)) {
                        @unlink($old_file);
                    }
                }
                $image_url = 'uploads/gallery/' . $new_filename;
            }
        } else {
            $error_message = 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP, AVIF';
        }
    } elseif ($is_edit && empty($image_url)) {
        // Keep existing image URL if not uploading new file
        $image_url = $image['image_url'];
    }
    
    // Validation
    if (empty($title)) {
        $error_message = 'Title is required.';
    } elseif (empty($category)) {
        $error_message = 'Category is required.';
    } elseif (empty($image_url)) {
        $error_message = 'Image URL or file is required.';
    } else {
        try {
            if ($is_edit) {
                // Update existing image
                $stmt = $pdo->prepare("
                    UPDATE gallery_images 
                    SET image_url = ?, title = ?, category = ?, description = ?, 
                        display_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $image_url, $title, $category, $description,
                    $display_order, $is_active, $image_id
                ]);
                $success_message = 'Image updated successfully!';
                
                // Reload image data
                $stmt = $pdo->prepare("SELECT * FROM gallery_images WHERE id = ?");
                $stmt->execute([$image_id]);
                $image = $stmt->fetch();
            } else {
                // Insert new image
                $stmt = $pdo->prepare("
                    INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $image_url, $title, $category, $description, $display_order, $is_active
                ]);
                $success_message = 'Image added successfully!';
                
                // Redirect to gallery list
                header("Location: admin_gallery.php?success=1");
                exit();
            }
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Available categories
$available_categories = [
    'living-room' => 'Living Room',
    'kitchen' => 'Full Kitchen',
    'dining' => 'Dining Area',
    'bedroom1' => 'Bedroom 1',
    'bedroom2' => 'Bedroom 2',
    'bathroom' => 'Full Bathroom',
    'workplace' => 'Workplace',
    'pool' => 'Pool',
    'activity' => 'Activity Area'
];

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
    <h1><i class="fas fa-images"></i> <?php echo $is_edit ? 'Edit Gallery Image' : 'Add New Gallery Image'; ?></h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / <a href="admin_gallery.php">Gallery</a> / <?php echo $is_edit ? 'Edit' : 'Add'; ?>
    </div>
</div>

<div class="content-section">
    <form method="POST" enctype="multipart/form-data" class="room-form">
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="title">Image Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" 
                       value="<?php echo htmlspecialchars($image['title'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category <span class="required">*</span></label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($available_categories as $key => $label): ?>
                    <option value="<?php echo $key; ?>" 
                            <?php echo (isset($image['category']) && $image['category'] === $key) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" id="display_order" name="display_order" 
                       value="<?php echo $image['display_order'] ?? 0; ?>" min="0">
                <small>Lower numbers appear first (0 = default)</small>
            </div>
            
            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($image['description'] ?? ''); ?></textarea>
            </div>
            
            <?php if ($is_edit && !empty($image['image_url'])): ?>
            <div class="form-group full-width">
                <label>Current Image</label>
                <div style="max-width: 400px; margin-top: 10px;">
                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($image['title']); ?>"
                         style="width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                         onerror="this.src='https://via.placeholder.com/400x300?text=Image+Not+Found'">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-group full-width">
                <label for="image_url">Image URL</label>
                <input type="url" id="image_url" name="image_url" 
                       value="<?php echo htmlspecialchars($image['image_url'] ?? ''); ?>"
                       placeholder="https://example.com/image.jpg">
                <small>Enter image URL or upload a file below</small>
            </div>
            
            <div class="form-group full-width">
                <label for="image_file">Or Upload Image File</label>
                <input type="file" id="image_file" name="image_file" accept="image/*">
                <small>Accepted formats: JPG, PNG, GIF, WEBP, AVIF</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" 
                           <?php echo (!isset($image['is_active']) || $image['is_active']) ? 'checked' : ''; ?>>
                    Image is Active
                </label>
                <small>Inactive images won't be displayed on the gallery page</small>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $is_edit ? 'Update Image' : 'Add Image'; ?>
            </button>
            <a href="admin_gallery.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

