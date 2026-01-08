<?php
/**
 * Update gallery images to use correct local files
 * - Fix dining area, bedroom 1, bedroom 2
 * - Split Full Bathroom into Bathroom 1 and Bathroom 2
 * - Remove Workplace
 */
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Update Gallery Images</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".info{color:blue;}";
echo "pre{background:white;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body>";

echo "<h1>Updating Gallery Images</h1>";

try {
    // 1. Remove Workplace entries
    echo "<h2>1. Removing Workplace entries...</h2>";
    $deleteStmt = $pdo->prepare("DELETE FROM gallery_images WHERE category = 'workplace' OR title = 'Workplace'");
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "<p class='success'>✓ Deleted {$deleted} Workplace entries</p>";
    
    // 2. Update Dining Area to use local images
    echo "<h2>2. Updating Dining Area...</h2>";
    $updateStmt = $pdo->prepare("UPDATE gallery_images SET image_url = ? WHERE category = 'dining' AND title = 'Dining Area' LIMIT 1");
    
    // Update first dining area entry to use dining1.png
    $updateStmt->execute(['pictures/dining1.png']);
    echo "<p class='success'>✓ Updated Dining Area to use dining1.png</p>";
    
    // Check if dining2 exists, if not add it
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/dining2.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Dining Area', 'dining', 'Elegant Dining Space', 7, 1)");
        $insertStmt->execute(['pictures/dining2.png']);
        echo "<p class='success'>✓ Added Dining Area 2 (dining2.png)</p>";
    } else {
        echo "<p class='info'>- Dining Area 2 already exists</p>";
    }
    
    // 3. Update Bedroom 1 to use local image
    echo "<h2>3. Updating Bedroom 1...</h2>";
    $updateStmt = $pdo->prepare("UPDATE gallery_images SET image_url = ? WHERE category = 'bedroom1' AND title = 'Bedroom 1' LIMIT 1");
    $updateStmt->execute(['pictures/bedroom1-2.png']);
    echo "<p class='success'>✓ Updated Bedroom 1 to use bedroom1-2.png</p>";
    
    // 4. Update Bedroom 2 to use local images
    echo "<h2>4. Updating Bedroom 2...</h2>";
    $updateStmt = $pdo->prepare("UPDATE gallery_images SET image_url = ? WHERE category = 'bedroom2' AND title = 'Bedroom 2' LIMIT 1");
    $updateStmt->execute(['pictures/bedroom2-1.png']);
    echo "<p class='success'>✓ Updated Bedroom 2 to use bedroom2-1.png</p>";
    
    // Check if bedroom2-2 exists, if not add it
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/bedroom2-2.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bedroom 2', 'bedroom2', 'Cozy Guest Room', 9, 1)");
        $insertStmt->execute(['pictures/bedroom2-2.png']);
        echo "<p class='success'>✓ Added Bedroom 2 second image (bedroom2-2.png)</p>";
    } else {
        echo "<p class='info'>- Bedroom 2 second image already exists</p>";
    }
    
    // 5. Replace Full Bathroom with Bathroom 1 and Bathroom 2
    echo "<h2>5. Replacing Full Bathroom with Bathroom 1 and Bathroom 2...</h2>";
    
    // Delete existing Full Bathroom entries
    $deleteStmt = $pdo->prepare("DELETE FROM gallery_images WHERE category = 'bathroom' AND title = 'Full Bathroom'");
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();
    echo "<p class='info'>- Deleted {$deleted} Full Bathroom entries</p>";
    
    // Add Bathroom 1
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/bathroom1.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bathroom 1', 'bathroom', 'Modern & Clean', 10, 1)");
        $insertStmt->execute(['pictures/bathroom1.png']);
        echo "<p class='success'>✓ Added Bathroom 1 (bathroom1.png)</p>";
    } else {
        // Update existing
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bathroom 1', category = 'bathroom', description = 'Modern & Clean' WHERE image_url = 'pictures/bathroom1.png'");
        $updateStmt->execute();
        echo "<p class='success'>✓ Updated Bathroom 1</p>";
    }
    
    // Add Bathroom 2
    $checkStmt = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = 'pictures/bathroom2.png'");
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bathroom 2', 'bathroom', 'Spacious & Elegant', 11, 1)");
        $insertStmt->execute(['pictures/bathroom2.png']);
        echo "<p class='success'>✓ Added Bathroom 2 (bathroom2.png)</p>";
    } else {
        // Update existing
        $updateStmt = $pdo->prepare("UPDATE gallery_images SET title = 'Bathroom 2', category = 'bathroom', description = 'Spacious & Elegant' WHERE image_url = 'pictures/bathroom2.png'");
        $updateStmt->execute();
        echo "<p class='success'>✓ Updated Bathroom 2</p>";
    }
    
    // 6. Verify all images exist
    echo "<h2>6. Verifying images...</h2>";
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY display_order ASC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $missing = [];
    foreach ($images as $image) {
        if (!preg_match('/^https?:\/\//', $image['image_url'])) {
            $local_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('pictures/', 'pictures' . DIRECTORY_SEPARATOR, $image['image_url']);
            if (!file_exists($local_path)) {
                $missing[] = $image['image_url'];
            }
        }
    }
    
    if (empty($missing)) {
        echo "<p class='success'>✓ All local images exist!</p>";
    } else {
        echo "<p class='error'>✗ Missing images:</p><ul>";
        foreach ($missing as $img) {
            echo "<li>{$img}</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<p class='success'>✓ Gallery images updated successfully!</p>";
    echo "<p><a href='dashboard.php#gallery'>View Gallery</a> | <a href='gallery.php'>View Gallery Page</a></p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

