<?php
/**
 * Add Activity Images to Gallery
 * This script adds activity1.png through activity4.png to the gallery_images table
 */

require_once 'config/database.php';

$activity_images = [
    [
        'image_url' => 'pictures/activity1.png',
        'title' => 'Activity Area',
        'category' => 'activity',
        'description' => 'Karaoke & Entertainment',
        'display_order' => 15,
        'is_active' => 1
    ],
    [
        'image_url' => 'pictures/activity2.png',
        'title' => 'Activity Area',
        'category' => 'activity',
        'description' => 'Recreation Space',
        'display_order' => 16,
        'is_active' => 1
    ],
    [
        'image_url' => 'pictures/activity3.png',
        'title' => 'Activity Area',
        'category' => 'activity',
        'description' => 'Game & Entertainment Room',
        'display_order' => 17,
        'is_active' => 1
    ],
    [
        'image_url' => 'pictures/activity4.png',
        'title' => 'Activity Area',
        'category' => 'activity',
        'description' => 'Fun & Games',
        'display_order' => 18,
        'is_active' => 1
    ]
];

try {
    // First, deactivate any existing activity images with different URLs
    $deactivateStmt = $pdo->prepare("
        UPDATE gallery_images 
        SET is_active = 0 
        WHERE category = 'activity' 
        AND image_url NOT IN (?, ?, ?, ?)
    ");
    $urls = array_column($activity_images, 'image_url');
    $deactivateStmt->execute($urls);
    
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
    
    foreach ($activity_images as $image) {
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
            $inserted++;
        }
    }
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Activity Images Added</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #7a6a4f; }
        .success { color: #10b981; font-weight: bold; }
        .info { color: #6b7280; margin-top: 20px; }
        a { color: #C3B091; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Activity Images Added Successfully!</h1>
        <p class='success'>Successfully processed " . ($inserted + $updated) . " activity images!</p>
        <ul>
            <li>Inserted: <strong>$inserted</strong> new images</li>
            <li>Updated: <strong>$updated</strong> existing images</li>
        </ul>
        <div class='info'>
            <p>The following activity images have been added to the gallery:</p>
            <ul>
                <li>Activity 1: Karaoke & Entertainment (activity1.png)</li>
                <li>Activity 2: Recreation Space (activity2.png)</li>
                <li>Activity 3: Game & Entertainment Room (activity3.png)</li>
                <li>Activity 4: Fun & Games (activity4.png)</li>
            </ul>
            <p><a href='gallery.php'>View Gallery</a> | <a href='dashboard.php'>Go to Dashboard</a></p>
        </div>
    </div>
</body>
</html>";
    
} catch(PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Error</h1>
        <p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>
        <p><a href='dashboard.php'>Go to Dashboard</a></p>
    </div>
</body>
</html>";
}

