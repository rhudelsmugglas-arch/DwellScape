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
    // Get all booked dates (checkin and checkout dates) where payment is paid
    $stmt = $pdo->prepare("
        SELECT 
            checkin_date,
            checkout_date
        FROM bookings
        WHERE payment_status = 'paid'
        ORDER BY checkin_date ASC
    ");
    
    $stmt->execute();
    $bookings = $stmt->fetchAll();
    
    // Convert booking ranges to individual dates
    $booked_dates = [];
    foreach ($bookings as $booking) {
        $checkin = new DateTime($booking['checkin_date']);
        $checkout = new DateTime($booking['checkout_date']);
        
        // Add all dates from checkin to checkout (including checkout date)
        // Both check-in and check-out dates are unavailable
        $current = clone $checkin;
        while ($current <= $checkout) {
            $booked_dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
    }
    
    // Remove duplicates and sort
    $booked_dates = array_unique($booked_dates);
    sort($booked_dates);
    
    echo json_encode([
        'success' => true,
        'booked_dates' => array_values($booked_dates)
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>

