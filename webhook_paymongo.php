<?php
/**
 * Paymongo Webhook Handler
 * Handles payment events from Paymongo
 * 
 * Configure this URL in Paymongo Dashboard:
 * https://dashboard.paymongo.com/settings/webhooks
 * 
 * URL: http://yourdomain.com/webhook_paymongo.php
 */

require_once 'config/database.php';
require_once 'config/paymongo.php';

// Set content type
header('Content-Type: application/json');

// Allow GET requests for testing webhook configuration
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $webhook_secret = PAYMONGO_WEBHOOK_SECRET;
    echo json_encode([
        'webhook_configured' => !empty($webhook_secret),
        'webhook_secret_length' => !empty($webhook_secret) ? strlen($webhook_secret) : 0,
        'webhook_secret_prefix' => !empty($webhook_secret) ? substr($webhook_secret, 0, 10) . '...' : 'not set',
        'api_mode' => strpos(PAYMONGO_SECRET_KEY, 'sk_test_') === 0 ? 'test' : 'live',
        'message' => 'Webhook endpoint is active. Use POST to receive webhooks.'
    ]);
    exit();
}

// Only accept POST requests for actual webhooks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get raw request body
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Log all headers for debugging (remove in production)
error_log("Paymongo Webhook Headers: " . json_encode($headers));
error_log("Paymongo Webhook Payload length: " . strlen($payload));

// Normalize headers to lowercase for case-insensitive lookup
$normalized_headers = [];
foreach ($headers as $key => $value) {
    $normalized_headers[strtolower($key)] = $value;
}

// Verify webhook signature (if webhook secret is configured)
$webhook_secret = PAYMONGO_WEBHOOK_SECRET;

// Log webhook secret status for debugging
error_log("Paymongo Webhook: Secret configured: " . (!empty($webhook_secret) ? 'YES (length: ' . strlen($webhook_secret) . ', prefix: ' . substr($webhook_secret, 0, 5) . '...)' : 'NO'));

// Check if webhook secret is configured
if (empty($webhook_secret)) {
    error_log("Paymongo Webhook: WARNING - Webhook secret not configured!");
    error_log("Paymongo Webhook: To fix this, add PAYMONGO_WEBHOOK_SECRET to your .env file");
    error_log("Paymongo Webhook: For testing, allowing webhook without verification (NOT RECOMMENDED FOR PRODUCTION)");
    // Temporarily allow webhooks without verification for testing
    // TODO: Remove this in production and require webhook secret
    $signature_valid = true;
    $skip_verification = true;
} else {
    $skip_verification = false;
}

// Paymongo sends signature in Paymongo-Signature header
// Format: t=<timestamp>,te=<test_signature>,li=<live_signature>
$signature_header = isset($normalized_headers['paymongo-signature']) 
                    ? $normalized_headers['paymongo-signature'] 
                    : (isset($headers['Paymongo-Signature']) ? $headers['Paymongo-Signature'] : '');

// Also check for x-paymongo-signature (some versions might use this)
if (empty($signature_header)) {
    $signature_header = isset($normalized_headers['x-paymongo-signature']) 
                        ? $normalized_headers['x-paymongo-signature'] 
                        : (isset($headers['x-paymongo-signature']) ? $headers['x-paymongo-signature'] : '');
}

error_log("Paymongo Webhook: Signature header found: " . (!empty($signature_header) ? 'YES (' . substr($signature_header, 0, 100) . '...)' : 'NO'));
error_log("Paymongo Webhook: All headers: " . json_encode(array_keys($headers)));
error_log("Paymongo Webhook: Payload preview: " . substr($payload, 0, 200) . "...");
error_log("Paymongo Webhook: Payload is JSON: " . (json_decode($payload) !== null ? 'YES' : 'NO'));

if (empty($signature_header) && !$skip_verification) {
    error_log("Paymongo Webhook: Missing Paymongo-Signature header. Available headers: " . json_encode(array_keys($headers)));
    if (!empty($webhook_secret)) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing signature', 'headers_received' => array_keys($headers)]);
        exit();
    }
}

$signature_valid = $skip_verification;

if (!empty($webhook_secret) && !empty($signature_header) && !$skip_verification) {
    // Parse Paymongo signature header format: t=<timestamp>,te=<test_signature>,li=<live_signature>
    $signature_parts = [];
    $parts = explode(',', $signature_header);
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (strpos($part, 't=') === 0) {
            $signature_parts['timestamp'] = substr($part, 2);
        } elseif (strpos($part, 'te=') === 0) {
            $signature_parts['test_signature'] = substr($part, 3);
        } elseif (strpos($part, 'li=') === 0) {
            $signature_parts['live_signature'] = substr($part, 3);
        }
    }
    
    if (empty($signature_parts['timestamp'])) {
        error_log("Paymongo Webhook: Could not parse timestamp from signature header: " . $signature_header);
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature format']);
        exit();
    }
    
    // Determine if we're in test or live mode based on the secret key
    $is_test_mode = (strpos(PAYMONGO_SECRET_KEY, 'sk_test_') === 0);
    $expected_signature = $is_test_mode 
                        ? ($signature_parts['test_signature'] ?? '') 
                        : ($signature_parts['live_signature'] ?? '');
    
    if (empty($expected_signature)) {
        error_log("Paymongo Webhook: Missing " . ($is_test_mode ? 'test' : 'live') . " signature in header");
        http_response_code(401);
        echo json_encode(['error' => 'Missing signature']);
        exit();
    }
    
    // Create signature string: timestamp + '.' + payload
    $signature_string = $signature_parts['timestamp'] . '.' . $payload;
    
    // Try multiple secret formats - Paymongo might require the full secret or just the part after prefix
    $secret_variants = [];
    
    // Variant 1: Use full secret as-is
    $secret_variants[] = ['name' => 'full_secret', 'value' => $webhook_secret];
    
    // Variant 2: Remove whsec_ prefix
    if (strpos($webhook_secret, 'whsec_') === 0) {
        $secret_variants[] = ['name' => 'without_whsec', 'value' => substr($webhook_secret, 6)];
    }
    
    // Variant 3: Remove whsk_ prefix
    if (strpos($webhook_secret, 'whsk_') === 0) {
        $secret_variants[] = ['name' => 'without_whsk', 'value' => substr($webhook_secret, 5)];
    }
    
    // Try each secret variant
    foreach ($secret_variants as $variant) {
        $test_secret = $variant['value'];
        $computed_signature = hash_hmac('sha256', $signature_string, $test_secret);
        
        error_log("Paymongo Webhook: Testing signature with " . $variant['name'] . " (length: " . strlen($test_secret) . ")");
        error_log("  Computed: " . substr($computed_signature, 0, 30) . "...");
        error_log("  Expected: " . substr($expected_signature, 0, 30) . "...");
        
        if (hash_equals($computed_signature, $expected_signature)) {
            $signature_valid = true;
            error_log("Paymongo Webhook: Signature verified successfully using " . $variant['name']);
            
            // Optional: Verify timestamp is recent (within 5 minutes) to prevent replay attacks
            $timestamp = (int)$signature_parts['timestamp'];
            $current_time = time();
            $time_diff = abs($current_time - $timestamp);
            
            if ($time_diff > 300) { // 5 minutes
                error_log("Paymongo Webhook: WARNING - Timestamp is " . $time_diff . " seconds old (current: $current_time, webhook: $timestamp)");
                // Don't reject, just log - some systems might have clock skew
            }
            break; // Found working variant, stop trying
        }
    }
    
    if (!$signature_valid) {
        error_log("Paymongo Webhook: Signature verification failed with all secret variants.");
        error_log("  Signature header: " . substr($signature_header, 0, 100) . "...");
        error_log("  Timestamp: " . $signature_parts['timestamp']);
        error_log("  Payload length: " . strlen($payload));
        error_log("  Payload preview: " . substr($payload, 0, 100) . "...");
        error_log("  Signature string preview: " . substr($signature_string, 0, 100) . "...");
        error_log("  Expected signature: " . substr($expected_signature, 0, 30) . "...");
        error_log("  Webhook secret prefix: " . substr($webhook_secret, 0, 10) . "...");
    }
}

// Final check - if verification was required but failed
if (!$signature_valid && !$skip_verification) {
    error_log("Paymongo Webhook: Signature verification failed - rejecting webhook");
    http_response_code(401);
    echo json_encode([
        'error' => 'Invalid signature',
        'debug_info' => [
            'webhook_secret_configured' => !empty($webhook_secret),
            'signature_header_present' => !empty($signature_header),
            'headers_received' => array_keys($headers)
        ]
    ]);
    exit();
} elseif ($skip_verification) {
    // Webhook secret not configured - allow but log warning
    error_log("Paymongo Webhook: Processing without signature verification (webhook secret not configured)");
}

// Parse webhook data
$data = json_decode($payload, true);

if (!$data || !isset($data['data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit();
}

$event = $data['data'];
$event_type = $event['type'] ?? '';

// Log webhook event (for debugging)
error_log("Paymongo Webhook: " . $event_type . " - " . json_encode($event));

try {
    // Handle different event types
    switch ($event_type) {
        case 'payment.paid':
        case 'payment.succeeded':
            // Payment was successful
            handlePaymentSuccess($event, $pdo);
            break;
            
        case 'payment.failed':
            // Payment failed
            handlePaymentFailed($event, $pdo);
            break;
            
        case 'link.paid':
            // Payment link was paid
            handleLinkPaid($event, $pdo);
            break;
            
        default:
            // Unknown event type - log but don't error
            error_log("Unknown webhook event type: " . $event_type);
            break;
    }
    
    // Return success
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processed']);
    
} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Webhook processing failed']);
}

/**
 * Handle successful payment
 */
function handlePaymentSuccess($event, $pdo) {
    $attributes = $event['attributes'] ?? [];
    $payment_id = $event['id'] ?? '';
    
    // Find transaction by payment link ID or payment ID
    $payment_link_id = $attributes['source']['id'] ?? '';
    
    if (!empty($payment_link_id)) {
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET payment_status = 'paid', 
                paymongo_response = ?,
                updated_at = NOW()
            WHERE payment_link_id = ? AND payment_status != 'paid'
        ");
        $stmt->execute([json_encode($event), $payment_link_id]);
        
        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE bookings b
            INNER JOIN transactions t ON b.id = t.booking_id
            SET b.payment_status = 'paid',
                b.updated_at = NOW()
            WHERE t.payment_link_id = ? AND b.payment_status != 'paid'
        ");
        $stmt->execute([$payment_link_id]);
    }
}

/**
 * Handle failed payment
 */
function handlePaymentFailed($event, $pdo) {
    $attributes = $event['attributes'] ?? [];
    $payment_link_id = $attributes['source']['id'] ?? '';
    
    if (!empty($payment_link_id)) {
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET payment_status = 'failed', 
                paymongo_response = ?,
                updated_at = NOW()
            WHERE payment_link_id = ? AND payment_status = 'pending'
        ");
        $stmt->execute([json_encode($event), $payment_link_id]);
    }
}

/**
 * Handle payment link paid
 */
function handleLinkPaid($event, $pdo) {
    $attributes = $event['attributes'] ?? [];
    $link_id = $event['id'] ?? '';
    
    if (!empty($link_id)) {
        // Update transaction and booking status
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET payment_status = 'paid', 
                paymongo_response = ?,
                updated_at = NOW()
            WHERE payment_link_id = ? AND payment_status != 'paid'
        ");
        $stmt->execute([json_encode($event), $link_id]);
        
        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE bookings b
            INNER JOIN transactions t ON b.id = t.booking_id
            SET b.payment_status = 'paid',
                b.updated_at = NOW()
            WHERE t.payment_link_id = ? AND b.payment_status != 'paid'
        ");
        $stmt->execute([$link_id]);
    }
}
?>

