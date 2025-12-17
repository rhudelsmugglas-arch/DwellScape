<?php
/**
 * Fix Gallery Images - Add/Update images from pictures folder
 * This script will add living room and kitchen images from the pictures folder
 */

require_once 'config/database.php';

// Get all files from pictures folder
$pictures_dir = __DIR__ . '/pictures';
$living_room_files = [];
$kitchen_files = [];

if (is_dir($pictures_dir)) {
    $files = scandir($pictures_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $file_lower = strtolower($file);
        // Check if it's an image file
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $file)) {
            // Check if it's a living room image
            if (preg_match('/living|liv/i', $file)) {
                $living_room_files[] = $file;
            }
            // Check if it's a kitchen image
            elseif (preg_match('/kitchen|kit/i', $file)) {
                $kitchen_files[] = $file;
            }
        }
    }
    
    // Sort files naturally
    natsort($living_room_files);
    natsort($kitchen_files);
}

// Prepare gallery images array
$gallery_images = [];

// Add living room images
$order = 1;
foreach ($living_room_files as $file) {
    $gallery_images[] = [
        'image_url' => 'pictures/' . $file,
        'title' => 'Living Room',
        'category' => 'living-room',
        'description' => 'Spacious & Comfortable Living Space',
        'display_order' => $order++,
        'is_active' => 1
    ];
}

// Add kitchen images
foreach ($kitchen_files as $file) {
    $gallery_images[] = [
        'image_url' => 'pictures/' . $file,
        'title' => 'Full Kitchen',
        'category' => 'kitchen',
        'description' => 'Fully Equipped Modern Kitchen',
        'display_order' => $order++,
        'is_active' => 1
    ];
}

if (empty($gallery_images)) {
    echo "No living room or kitchen images found in the pictures folder.\n";
    echo "Please make sure your images are named with 'living' or 'kitchen' in the filename.\n";
    echo "\nFiles found in pictures folder:\n";
    if (is_dir($pictures_dir)) {
        $all_files = scandir($pictures_dir);
        foreach ($all_files as $file) {
            if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $file)) {
                echo "  - $file\n";
            }
        }
    }
    exit;
}

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
        // Check if image already exists
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
            echo "Updated: " . $image['image_url'] . " (" . $image['category'] . ")\n";
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
            echo "Added: " . $image['image_url'] . " (" . $image['category'] . ")\n";
            $inserted++;
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Successfully processed " . ($inserted + $updated) . " gallery images!\n";
    echo "- Added: $inserted new images\n";
    echo "- Updated: $updated existing images\n";
    echo "\nYou can now view them in the gallery page!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

