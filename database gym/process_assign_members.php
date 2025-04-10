<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainer_id = intval($_POST['trainer_id']);
    $member_ids = $_POST['member_ids'];
    
    // Verify trainer exists and is staff
    $staff_check = "SELECT 1 FROM register r 
                    INNER JOIN login l ON r.user_id = l.user_id 
                    WHERE r.user_id = ? AND l.role = 'staff'";
    $stmt = mysqli_prepare($conn, $staff_check);
    mysqli_stmt_bind_param($stmt, "i", $trainer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid staff member']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("INSERT INTO StaffAssignedMembers (trainer_id, member_id, assigned_date) VALUES (?, ?, NOW())");
        
        foreach ($member_ids as $member_id) {
            // Verify member exists and has role 'member'
            $member_check = "SELECT 1 FROM register r 
                            INNER JOIN login l ON r.user_id = l.user_id 
                            WHERE r.user_id = ? AND l.role = 'member'";
            $check_stmt = mysqli_prepare($conn, $member_check);
            mysqli_stmt_bind_param($check_stmt, "i", $member_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $stmt->bind_param("ii", $trainer_id, $member_id);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 