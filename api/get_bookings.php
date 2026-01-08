<?php
// Use cookie-based authentication instead of sessions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
header('Content-Type: application/json');

// Verify authentication token
$current_user = verifyAuthToken();

if (!$current_user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get today's date for filtering ongoing bookings
    $today = date('Y-m-d');
    
    // Fetch ongoing bookings for the current user
    // Ongoing bookings: check-in date <= today AND check-out date >= today
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.booking_id,
            b.room_name as suite,
            b.checkin_date as checkIn,
            b.checkout_date as checkOut,
            b.nights,
            b.payment_status,
            b.total_price,
            b.created_at as bookingDate,
            u.username as guest
        FROM bookings b
        INNER JOIN users u ON b.user_id = u.id
        WHERE b.user_id = ?
        AND b.checkin_date <= ?
        AND b.checkout_date >= ?
        ORDER BY b.checkin_date ASC, b.created_at DESC
    ");
    
    $stmt->execute([$current_user['user_id'], $today, $today]);
    $bookings = $stmt->fetchAll();
    
    // Format bookings for frontend
    $formattedBookings = [];
    foreach ($bookings as $booking) {
        // Get user info for guest name
        $guestName = $booking['guest'];
        
        // Format the booking data
        $formattedBookings[] = [
            'id' => $booking['booking_id'],
            'bookingDate' => date('Y-m-d', strtotime($booking['bookingDate'])),
            'checkIn' => $booking['checkIn'],
            'checkOut' => $booking['checkOut'],
            'time' => '14:00', // Default time
            'guest' => $guestName,
            'guests' => 2, // Default, can be added to bookings table later
            'suite' => $booking['suite'],
            'totalPrice' => floatval($booking['total_price'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $formattedBookings
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>

