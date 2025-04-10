<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

include "connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $fee_amount = $_POST['fee_amount'];
    
    // Get current due date
    $query = "SELECT next_payment_date FROM register WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_due = $result->fetch_assoc()['next_payment_date'];
    
    // Calculate new due date (30 days from current due date)
    $new_due_date = date('Y-m-d', strtotime($current_due . ' +30 days'));
    
    // Update payment date and status
    $update_query = "UPDATE register 
                    SET next_payment_date = ?, 
                        last_payment_date = NOW(),
                        fee_status = 'paid' 
                    WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_due_date, $user_id);
    
    if ($stmt->execute()) {
        // Insert payment record
        $payment_query = "INSERT INTO payments (user_id, amount, payment_date, payment_type) 
                         VALUES (?, ?, NOW(), 'fee')";
        $stmt = $conn->prepare($payment_query);
        $stmt->bind_param("id", $user_id, $fee_amount);
        $stmt->execute();
        
        $_SESSION['success'] = "Fee payment successful. Next due date: " . date('d M Y', strtotime($new_due_date));
    } else {
        $_SESSION['error'] = "Error processing payment. Please try again.";
    }
    
    header("Location: index.php#next-due");
    exit();
}
?> 