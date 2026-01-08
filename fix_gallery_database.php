<?php
/**
 * Fix gallery database - remove AI images, use local files only
 * - Remove all workplace entries
 * - Replace Full Bathroom with Bathroom 1 and Bathroom 2
 * - Update all entries to use local images from pictures folder
 */
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Fix Gallery Database</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".info{color:blue;}";
echo "table{border-collapse:collapse;width:100%;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#C3B091;color:white;}</style></head><body>";

echo "<h1>Fixing Gallery Database</h1>";

try {
    // Step 1: Remove all workplace entries
    echo "<h2>Step 1: Removing Workplace entries...</h2>";
    $deleteStmt = $pdo->prepare("DELETE FROM gallery_images WHERE category = 'workplace' OR title = 'Workplace' OR title LIKE '%Workplace%'");
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "<p class='success'>✓ Deleted {$deleted} Workplace entries</p>";
    
    // Step 2: Remove all entries with external URLs (AI images)
    echo "<h2>Step 2: Removing AI-generated images (external URLs)...</h2>";
    $deleteStmt = $pdo->prepare("DELETE FROM gallery_images WHERE image_url LIKE 'https://%' OR image_url LIKE 'http://%'");
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "<p class='success'>✓ Deleted {$deleted} external URL entries</p>";
    
    // Step 3: Remove all Full Bathroom entries
    echo "<h2>Step 3: Removing Full Bathroom entries...</h2>";
    $deleteStmt = $pdo->prepare("DELETE FROM gallery_images WHERE title = 'Full Bathroom'");
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "<p class='success'>✓ Deleted {$deleted} Full Bathroom entries</p>";
    
    // Step 4: Add/Update Dining Area entries
    echo "<h2>Step 4: Adding/Updating Dining Area entries...</h2>";
    
    // Check and add dining1.png
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/dining1.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES ('pictures/dining1.png', 'Dining Area', 'dining', 'Elegant Dining Space', 7, 1)");
        $insertStmt->execute();
        echo "<p class='success'>✓ Added Dining Area 1 (dining1.png)</p>";
    } else {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Dining Area', category = 'dining', description = 'Elegant Dining Space', is_active = 1 WHERE image_url = 'pictures/dining1.png'");
        $updateStmt->execute();
        echo "<p class='info'>- Updated Dining Area 1</p>";
    }
    
    // Check and add dining2.png
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/dining2.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES ('pictures/dining2.png', 'Dining Area', 'dining', 'Spacious Dining Room', 8, 1)");
        $insertStmt->execute();
        echo "<p class='success'>✓ Added Dining Area 2 (dining2.png)</p>";
    } else {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Dining Area', category = 'dining', description = 'Spacious Dining Room', is_active = 1 WHERE image_url = 'pictures/dining2.png'");
        $updateStmt->execute();
        echo "<p class='info'>- Updated Dining Area 2</p>";
    }
    
    // Step 5: Add/Update Bedroom 1
    echo "<h2>Step 5: Adding/Updating Bedroom 1...</h2>";
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/bedroom1-2.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES ('pictures/bedroom1-2.png', 'Bedroom 1', 'bedroom1', 'Master Bedroom', 9, 1)");
        $insertStmt->execute();
        echo "<p class='success'>✓ Added Bedroom 1 (bedroom1-2.png)</p>";
    } else {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 1', category = 'bedroom1', description = 'Master Bedroom', is_active = 1 WHERE image_url = 'pictures/bedroom1-2.png'");
        $updateStmt->execute();
        echo "<p class='info'>- Updated Bedroom 1</p>";
    }
    
    // Step 6: Add/Update Bedroom 2
    echo "<h2>Step 6: Adding/Updating Bedroom 2...</h2>";
    
    // bedroom2-1.png
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/bedroom2-1.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES ('pictures/bedroom2-1.png', 'Bedroom 2', 'bedroom2', 'Cozy Guest Room', 10, 1)");
        $insertStmt->execute();
        echo "<p class='success'>✓ Added Bedroom 2 (bedroom2-1.png)</p>";
    } else {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 2', category = 'bedroom2', description = 'Cozy Guest Room', is_active = 1 WHERE image_url = 'pictures/bedroom2-1.png'");
        $updateStmt->execute();
        echo "<p class='info'>- Updated Bedroom 2 (bedroom2-1.png)</p>";
    }
    
    // bedroom2-2.png
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/bedroom2-2.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES ('pictures/bedroom2-2.png', 'Bedroom 2', 'bedroom2', 'Comfortable Guest Space', 11, 1)");
        $insertStmt->execute();
        echo "<p class='success'>✓ Added Bedroom 2 (bedroom2-2.png)</p>";
    } else {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 2', category = 'bedroom2', description = 'Comfortable Guest Space', is_active = 1 WHERE image_url = 'pictures/bedroom2-2.png'");
        $updateStmt->execute();
        echo "<p class='info'>- Updated Bedroom 2 (bedroom2-2.png)</p>";
    }
    
    // Step 7: Add Bathroom 1 and Bathroom 2
    echo "<h2>Step 7: Adding Bathroom 1 and Bathroom 2...</h2>";
    
    // Bathroom 1
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/bathroom1.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES ('pictures/bathroom1.png', 'Bathroom 1', 'bathroom', 'Modern & Clean', 12, 1)");
        $insertStmt->execute();
        echo "<p class='success'>✓ Added Bathroom 1 (bathroom1.png)</p>";
    } else {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bathroom 1', category = 'bathroom', description = 'Modern & Clean', is_active = 1 WHERE image_url = 'pictures/bathroom1.png'");
        $updateStmt->execute();
        echo "<p class='info'>- Updated Bathroom 1</p>";
    }
    
    // Bathroom 2
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/bathroom2.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES ('pictures/bathroom2.png', 'Bathroom 2', 'bathroom', 'Spacious & Elegant', 13, 1)");
        $insertStmt->execute();
        echo "<p class='success'>✓ Added Bathroom 2 (bathroom2.png)</p>";
    } else {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bathroom 2', category = 'bathroom', description = 'Spacious & Elegant', is_active = 1 WHERE image_url = 'pictures/bathroom2.png'");
        $updateStmt->execute();
        echo "<p class='info'>- Updated Bathroom 2</p>";
    }
    
    // Step 8: Update Pool to use local image
    echo "<h2>Step 8: Updating Pool...</h2>";
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE category = 'pool'");
    $checkStmt->execute();
    $pool = $checkStmt->fetch();
    if ($pool) {
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET image_url = 'pictures/pool1.png', title = 'Pool', category = 'pool', description = 'Resort-Style Pool', is_active = 1 WHERE id = ?");
        $updateStmt->execute([$pool['id']]);
        echo "<p class='success'>✓ Updated Pool to use pool1.png</p>";
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES ('pictures/pool1.png', 'Pool', 'pool', 'Resort-Style Pool', 14, 1)");
        $insertStmt->execute();
        echo "<p class='success'>✓ Added Pool (pool1.png)</p>";
    }
    
    // Step 9: Show final gallery status
    echo "<h2>Step 9: Final Gallery Status</h2>";
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY display_order ASC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Category</th><th>Image URL</th><th>File Exists</th></tr>";
    
    foreach ($images as $image) {
        $file_exists = 'N/A';
        if (!preg_match('/^https?:\/\//', $image['image_url'])) {
            $local_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('pictures/', 'pictures' . DIRECTORY_SEPARATOR, $image['image_url']);
            $file_exists = file_exists($local_path) ? '✓ YES' : '✗ NO';
        }
        
        echo "<tr>";
        echo "<td>{$image['id']}</td>";
        echo "<td>{$image['title']}</td>";
        echo "<td>{$image['category']}</td>";
        echo "<td style='font-family:monospace;font-size:11px;'>{$image['image_url']}</td>";
        echo "<td>{$file_exists}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<p class='success'>✓ Gallery database fixed successfully!</p>";
    echo "<p class='success'>✓ All AI-generated images removed</p>";
    echo "<p class='success'>✓ Workplace section removed</p>";
    echo "<p class='success'>✓ Full Bathroom replaced with Bathroom 1 and Bathroom 2</p>";
    echo "<p class='success'>✓ All images now use local files from pictures folder</p>";
    echo "<p><strong>Total active images:</strong> " . count($images) . "</p>";
    echo "<p><a href='dashboard.php#gallery'>View Gallery</a> | <a href='gallery.php'>View Gallery Page</a></p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

