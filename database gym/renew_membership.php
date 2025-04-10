<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized access');
}

include 'connect.php';

if (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $monthly_fee = 999; // Current monthly fee
    
    try {
        $conn->begin_transaction();

        // Get the last membership record
        $query = "SELECT * FROM memberships WHERE user_id = ? ORDER BY membership_id DESC LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $last_membership = $stmt->get_result()->fetch_assoc();

        $current_date = date('Y-m-d');
        $next_payment_date = date('Y-m-d', strtotime('+1 month'));

        // Insert new monthly payment record
        $stmt = $conn->prepare("INSERT INTO memberships (
            user_id, 
            joining_date,
            last_payment_date,
            next_payment_date,
            membership_status,
            payment_amount,
            payment_type,
            payment_status,
            payment_method
        ) VALUES (?, ?, ?, ?, 'active', ?, 'monthly', 'completed', 'admin')");
        
        $stmt->bind_param("isssd", 
            $user_id,
            $last_membership['joining_date'], // Keep original joining date
            $current_date,
            $next_payment_date,
            $monthly_fee
        );
        
        $stmt->execute();
        $conn->commit();
        
        echo "success";
    } catch (Exception $e) {
        $conn->rollback();
        echo "error: " . $e->getMessage();
    }
}
?> 