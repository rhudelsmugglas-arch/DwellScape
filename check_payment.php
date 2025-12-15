<?php
session_start();
header('Content-Type: application/json');

// Load PayMongo config
require_once 'config/paymongo.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['payment_link_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment link ID required']);
    exit();
}

$secret_key = PAYMONGO_SECRET_KEY;
$payment_link_id = $_GET['payment_link_id'];

// Check payment link status
$ch = curl_init('https://api.paymongo.com/v1/links/' . $payment_link_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode($secret_key . ':')
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $result = json_decode($response, true);
    if (isset($result['data']['attributes']['status'])) {
        $status = $result['data']['attributes']['status'];
        echo json_encode([
            'success' => true,
            'status' => $status,
            'paid' => $status === 'paid'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Status not found in response'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to check payment status',
        'http_code' => $http_code
    ]);
}
?>

