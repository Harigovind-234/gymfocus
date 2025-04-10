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
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    $user_id = $_SESSION['user_id'];
    
    // Get and validate POST data
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile_no = trim($_POST['mobile_no'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($mobile_no) || empty($address)) {
        throw new Exception('All fields are required');
    }

    if (!preg_match('/^[a-zA-Z\s]{3,50}$/', $full_name)) {
        throw new Exception('Invalid full name format');
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        throw new Exception('Invalid username format');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (!preg_match('/^[0-9]{10}$/', $mobile_no)) {
        throw new Exception('Invalid mobile number format');
    }

    if (strlen($address) < 5 || strlen($address) > 200) {
        throw new Exception('Address must be between 5 and 200 characters');
    }

    // Check if username or email already exists for other users
    $check_sql = "SELECT user_id FROM login WHERE (username = ? OR email = ?) AND user_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ssi", $username, $email, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        throw new Exception('Username or email already exists');
    }

    // Start transaction
    $conn->begin_transaction();

    // Update login table
    $login_sql = "UPDATE login SET username = ?, email = ? WHERE user_id = ?";
    $login_stmt = $conn->prepare($login_sql);
    if (!$login_stmt) {
        throw new Exception('Failed to prepare login statement');
    }
    $login_stmt->bind_param("ssi", $username, $email, $user_id);
    if (!$login_stmt->execute()) {
        throw new Exception('Failed to update login information');
    }

    // Update register table
    $register_sql = "UPDATE register SET full_name = ?, mobile_no = ?, address = ? WHERE user_id = ?";
    $register_stmt = $conn->prepare($register_sql);
    if (!$register_stmt) {
        throw new Exception('Failed to prepare register statement');
    }
    $register_stmt->bind_param("sssi", $full_name, $mobile_no, $address, $user_id);
    if (!$register_stmt->execute()) {
        throw new Exception('Failed to update register information');
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction if started
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }

    // Log error
    error_log("Profile update error: " . $e->getMessage());

    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Close statements and connection
if (isset($check_stmt)) $check_stmt->close();
if (isset($login_stmt)) $login_stmt->close();
if (isset($register_stmt)) $register_stmt->close();
if (isset($conn)) $conn->close();
?>