<?php
// Suppress any output before JSON
ob_start();

// Start session without output
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../config/database.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$name = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate input
if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit();
}

// Ensure suggestions table exists
try {
    $createSuggestionsTable = "
    CREATE TABLE IF NOT EXISTS suggestions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    $pdo->exec($createSuggestionsTable);
} catch(PDOException $e) {
    // Table might already exist, ignore error
}

// Save suggestion to database
try {
    $stmt = $pdo->prepare("INSERT INTO suggestions (name, email, message, status) VALUES (?, ?, ?, 'unread')");
    $stmt->execute([$name, $email, $message]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Message sent successfully!'
    ]);
    exit();
} catch(PDOException $e) {
    error_log("Error saving suggestion: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sorry, there was an error sending your message. Please try again later.'
    ]);
    exit();
}

