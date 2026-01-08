<?php
/**
 * Diagnostic script to check gallery images and their paths
 */
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Gallery Image Checker</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo "table{border-collapse:collapse;width:100%;background:white;margin:20px 0;}";
echo "th,td{padding:12px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#C3B091;color:white;}";
echo ".exists{color:green;font-weight:bold;}";
echo ".missing{color:red;font-weight:bold;}";
echo ".path{font-family:monospace;font-size:12px;}";
echo "img{max-width:100px;max-height:100px;object-fit:cover;border:1px solid #ddd;}</style></head><body>";

echo "<h1>Gallery Images Diagnostic</h1>";

try {
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY display_order ASC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found <strong>" . count($images) . "</strong> active gallery images.</p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Database Path</th><th>Resolved Path</th><th>File Exists?</th><th>Preview</th></tr>";
    
    foreach ($images as $image) {
        $raw_url = trim($image['image_url']);
        $resolved_path = '';
        $file_exists = false;
        
        // Resolve path similar to gallery.php
        if (preg_match('~^https?://~i', $raw_url)) {
            $resolved_path = $raw_url;
            $file_exists = 'N/A (External URL)';
        } elseif (preg_match('~^pictures/~i', $raw_url)) {
            $resolved_path = '../' . $raw_url;
            $full_path = __DIR__ . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . basename($raw_url);
            $file_exists = file_exists($full_path) ? 'YES' : 'NO';
        } elseif (preg_match('~^\.\./pictures/~i', $raw_url)) {
            $resolved_path = $raw_url;
            $full_path = __DIR__ . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . basename($raw_url);
            $file_exists = file_exists($full_path) ? 'YES' : 'NO';
        } else {
            // Try to find the file
            $filename = basename($raw_url);
            $full_path = __DIR__ . DIRECTORY_SEPARATOR . 'pictures' . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($full_path)) {
                $resolved_path = '../pictures/' . $filename;
                $file_exists = 'YES';
            } else {
                $resolved_path = $raw_url . ' (unresolved)';
                $file_exists = 'NO';
            }
        }
        
        $status_class = ($file_exists === 'YES' || $file_exists === 'N/A (External URL)') ? 'exists' : 'missing';
        
        echo "<tr>";
        echo "<td>{$image['id']}</td>";
        echo "<td>{$image['title']}</td>";
        echo "<td class='path'>{$raw_url}</td>";
        echo "<td class='path'>{$resolved_path}</td>";
        echo "<td class='{$status_class}'>{$file_exists}</td>";
        echo "<td>";
        if ($file_exists === 'YES' || $file_exists === 'N/A (External URL)') {
            echo "<img src='{$resolved_path}' alt='{$image['title']}' onerror=\"this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27100%27 height=%27100%27%3E%3Crect fill=%27%23ff0000%27 width=%27100%27 height=%27100%27/%3E%3Ctext x=%2750%27 y=%2750%27 text-anchor=%27middle%27 fill=%27white%27 font-size=%2714%27%3ENot Found%3C/text%3E%3C/svg%3E'\">";
        } else {
            echo "<span style='color:red;'>No preview</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check pictures folder
    echo "<h2>Files in pictures/ folder:</h2>";
    $pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
    if (is_dir($pictures_dir)) {
        $files = scandir($pictures_dir);
        $image_files = array_filter($files, function($file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif']);
        });
        
        echo "<p>Found <strong>" . count($image_files) . "</strong> image files in pictures/ folder:</p>";
        echo "<ul>";
        foreach ($image_files as $file) {
            echo "<li>{$file}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>pictures/ folder does not exist!</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

