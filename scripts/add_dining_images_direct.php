<?php
/**
 * Direct Add Dining Area Images
 * This script will help add dining area images directly
 */

session_start();
require_once 'config/database.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image1']) && isset($_FILES['image2'])) {
    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR;
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded = [];
    $errors = [];
    
    // Process both images
    for ($i = 1; $i <= 2; $i++) {
        $file_key = 'image' . $i;
        
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$file_key];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'dining_' . time() . '_' . $i . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $title = isset($_POST['title' . $i]) ? trim($_POST['title' . $i]) : 'Dining Area';
                    $description = isset($_POST['description' . $i]) ? trim($_POST['description' . $i]) : 'Elegant Dining Space';
                    
                    try {
                        // Get next display order
                        $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM gallery_images WHERE category = 'dining'");
                        $result = $stmt->fetch();
                        $display_order = ($result['max_order'] ?? 0) + $i;
                        
                        // Insert into database
                        $stmt = $pdo->prepare("
                            INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active)
                            VALUES (?, ?, 'dining', ?, ?, 1)
                        ");
                        $stmt->execute([
                            'uploads/gallery/' . $new_filename,
                            $title,
                            $description,
                            $display_order
                        ]);
                        
                        $uploaded[] = "Image $i uploaded successfully!";
                    } catch(PDOException $e) {
                        $errors[] = "Error saving image $i: " . $e->getMessage();
                        @unlink($upload_path);
                    }
                } else {
                    $errors[] = "Failed to upload image $i";
                }
            } else {
                $errors[] = "Invalid file type for image $i. Allowed: JPG, PNG, GIF, WEBP, AVIF";
            }
        } else {
            $errors[] = "Image $i upload error: " . ($_FILES[$file_key]['error'] ?? 'No file uploaded');
        }
    }
    
    if (!empty($uploaded)) {
        $success_message = implode('<br>', $uploaded);
    }
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}

// Check existing dining images
try {
    $stmt = $pdo->query("SELECT * FROM gallery_images WHERE category = 'dining' ORDER BY display_order");
    $existing_images = $stmt->fetchAll();
} catch(PDOException $e) {
    $existing_images = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Dining Area Images</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 { color: #1f2937; margin-bottom: 10px; }
        .subtitle { color: #6b7280; margin-bottom: 30px; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
        }
        input[type="file"], input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        input[type="file"] {
            border: 2px dashed #d1d5db;
            background: #f9fafb;
            cursor: pointer;
        }
        .image-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        .btn {
            background: #C3B091;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #9A8B6F;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6b7280;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }
        small { color: #6b7280; font-size: 12px; }
        .existing-images {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }
        .existing-images h3 {
            margin-bottom: 15px;
            color: #1f2937;
        }
        .existing-image-item {
            padding: 10px;
            background: #f9fafb;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Dining Area Images</h1>
        <p class="subtitle">Upload 2 images for the Dining Area gallery</p>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
            <br><br>
            <a href="dashboard.php#gallery" style="color: #155724; text-decoration: underline;">View Gallery</a> | 
            <a href="admin/admin_gallery.php" style="color: #155724; text-decoration: underline;">Manage in Admin</a>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="image-section">
                <h3 style="margin-bottom: 15px; color: #1f2937;">Image 1</h3>
                <div class="form-group">
                    <label for="image1">Select Image <span style="color: red;">*</span></label>
                    <input type="file" id="image1" name="image1" accept="image/*" required>
                    <small>Accepted formats: JPG, PNG, GIF, WEBP, AVIF</small>
                </div>
                <div class="form-group">
                    <label for="title1">Title</label>
                    <input type="text" id="title1" name="title1" value="Dining Area" placeholder="Dining Area">
                </div>
                <div class="form-group">
                    <label for="description1">Description</label>
                    <input type="text" id="description1" name="description1" value="Elegant Dining Space" placeholder="Elegant Dining Space">
                </div>
            </div>
            
            <div class="image-section">
                <h3 style="margin-bottom: 15px; color: #1f2937;">Image 2</h3>
                <div class="form-group">
                    <label for="image2">Select Image <span style="color: red;">*</span></label>
                    <input type="file" id="image2" name="image2" accept="image/*" required>
                    <small>Accepted formats: JPG, PNG, GIF, WEBP, AVIF</small>
                </div>
                <div class="form-group">
                    <label for="title2">Title</label>
                    <input type="text" id="title2" name="title2" value="Dining Area" placeholder="Dining Area">
                </div>
                <div class="form-group">
                    <label for="description2">Description</label>
                    <input type="text" id="description2" name="description2" value="Modern Dining Space" placeholder="Modern Dining Space">
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn">Upload Both Images</button>
                <a href="dashboard.php#gallery" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <?php if (!empty($existing_images)): ?>
        <div class="existing-images">
            <h3>Existing Dining Area Images (<?php echo count($existing_images); ?>)</h3>
            <?php foreach ($existing_images as $img): ?>
            <div class="existing-image-item">
                <strong><?php echo htmlspecialchars($img['title']); ?></strong> - 
                <?php echo htmlspecialchars($img['description']); ?> 
                (<?php echo htmlspecialchars($img['image_url']); ?>)
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>




