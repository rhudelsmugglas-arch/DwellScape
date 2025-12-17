<?php
// Use cookie-based authentication instead of sessions
// Start output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// Set headers
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Verify authentication token
$current_user = verifyAuthToken();

if (!$current_user) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized',
        'message' => 'Authentication required. Please log in.',
        'debug' => [
            'cookies_received' => $_COOKIE ?? [],
            'auth_token_exists' => isset($_COOKIE['auth_token'])
        ]
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['checkin']) || !isset($input['checkout'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: checkin, checkout']);
    exit();
}

$checkin_date = $input['checkin'];
$checkout_date = $input['checkout'];

try {
    // Get all rooms from the database
    $rooms_stmt = $pdo->prepare("
        SELECT 
            room_id,
            name,
            description,
            price_per_night,
            max_guests,
            bedrooms,
            bathrooms,
            area_sqm,
            amenities,
            images,
            is_available
        FROM rooms
        WHERE is_available = 1
        ORDER BY price_per_night ASC
    ");
    $rooms_stmt->execute();
    $all_rooms = $rooms_stmt->fetchAll();
    
    // If no rooms in database, use default rooms (fallback)
    if (empty($all_rooms)) {
        $all_rooms = [
            [
                'room_id' => '1',
                'name' => '1 Bedroom Suite',
                'description' => 'Cozy and comfortable suite perfect for small families',
                'price_per_night' => 2500.00,
                'max_guests' => 5,
                'amenities' => 'Wi-Fi,Kitchen,TV,AC',
                'images' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop',
                'is_available' => 1
            ],
            [
                'room_id' => '2',
                'name' => '2 Bedroom Premium',
                'description' => 'Spacious premium suite with modern amenities',
                'price_per_night' => 4500.00,
                'max_guests' => 10,
                'amenities' => 'Wi-Fi,Full Kitchen,Smart TV,AC,Balcony',
                'images' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=800&auto=format&fit=crop',
                'is_available' => 1
            ],
            [
                'room_id' => '3',
                'name' => 'Family Loft',
                'description' => 'Large family-friendly loft with pool access',
                'price_per_night' => 5500.00,
                'max_guests' => 8,
                'amenities' => 'Wi-Fi,Full Kitchen,Smart TV,AC,Pool Access',
                'images' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=800&auto=format&fit=crop',
                'is_available' => 1
            ]
        ];
    }
    
    // Check which rooms are available for the given dates
    // A room is unavailable if there's a paid booking that overlaps with the requested dates
    $available_rooms = [];
    
    foreach ($all_rooms as $room) {
        $room_id = $room['room_id'];
        
        // Check for overlapping bookings
        // Overlap occurs when: existing_checkin < new_checkout AND existing_checkout > new_checkin
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as overlap_count
            FROM bookings
            WHERE room_id = ?
            AND payment_status = 'paid'
            AND checkin_date < ? AND checkout_date > ?
        ");
        
        $stmt->execute([
            $room_id,
            $checkout_date,
            $checkin_date
        ]);
        
        $result = $stmt->fetch();
        $is_available = ($result['overlap_count'] == 0);
        
        if ($is_available) {
            // Parse amenities from string to array
            $amenities = !empty($room['amenities']) ? explode(',', $room['amenities']) : [];
            $amenities = array_map('trim', $amenities);
            
            // Parse images - get first image from comma-separated list
            $image_string = !empty($room['images']) ? trim($room['images']) : '';
            $main_image = 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop';
            
            if (!empty($image_string)) {
                $image_list = explode(',', $image_string);
                $first_image = trim($image_list[0]);
                
                if (!empty($first_image)) {
                    // Check if it's a URL (starts with http:// or https://)
                    if (preg_match('/^https?:\/\//', $first_image)) {
                        $main_image = $first_image;
                    } else {
                        // Local file - use relative path
                        $main_image = $first_image;
                    }
                }
            }
            
            // Get all images for details view
            $all_images = [];
            if (!empty($image_string)) {
                $image_list = explode(',', $image_string);
                foreach ($image_list as $img) {
                    $img = trim($img);
                    if (!empty($img)) {
                        if (preg_match('/^https?:\/\//', $img)) {
                            $all_images[] = $img;
                        } else {
                            $all_images[] = $img;
                        }
                    }
                }
            }
            
            // Calculate size
            $size = '';
            if (!empty($room['area_sqm'])) {
                $size = number_format($room['area_sqm'], 0) . ' sqm';
            } else {
                // Fallback based on room_id
                $size = (int)$room_id == 1 ? '45 sqm' : ((int)$room_id == 2 ? '85 sqm' : '95 sqm');
            }
            
            // Map room data to frontend format
            $available_rooms[] = [
                'id' => (int)$room_id,
                'name' => $room['name'],
                'description' => $room['description'] ?? '',
                'image' => $main_image,
                'images' => $all_images,
                'bedrooms' => !empty($room['bedrooms']) ? (int)$room['bedrooms'] : ((int)$room_id <= 2 ? (int)$room_id : 2),
                'guests' => (int)$room['max_guests'],
                'bathrooms' => !empty($room['bathrooms']) ? (int)$room['bathrooms'] : (int)$room_id,
                'size' => $size,
                'amenities' => $amenities,
                'pricePerNight' => (float)$room['price_per_night']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'rooms' => $available_rooms
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>

