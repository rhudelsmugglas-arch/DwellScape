<?php
/**
 * Update Living Room Images from PNG to JPG
 * This script updates the database to use JPG files instead of PNG for living room images
 */

require_once 'config/database.php';

try {
    // Update living room images from .png to .jpg
    $updates = [
        'pictures/living1.png' => 'pictures/living1.jpg',
        'pictures/living2.png' => 'pictures/living2.jpg',
        'pictures/living3.png' => 'pictures/living3.jpg',
        'pictures/living4.png' => 'pictures/living4.jpg',
    ];
    
    $stmt = $pdo->prepare("UPDATE gallery_images SET image_url = ? WHERE image_url = ?");
    $updated_count = 0;
    
    foreach ($updates as $old_url => $new_url) {
        $stmt->execute([$new_url, $old_url]);
        if ($stmt->rowCount() > 0) {
            echo "✅ Updated: $old_url → $new_url\n";
            $updated_count++;
        } else {
            echo "⚠️  No record found for: $old_url\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Successfully updated $updated_count living room image(s) from PNG to JPG\n";
    echo "\nThe gallery should now display the JPG images correctly!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
