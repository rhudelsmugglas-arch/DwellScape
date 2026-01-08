<?php
/**
 * Fix Bedroom Images in Gallery Database
 * Directly updates bedroom1 and bedroom2 to use local images from pictures folder
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Bedroom Images</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
        .error { color: red; font-weight: bold; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 20px; }
        a { color: #C3B091; text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Fix Bedroom Images in Gallery</h1>

<?php
try {
    // Step 1: Deactivate all existing bedroom1 and bedroom2 entries
    echo "<h2>Step 1: Deactivating old bedroom images...</h2>";
    $deactivate1 = $pdo->prepare("UPDATE gallery_images SET is_active = 0 WHERE category = 'bedroom1'");
    $deactivate1->execute();
    $count1 = $deactivate1->rowCount();
    echo "<p class='info'>Deactivated $count1 Bedroom 1 entries</p>";
    
    $deactivate2 = $pdo->prepare("UPDATE gallery_images SET is_active = 0 WHERE category = 'bedroom2'");
    $deactivate2->execute();
    $count2 = $deactivate2->rowCount();
    echo "<p class='info'>Deactivated $count2 Bedroom 2 entries</p>";
    
    // Step 2: Update or Insert Bedroom 1
    echo "<h2>Step 2: Setting Bedroom 1 image...</h2>";
    $bedroom1_path = 'pictures/bedroom1-2.png';
    
    // Check if this image already exists in database
    $check1 = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = ?");
    $check1->execute([$bedroom1_path]);
    $existing1 = $check1->fetch();
    
    if ($existing1) {
        // Update existing entry
        $update1 = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 1', category = 'bedroom1', description = 'Master Bedroom', display_order = 9, is_active = 1 WHERE id = ?");
        $update1->execute([$existing1['id']]);
        echo "<p class='success'>✓ Updated Bedroom 1 to use bedroom1-2.png</p>";
    } else {
        // Insert new entry
        $insert1 = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bedroom 1', 'bedroom1', 'Master Bedroom', 9, 1)");
        $insert1->execute([$bedroom1_path]);
        echo "<p class='success'>✓ Added Bedroom 1 with bedroom1-2.png</p>";
    }
    
    // Step 3: Update or Insert Bedroom 2 (first image)
    echo "<h2>Step 3: Setting Bedroom 2 images...</h2>";
    $bedroom2_path1 = 'pictures/bedroom2-1.png';
    
    // Check if this image already exists in database
    $check2 = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = ?");
    $check2->execute([$bedroom2_path1]);
    $existing2 = $check2->fetch();
    
    if ($existing2) {
        // Update existing entry
        $update2 = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 2', category = 'bedroom2', description = 'Cozy Guest Room', display_order = 10, is_active = 1 WHERE id = ?");
        $update2->execute([$existing2['id']]);
        echo "<p class='success'>✓ Updated Bedroom 2 to use bedroom2-1.png</p>";
    } else {
        // Insert new entry
        $insert2 = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bedroom 2', 'bedroom2', 'Cozy Guest Room', 10, 1)");
        $insert2->execute([$bedroom2_path1]);
        echo "<p class='success'>✓ Added Bedroom 2 with bedroom2-1.png</p>";
    }
    
    // Step 4: Add Bedroom 2 second image if it exists
    $bedroom2_path2 = 'pictures/bedroom2-2.png';
    $pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
    
    if (file_exists($pictures_dir . DIRECTORY_SEPARATOR . 'bedroom2-2.png')) {
        $check3 = $pdo->prepare("SELECT id FROM gallery_images WHERE image_url = ?");
        $check3->execute([$bedroom2_path2]);
        $existing3 = $check3->fetch();
        
        if ($existing3) {
            $update3 = $pdo->prepare("UPDATE gallery_images SET title = 'Bedroom 2', category = 'bedroom2', description = 'Comfortable Guest Space', display_order = 11, is_active = 1 WHERE id = ?");
            $update3->execute([$existing3['id']]);
            echo "<p class='success'>✓ Updated Bedroom 2 second image to use bedroom2-2.png</p>";
        } else {
            $insert3 = $pdo->prepare("INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active) VALUES (?, 'Bedroom 2', 'bedroom2', 'Comfortable Guest Space', 11, 1)");
            $insert3->execute([$bedroom2_path2]);
            echo "<p class='success'>✓ Added Bedroom 2 second image with bedroom2-2.png</p>";
        }
    }
    
    // Step 5: Verify the updates
    echo "<h2>Step 5: Verifying updates...</h2>";
    $verify1 = $pdo->prepare("SELECT * FROM gallery_images WHERE category = 'bedroom1' AND is_active = 1");
    $verify1->execute();
    $bedroom1_images = $verify1->fetchAll();
    
    $verify2 = $pdo->prepare("SELECT * FROM gallery_images WHERE category = 'bedroom2' AND is_active = 1");
    $verify2->execute();
    $bedroom2_images = $verify2->fetchAll();
    
    echo "<p class='info'>Active Bedroom 1 images: " . count($bedroom1_images) . "</p>";
    foreach ($bedroom1_images as $img) {
        echo "<p>- " . htmlspecialchars($img['image_url']) . " (" . htmlspecialchars($img['title']) . ")</p>";
    }
    
    echo "<p class='info'>Active Bedroom 2 images: " . count($bedroom2_images) . "</p>";
    foreach ($bedroom2_images as $img) {
        echo "<p>- " . htmlspecialchars($img['image_url']) . " (" . htmlspecialchars($img['title']) . ")</p>";
    }
    
    echo "<h2>✅ Complete!</h2>";
    echo "<p class='success'>Bedroom images have been updated successfully.</p>";
    echo "<p><a href='dashboard.php#gallery'>View Gallery in Dashboard</a> | <a href='gallery.php'>View Gallery Page</a> | <a href='admin/admin_gallery.php'>Admin Gallery Management</a></p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

</body>
</html>

