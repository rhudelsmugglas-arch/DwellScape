<?php
/**
 * Add All Gallery Images to Database
 * This script will add images for all categories
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Adding Gallery Images</h2>";
echo "<pre>";

// Check pictures folder
$pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
$all_image_files = [];

if (is_dir($pictures_dir)) {
    $files = scandir($pictures_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $file)) {
            $all_image_files[] = $file;
        }
    }
    echo "Found " . count($all_image_files) . " image files in pictures folder:\n";
    foreach ($all_image_files as $file) {
        echo "  - $file\n";
    }
    echo "\n";
} else {
    echo "WARNING: Pictures folder not found at: $pictures_dir\n";
    echo "Creating default images with placeholder paths...\n\n";
}

// Define all gallery images with proper paths
$gallery_images = [
    // Living Room images - try to find actual files or use defaults
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
        'image_url' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?q=80&w=800&auto=format&fit=crop',
        'title' => 'Dining Area',
        'category' => 'dining',
        'description' => 'Elegant Dining Space',
        'display_order' => 7,
        'is_active' => 1
    ],
    // Bedroom 1
    [
        'image_url' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop',
        'title' => 'Bedroom 1',
        'category' => 'bedroom1',
        'description' => 'Master Bedroom',
        'display_order' => 8,
        'is_active' => 1
    ],
    // Bedroom 2
    [
        'image_url' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=800&auto=format&fit=crop',
        'title' => 'Bedroom 2',
        'category' => 'bedroom2',
        'description' => 'Cozy Guest Room',
        'display_order' => 9,
        'is_active' => 1
    ],
    // Bathroom
    [
        'image_url' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?q=80&w=800&auto=format&fit=crop',
        'title' => 'Full Bathroom',
        'category' => 'bathroom',
        'description' => 'Modern & Clean',
        'display_order' => 10,
        'is_active' => 1
    ],
    // Workplace
    [
        'image_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop',
        'title' => 'Workplace',
        'category' => 'workplace',
        'description' => 'Productive Workspace',
        'display_order' => 11,
        'is_active' => 1
    ],
    // Pool
    [
        'image_url' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?q=80&w=800&auto=format&fit=crop',
        'title' => 'Pool',
        'category' => 'pool',
        'description' => 'Resort-Style Pool',
        'display_order' => 12,
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
    // First, let's check what's currently in the database
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery_images");
    $result = $stmt->fetch();
    echo "Current images in database: " . $result['count'] . "\n\n";
    
    // Delete all existing images to start fresh (optional - comment out if you want to keep existing)
    // $pdo->exec("DELETE FROM gallery_images");
    // echo "Cleared existing images.\n\n";
    
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
    $skipped = 0;
    
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
    
    // Show images by category
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM gallery_images WHERE is_active = 1 GROUP BY category");
    $categories = $stmt->fetchAll();
    echo "\nImages by category:\n";
    foreach ($categories as $cat) {
        echo "  - {$cat['category']}: {$cat['count']} images\n";
    }
    
    echo "\n✓ Done! Refresh the gallery page to see the images.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

