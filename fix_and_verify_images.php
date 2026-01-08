<?php
/**
 * Comprehensive script to fix gallery image paths and verify they load correctly
 */
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Fix & Verify Gallery Images</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".info{color:blue;}";
echo ".warning{color:orange;}";
echo "table{border-collapse:collapse;width:100%;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#C3B091;color:white;}";
echo "img{max-width:150px;max-height:150px;object-fit:cover;border:2px solid #ddd;border-radius:8px;}</style></head><body>";

echo "<h1>Fix & Verify Gallery Images</h1>";

try {
    // Get all active gallery images
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found <strong>" . count($images) . "</strong> active gallery images in database.</p>";
    
    // Get all files in pictures folder
    $pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
    $available_files = [];
    if (is_dir($pictures_dir)) {
        $dir_files = scandir($pictures_dir);
        foreach ($dir_files as $file) {
            if ($file !== '.' && $file !== '..') {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
                    $available_files[strtolower($file)] = $file; // Store with lowercase key for case-insensitive lookup
                    $available_files[strtolower(pathinfo($file, PATHINFO_FILENAME))] = $file; // Also index by base name
                }
            }
        }
    }
    
    echo "<p>Found <strong>" . count($available_files) . "</strong> image files in pictures folder.</p>";
    echo "<hr>";
    
    $updated = 0;
    $not_found = 0;
    $updateStmt = $pdo->prepare("UPDATE gallery_images SET image_url = ? WHERE id = ?");
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Old Path</th><th>New Path</th><th>Status</th><th>Preview</th></tr>";
    
    foreach ($images as $image) {
        $raw_url = trim($image['image_url']);
        $current_filename = basename($raw_url);
        $base_name = strtolower(pathinfo($current_filename, PATHINFO_FILENAME));
        
        // Try to find matching file
        $found_file = null;
        
        // Direct filename match (case-insensitive)
        if (isset($available_files[strtolower($current_filename)])) {
            $found_file = $available_files[strtolower($current_filename)];
        }
        // Base name match (case-insensitive)
        elseif (isset($available_files[$base_name])) {
            $found_file = $available_files[$base_name];
        }
        // Try to find by scanning (for files with spaces or special chars)
        else {
            foreach ($available_files as $key => $file) {
                $file_base = strtolower(pathinfo($file, PATHINFO_FILENAME));
                // Remove special chars and compare
                $normalized_base = preg_replace('/[^a-z0-9]/', '', $base_name);
                $normalized_file = preg_replace('/[^a-z0-9]/', '', $file_base);
                
                if ($normalized_base === $normalized_file) {
                    $found_file = $file;
                    break;
                }
            }
        }
        
        // Special handling for "living 2.avif" (has space)
        if (!$found_file && strpos($base_name, 'living2') !== false) {
            foreach ($available_files as $file) {
                if (stripos($file, 'living') !== false && stripos($file, '2') !== false) {
                    $found_file = $file;
                    break;
                }
            }
        }
        
        if ($found_file) {
            $new_path = 'pictures/' . $found_file;
            
            // Update database if path is different
            if ($raw_url !== $new_path) {
                $updateStmt->execute([$new_path, $image['id']]);
                $status = "<span class='success'>✓ Updated</span>";
                $updated++;
            } else {
                $status = "<span class='info'>✓ Correct</span>";
            }
            
            // Verify file exists
            $local_path = __DIR__ . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . $found_file;
            $web_path = '../pictures/' . $found_file;
            
            if (file_exists($local_path)) {
                $preview = "<img src='{$web_path}' alt='{$image['title']}' onerror=\"this.style.border='3px solid red'; this.alt='FAILED TO LOAD';\">";
            } else {
                $preview = "<span class='error'>File not found locally</span>";
            }
            
            echo "<tr>";
            echo "<td>{$image['id']}</td>";
            echo "<td>{$image['title']}</td>";
            echo "<td style='font-family:monospace;font-size:11px;'>{$raw_url}</td>";
            echo "<td style='font-family:monospace;font-size:11px;'>{$new_path}</td>";
            echo "<td>{$status}</td>";
            echo "<td>{$preview}</td>";
            echo "</tr>";
        } else {
            $not_found++;
            echo "<tr style='background:#fee;'>";
            echo "<td>{$image['id']}</td>";
            echo "<td>{$image['title']}</td>";
            echo "<td style='font-family:monospace;font-size:11px;'>{$raw_url}</td>";
            echo "<td class='error'>NOT FOUND</td>";
            echo "<td class='error'>✗ Missing</td>";
            echo "<td><span class='error'>No matching file</span></td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<p class='success'>✓ Updated/Verified: " . (count($images) - $not_found) . " images</p>";
    if ($not_found > 0) {
        echo "<p class='error'>✗ Not found: {$not_found} images</p>";
        echo "<p class='warning'>⚠ Some images in the database don't have matching files in the pictures folder.</p>";
    }
    
    echo "<hr>";
    echo "<h2>Available Files in pictures/ folder:</h2>";
    echo "<ul style='columns:3;'>";
    $pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
    if (is_dir($pictures_dir)) {
        $files = scandir($pictures_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
                    echo "<li>{$file}</li>";
                }
            }
        }
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='gallery.php' target='_blank'>View Gallery Page</a> - Check if images are loading</li>";
    echo "<li><a href='dashboard.php#gallery' target='_blank'>View Dashboard Gallery</a> - Check dashboard gallery section</li>";
    echo "<li><a href='check_gallery_images.php'>Run Diagnostic Check</a> - Detailed path analysis</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

