<?php
/**
 * Update Living Room Images from PNG to JPG
 * This script updates the database to use JPG files instead of PNG for living room images
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Update Living Room Images</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo "h2{color:#7a6a4f;}pre{background:#f8f9fa;padding:15px;border-radius:5px;overflow-x:auto;}";
echo ".success{color:#28a745;}.warning{color:#ffc107;}.error{color:#dc3545;}</style></head><body>";
echo "<div class='container'><h2>Update Living Room Images (PNG → JPG)</h2><pre>";

try {
    // First, show current state
    echo "📋 Current living room images in database:\n";
    $checkStmt = $pdo->query("SELECT id, image_url, title FROM gallery_images WHERE category = 'living-room' ORDER BY display_order");
    $current_images = $checkStmt->fetchAll();
    
    if (empty($current_images)) {
        echo "⚠️  No living room images found in database!\n";
    } else {
        foreach ($current_images as $img) {
            echo "  - ID {$img['id']}: {$img['image_url']}\n";
        }
    }
    echo "\n";
    
    // Update living room images from .png to .jpg (handle various path formats)
    $updates = [
        'pictures/living1.png' => 'pictures/living1.jpg',
        'pictures/living2.png' => 'pictures/living2.jpg',
        'pictures/living3.png' => 'pictures/living3.jpg',
        'pictures/living4.png' => 'pictures/living4.jpg',
        '../pictures/living1.png' => 'pictures/living1.jpg',
        '../pictures/living2.png' => 'pictures/living2.jpg',
        '../pictures/living3.png' => 'pictures/living3.jpg',
        '../pictures/living4.png' => 'pictures/living4.jpg',
    ];
    
    $stmt = $pdo->prepare("UPDATE gallery_images SET image_url = ? WHERE image_url = ?");
    $updated_count = 0;
    
    foreach ($updates as $old_url => $new_url) {
        $stmt->execute([$new_url, $old_url]);
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>✅ Updated: $old_url → $new_url</span>\n";
            $updated_count++;
        }
    }
    
    // Also try a bulk update using REPLACE for any remaining .png files
    $bulkStmt = $pdo->prepare("
        UPDATE gallery_images 
        SET image_url = REPLACE(image_url, '.png', '.jpg')
        WHERE category = 'living-room' 
        AND image_url LIKE '%living%.png'
    ");
    $bulkStmt->execute();
    $bulk_updated = $bulkStmt->rowCount();
    
    if ($bulk_updated > 0) {
        echo "<span class='success'>✅ Bulk updated $bulk_updated additional living room image(s)</span>\n";
        $updated_count += $bulk_updated;
    }
    
    echo "\n=== Summary ===\n";
    if ($updated_count > 0) {
        echo "<span class='success'>✅ Successfully updated $updated_count living room image(s) from PNG to JPG</span>\n";
    } else {
        echo "<span class='warning'>⚠️  No images were updated. They may already be using JPG format.</span>\n";
    }
    
    // Show final state
    echo "\n📋 Final living room images in database:\n";
    $finalStmt = $pdo->query("SELECT id, image_url, title FROM gallery_images WHERE category = 'living-room' ORDER BY display_order");
    $final_images = $finalStmt->fetchAll();
    
    foreach ($final_images as $img) {
        $status = (strpos($img['image_url'], '.jpg') !== false) ? '✅' : '⚠️';
        echo "  $status ID {$img['id']}: {$img['image_url']}\n";
    }
    
    echo "\n<span class='success'>✅ The gallery should now display the JPG images correctly!</span>\n";
    echo "\n💡 <strong>Important:</strong> Make sure the JPG files are deployed to Railway:\n";
    echo "   - pictures/living1.jpg\n";
    echo "   - pictures/living2.jpg\n";
    echo "   - pictures/living3.jpg\n";
    echo "   - pictures/living4.jpg\n";
    
} catch (PDOException $e) {
    echo "<span class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

echo "</pre></div></body></html>";
?>
