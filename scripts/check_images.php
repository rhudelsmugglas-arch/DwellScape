<?php
/**
 * Image Checker - Check which images are missing
 * Run this file in your browser to see which images need to be added
 */

$base_dir = __DIR__;
$missing_images = [];
$found_images = [];

// Required images
$required_images = [
    'pictures' => [
        'dashboard1.png' => 'Main building hero image',
        'dashboard2.png' => 'Pool area image',
        'dashboard3.png' => 'Playground image',
        'dashboard4.png' => 'Billiards/Activity room image',
        'virtual.jpg' => 'Virtual tour preview image',
        'bookings.png' => 'Bookings page hero image',
        'boy.png' => 'Default male profile picture',
        'woman.png' => 'Default female profile picture',
    ],
    'assets/img' => [
        'dwellscape-logo.png' => 'Website logo'
    ]
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Image Checker - Dwellscape</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #7a6a4f; }
        h2 { color: #5a4d3a; margin-top: 30px; border-bottom: 2px solid #C3B091; padding-bottom: 10px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .found { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .missing { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; margin-top: 20px; padding: 15px; border-radius: 5px; }
        ul { list-style: none; padding: 0; }
        li { padding: 8px; margin: 5px 0; }
        .path { font-family: monospace; color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Dwellscape Image Checker</h1>
        <p>This tool checks which images are missing from your project.</p>";

foreach ($required_images as $folder => $images) {
    $folder_path = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
    
    echo "<h2>📁 {$folder}/</h2>";
    
    foreach ($images as $filename => $description) {
        $file_path = $folder_path . DIRECTORY_SEPARATOR . $filename;
        $relative_path = $folder . '/' . $filename;
        
        if (file_exists($file_path)) {
            $file_size = filesize($file_path);
            $file_size_kb = round($file_size / 1024, 2);
            echo "<div class='status found'>";
            echo "✅ <strong>{$filename}</strong> - {$description}<br>";
            echo "<span class='path'>Path: {$relative_path} ({$file_size_kb} KB)</span>";
            echo "</div>";
            $found_images[] = $relative_path;
        } else {
            echo "<div class='status missing'>";
            echo "❌ <strong>{$filename}</strong> - {$description}<br>";
            echo "<span class='path'>Missing: {$relative_path}</span>";
            echo "</div>";
            $missing_images[] = $relative_path;
        }
    }
}

echo "<div class='info'>";
echo "<h3>📊 Summary</h3>";
echo "<p><strong>Found:</strong> " . count($found_images) . " images</p>";
echo "<p><strong>Missing:</strong> " . count($missing_images) . " images</p>";

if (count($missing_images) > 0) {
    echo "<h3>⚠️ Action Required</h3>";
    echo "<p>Please add the following missing images to your project:</p>";
    echo "<ul>";
    foreach ($missing_images as $img) {
        echo "<li>• {$img}</li>";
    }
    echo "</ul>";
    echo "<p><strong>Note:</strong> Images can be in PNG, JPG, JPEG, GIF, WebP, or AVIF format.</p>";
} else {
    echo "<h3>✅ All Required Images Found!</h3>";
    echo "<p>Your project has all the required images. If images are still not displaying, check:</p>";
    echo "<ul>";
    echo "<li>• File permissions (images should be readable)</li>";
    echo "<li>• Web server configuration</li>";
    echo "<li>• Browser console for 404 errors</li>";
    echo "</ul>";
}

echo "</div>";
echo "</div></body></html>";
?>

