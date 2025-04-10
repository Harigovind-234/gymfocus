<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Set JSON header
header('Content-Type: application/json');

require_once 'connect.php';

try {
    // Debug logging
    error_log('Request received');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r($_FILES, true));

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    // Check if file was uploaded
    if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_pic'];

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Please upload JPG, PNG or GIF');
    }

    // Validate file size (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 5MB');
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Failed to create uploads directory');
        }
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save the uploaded file');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update database
        $sql = "INSERT INTO profilepictures (user_id, pic_url) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("is", $user_id, $filename);
        
        if (!$stmt->execute()) {
            throw new Exception('Database update failed: ' . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'pic_url' => $filename,
            'message' => 'Profile picture updated successfully'
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        // Delete uploaded file if database insert fails
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        throw $e;
    }

} catch (Exception $e) {
    error_log('Profile pic upload error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Clean up
if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();
?> 