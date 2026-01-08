<?php
/**
 * Fix gallery image paths in database to match actual files in pictures folder
 */
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Fix Gallery Paths</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}";
echo ".error{color:red;font-weight:bold;}";
echo ".info{color:blue;}";
echo "pre{background:white;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body>";

echo "<h1>Fixing Gallery Image Paths</h1>";

try {
    // Get all active gallery images
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY id ASC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found <strong>" . count($images) . "</strong> active gallery images.</p>";
    
    // Get all files in pictures folder
    $pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
    $files = [];
    if (is_dir($pictures_dir)) {
        $dir_files = scandir($pictures_dir);
        foreach ($dir_files as $file) {
            if ($file !== '.' && $file !== '..') {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
                    $files[] = $file;
                }
            }
        }
    }
    
    echo "<p>Found <strong>" . count($files) . "</strong> image files in pictures folder.</p>";
    echo "<hr>";
    
    $updated = 0;
    $not_found = 0;
    $updateStmt = $pdo->prepare("UPDATE gallery_images SET image_url = ? WHERE id = ?");
    
    foreach ($images as $image) {
        $raw_url = trim($image['image_url']);
        $current_filename = basename($raw_url);
        
        // Remove extension to get base name
        $base_name = pathinfo($current_filename, PATHINFO_FILENAME);
        
        // Try to find matching file (case-insensitive, any extension)
        $found_file = null;
        foreach ($files as $file) {
            $file_base = pathinfo($file, PATHINFO_FILENAME);
            // Case-insensitive comparison
            if (strcasecmp($base_name, $file_base) === 0) {
                $found_file = $file;
                break;
            }
        }
        
        // Also try common variations
        if (!$found_file) {
            // Try removing spaces and special chars
            $normalized_base = preg_replace('/[^a-z0-9]/i', '', $base_name);
            foreach ($files as $file) {
                $file_base = pathinfo($file, PATHINFO_FILENAME);
                $normalized_file = preg_replace('/[^a-z0-9]/i', '', $file_base);
                if (strcasecmp($normalized_base, $normalized_file) === 0) {
                    $found_file = $file;
                    break;
                }
            }
        }
        
        // Map common names
        $name_mapping = [
            'living1' => 'living1.avif',
            'living2' => 'living 2.avif',  // Note: has space
            'living3' => 'living3.avif',
            'living4' => 'living4.avif',
            'kitchen1' => 'kitchen1.avif',
            'kitchen2' => 'kitchen2.avif',
            'dining1' => 'dining1.png',
            'dining2' => 'dining2.png',
            'bedroom1' => 'bedroom1-2.png',
            'bedroom2' => 'bedroom2-1.png',
            'bathroom1' => 'bathroom1.png',
            'bathroom2' => 'bathroom2.png',
            'pool1' => 'pool1.png',
            'activity1' => 'activity1.png',
            'activity2' => 'activity2.png',
            'activity3' => 'activity3.png',
            'activity4' => 'activity4.png',
        ];
        
        if (!$found_file && isset($name_mapping[strtolower($base_name)])) {
            $mapped_name = $name_mapping[strtolower($base_name)];
            foreach ($files as $file) {
                if (strcasecmp($file, $mapped_name) === 0) {
                    $found_file = $file;
                    break;
                }
            }
        }
        
        if ($found_file) {
            // Update to use pictures/ path (relative to root)
            $new_path = 'pictures/' . $found_file;
            
            // Only update if path is different
            if ($raw_url !== $new_path) {
                $updateStmt->execute([$new_path, $image['id']]);
                echo "<p class='success'>✓ Updated ID {$image['id']}: '{$raw_url}' → '{$new_path}'</p>";
                $updated++;
            } else {
                echo "<p class='info'>- ID {$image['id']}: Path already correct: '{$raw_url}'</p>";
            }
        } else {
            echo "<p class='error'>✗ ID {$image['id']}: Could not find file for '{$raw_url}' (base: '{$base_name}')</p>";
            $not_found++;
        }
    }
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<p class='success'>Updated: {$updated} images</p>";
    if ($not_found > 0) {
        echo "<p class='error'>Not found: {$not_found} images</p>";
    }
    echo "<p><a href='gallery.php'>View Gallery</a> | <a href='check_gallery_images.php'>Check Images</a></p>";
    
} catch(PDOException $e) {
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

