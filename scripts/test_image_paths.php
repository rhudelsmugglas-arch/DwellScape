<?php
/**
 * Test Image Paths
 * This script tests if images can be accessed correctly
 */

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['REQUEST_URI']);
$full_base = rtrim($base_url . $base_path, '/');

$images = [
    'dashboard1.png',
    'dashboard2.png',
    'dashboard3.png',
    'dashboard4.png',
    'living1.png',
    'living2.png',
    'living3.png',
    'living4.png',
    'kitchen1.png',
    'kitchen2.png'
];

echo "<h1>Image Path Test</h1>";
echo "<p>Base URL: $full_base</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Directory: " . __DIR__ . "</p>";
echo "<hr>";

echo "<h2>Testing Image Paths:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Image</th><th>File Exists</th><th>Relative Path</th><th>Absolute Path</th><th>Preview</th></tr>";

foreach ($images as $img) {
    $relative_path = 'pictures/' . $img;
    $absolute_path = __DIR__ . '/pictures/' . $img;
    $file_exists = file_exists($absolute_path);
    $web_path = $full_base . '/pictures/' . $img;
    
    echo "<tr>";
    echo "<td>$img</td>";
    echo "<td>" . ($file_exists ? '✓ YES' : '✗ NO') . "</td>";
    echo "<td>$relative_path</td>";
    echo "<td>$absolute_path</td>";
    if ($file_exists) {
        echo "<td><img src='$relative_path' style='max-width: 200px; height: auto;' onerror='this.style.display=\"none\"; this.parentElement.innerHTML=\"Failed to load\"'></td>";
    } else {
        echo "<td style='color: red;'>File not found</td>";
    }
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>Recommended Path Format:</h2>";
echo "<p>For home.php (root level): Use <code>pictures/dashboard1.png</code></p>";
echo "<p>For JavaScript: Use <code>" . (strpos($_SERVER['REQUEST_URI'], '/dwellscape/') !== false ? '/dwellscape/' : '/') . "pictures/dashboard1.png</code></p>";
?>

