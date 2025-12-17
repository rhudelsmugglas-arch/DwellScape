<?php
/**
 * Fix BOM and whitespace issues in dashboard.php
 * Run this once via browser or CLI, then delete it
 */

$file = 'dashboard.php';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

// Read file content
$content = file_get_contents($file);

// Remove BOM (UTF-8 BOM is EF BB BF)
if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
    $content = substr($content, 3);
    echo "Removed BOM from $file\n";
}

// Remove any leading whitespace/newlines before <?php
$content = ltrim($content);

// Ensure file starts with <?php
if (!str_starts_with($content, '<?php')) {
    die("Error: File does not start with <?php\n");
}

// Save cleaned content
file_put_contents($file, $content);
echo "Fixed $file - removed BOM and leading whitespace\n";
echo "File now starts with: " . substr($content, 0, 20) . "...\n";

