<?php
session_start();
if (!isset($_SESSION['user_id']) ) {
    header("Location: login2.php");
    exit();
}
include "connect.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Process cancellation
if (isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $user_id = $_SESSION['user_id'];
    
    // Simple direct deletion - no extra checks
    $sql = "DELETE FROM slot_bookings WHERE booking_id = '$booking_id' AND user_id = '$user_id'";
    
    if ($conn->query($sql)) {
        // Success - redirect back to index with success message
        header("Location: index.php?cancelled=true#status-header");
        exit();
    } else {
        // Error - redirect with error message
        header("Location: index.php?error=Failed+to+cancel+booking#booking-status");
        exit();
    }
} else {
    // No booking ID provided
    header("Location: index.php?error=No+booking+ID+provided#booking-status");
    exit();
}
?> 