<?php
/**
 * Upload Bedroom Images
 * Simple form to upload 2 images to bedroom1 category
 */

session_start();
require_once 'config/database.php';

// Check if user is logged in (admin or regular user can use this)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../home.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bedroom_images'])) {
    $upload_dir = 'uploads/gallery/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded_count = 0;
    $errors = [];
    
    // Get current max display_order for bedroom1 category
    try {
        $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM gallery_images WHERE category = 'bedroom1'");
        $result = $stmt->fetch();
        $next_order = ($result['max_order'] ?? 0) + 1;
    } catch(PDOException $e) {
        $next_order = 1;
    }
    
    // Process each uploaded file
    foreach ($_FILES['bedroom_images']['name'] as $key => $filename) {
        if ($_FILES['bedroom_images']['error'][$key] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'bedroom1_' . time() . '_' . $key . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['bedroom_images']['tmp_name'][$key], $upload_path)) {
                    // Insert into database
                    try {
                        $title = isset($_POST['titles'][$key]) ? trim($_POST['titles'][$key]) : 'Bedroom 1';
                        $description = isset($_POST['descriptions'][$key]) ? trim($_POST['descriptions'][$key]) : 'Comfortable Bedroom Space';
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO gallery_images (image_url, title, category, description, display_order, is_active)
                            VALUES (?, ?, 'bedroom1', ?, ?, 1)
                        ");
                        $stmt->execute([
                            'uploads/gallery/' . $new_filename,
                            $title,
                            $description,
                            $next_order + $key
                        ]);
                        $uploaded_count++;
                    } catch(PDOException $e) {
                        $errors[] = "Error saving image {$filename} to database: " . $e->getMessage();
                        @unlink($upload_path); // Delete uploaded file if DB insert fails
                    }
                } else {
                    $errors[] = "Failed to upload {$filename}";
                }
            } else {
                $errors[] = "Invalid file type for {$filename}. Allowed: JPG, PNG, GIF, WEBP, AVIF";
            }
        } elseif ($_FILES['bedroom_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "Upload error for {$filename}: " . $_FILES['bedroom_images']['error'][$key];
        }
    }
    
    if ($uploaded_count > 0) {
        $success_message = "Successfully uploaded {$uploaded_count} image(s) to Bedroom 1!";
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        }
    } else {
        $error_message = !empty($errors) ? implode('<br>', $errors) : "No images were uploaded.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Bedroom Images</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
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
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }
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
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
            cursor: pointer;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        .image-upload-item {
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
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        small {
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Bedroom Images</h1>
        <p class="subtitle">Upload 2 images for Bedroom 1 gallery</p>
        
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
            <div class="image-upload-item">
                <div class="form-group">
                    <label for="bedroom_images_0">Image 1 <span style="color: red;">*</span></label>
                    <input type="file" id="bedroom_images_0" name="bedroom_images[]" accept="image/*" required>
                    <small>Accepted formats: JPG, PNG, GIF, WEBP, AVIF</small>
                </div>
                <div class="form-group">
                    <label for="title_0">Title (optional)</label>
                    <input type="text" id="title_0" name="titles[]" placeholder="Bedroom 1" value="Bedroom 1">
                </div>
                <div class="form-group">
                    <label for="description_0">Description (optional)</label>
                    <input type="text" id="description_0" name="descriptions[]" placeholder="Comfortable Bedroom Space" value="Comfortable Bedroom Space">
                </div>
            </div>
            
            <div class="image-upload-item">
                <div class="form-group">
                    <label for="bedroom_images_1">Image 2 <span style="color: red;">*</span></label>
                    <input type="file" id="bedroom_images_1" name="bedroom_images[]" accept="image/*" required>
                    <small>Accepted formats: JPG, PNG, GIF, WEBP, AVIF</small>
                </div>
                <div class="form-group">
                    <label for="title_1">Title (optional)</label>
                    <input type="text" id="title_1" name="titles[]" placeholder="Bedroom 1" value="Bedroom 1">
                </div>
                <div class="form-group">
                    <label for="description_1">Description (optional)</label>
                    <input type="text" id="description_1" name="descriptions[]" placeholder="Elegant Bedroom View" value="Elegant Bedroom View">
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn">
                    <i class="fas fa-upload"></i> Upload Images
                </button>
                <a href="dashboard.php#gallery" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>




