<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Room Details';

// Get room ID
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$room_id) {
    header('Location: admin_rooms.php');
    exit();
}

// Filter options
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';

// Get room details
try {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    
    if (!$room) {
        header('Location: admin_rooms.php');
        exit();
    }
    
    // Get bookings for this room
    $booking_condition = "WHERE b.room_id = ?";
    $booking_params = [$room['room_id']];
    
    // Apply date filter
    if ($date_filter === 'upcoming') {
        $booking_condition .= " AND b.checkin_date >= CURDATE()";
    } elseif ($date_filter === 'past') {
        $booking_condition .= " AND b.checkout_date < CURDATE()";
    } elseif ($date_filter === 'current') {
        $booking_condition .= " AND b.checkin_date <= CURDATE() AND b.checkout_date >= CURDATE()";
    }
    
    // Get bookings
    $sql = "SELECT b.*, u.username, u.email 
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id 
            $booking_condition
            ORDER BY b.checkin_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($booking_params);
    $bookings = $stmt->fetchAll();
    
    // Calculate availability stats
    $total_bookings = count($bookings);
    $upcoming_bookings = 0;
    $current_bookings = 0;
    $past_bookings = 0;
    
    $today = date('Y-m-d');
    foreach ($bookings as $booking) {
        if ($booking['checkin_date'] > $today) {
            $upcoming_bookings++;
        } elseif ($booking['checkin_date'] <= $today && $booking['checkout_date'] >= $today) {
            $current_bookings++;
        } else {
            $past_bookings++;
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    $room = null;
    $bookings = [];
}

include 'includes/header.php';
?>

<?php if ($room): ?>
<div class="page-header">
    <h1><i class="fas fa-bed"></i> <?php echo htmlspecialchars($room['name']); ?></h1>
    <div class="breadcrumb">
        <a href="admin.php">Home</a> / <a href="admin_rooms.php">Rooms</a> / Details
    </div>
</div>

<!-- Room Info Card -->
<div class="content-section">
    <div class="room-detail-header">
        <div class="room-detail-image">
            <?php 
            $images = !empty($room['images']) ? explode(',', $room['images']) : [];
            $images = array_map('trim', $images);
            $images = array_filter($images);
            
            if (!empty($images)) {
                // Main image (first one)
                $main_image = $images[0];
                // Check if it's a URL (starts with http:// or https://)
                if (!preg_match('/^https?:\/\//', $main_image)) {
                    // Local file - adjust path for admin folder
                    $main_image = '../' . $main_image;
                }
            } else {
                $main_image = 'https://via.placeholder.com/600x400?text=No+Image';
            }
            ?>
            <img src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" id="main-room-image" onerror="this.src='https://via.placeholder.com/600x400?text=No+Image'">
            
            <?php if (count($images) > 1): ?>
            <div class="room-image-gallery">
                <h4>All Images (<?php echo count($images); ?>)</h4>
                <div class="gallery-thumbnails">
                    <?php foreach ($images as $index => $img): 
                        $img = trim($img);
                        if (empty($img)) continue;
                        
                        // Check if it's a URL or local file
                        if (preg_match('/^https?:\/\//', $img)) {
                            $img_src = $img;
                        } else {
                            $img_src = '../' . $img;
                        }
                    ?>
                    <div class="gallery-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                         data-image="<?php echo htmlspecialchars($img_src); ?>">
                        <img src="<?php echo htmlspecialchars($img_src); ?>" 
                             alt="Room image <?php echo $index + 1; ?>"
                             onerror="this.style.display='none'">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="room-detail-info">
            <h2><?php echo htmlspecialchars($room['name']); ?></h2>
            <p class="room-description-full"><?php echo nl2br(htmlspecialchars($room['description'] ?? '')); ?></p>
            
            <div class="room-specs">
                <div class="spec-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <div>
                        <strong>Price per Night</strong>
                        <span>₱<?php echo number_format($room['price_per_night'], 2); ?></span>
                    </div>
                </div>
                <div class="spec-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <strong>Max Guests</strong>
                        <span><?php echo $room['max_guests']; ?> guests</span>
                    </div>
                </div>
                <div class="spec-item">
                    <i class="fas fa-bed"></i>
                    <div>
                        <strong>Bedrooms</strong>
                        <span><?php echo $room['bedrooms'] ?? 1; ?> <?php echo ($room['bedrooms'] ?? 1) == 1 ? 'Bedroom' : 'Bedrooms'; ?></span>
                    </div>
                </div>
                <div class="spec-item">
                    <i class="fas fa-bath"></i>
                    <div>
                        <strong>Bathrooms</strong>
                        <span><?php echo $room['bathrooms'] ?? 1; ?> <?php echo ($room['bathrooms'] ?? 1) == 1 ? 'Bathroom' : 'Bathrooms'; ?></span>
                    </div>
                </div>
                <?php if (!empty($room['area_sqm'])): ?>
                <div class="spec-item">
                    <i class="fas fa-ruler-combined"></i>
                    <div>
                        <strong>Area</strong>
                        <span><?php echo number_format($room['area_sqm'], 0); ?> sqm</span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="spec-item">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Status</strong>
                        <span class="<?php echo $room['is_available'] ? 'status-available' : 'status-unavailable'; ?>">
                            <?php echo $room['is_available'] ? 'Available' : 'Unavailable'; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($room['amenities'])): 
                $amenities = explode(',', $room['amenities']);
            ?>
            <div class="room-amenities-list">
                <h4>Amenities</h4>
                <div class="amenities-grid">
                    <?php foreach ($amenities as $amenity): ?>
                        <span class="amenity-item"><i class="fas fa-check"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="room-actions-detail">
                <a href="admin_room_manage.php?id=<?php echo $room['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Room
                </a>
                <a href="admin_rooms.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Rooms
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Availability Stats -->
<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="icon"><i class="fas fa-calendar-check"></i></div>
        <h3>Total Bookings</h3>
        <div class="value"><?php echo $total_bookings; ?></div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-clock"></i></div>
        <h3>Upcoming</h3>
        <div class="value"><?php echo $upcoming_bookings; ?></div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-calendar-day"></i></div>
        <h3>Current</h3>
        <div class="value"><?php echo $current_bookings; ?></div>
    </div>
    <div class="stat-card">
        <div class="icon"><i class="fas fa-history"></i></div>
        <h3>Past</h3>
        <div class="value"><?php echo $past_bookings; ?></div>
    </div>
</div>

<!-- Reservations -->
<div class="content-section">
    <div class="section-header">
        <h2><i class="fas fa-calendar-alt"></i> Reservations</h2>
        <div class="section-actions">
            <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="id" value="<?php echo $room_id; ?>">
                <select name="date_filter" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; min-width: 150px;">
                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Dates</option>
                    <option value="upcoming" <?php echo $date_filter === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="current" <?php echo $date_filter === 'current' ? 'selected' : ''; ?>>Current</option>
                    <option value="past" <?php echo $date_filter === 'past' ? 'selected' : ''; ?>>Past</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>
    </div>
    
    <div class="table-container">
        <table class="table-wrapper">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Nights</th>
                    <th>Total Price</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No reservations found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo $booking['id']; ?></td>
                        <td><?php echo htmlspecialchars($booking['username'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?></td>
                        <td class="text-center"><?php echo $booking['nights']; ?></td>
                        <td class="currency">₱<?php echo number_format($booking['total_price'], 2); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.room-detail-header {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.room-detail-image {
    width: 100%;
    border-radius: 10px;
    overflow: hidden;
}

.room-detail-image img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 10px;
    margin-bottom: 20px;
}

.room-image-gallery {
    margin-top: 20px;
}

.room-image-gallery h4 {
    color: #7a6a4f;
    font-size: 18px;
    margin-bottom: 15px;
}

.gallery-thumbnails {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
}

.gallery-thumbnail {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    border: 3px solid transparent;
    transition: all 0.3s;
}

.gallery-thumbnail:hover {
    border-color: #7a6a4f;
    transform: scale(1.05);
}

.gallery-thumbnail.active {
    border-color: #7a6a4f;
    box-shadow: 0 0 0 2px rgba(122, 106, 79, 0.3);
}

.gallery-thumbnail img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.room-detail-info h2 {
    color: #7a6a4f;
    font-size: 28px;
    margin-bottom: 15px;
}

.room-description-full {
    color: #666;
    line-height: 1.8;
    margin-bottom: 25px;
}

.room-specs {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.spec-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.spec-item i {
    font-size: 24px;
    color: #7a6a4f;
    width: 30px;
}

.spec-item div {
    flex: 1;
}

.spec-item strong {
    display: block;
    color: #333;
    font-size: 14px;
    margin-bottom: 5px;
}

.spec-item span {
    color: #666;
    font-size: 16px;
}

.status-available {
    color: #28a745;
    font-weight: 600;
}

.status-unavailable {
    color: #dc3545;
    font-weight: 600;
}

.room-amenities-list {
    margin-bottom: 25px;
}

.room-amenities-list h4 {
    color: #7a6a4f;
    margin-bottom: 15px;
}

.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.amenity-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 14px;
}

.amenity-item i {
    color: #28a745;
}

.room-actions-detail {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

@media (max-width: 768px) {
    .room-detail-header {
        grid-template-columns: 1fr;
    }
}
</style>

<?php else: ?>
<div class="content-section">
    <div class="empty-state">
        <i class="fas fa-exclamation-triangle"></i>
        <p>Room not found</p>
        <a href="admin_rooms.php" class="btn btn-primary mt-20">Back to Rooms</a>
    </div>
</div>
<?php endif; ?>

<script>
// Image gallery functionality
document.addEventListener('DOMContentLoaded', function() {
    const thumbnails = document.querySelectorAll('.gallery-thumbnail');
    const mainImage = document.getElementById('main-room-image');
    
    if (thumbnails.length > 0 && mainImage) {
        thumbnails.forEach(function(thumbnail) {
            thumbnail.addEventListener('click', function() {
                // Remove active class from all thumbnails
                thumbnails.forEach(function(thumb) {
                    thumb.classList.remove('active');
                });
                
                // Add active class to clicked thumbnail
                this.classList.add('active');
                
                // Update main image
                const newImageSrc = this.getAttribute('data-image');
                mainImage.src = newImageSrc;
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>

