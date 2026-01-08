<?php
/**
 * Update Bedroom 1 and Bedroom 2 Images in Gallery
 * This script updates the gallery_images table to use the correct bedroom images
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Update Bedroom Images</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;padding:10px;background:#d4edda;border-radius:5px;margin:10px 0;}";
echo ".info{color:blue;padding:10px;background:#d1ecf1;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;font-weight:bold;padding:10px;background:#f8d7da;border-radius:5px;margin:10px 0;}";
echo "h1{color:#333;} h2{color:#555;margin-top:20px;}</style></head><body>";

echo "<h1>Update Bedroom Images in Gallery</h1>";

try {
    // Check which bedroom images exist in pictures folder
    $pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
    $bedroom_files = [];
    
    if (is_dir($pictures_dir)) {
        $files = scandir($pictures_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $file_lower = strtolower($file);
                // Check for bedroom-related images
                if (preg_match('/bedroom|1bedroom/i', $file) && preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $file)) {
                    $bedroom_files[] = $file;
                }
            }
        }
    }
    
    echo "<h2>Found Bedroom Images:</h2>";
    echo "<ul>";
    foreach ($bedroom_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    
    // Update Bedroom 1
    echo "<h2>Updating Bedroom 1...</h2>";
    
    // First, deactivate any existing bedroom1 entries with wrong images
    $deactivateStmt = $pdo->prepare("UPDATE gallery_images SET is_active = 0 WHERE category = 'bedroom1' AND image_url NOT LIKE 'pictures/bedroom1%' AND image_url NOT LIKE 'pictures/1bedroom%'");
    $deactivateStmt->execute();
    
    // Check if bedroom1-2.png exists, if not try 1bedroom.png
    $bedroom1_file = 'bedroom1-2.png';
    if (!file_exists($pictures_dir . DIRECTORY_SEPARATOR . $bedroom1_file)) {
        $bedroom1_file = '1bedroom.png';
    }
    
    $bedroom1_path = 'pictures/' . $bedroom1_file;
    
    // Update or insert Bedroom 1
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE category = 'bedroom1' AND image_url = ?");
    $checkStmt->execute([$bedroom1_path]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 1', category = 'bedroom1', description = 'Master Bedroom', display_order = 9, is_active = 1 WHERE id = ?");
        $updateStmt->execute([$existing['id']]);
        echo "<p class='success'>✓ Updated Bedroom 1 to use $bedroom1_file</p>";
    } else {
        // Deactivate old bedroom1 entries
        $deactivateStmt = $pdo->prepare("UPDATE gallery_images SET is_active = 0 WHERE category = 'bedroom1'");
        $deactivateStmt->execute();
        
        // Insert new bedroom1
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bedroom 1', 'bedroom1', 'Master Bedroom', 9, 1)");
        $insertStmt->execute([$bedroom1_path]);
        echo "<p class='success'>✓ Added Bedroom 1 with $bedroom1_file</p>";
    }
    
    // Update Bedroom 2
    echo "<h2>Updating Bedroom 2...</h2>";
    
    // Deactivate any existing bedroom2 entries with wrong images
    $deactivateStmt = $pdo->prepare("UPDATE gallery_images SET is_active = 0 WHERE category = 'bedroom2' AND image_url NOT LIKE 'pictures/bedroom2%'");
    $deactivateStmt->execute();
    
    // Use bedroom2-1.png for the first bedroom2 image
    $bedroom2_file1 = 'bedroom2-1.png';
    $bedroom2_path1 = 'pictures/' . $bedroom2_file1;
    
    // Check and update/insert first bedroom2 image
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE category = 'bedroom2' AND image_url = ?");
    $checkStmt->execute([$bedroom2_path1]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 2', category = 'bedroom2', description = 'Cozy Guest Room', display_order = 10, is_active = 1 WHERE id = ?");
        $updateStmt->execute([$existing['id']]);
        echo "<p class='success'>✓ Updated Bedroom 2 to use $bedroom2_file1</p>";
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bedroom 2', 'bedroom2', 'Cozy Guest Room', 10, 1)");
        $insertStmt->execute([$bedroom2_path1]);
        echo "<p class='success'>✓ Added Bedroom 2 with $bedroom2_file1</p>";
    }
    
    // Add bedroom2-2.png as second bedroom2 image if it exists
    $bedroom2_file2 = 'bedroom2-2.png';
    $bedroom2_path2 = 'pictures/' . $bedroom2_file2;
    
    if (file_exists($pictures_dir . DIRECTORY_SEPARATOR . $bedroom2_file2)) {
        $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = ?");
        $checkStmt->execute([$bedroom2_path2]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 2', category = 'bedroom2', description = 'Comfortable Guest Space', display_order = 11, is_active = 1 WHERE id = ?");
            $updateStmt->execute([$existing['id']]);
            echo "<p class='success'>✓ Updated Bedroom 2 second image to use $bedroom2_file2</p>";
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bedroom 2', 'bedroom2', 'Comfortable Guest Space', 11, 1)");
            $insertStmt->execute([$bedroom2_path2]);
            echo "<p class='success'>✓ Added Bedroom 2 second image with $bedroom2_file2</p>";
        }
    }
    
    echo "<h2>Summary</h2>";
    echo "<p class='info'>Bedroom images have been updated in the gallery database.</p>";
    echo "<p><a href='gallery.php' style='color:#C3B091;text-decoration:underline;'>View Gallery</a> | <a href='admin/admin_gallery.php' style='color:#C3B091;text-decoration:underline;'>Admin Gallery Management</a></p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

