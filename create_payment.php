<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// PayMongo API credentials
$secret_key = 'REPLACE_WITH_SECRET';
$public_key = 'REPLACE_WITH_PUBLIC';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['amount']) || !isset($input['booking_data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$amount = $input['amount']; // Amount in centavos (multiply by 100)
$booking_data = $input['booking_data'];

// Check room availability before creating payment
require_once 'config/database.php';

try {
    $room_id = (string)$booking_data['roomId'];
    $checkin_date = $booking_data['checkin'];
    $checkout_date = $booking_data['checkout'];
    
    // Check for overlapping bookings
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
    
    if (!$is_available) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Room is no longer available for the selected dates',
            'message' => 'This room has been booked by another guest. Please select different dates or another room.'
        ]);
        exit();
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error checking availability',
        'message' => $e->getMessage()
    ]);
    exit();
}

// Get base URL for redirects
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['REQUEST_URI']);
$base_url = $protocol . '://' . $host . $base_path;

$success_url = rtrim($base_url, '/') . '/payment-success.php';
$failed_url = rtrim($base_url, '/') . '/payment-failed.php';

// Create Payment Link with PayMongo API v1 (simpler approach, redirects to PayMongo checkout)
$payment_link_data = [
    'data' => [
        'attributes' => [
            'amount' => $amount * 100, // Convert to centavos
            'currency' => 'PHP',
            'description' => 'Booking for ' . $booking_data['roomName'] . ' - ' . $booking_data['checkin'] . ' to ' . $booking_data['checkout'],
            'remarks' => 'Dwellscape Staycation Booking',
            'metadata' => [
                'booking_id' => uniqid('BK_'),
                'room_id' => (string)$booking_data['roomId'],
                'room_name' => (string)$booking_data['roomName'],
                'checkin' => (string)$booking_data['checkin'],
                'checkout' => (string)$booking_data['checkout'],
                'nights' => (string)$booking_data['nights'],
                'user_id' => (string)$_SESSION['user_id']
            ]
        ]
    ]
];

// Encode payment link data
$json_data = json_encode($payment_link_data);
if ($json_data === false) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to encode payment data: ' . json_last_error_msg()
    ]);
    exit();
}

// Make request to PayMongo API v1 - Payment Links endpoint
$ch = curl_init('https://api.paymongo.com/v1/links');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($secret_key . ':')
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code !== 200 && $http_code !== 201) {
    $error_details = json_decode($response, true);
    $error_message = 'Payment link creation failed';
    
    if (isset($error_details['errors'])) {
        $error_message = isset($error_details['errors'][0]['detail']) ? $error_details['errors'][0]['detail'] : $error_message;
    } elseif (isset($error_details['message'])) {
        $error_message = $error_details['message'];
    }
    
    if ($curl_error) {
        $error_message .= ' - ' . $curl_error;
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => $error_message,
        'http_code' => $http_code,
        'details' => $error_details
    ]);
    exit();
}

$result = json_decode($response, true);

if (!isset($result['data'])) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from PayMongo',
        'response' => $result
    ]);
    exit();
}

// Extract checkout URL from Payment Link response
$checkout_url = null;
if (isset($result['data']['attributes']['checkout_url'])) {
    $checkout_url = $result['data']['attributes']['checkout_url'];
} elseif (isset($result['data']['attributes']['url'])) {
    $checkout_url = $result['data']['attributes']['url'];
}

if (!$checkout_url) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Checkout URL not found in PayMongo response',
        'response' => $result
    ]);
    exit();
}

// Store payment link ID in session for later verification
$_SESSION['payment_link_id'] = $result['data']['id'];
$_SESSION['booking_data'] = $booking_data;

// Return checkout URL for frontend redirect
echo json_encode([
    'success' => true,
    'checkout_url' => $checkout_url,
    'payment_link_id' => $result['data']['id']
]);

