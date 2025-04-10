<?php
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login first";
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time_slot = mysqli_real_escape_string($conn, $_POST['time_slot']);

    try {
        // Check for existing booking
        $check_sql = "SELECT booking_id FROM slot_bookings 
                     WHERE user_id = $user_id 
                     AND booking_date = '$date' 
                     AND cancelled_at IS NULL";
        
        $check_result = mysqli_query($conn, $check_sql);
        
        if (!$check_result) {
            throw new Exception(mysqli_error($conn));
        }

        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['error'] = "You already have a booking for this date";
            header('Location: book_slot.php?session=' . $time_slot);
            exit();
        }

        // Insert new booking
        $insert_sql = "INSERT INTO slot_bookings (user_id, time_slot, booking_date) 
                      VALUES ($user_id, '$time_slot', '$date')";
        
        if (mysqli_query($conn, $insert_sql)) {
            $_SESSION['success'] = "Booking successful! Your slot has been reserved.";
            header('Location: index.php');
            exit();
        } else {
            throw new Exception(mysqli_error($conn));
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Error processing booking: " . $e->getMessage();
        header('Location: book_slot.php?session=' . $time_slot);
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header('Location: index.php');
    exit();
}

// Close connection
mysqli_close($conn);
?> 