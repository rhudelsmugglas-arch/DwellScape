<?php
/**
 * Check and Add Gallery Images
 * This will check the database and add images from pictures folder
 */

require_once 'config/database.php';

echo "<h2>Gallery Images Check & Restore</h2>";
echo "<pre>";

// Check current database status
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery_images WHERE is_active = 1");
    $result = $stmt->fetch();
    echo "Current active images in database: " . $result['count'] . "\n\n";
    
    // Show existing images
    $stmt = $pdo->query("SELECT id, image_url, category, title, is_active FROM gallery_images ORDER BY id");
    $existing = $stmt->fetchAll();
    
    if (!empty($existing)) {
        echo "Existing images in database:\n";
        foreach ($existing as $img) {
            echo "  ID: {$img['id']} | URL: {$img['image_url']} | Category: {$img['category']} | Active: {$img['is_active']}\n";
        }
        echo "\n";
    }
} catch(PDOException $e) {
    echo "Error checking database: " . $e->getMessage() . "\n\n";
}

// Check pictures folder
$pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
echo "Checking pictures folder: " . $pictures_dir . "\n";

if (!is_dir($pictures_dir)) {
    echo "ERROR: Pictures folder does not exist!\n";
    echo "Please create the 'pictures' folder in: " . __DIR__ . "\n";
    exit;
}

$files = scandir($pictures_dir);
$image_files = [];

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $file)) {
        $image_files[] = $file;
    }
}

echo "Found " . count($image_files) . " image files:\n";
foreach ($image_files as $file) {
    echo "  - $file\n";
}
echo "\n";

// Categorize images
$living_room_images = [];
$kitchen_images = [];

foreach ($image_files as $file) {
    $file_lower = strtolower($file);
    if (preg_match('/living|liv/i', $file_lower)) {
        $living_room_images[] = $file;
    } elseif (preg_match('/kitchen|kit/i', $file_lower)) {
        $kitchen_images[] = $file;
    }
}

echo "Living room images found: " . count($living_room_images) . "\n";
echo "Kitchen images found: " . count($kitchen_images) . "\n\n";

// Prepare images to add
$images_to_add = [];
$order = 1;

foreach ($living_room_images as $file) {
    $images_to_add[] = [
        'image_url' => 'pictures/' . $file,
        'title' => 'Living Room',
        'category' => 'living-room',
        'description' => 'Spacious & Comfortable Living Space',
        'display_order' => $order++,
        'is_active' => 1
    ];
}

foreach ($kitchen_images as $file) {
    $images_to_add[] = [
        'image_url' => 'pictures/' . $file,
        'title' => 'Full Kitchen',
        'category' => 'kitchen',
        'description' => 'Fully Equipped Modern Kitchen',
        'display_order' => $order++,
        'is_active' => 1
    ];
}

if (empty($images_to_add)) {
    echo "No images to add. Please make sure your images are named with 'living' or 'kitchen' in the filename.\n";
    exit;
}

echo "Preparing to add/update " . count($images_to_add) . " images...\n\n";

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
    
    foreach ($images_to_add as $image) {
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
            echo "✓ Updated: {$image['image_url']} ({$image['category']})\n";
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
            echo "✓ Added: {$image['image_url']} ({$image['category']})\n";
            $inserted++;
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Successfully processed " . ($inserted + $updated) . " gallery images!\n";
    echo "- Added: $inserted new images\n";
    echo "- Updated: $updated existing images\n";
    
    // Verify final count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery_images WHERE is_active = 1");
    $result = $stmt->fetch();
    echo "\nFinal active images count: " . $result['count'] . "\n";
    echo "\n✓ Done! Refresh the gallery page to see the images.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

