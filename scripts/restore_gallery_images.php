<?php
/**
 * Restore Gallery Images from Pictures Folder
 * This script will scan the pictures folder and add/update gallery images
 */

require_once 'config/database.php';

// Common image patterns for living room and kitchen
$image_patterns = [
    'living-room' => [
        'patterns' => ['living', 'liv', 'lounge', 'sala'],
        'title' => 'Living Room',
        'description' => 'Spacious & Comfortable Living Space'
    ],
    'kitchen' => [
        'patterns' => ['kitchen', 'kit', 'cooking'],
        'title' => 'Full Kitchen',
        'description' => 'Fully Equipped Modern Kitchen'
    ]
];

// Scan pictures folder
$pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
$gallery_images = [];

if (is_dir($pictures_dir)) {
    $files = scandir($pictures_dir);
    $order = 1;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        // Check if it's an image file
        if (!preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $file)) continue;
        
        $file_lower = strtolower($file);
        $category = null;
        $title = '';
        $description = '';
        
        // Check which category this image belongs to
        foreach ($image_patterns as $cat => $info) {
            foreach ($info['patterns'] as $pattern) {
                if (strpos($file_lower, $pattern) !== false) {
                    $category = $cat;
                    $title = $info['title'];
                    $description = $info['description'];
                    break 2;
                }
            }
        }
        
        // If category found, add to gallery
        if ($category) {
            $gallery_images[] = [
                'image_url' => 'pictures/' . $file,
                'title' => $title,
                'category' => $category,
                'description' => $description,
                'display_order' => $order++,
                'is_active' => 1
            ];
        }
    }
}

if (empty($gallery_images)) {
    echo "No images found in pictures folder matching living room or kitchen patterns.\n";
    echo "\nPlease check:\n";
    echo "1. The pictures folder exists at: " . $pictures_dir . "\n";
    if (is_dir($pictures_dir)) {
        echo "2. Files in pictures folder:\n";
        $all_files = scandir($pictures_dir);
        foreach ($all_files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "   - $file\n";
            }
        }
    } else {
        echo "2. Pictures folder does not exist!\n";
    }
    exit;
}

echo "Found " . count($gallery_images) . " images to add/update:\n";
foreach ($gallery_images as $img) {
    echo "  - {$img['image_url']} ({$img['category']})\n";
}
echo "\n";

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
            echo "✓ Updated: {$image['image_url']}\n";
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
            echo "✓ Added: {$image['image_url']}\n";
            $inserted++;
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Successfully processed " . ($inserted + $updated) . " gallery images!\n";
    echo "- Added: $inserted new images\n";
    echo "- Updated: $updated existing images\n";
    echo "\n✓ Gallery images restored! You can now view them in the gallery page.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

