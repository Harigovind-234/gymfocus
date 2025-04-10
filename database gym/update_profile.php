<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Set JSON header
header('Content-Type: application/json');

require_once 'connect.php';

try {
    // Debug logging
    error_log('Request received');
    error_log('POST data: ' . print_r($_POST, true));

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $user_id = $_SESSION['user_id'];
    
    // Get POST data
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile_no = trim($_POST['mobile_no'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($mobile_no) || empty($address)) {
        throw new Exception('All fields are required');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update login table
        $login_sql = "UPDATE login SET username = ?, email = ? WHERE user_id = ?";
        $login_stmt = $conn->prepare($login_sql);
        if (!$login_stmt) {
            throw new Exception('Failed to prepare login statement: ' . $conn->error);
        }
        $login_stmt->bind_param("ssi", $username, $email, $user_id);
        if (!$login_stmt->execute()) {
            throw new Exception('Failed to update login information: ' . $login_stmt->error);
        }

        // Update register table
        $register_sql = "UPDATE register SET full_name = ?, mobile_no = ?, address = ? WHERE user_id = ?";
        $register_stmt = $conn->prepare($register_sql);
        if (!$register_stmt) {
            throw new Exception('Failed to prepare register statement: ' . $conn->error);
        }
        $register_stmt->bind_param("sssi", $full_name, $mobile_no, $address, $user_id);
        if (!$register_stmt->execute()) {
            throw new Exception('Failed to update register information: ' . $register_stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Clean up
if (isset($login_stmt)) $login_stmt->close();
if (isset($register_stmt)) $register_stmt->close();
if (isset($conn)) $conn->close();
?> 