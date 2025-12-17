<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
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

// PayMongo API credentials - Load from config
require_once __DIR__ . '/../config/paymongo.php';
$secret_key = PAYMONGO_SECRET_KEY;
$public_key = PAYMONGO_PUBLIC_KEY;

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
require_once __DIR__ . '/../config/database.php';

// Get user information for pre-filling checkout form
try {
    $stmt = $pdo->prepare("SELECT email, first_name, middle_initial, last_name, username FROM users WHERE id = ?");
    $stmt->execute([$current_user['user_id']]);
    $user = $stmt->fetch();
    
    $user_email = trim($user['email'] ?? '');
    
    // Build full name - trim each part and filter out empty strings
    $name_parts = array_filter(array_map('trim', [
        $user['first_name'] ?? '',
        $user['middle_initial'] ?? '',
        $user['last_name'] ?? ''
    ]), function($part) {
        return !empty($part);
    });
    
    // Join name parts with spaces, or fallback to username
    if (!empty($name_parts)) {
        $user_full_name = implode(' ', $name_parts);
    } else {
        $user_full_name = trim($user['username'] ?? '');
    }
    
    // Ensure we have at least email (should always be present for logged-in users)
    if (empty($user_email) && isset($current_user['email'])) {
        $user_email = trim($current_user['email']);
    }
    
    // If still no email, log warning but continue
    if (empty($user_email)) {
        error_log("Warning: No email found for user ID: " . $current_user['user_id']);
        error_log("User data from database: " . json_encode($user));
    }
    
    // Debug: Log what we fetched
    error_log("Fetched user data - Email: " . ($user_email ?: 'EMPTY') . ", Name: " . ($user_full_name ?: 'EMPTY'));
    error_log("User details - First: " . ($user['first_name'] ?? 'NULL') . ", Middle: " . ($user['middle_initial'] ?? 'NULL') . ", Last: " . ($user['last_name'] ?? 'NULL'));
} catch(PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    // If error, use session data as fallback
    $user_email = trim($_SESSION['email'] ?? '');
    $user_full_name = trim($_SESSION['username'] ?? '');
    error_log("Using session fallback - Email: " . ($user_email ?: 'EMPTY') . ", Name: " . ($user_full_name ?: 'EMPTY'));
}

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
$payment_link_attributes = [
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
        'user_id' => (string)$current_user['user_id']
    ]
];

// Add billing information to pre-fill checkout form
// Paymongo Payment Links API supports billing.email and billing.name for pre-filling checkout
// Always include billing if we have email (required field)
if (!empty($user_email)) {
    $billing_data = [
        'email' => $user_email
    ];
    
    // Add name if available (helps pre-fill the name field)
    // Paymongo accepts name as a string
    if (!empty($user_full_name)) {
        $billing_data['name'] = $user_full_name;
    }
    
    // Add billing to payment link attributes
    $payment_link_attributes['billing'] = $billing_data;
    
    // Log for debugging
    error_log("Paymongo billing data being sent: " . json_encode($billing_data));
    error_log("User email: " . $user_email);
    error_log("User full name: " . $user_full_name);
} else {
    error_log("WARNING: No email found for user - billing information not included");
}

$payment_link_data = [
    'data' => [
        'attributes' => $payment_link_attributes
    ]
];

// Log the complete payment link data being sent (for debugging)
error_log("Paymongo Payment Link Data (full): " . json_encode($payment_link_data, JSON_PRETTY_PRINT));
error_log("Billing info in attributes: " . (isset($payment_link_attributes['billing']) ? json_encode($payment_link_attributes['billing']) : 'NOT SET'));

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

// Log Paymongo response for debugging
error_log("Paymongo API Response: " . json_encode($result, JSON_PRETTY_PRINT));

if (!isset($result['data'])) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from PayMongo',
        'response' => $result
    ]);
    exit();
}

// Log billing information from response (if present)
if (isset($result['data']['attributes']['billing'])) {
    error_log("Paymongo returned billing info: " . json_encode($result['data']['attributes']['billing']));
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

