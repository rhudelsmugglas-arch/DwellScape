<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../home.php');
    exit();
}

$page_title = 'Manage Room';
$is_edit = false;
$room = null;
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get room data if editing
if ($room_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch();
        if ($room) {
            $is_edit = true;
            $page_title = 'Edit Room';
        }
    } catch(PDOException $e) {
        $error_message = "Error loading room: " . $e->getMessage();
    }
} else {
    $page_title = 'Add New Room';
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price_per_night = isset($_POST['price_per_night']) ? floatval($_POST['price_per_night']) : 0;
    $max_guests = isset($_POST['max_guests']) ? (int)$_POST['max_guests'] : 2;
    $bedrooms = isset($_POST['bedrooms']) ? (int)$_POST['bedrooms'] : 1;
    $bathrooms = isset($_POST['bathrooms']) ? (int)$_POST['bathrooms'] : 1;
    $area_sqm = isset($_POST['area_sqm']) && !empty($_POST['area_sqm']) ? floatval($_POST['area_sqm']) : null;
    $amenities = isset($_POST['amenities']) ? trim($_POST['amenities']) : '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Handle image removal
    $images = [];
    if (!empty($room['images'])) {
        $images = explode(',', $room['images']);
        $images = array_map('trim', $images);
        $images = array_filter($images);
    }
    
    // Remove images if specified
    if (isset($_POST['remove_images']) && is_array($_POST['remove_images'])) {
        foreach ($_POST['remove_images'] as $img_to_remove) {
            $img_to_remove = trim($img_to_remove);
            // Remove from array
            $images = array_filter($images, function($img) use ($img_to_remove) {
                return trim($img) !== $img_to_remove;
            });
            // Delete file if it's a local upload
            if (strpos($img_to_remove, 'uploads/') === 0) {
                $file_path = '../' . $img_to_remove;
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
        }
    }
    
    // Handle new image URLs
    if (isset($_POST['image_urls']) && !empty($_POST['image_urls'])) {
        $new_urls = explode("\n", $_POST['image_urls']);
        $new_urls = array_map('trim', $new_urls);
        $new_urls = array_filter($new_urls);
        $images = array_merge($images, $new_urls);
    }
    
    // Handle file upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $upload_dir = '../uploads/rooms/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $new_filename = 'room_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                $images[] = 'uploads/rooms/' . $new_filename;
            }
        }
    }
    
    $images_string = implode(',', $images);
    
    // Validation
    if (empty($name)) {
        $error_message = 'Room name is required.';
    } elseif ($price_per_night <= 0) {
        $error_message = 'Price per night must be greater than 0.';
    } elseif ($max_guests <= 0) {
        $error_message = 'Max guests must be greater than 0.';
    } else {
        try {
            if ($is_edit) {
                // Update existing room
                $stmt = $pdo->prepare("
                    UPDATE rooms 
                    SET name = ?, description = ?, price_per_night = ?, max_guests = ?, 
                        bedrooms = ?, bathrooms = ?, area_sqm = ?,
                        amenities = ?, images = ?, is_available = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $description, $price_per_night, $max_guests,
                    $bedrooms, $bathrooms, $area_sqm,
                    $amenities, $images_string, $is_available, $room_id
                ]);
                $success_message = 'Room updated successfully!';
                
                // Reload room data after update
                $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
                $stmt->execute([$room_id]);
                $room = $stmt->fetch();
            } else {
                // Insert new room with temporary room_id, then update it to match the auto-incremented id
                $temp_room_id = 'temp_' . time() . '_' . rand(1000, 9999);
                
                $stmt = $pdo->prepare("
                    INSERT INTO rooms (room_id, name, description, price_per_night, max_guests, bedrooms, bathrooms, area_sqm, amenities, images, is_available)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $temp_room_id, $name, $description, $price_per_night, 
                    $max_guests, $bedrooms, $bathrooms, $area_sqm,
                    $amenities, $images_string, $is_available
                ]);
                
                // Get the auto-incremented id
                $new_room_id = $pdo->lastInsertId();
                
                // Update room_id to match the id (auto-increment value)
                $stmt = $pdo->prepare("UPDATE rooms SET room_id = ? WHERE id = ?");
                $stmt->execute([(string)$new_room_id, $new_room_id]);
                
                // Redirect to rooms list page
                header("Location: admin_rooms.php?success=1");
                exit();
            }
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Check for success parameter (for edit mode)
if (isset($_GET['success']) && $is_edit) {
    $success_message = 'Room saved successfully!';
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-<?php echo $is_edit ? 'edit' : 'plus'; ?>"></i> <?php echo $is_edit ? 'Edit Room' : 'Add New Room'; ?></h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / <a href="admin_rooms.php">Rooms</a> / <?php echo $is_edit ? 'Edit' : 'Add'; ?>
    </div>
</div>

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

<div class="content-section">
    <form method="POST" enctype="multipart/form-data" class="room-form">
        <div class="form-grid">
            <?php if ($is_edit): ?>
            <div class="form-group">
                <label for="room_id">Room ID</label>
                <input type="text" id="room_id" name="room_id" 
                       value="<?php echo htmlspecialchars($room['room_id'] ?? ''); ?>" 
                       readonly style="background: #f5f5f5;">
                <small>Auto-generated identifier</small>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Room Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" 
                       value="<?php echo htmlspecialchars($room['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="price_per_night">Price per Night (₱) <span class="required">*</span></label>
                <input type="number" id="price_per_night" name="price_per_night" 
                       value="<?php echo $room['price_per_night'] ?? ''; ?>" 
                       step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="max_guests">Max Guests <span class="required">*</span></label>
                <input type="number" id="max_guests" name="max_guests" 
                       value="<?php echo $room['max_guests'] ?? 2; ?>" 
                       min="1" required>
            </div>
            
            <div class="form-group">
                <label for="bedrooms">Bedrooms <span class="required">*</span></label>
                <input type="number" id="bedrooms" name="bedrooms" 
                       value="<?php echo $room['bedrooms'] ?? 1; ?>" 
                       min="1" required>
            </div>
            
            <div class="form-group">
                <label for="bathrooms">Bathrooms <span class="required">*</span></label>
                <input type="number" id="bathrooms" name="bathrooms" 
                       value="<?php echo $room['bathrooms'] ?? 1; ?>" 
                       min="1" required>
            </div>
            
            <div class="form-group">
                <label for="area_sqm">Area (sqm)</label>
                <input type="number" id="area_sqm" name="area_sqm" 
                       value="<?php echo $room['area_sqm'] ?? ''; ?>" 
                       step="0.01" min="0" placeholder="e.g., 45">
                <small>Room area in square meters</small>
            </div>
            
            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($room['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group full-width">
                <label for="amenities">Guest Access</label>
                <input type="text" id="amenities" name="amenities" 
                       value="<?php echo htmlspecialchars($room['amenities'] ?? ''); ?>"
                       placeholder="e.g., Wi-Fi, Kitchen, TV, AC">
                <small>Separate multiple guest access items with commas</small>
            </div>
            
            <div class="form-group full-width">
                <label>Current Images</label>
                <div class="current-images" id="current-images-container">
                    <?php 
                    if (!empty($room['images'])) {
                        $current_images = explode(',', $room['images']);
                        foreach ($current_images as $img): 
                            $img = trim($img);
                            if (!empty($img)):
                                // Check if it's a URL or local file
                                if (preg_match('/^https?:\/\//', $img)) {
                                    $img_src = $img;
                                } else {
                                    $img_src = '../' . $img;
                                }
                    ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Room image" onerror="this.style.display='none'">
                        <button type="button" class="remove-image" data-image="<?php echo htmlspecialchars($img); ?>">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php 
                            endif;
                        endforeach;
                    } else {
                        echo '<p style="color: #666;">No images uploaded</p>';
                    }
                    ?>
                </div>
                <div id="removed-images-inputs"></div>
            </div>
            
            <div class="form-group full-width">
                <label for="image_urls">Add Image URLs (one per line)</label>
                <textarea id="image_urls" name="image_urls" rows="3" 
                          placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg"></textarea>
            </div>
            
            <div class="form-group full-width">
                <label for="image_file">Or Upload Image File</label>
                <input type="file" id="image_file" name="image_file" accept="image/*">
                <small>Accepted formats: JPG, PNG, GIF, WEBP</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_available" value="1" 
                           <?php echo ($room['is_available'] ?? true) ? 'checked' : ''; ?>>
                    Room is Available
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $is_edit ? 'Update Room' : 'Add Room'; ?>
            </button>
            <a href="admin_rooms.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <?php if ($is_edit): ?>
            <a href="admin_room_details.php?id=<?php echo $room_id; ?>" class="btn btn-secondary">
                <i class="fas fa-eye"></i> View Details
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<style>
.alert {
    padding: 15px 20px;
    border-radius: 5px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.room-form {
    max-width: 100%;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-group .required {
    color: #dc3545;
}

.form-group input,
.form-group textarea,
.form-group select {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #7a6a4f;
    box-shadow: 0 0 0 3px rgba(122, 106, 79, 0.1);
}

.form-group small {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
}

.current-images {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.image-preview {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #ddd;
}

.image-preview img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    display: block;
}

.remove-image {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
}

.remove-image:hover {
    background: #c82333;
}

.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Handle image removal
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('current-images-container');
    const removedContainer = document.getElementById('removed-images-inputs');
    
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-image')) {
            const btn = e.target.closest('.remove-image');
            const imagePath = btn.getAttribute('data-image');
            
            if (confirm('Remove this image?')) {
                // Add hidden input to mark image for removal
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remove_images[]';
                input.value = imagePath;
                removedContainer.appendChild(input);
                
                // Remove the image preview
                btn.closest('.image-preview').remove();
                
                // Show message if no images left
                if (container.querySelectorAll('.image-preview').length === 0) {
                    container.innerHTML = '<p style="color: #666;">No images uploaded</p>';
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>

