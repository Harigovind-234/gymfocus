<?php
require_once 'connect.php';

if (isset($_GET['staff_id'])) {
    $staff_id = intval($_GET['staff_id']);
    
    // Check connection first
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    $query = "SELECT r.full_name, r.email, r.mobile_no, sam.assigned_date
              FROM StaffAssignedMembers sam
              JOIN register r ON sam.member_id = r.user_id
              WHERE sam.trainer_id = ?
              ORDER BY sam.assigned_date DESC";
              
    $stmt = mysqli_prepare($conn, $query);
    
    // Check if prepare statement succeeded
    if ($stmt === false) {
        die("Error preparing statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $staff_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>Member Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Assigned Date</th>
                    </tr>
                </thead>
                <tbody>";
        
        while ($member = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($member['email']) . "</td>";
            echo "<td>" . htmlspecialchars($member['mobile_no']) . "</td>";
            echo "<td>" . date('Y-m-d', strtotime($member['assigned_date'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p class='text-center'>No members assigned to this staff member yet.</p>";
    }
    
    mysqli_stmt_close($stmt);
}
?> 