<?php
/**
 * Create Placeholder Images
 * This script creates simple placeholder images using PHP GD library
 * Run this once to generate placeholder images for testing
 */

$pictures_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pictures';
$assets_img_dir = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img';

// Ensure directories exist
if (!is_dir($pictures_dir)) {
    mkdir($pictures_dir, 0755, true);
}
if (!is_dir($assets_img_dir)) {
    mkdir($assets_img_dir, 0755, true);
}

// Check if GD library is available
if (!function_exists('imagecreatetruecolor')) {
    die("GD library is not available. Please install php-gd extension.\n");
}

function createPlaceholderImage($width, $height, $text, $filename, $bgColor = [122, 106, 79], $textColor = [255, 255, 255]) {
    global $pictures_dir, $assets_img_dir;
    
    // Determine which directory to use
    $dir = (strpos($filename, 'logo') !== false) ? $assets_img_dir : $pictures_dir;
    $filepath = $dir . DIRECTORY_SEPARATOR . $filename;
    
    // Create image
    $img = imagecreatetruecolor($width, $height);
    
    // Allocate colors
    $bg = imagecolorallocate($img, $bgColor[0], $bgColor[1], $bgColor[2]);
    $text_col = imagecolorallocate($img, $textColor[0], $textColor[1], $textColor[2]);
    
    // Fill background
    imagefilledrectangle($img, 0, 0, $width, $height, $bg);
    
    // Add text
    $font_size = 24;
    $font = 5; // Built-in font
    $text_width = imagefontwidth($font) * strlen($text);
    $text_height = imagefontheight($font);
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($img, $font, $x, $y, $text, $text_col);
    
    // Save image
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'png') {
        imagepng($img, $filepath);
    } elseif ($ext === 'jpg' || $ext === 'jpeg') {
        imagejpeg($img, $filepath, 90);
    }
    
    imagedestroy($img);
    return file_exists($filepath);
}

echo "Creating placeholder images...\n\n";

// Create hero images (1920x1080)
$hero_images = [
    ['dashboard1.png', 'Main Building'],
    ['dashboard2.png', 'Pool Area'],
    ['dashboard3.png', 'Playground'],
    ['dashboard4.png', 'Activity Room'],
];

foreach ($hero_images as $img) {
    if (createPlaceholderImage(1920, 1080, $img[1], $img[0])) {
        echo "✓ Created: {$img[0]}\n";
    } else {
        echo "✗ Failed: {$img[0]}\n";
    }
}

// Create other images
$other_images = [
    ['virtual.jpg', 1200, 675, 'Virtual Tour'],
    ['bookings.png', 1920, 600, 'Bookings'],
    ['boy.png', 400, 400, 'Male Profile'],
    ['woman.png', 400, 400, 'Female Profile'],
];

foreach ($other_images as $img) {
    if (createPlaceholderImage($img[1], $img[2], $img[3], $img[0])) {
        echo "✓ Created: {$img[0]}\n";
    } else {
        echo "✗ Failed: {$img[0]}\n";
    }
}

// Create logo (200x50)
if (createPlaceholderImage(200, 50, 'DWELLSCAPE', 'dwellscape-logo.png', [122, 106, 79], [255, 255, 255])) {
    echo "✓ Created: dwellscape-logo.png\n";
} else {
    echo "✗ Failed: dwellscape-logo.png\n";
}

echo "\nDone! Placeholder images created.\n";
echo "Replace these with your actual images when ready.\n";
?>

