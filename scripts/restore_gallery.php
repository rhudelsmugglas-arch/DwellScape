<?php
/**
 * Restore All Gallery Images
 * This will add images for all categories including living room and kitchen
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Restore Gallery</title></head><body>";
echo "<h2>Restoring Gallery Images</h2>";
echo "<pre>";

// Check pictures folder and find actual files
$pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
$found_files = [];

if (is_dir($pictures_dir)) {
    $files = scandir($pictures_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $file)) {
            $found_files[] = $file;
        }
    }
    echo "Found " . count($found_files) . " image files in pictures folder.\n\n";
} else {
    echo "Pictures folder not found. Will use default paths.\n\n";
}

// Map found files to categories
$living_files = [];
$kitchen_files = [];

foreach ($found_files as $file) {
    $file_lower = strtolower($file);
    if (preg_match('/living|liv/i', $file_lower)) {
        $living_files[] = $file;
    } elseif (preg_match('/kitchen|kit/i', $file_lower)) {
        $kitchen_files[] = $file;
    }
}

// Build gallery images array
$gallery_images = [];
$order = 1;

// Add living room images (use found files or defaults)
if (!empty($living_files)) {
    foreach ($living_files as $file) {
        $gallery_images[] = [
            'image_url' => 'pictures/' . $file,
            'title' => 'Living Room',
            'category' => 'living-room',
            'description' => 'Spacious & Comfortable Living Space',
            'display_order' => $order++,
            'is_active' => 1
        ];
    }
} else {
    // Use default paths
    for ($i = 1; $i <= 4; $i++) {
        $gallery_images[] = [
            'image_url' => 'pictures/living' . $i . '.avif',
            'title' => 'Living Room',
            'category' => 'living-room',
            'description' => 'Spacious & Comfortable Living Space',
            'display_order' => $order++,
            'is_active' => 1
        ];
    }
}

// Add kitchen images
if (!empty($kitchen_files)) {
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
} else {
    // Use default paths
    for ($i = 1; $i <= 2; $i++) {
        $gallery_images[] = [
            'image_url' => 'pictures/kitchen' . $i . '.avif',
            'title' => 'Full Kitchen',
            'category' => 'kitchen',
            'description' => 'Fully Equipped Modern Kitchen',
            'display_order' => $order++,
            'is_active' => 1
        ];
    }
}

// Add other categories with placeholder images
$gallery_images[] = [
    'image_url' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?q=80&w=800&auto=format&fit=crop',
    'title' => 'Dining Area',
    'category' => 'dining',
    'description' => 'Elegant Dining Space',
    'display_order' => $order++,
    'is_active' => 1
];

$gallery_images[] = [
    'image_url' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?q=80&w=800&auto=format&fit=crop',
    'title' => 'Bedroom 1',
    'category' => 'bedroom1',
    'description' => 'Master Bedroom',
    'display_order' => $order++,
    'is_active' => 1
];

$gallery_images[] = [
    'image_url' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=800&auto=format&fit=crop',
    'title' => 'Bedroom 2',
    'category' => 'bedroom2',
    'description' => 'Cozy Guest Room',
    'display_order' => $order++,
    'is_active' => 1
];

$gallery_images[] = [
    'image_url' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?q=80&w=800&auto=format&fit=crop',
    'title' => 'Full Bathroom',
    'category' => 'bathroom',
    'description' => 'Modern & Clean',
    'display_order' => $order++,
    'is_active' => 1
];

$gallery_images[] = [
    'image_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop',
    'title' => 'Workplace',
    'category' => 'workplace',
    'description' => 'Productive Workspace',
    'display_order' => $order++,
    'is_active' => 1
];

$gallery_images[] = [
    'image_url' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?q=80&w=800&auto=format&fit=crop',
    'title' => 'Pool',
    'category' => 'pool',
    'description' => 'Resort-Style Pool',
    'display_order' => $order++,
    'is_active' => 1
];

$gallery_images[] = [
    'image_url' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=800&auto=format&fit=crop',
    'title' => 'Activity Area',
    'category' => 'activity',
    'description' => 'Recreation Space',
    'display_order' => $order++,
    'is_active' => 1
];

try {
    echo "Preparing to add/update " . count($gallery_images) . " gallery images...\n\n";
    
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
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM gallery_images WHERE is_active = 1 GROUP BY category ORDER BY category");
    $categories = $stmt->fetchAll();
    echo "\nImages by category:\n";
    foreach ($categories as $cat) {
        echo "  - {$cat['category']}: {$cat['count']} images\n";
    }
    
    echo "\n✓ Done! <a href='dashboard.php#gallery'>Click here to view the gallery</a>\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "</body></html>";
?>

