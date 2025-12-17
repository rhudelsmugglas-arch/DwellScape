<?php
/**
 * Script to remove BOM from PHP files
 * Run this once to clean your files, then delete it
 */

$files = [
    'login.php',
    'dashboard.php',
    'signup.php',
    'home.php',
    'admin/admin.php',
    'config/database.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Remove BOM (UTF-8 BOM is EF BB BF)
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
            file_put_contents($file, $content);
            echo "Removed BOM from: $file\n";
        } else {
            echo "No BOM found in: $file\n";
        }
        
        // Remove any whitespace before <?php
        $content = ltrim($content);
        
        // Ensure file starts with <?php exactly
        if (!str_starts_with($content, '<?php')) {
            echo "Warning: $file does not start with <?php\n";
        } else {
            // Save cleaned content
            file_put_contents($file, $content);
            echo "Cleaned: $file\n";
        }
    }
}

echo "Done!\n";

