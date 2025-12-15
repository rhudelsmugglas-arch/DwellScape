<?php
// Database configuration
$host = 'localhost';
$dbname = 'dwellscape_capstone';
$username = 'root';
$password = '';

// First, connect without database to create it if it doesn't exist
try {
    $pdo_temp = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create users table if it doesn't exist
$createTable = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    birthday DATE NULL,
    gender VARCHAR(10) NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Add birthday and gender columns if they don't exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS birthday DATE NULL");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender VARCHAR(10) NULL");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NULL");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS middle_initial VARCHAR(5) NULL");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) NULL");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'user'");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

// Update existing users: set role based on is_admin
try {
    $pdo->exec("UPDATE users SET role = 'admin' WHERE is_admin = 1 AND (role IS NULL OR role = 'user')");
    $pdo->exec("UPDATE users SET role = 'user' WHERE (is_admin = 0 OR is_admin IS NULL) AND (role IS NULL OR role = '')");
} catch(PDOException $e) {
    // Ignore errors
}

try {
    $pdo->exec($createTable);
} catch(PDOException $e) {
    // Table might already exist, ignore error
}

// Create bookings table
$createBookingsTable = "
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    room_id VARCHAR(50) NOT NULL,
    room_name VARCHAR(255) NOT NULL,
    checkin_date DATE NOT NULL,
    checkout_date DATE NOT NULL,
    nights INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_link_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_checkin_date (checkin_date),
    INDEX idx_checkout_date (checkout_date),
    INDEX idx_booking_id (booking_id)
)";

try {
    $pdo->exec($createBookingsTable);
} catch(PDOException $e) {
    // Table might already exist, ignore error
}

// Create transactions table
$createTransactionsTable = "
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_link_id VARCHAR(255) NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PHP',
    payment_method VARCHAR(50) NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    paymongo_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking_id (booking_id),
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_transaction_id (transaction_id)
)";

try {
    $pdo->exec($createTransactionsTable);
} catch(PDOException $e) {
    // Table might already exist, ignore error
}

// Create rooms table (if it doesn't exist) to track available rooms
$createRoomsTable = "
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    max_guests INT DEFAULT 2,
    bedrooms INT DEFAULT 1,
    bathrooms INT DEFAULT 1,
    area_sqm DECIMAL(10, 2) NULL,
    amenities TEXT NULL,
    images TEXT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_id (room_id),
    INDEX idx_is_available (is_available)
)";

// Add new columns if they don't exist
try {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS bedrooms INT DEFAULT 1");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS bathrooms INT DEFAULT 1");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS area_sqm DECIMAL(10, 2) NULL");
} catch(PDOException $e) {
    // Column might already exist, ignore error
}

// Create gallery_images table
$createGalleryTable = "
CREATE TABLE IF NOT EXISTS gallery_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(500) NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
)";

try {
    $pdo->exec($createGalleryTable);
} catch(PDOException $e) {
    // Table might already exist, ignore error
}

// Create suggestions table
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

try {
    $pdo->exec($createSuggestionsTable);
} catch(PDOException $e) {
    // Table might already exist, ignore error
}

try {
    $pdo->exec($createRoomsTable);
} catch(PDOException $e) {
    // Table might already exist, ignore error
}

// Add foreign key constraints if they don't exist (after tables are created)
try {
    // Check if foreign key exists before adding
    $fk_check = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'bookings' 
        AND CONSTRAINT_NAME = 'bookings_ibfk_1'
    ")->fetch();
    
    if (!$fk_check) {
        $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT bookings_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    }
} catch(PDOException $e) {
    // Foreign key might already exist or users table might not exist yet, ignore
}

try {
    $fk_check2 = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'transactions' 
        AND CONSTRAINT_NAME = 'transactions_ibfk_1'
    ")->fetch();
    
    if (!$fk_check2) {
        $pdo->exec("ALTER TABLE transactions ADD CONSTRAINT transactions_ibfk_1 FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE");
        $pdo->exec("ALTER TABLE transactions ADD CONSTRAINT transactions_ibfk_2 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    }
} catch(PDOException $e) {
    // Foreign keys might already exist, ignore
}
?>
