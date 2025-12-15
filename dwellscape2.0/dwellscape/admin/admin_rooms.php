<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Rooms Management';

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        // Get room data to delete images
        $stmt = $pdo->prepare("SELECT images FROM rooms WHERE id = ?");
        $stmt->execute([$delete_id]);
        $room_to_delete = $stmt->fetch();
        
        if ($room_to_delete) {
            // Delete associated images
            if (!empty($room_to_delete['images'])) {
                $images = explode(',', $room_to_delete['images']);
                foreach ($images as $img) {
                    $img = trim($img);
                    // Only delete local uploads, not URLs
                    if (strpos($img, 'uploads/') === 0) {
                        $file_path = '../' . $img;
                        if (file_exists($file_path)) {
                            @unlink($file_path);
                        }
                    }
                }
            }
            
            // Delete the room
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            header("Location: admin_rooms.php?deleted=1");
            exit();
        }
    } catch(PDOException $e) {
        header("Location: admin_rooms.php?error=delete_failed");
        exit();
    }
}

// Check for success/error messages
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) {
    $success_message = 'Room added successfully!';
}
if (isset($_GET['deleted'])) {
    $success_message = 'Room deleted successfully!';
}
if (isset($_GET['error'])) {
    $error_message = 'Failed to delete room. Please try again.';
}

// Get all rooms
try {
    $stmt = $pdo->query("SELECT * FROM rooms ORDER BY created_at DESC");
    $all_rooms = $stmt->fetchAll();
    
    // If no rooms exist, auto-seed default rooms
    if (empty($all_rooms)) {
        try {
            require_once '../seed_rooms.php';
            // Re-fetch rooms after seeding
            $stmt = $pdo->query("SELECT * FROM rooms ORDER BY created_at DESC");
            $all_rooms = $stmt->fetchAll();
            if (!empty($all_rooms)) {
                $success_message = 'Default rooms have been added successfully!';
            }
        } catch(PDOException $seed_error) {
            // If seeding fails, just continue with empty rooms
            $error_message = "Could not load default rooms: " . $seed_error->getMessage();
        }
    }
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    $all_rooms = [];
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
    <h1><i class="fas fa-bed"></i> Rooms Management</h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / Rooms
    </div>
</div>

<div class="content-section">
    <div class="section-header">
        <h2><i class="fas fa-bed"></i> All Rooms</h2>
        <div class="section-actions">
            <a href="admin_room_manage.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Room
            </a>
        </div>
    </div>
    
    <div class="rooms-grid">
        <?php if (empty($all_rooms)): ?>
        <div class="empty-state" style="grid-column: 1 / -1;">
            <i class="fas fa-inbox"></i>
            <p>No rooms found</p>
            <a href="admin_room_manage.php" class="btn btn-primary mt-20">Add First Room</a>
        </div>
        <?php else: ?>
            <?php foreach ($all_rooms as $room): 
                $images = !empty($room['images']) ? explode(',', $room['images']) : [];
                $main_image = 'https://via.placeholder.com/400x300?text=No+Image';
                if (!empty($images)) {
                    $img_path = trim($images[0]);
                    // Check if it's a URL (starts with http:// or https://)
                    if (preg_match('/^https?:\/\//', $img_path)) {
                        $main_image = $img_path;
                    } else {
                        // Local file - adjust path for admin folder
                        $main_image = '../' . $img_path;
                    }
                }
                $amenities = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
            ?>
            <div class="room-card">
                <div class="room-image">
                    <img src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                    <?php if ($room['is_available']): ?>
                        <span class="room-status available">Available</span>
                    <?php else: ?>
                        <span class="room-status unavailable">Unavailable</span>
                    <?php endif; ?>
                </div>
                <div class="room-info">
                    <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                    <p class="room-description"><?php echo htmlspecialchars(substr($room['description'] ?? '', 0, 100)) . (strlen($room['description'] ?? '') > 100 ? '...' : ''); ?></p>
                    <div class="room-details">
                        <span><i class="fas fa-bed"></i> <?php echo $room['bedrooms'] ?? 1; ?> <?php echo ($room['bedrooms'] ?? 1) == 1 ? 'Bedroom' : 'Bedrooms'; ?></span>
                        <span><i class="fas fa-users"></i> Up to <?php echo $room['max_guests']; ?> Guests</span>
                        <span><i class="fas fa-bath"></i> <?php echo $room['bathrooms'] ?? 1; ?> <?php echo ($room['bathrooms'] ?? 1) == 1 ? 'Bathroom' : 'Bathrooms'; ?></span>
                        <?php if (!empty($room['area_sqm'])): ?>
                        <span><i class="fas fa-ruler-combined"></i> <?php echo number_format($room['area_sqm'], 0); ?> sqm</span>
                        <?php endif; ?>
                        <span><i class="fas fa-money-bill-wave"></i> ₱<?php echo number_format($room['price_per_night'], 2); ?>/night</span>
                    </div>
                    <?php if (!empty($amenities)): ?>
                    <div class="room-amenities">
                        <?php foreach (array_slice($amenities, 0, 3) as $amenity): ?>
                            <span class="amenity-tag"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                        <?php endforeach; ?>
                        <?php if (count($amenities) > 3): ?>
                            <span class="amenity-tag">+<?php echo count($amenities) - 3; ?> more</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="room-actions">
                        <a href="admin_room_details.php?id=<?php echo $room['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="admin_room_manage.php?id=<?php echo $room['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="admin_rooms.php?delete=1&id=<?php echo $room['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this room? This action cannot be undone.');">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
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

.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.room-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.room-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.room-image {
    position: relative;
    width: 100%;
    height: 250px;
    overflow: hidden;
}

.room-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.room-card:hover .room-image img {
    transform: scale(1.05);
}

.room-status {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.room-status.available {
    background: #d4edda;
    color: #155724;
}

.room-status.unavailable {
    background: #f8d7da;
    color: #721c24;
}

.room-info {
    padding: 20px;
}

.room-info h3 {
    color: #7a6a4f;
    font-size: 20px;
    margin-bottom: 10px;
}

.room-description {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.room-details {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #333;
}

.room-details span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.room-details i {
    color: #7a6a4f;
}

.room-amenities {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.amenity-tag {
    background: #f0f0f0;
    color: #666;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
}

.room-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.room-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.room-actions .btn {
    flex: 1;
    text-align: center;
    padding: 10px;
    font-size: 14px;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<?php include 'includes/footer.php'; ?>

