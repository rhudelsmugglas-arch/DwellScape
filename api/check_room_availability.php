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

if (!isset($input['room_id']) || !isset($input['checkin']) || !isset($input['checkout'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: room_id, checkin, checkout']);
    exit();
}

$room_id = $input['room_id'];
$checkin_date = $input['checkin'];
$checkout_date = $input['checkout'];

try {
    // Check for overlapping bookings
    // A booking overlaps if the date ranges intersect
    // Overlap occurs when: new_checkin < existing_checkout AND new_checkout > existing_checkin
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
    
    echo json_encode([
        'success' => true,
        'available' => $is_available,
        'message' => $is_available ? 'Room is available' : 'Room is not available for the selected dates'
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>

