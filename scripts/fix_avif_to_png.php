<?php
/**
 * Fix AVIF to PNG - Update gallery images to use PNG instead of missing AVIF files
 */

require_once 'config/database.php';

try {
    // Update all gallery images that reference .avif files to use .png instead
    $stmt = $pdo->prepare("
        UPDATE gallery_images 
        SET image_url = REPLACE(image_url, '.avif', '.png')
        WHERE image_url LIKE '%.avif'
    ");
    
    $stmt->execute();
    $updated_count = $stmt->rowCount();
    
    echo "✅ Successfully updated $updated_count gallery image(s) from .avif to .png\n";
    echo "\nUpdated images:\n";
    
    // Show what was updated
    $stmt = $pdo->prepare("
        SELECT id, image_url, title 
        FROM gallery_images 
        WHERE image_url LIKE '%.png'
        ORDER BY id
    ");
    $stmt->execute();
    $updated_images = $stmt->fetchAll();
    
    foreach ($updated_images as $img) {
        echo "  - ID {$img['id']}: {$img['title']} → {$img['image_url']}\n";
    }
    
    echo "\n✅ All AVIF references have been updated to PNG!\n";
    echo "The 404 errors should now be fixed.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

