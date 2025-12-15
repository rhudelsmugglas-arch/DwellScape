<?php
/**
 * Script to seed initial room data into the database
 * Run this once to populate the rooms table with default rooms
 */
require_once 'config/database.php';

// Check if this is being included (silent mode) or run directly
// When included, __FILE__ will be seed_rooms.php but $_SERVER['SCRIPT_FILENAME'] will be the including file
$silent_mode = !defined('SEED_ROOMS_DIRECT') && (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== 'seed_rooms.php');

try {
    // Insert rooms if they don't exist
    $rooms = [
        [
            'room_id' => '1',
            'name' => '1 Bedroom Suite',
            'description' => 'Cozy and comfortable suite perfect for small families or couples',
            'price_per_night' => 2500.00,
            'max_guests' => 5,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_sqm' => 45.00,
            'amenities' => 'Wi-Fi,Kitchen,TV,AC',
            'images' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop',
            'is_available' => 1
        ],
        [
            'room_id' => '2',
            'name' => '2 Bedroom Premium',
            'description' => 'Spacious premium suite with modern amenities and balcony',
            'price_per_night' => 4500.00,
            'max_guests' => 10,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area_sqm' => 85.00,
            'amenities' => 'Wi-Fi,Full Kitchen,Smart TV,AC,Balcony',
            'images' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=800&auto=format&fit=crop',
            'is_available' => 1
        ],
        [
            'room_id' => '3',
            'name' => 'Family Loft',
            'description' => 'Large family-friendly loft with pool access and multiple bedrooms',
            'price_per_night' => 5500.00,
            'max_guests' => 8,
            'bedrooms' => 2,
            'bathrooms' => 3,
            'area_sqm' => 95.00,
            'amenities' => 'Wi-Fi,Full Kitchen,Smart TV,AC,Pool Access',
            'images' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?q=80&w=800&auto=format&fit=crop',
            'is_available' => 1
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO rooms (room_id, name, description, price_per_night, max_guests, bedrooms, bathrooms, area_sqm, amenities, images, is_available)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            price_per_night = VALUES(price_per_night),
            max_guests = VALUES(max_guests),
            bedrooms = VALUES(bedrooms),
            bathrooms = VALUES(bathrooms),
            area_sqm = VALUES(area_sqm),
            amenities = VALUES(amenities),
            images = VALUES(images),
            is_available = VALUES(is_available)
    ");
    
    foreach ($rooms as $room) {
        $stmt->execute([
            $room['room_id'],
            $room['name'],
            $room['description'],
            $room['price_per_night'],
            $room['max_guests'],
            $room['bedrooms'],
            $room['bathrooms'],
            $room['area_sqm'],
            $room['amenities'],
            $room['images'],
            $room['is_available']
        ]);
    }
    
    if (!$silent_mode) {
        echo "Rooms seeded successfully!\n";
        echo "Total rooms inserted/updated: " . count($rooms) . "\n";
    }
    
} catch(PDOException $e) {
    if (!$silent_mode) {
        echo "Error seeding rooms: " . $e->getMessage() . "\n";
    }
    // Re-throw if in silent mode so calling code can handle it
    if ($silent_mode) {
        throw $e;
    }
}
?>

