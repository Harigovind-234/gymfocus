<?php
session_start();
require_once 'connect.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    $trainer_id = isset($_POST['trainer_id']) ? intval($_POST['trainer_id']) : 0;
    
    if ($member_id && $trainer_id) {
        // Delete from StaffAssignedMembers table
        $query = "DELETE FROM StaffAssignedMembers WHERE member_id = ? AND trainer_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $member_id, $trainer_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "success";
            } else {
                echo "error: execution failed";
            }
        } else {
            echo "error: prepare failed";
        }
    } else {
        echo "error: invalid parameters";
    }
} else {
    echo "error: invalid request method";
}

mysqli_close($conn);
?>