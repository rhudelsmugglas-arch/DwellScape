<?php
/**
 * Seed Gallery Images
 * This script populates the gallery_images table with images from dashboard.php
 */

require_once 'config/database.php';

$gallery_images = [
    // Living Room images
    [
        'image_url' => 'pictures/living1.avif',
        'title' => 'Living Room',
        'category' => 'living-room',
        'description' => 'Spacious & Comfortable',
        'display_order' => 1,
        'is_active' => 1
    ],
    [
        'image_url' => 'pictures/living2.avif',
        'title' => 'Living Room',
        'category' => 'living-room',
        'description' => 'Modern Design',
        'display_order' => 2,
        'is_active' => 1
    ],
    [
        'image_url' => 'pictures/living3.avif',
        'title' => 'Living Room',
        'category' => 'living-room',
        'description' => 'Elegant & Cozy',
        'display_order' => 3,
        'is_active' => 1
    ],
    [
        'image_url' => 'pictures/living4.avif',
        'title' => 'Living Room',
        'category' => 'living-room',
        'description' => 'Stylish Interior',
        'display_order' => 4,
        'is_active' => 1
    ],
    // Kitchen images
    [
        'image_url' => 'pictures/kitchen1.avif',
        'title' => 'Full Kitchen',
        'category' => 'kitchen',
        'description' => 'Fully Equipped',
        'display_order' => 5,
        'is_active' => 1
    ],
    [
        'image_url' => 'pictures/kitchen2.avif',
        'title' => 'Full Kitchen',
        'category' => 'kitchen',
        'description' => 'Modern & Spacious',
        'display_order' => 6,
        'is_active' => 1
    ],
    // Dining Area
    [
        'image_url' => 'pictures/dining1.png',
        'title' => 'Dining Area',
        'category' => 'dining',
        'description' => 'Elegant Dining Space',
        'display_order' => 7,
        'is_active' => 1
    ],
    [
        'image_url' => 'pictures/dining2.png',
        'title' => 'Dining Area',
        'category' => 'dining',
        'description' => 'Spacious Dining Room',
        'display_order' => 8,
        'is_active' => 1
    ],
    // Bedroom 1 - Use bedroom1-2.png or 1bedroom.png
    [
        'image_url' => 'pictures/bedroom1-2.png',
        'title' => 'Bedroom 1',
        'category' => 'bedroom1',
        'description' => 'Master Bedroom',
        'display_order' => 9,
        'is_active' => 1
    ],
    // Bedroom 2 - First image
    [
        'image_url' => 'pictures/bedroom2-1.png',
        'title' => 'Bedroom 2',
        'category' => 'bedroom2',
        'description' => 'Cozy Guest Room',
        'display_order' => 10,
        'is_active' => 1
    ],
    // Bedroom 2 - Second image
    [
        'image_url' => 'pictures/bedroom2-2.png',
        'title' => 'Bedroom 2',
        'category' => 'bedroom2',
        'description' => 'Comfortable Guest Space',
        'display_order' => 11,
        'is_active' => 1
    ],
    // Bathroom 1
    [
        'image_url' => 'pictures/bathroom1.png',
        'title' => 'Bathroom 1',
        'category' => 'bathroom',
        'description' => 'Modern & Clean',
        'display_order' => 12,
        'is_active' => 1
    ],
    // Bathroom 2
    [
        'image_url' => 'pictures/bathroom2.png',
        'title' => 'Bathroom 2',
        'category' => 'bathroom',
        'description' => 'Spacious & Elegant',
        'display_order' => 13,
        'is_active' => 1
    ],
    // Pool
    [
        'image_url' => 'pictures/pool1.png',
        'title' => 'Pool',
        'category' => 'pool',
        'description' => 'Resort-Style Pool',
        'display_order' => 14,
        'is_active' => 1
    ],
    // Activity Area
    [
        'image_url' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=800&auto=format&fit=crop',
        'title' => 'Activity Area',
        'category' => 'activity',
        'description' => 'Recreation Space',
        'display_order' => 13,
        'is_active' => 1
    ]
];

try {
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = ?");
    $insertStmt = $pdo->prepare("
        INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $updateStmt = $pdo->prepare("
        UPDATE gallery_images 
        SET title = ?, category = ?, description = ?, display_order = ?, is_active = ?
        WHERE image_url = ?
    ");
    
    $inserted = 0;
    $updated = 0;
    
    foreach ($gallery_images as $image) {
        $checkStmt->execute([$image['image_url']]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Update existing
            $updateStmt->execute([
                $image['title'],
                $image['category'],
                $image['description'],
                $image['display_order'],
                $image['is_active'],
                $image['image_url']
            ]);
            $updated++;
        } else {
            // Insert new
            $insertStmt->execute([
                $image['image_url'],
                $image['title'],
                $image['category'],
                $image['description'],
                $image['display_order'],
                $image['is_active']
            ]);
            $inserted++;
        }
    }
    
    echo "Successfully processed " . ($inserted + $updated) . " gallery images!\n";
    echo "- Inserted: $inserted new images\n";
    echo "- Updated: $updated existing images\n";
    echo "You can now view them in the admin gallery management page.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

