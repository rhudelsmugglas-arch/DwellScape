<?php
/**
 * Check Database Images - Diagnostic script to see what's in the database
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Check Database Images</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".container{max-width:1000px;margin:0 auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo "h2{color:#7a6a4f;}table{border-collapse:collapse;width:100%;margin-top:20px;}";
echo "th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd;}th{background:#7a6a4f;color:white;}";
echo ".jpg{color:#28a745;font-weight:bold;}.png{color:#ffc107;font-weight:bold;}</style></head><body>";
echo "<div class='container'><h2>Gallery Images in Database</h2>";

try {
    $stmt = $pdo->query("SELECT id, image_url, title, category, description, is_active FROM gallery_images ORDER BY category, display_order");
    $images = $stmt->fetchAll();
    
    if (empty($images)) {
        echo "<p>No images found in database.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Image URL</th><th>Title</th><th>Category</th><th>Status</th></tr>";
        
        foreach ($images as $img) {
            $ext = pathinfo($img['image_url'], PATHINFO_EXTENSION);
            $ext_class = ($ext === 'jpg' || $ext === 'jpeg') ? 'jpg' : (($ext === 'png') ? 'png' : '');
            $status = $img['is_active'] ? '✅ Active' : '❌ Inactive';
            
            echo "<tr>";
            echo "<td>{$img['id']}</td>";
            echo "<td><span class='$ext_class'>{$img['image_url']}</span></td>";
            echo "<td>{$img['title']}</td>";
            echo "<td>{$img['category']}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Summary
        $jpg_count = 0;
        $png_count = 0;
        foreach ($images as $img) {
            $ext = strtolower(pathinfo($img['image_url'], PATHINFO_EXTENSION));
            if ($ext === 'jpg' || $ext === 'jpeg') $jpg_count++;
            if ($ext === 'png') $png_count++;
        }
        
        echo "<div style='margin-top:20px;padding:15px;background:#f8f9fa;border-radius:5px;'>";
        echo "<strong>Summary:</strong><br>";
        echo "Total images: " . count($images) . "<br>";
        echo "JPG images: <span class='jpg'>$jpg_count</span><br>";
        echo "PNG images: <span class='png'>$png_count</span>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:#dc3545;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>
