<?php
/**
 * Replace all AI/Google images with local images from pictures folder
 * Remove workplace section
 */
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Replace AI Images with Local</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".info{color:blue;}";
echo "table{border-collapse:collapse;width:100%;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#C3B091;color:white;}</style></head><body>";

echo "<h1>Replacing AI Images with Local Files</h1>";

try {
    // Step 1: Delete ALL entries with external URLs (AI/Google images)
    echo "<h2>Step 1: Removing all AI/Google images (external URLs)...</h2>";
    $deleteStmt = $pdo->prepare("DELETE FROM gallery_images WHERE image_url LIKE 'https://%' OR image_url LIKE 'http://%'");
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "<p class='success'>✓ Deleted {$deleted} external URL entries (AI/Google images)</p>";
    
    // Step 2: Delete ALL workplace entries
    echo "<h2>Step 2: Removing Workplace section...</h2>";
    $deleteStmt = $pdo->prepare("DELETE FROM gallery_images WHERE category = 'workplace' OR title = 'Workplace' OR title LIKE '%Workplace%' OR description LIKE '%Workspace%'");
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "<p class='success'>✓ Deleted {$deleted} Workplace entries</p>";
    
    // Step 3: Delete all Full Bathroom entries
    echo "<h2>Step 3: Removing Full Bathroom entries...</h2>";
    $deleteStmt = $pdo->prepare("DELETE FROM gallery_images WHERE title = 'Full Bathroom'");
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "<p class='success'>✓ Deleted {$deleted} Full Bathroom entries</p>";
    
    // Step 4: Ensure all local images are properly set up
    echo "<h2>Step 4: Setting up local images...</h2>";
    
    $images_to_add = [
        // Dining Area
        ['pictures/dining1.png', 'Dining Area', 'dining', 'Elegant Dining Space', 7],
        ['pictures/dining2.png', 'Dining Area', 'dining', 'Spacious Dining Room', 8],
        
        // Bedroom 1
        ['pictures/bedroom1-2.png', 'Bedroom 1', 'bedroom1', 'Master Bedroom', 9],
        
        // Bedroom 2
        ['pictures/bedroom2-1.png', 'Bedroom 2', 'bedroom2', 'Cozy Guest Room', 10],
        ['pictures/bedroom2-2.png', 'Bedroom 2', 'bedroom2', 'Comfortable Guest Space', 11],
        
        // Bathroom 1
        ['pictures/bathroom1.png', 'Bathroom 1', 'bathroom', 'Modern & Clean', 12],
        
        // Bathroom 2
        ['pictures/bathroom2.png', 'Bathroom 2', 'bathroom', 'Spacious & Elegant', 13],
        
        // Pool
        ['pictures/pool1.png', 'Pool', 'pool', 'Resort-Style Pool', 14],
    ];
    
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = ?");
    $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = ?, category = ?, description = ?, display_order = ?, is_active = 1 WHERE image_url = ?");
    
    $added = 0;
    $updated = 0;
    
    foreach ($images_to_add as $img) {
        list($image_url, $title, $category, $description, $display_order) = $img;
        
        // Check if file exists locally
        $local_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('pictures/', 'pictures' . DIRECTORY_SEPARATOR, $image_url);
        if (!file_exists($local_path)) {
            echo "<p class='error'>✗ File not found: {$image_url}</p>";
            continue;
        }
        
        $checkStmt->execute([$image_url]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Update existing
            $updateStmt->execute([$title, $category, $description, $display_order, $image_url]);
            echo "<p class='info'>- Updated: {$title} ({$image_url})</p>";
            $updated++;
        } else {
            // Insert new
            $insertStmt->execute([$image_url, $title, $category, $description, $display_order]);
            echo "<p class='success'>✓ Added: {$title} ({$image_url})</p>";
            $added++;
        }
    }
    
    // Step 5: Deactivate any remaining entries that aren't in our list
    echo "<h2>Step 5: Cleaning up other entries...</h2>";
    $valid_urls = array_column($images_to_add, 0);
    $placeholders = str_repeat('?,', count($valid_urls) - 1) . '?';
    $deactivateStmt = $pdo->prepare("UPDATE gallery_images SET is_active = 0 WHERE image_url NOT IN ({$placeholders}) AND (image_url LIKE 'https://%' OR image_url LIKE 'http://%' OR category = 'workplace' OR title = 'Workplace' OR title = 'Full Bathroom')");
    $deactivateStmt->execute($valid_urls);
    $deactivated = $deactivateStmt->rowCount();
    if ($deactivated > 0) {
        echo "<p class='info'>- Deactivated {$deactivated} unwanted entries</p>";
    }
    
    // Step 6: Show final gallery status
    echo "<h2>Step 6: Final Gallery Status</h2>";
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY display_order ASC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Category</th><th>Image URL</th><th>File Exists</th></tr>";
    
    $all_exist = true;
    foreach ($images as $image) {
        $file_exists = 'N/A';
        $status_class = '';
        
        if (preg_match('/^https?:\/\//', $image['image_url'])) {
            $file_exists = 'EXTERNAL URL';
            $status_class = 'error';
            $all_exist = false;
        } else {
            $local_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('pictures/', 'pictures' . DIRECTORY_SEPARATOR, $image['image_url']);
            if (file_exists($local_path)) {
                $file_exists = '✓ YES';
                $status_class = 'success';
            } else {
                $file_exists = '✗ NO';
                $status_class = 'error';
                $all_exist = false;
            }
        }
        
        echo "<tr>";
        echo "<td>{$image['id']}</td>";
        echo "<td>{$image['title']}</td>";
        echo "<td>{$image['category']}</td>";
        echo "<td style='font-family:monospace;font-size:11px;'>{$image['image_url']}</td>";
        echo "<td class='{$status_class}'>{$file_exists}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<p class='success'>✓ Added: {$added} new images</p>";
    echo "<p class='success'>✓ Updated: {$updated} existing images</p>";
    echo "<p class='success'>✓ All AI/Google images removed</p>";
    echo "<p class='success'>✓ Workplace section removed</p>";
    echo "<p class='success'>✓ Full Bathroom replaced with Bathroom 1 and Bathroom 2</p>";
    echo "<p><strong>Total active images:</strong> " . count($images) . "</p>";
    
    if ($all_exist) {
        echo "<p class='success'>✓ All local image files exist!</p>";
    } else {
        echo "<p class='error'>⚠ Some image files are missing or are external URLs</p>";
    }
    
    echo "<p><a href='dashboard.php#gallery' target='_blank'>View Gallery in Dashboard</a> | <a href='gallery.php' target='_blank'>View Gallery Page</a></p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

