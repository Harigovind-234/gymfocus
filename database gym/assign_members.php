<?php
session_start();
require_once 'connect.php';

// Check if staff_id is provided in URL
if (!isset($_GET['staff_id'])) {
    header('Location: staff_management.php');
    exit();
}

$staff_id = intval($_GET['staff_id']);

// Get staff details
$staff_query = "SELECT r.*, l.email 
                FROM register r 
                INNER JOIN login l ON r.user_id = l.user_id 
                WHERE r.user_id = ? AND l.role = 'staff'";
$stmt = mysqli_prepare($conn, $staff_query);
mysqli_stmt_bind_param($stmt, "i", $staff_id);
mysqli_stmt_execute($stmt);
$staff_result = mysqli_stmt_get_result($stmt);
$staff = mysqli_fetch_assoc($staff_result);

// If no staff found with this ID
if (!$staff) {
    header('Location: staff_management.php');
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Assign Members to Staff</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
    .admin-card {
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }

    .admin-table {
        width: 100%;
        margin-top: 20px;
        border-collapse: collapse;
    }

    .admin-table th,
    .admin-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .admin-table th {
        background-color: #f5f5f5;
        font-weight: 600;
    }

    .admin-button {
        display: inline-block;
        background-color: #ed563b;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        margin: 0 5px;
        border: none;
        cursor: pointer;
    }

    .admin-button:hover {
        background-color: #f9735b;
        color: #fff;
        text-decoration: none;
    }

    .btn-secondary {
        background-color: #6c757d;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    .text-center {
        text-align: center;
    }
    </style>

</head>
<body>
    <div class="container mt-5">
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Assign Members to <?php echo htmlspecialchars($staff['full_name']); ?></h3>
                <a href="staff_management.php" class="admin-button btn-secondary">Back to Staff</a>
            </div>

            <!-- Available Members to Assign -->
            <div class="mb-4">
                <h4>Available Members to Assign</h4>
                <form id="assignMembersForm">
                    <input type="hidden" name="trainer_id" value="<?php echo $staff_id; ?>">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"> Select</th>
                                <th>Member Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Join Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get unassigned members with role 'member'
                            $members_query = "SELECT r.*, l.email 
                                            FROM register r 
                                            INNER JOIN login l ON r.user_id = l.user_id 
                                            WHERE l.role = 'member' 
                                            AND r.user_id NOT IN (
                                                SELECT member_id FROM StaffAssignedMembers
                                            )
                                            ORDER BY r.full_name";
                            $members_result = mysqli_query($conn, $members_query);
                            
                            if (mysqli_num_rows($members_result) > 0) {
                                while ($member = mysqli_fetch_assoc($members_result)) {
                                    echo "<tr>";
                                    echo "<td><input type='checkbox' name='member_ids[]' value='" . $member['user_id'] . "'></td>";
                                    echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($member['email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($member['mobile_no']) . "</td>";
                                    echo "<td>" . htmlspecialchars($member['gender']) . "</td>";
                                    echo "<td>" . htmlspecialchars($member['created_at']) . "</td>";
                                    echo "</tr>";
                                }

                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No members available to assign</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <div class="mt-3">
                        <button type="submit" class="admin-button">Assign Selected Members</button>
                    </div>
                </form>
            </div>

            <!-- Currently Assigned Members -->
            <div class="mb-4">
                <h4>Currently Assigned Members</h4>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Assigned Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $assigned_query = "SELECT r.user_id, r.full_name, r.mobile_no, r.gender, l.email, sam.assigned_date 
                        FROM StaffAssignedMembers sam
                        JOIN register r ON sam.member_id = r.user_id
                        JOIN login l ON r.user_id = l.user_id
                        WHERE sam.trainer_id = ? AND l.role = 'member'";
                        $stmt = mysqli_prepare($conn, $assigned_query);
                        mysqli_stmt_bind_param($stmt, "i", $staff_id);
                        mysqli_stmt_execute($stmt);
                        $assigned_result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($assigned_result) > 0) {
                            while ($member = mysqli_fetch_assoc($assigned_result)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($member['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($member['mobile_no']) . "</td>";
                                echo "<td>" . htmlspecialchars($member['gender']) . "</td>";
                                echo "<td>" . date('Y-m-d', strtotime($member['assigned_date'])) . "</td>";
                                echo "<td>
                                <button type='button' class='admin-button btn-danger btn-sm' 
                                        onclick='unassignMember(" . $member['user_id'] . ")'>
                                    Unassign
                                </button>
                              </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No members currently assigned</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

   
    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Select All checkbox functionality
        $('#selectAll').change(function() {
            $('input[name="member_ids[]"]').prop('checked', $(this).prop('checked'));
        });

        $('#assignMembersForm').submit(function(e) {
            e.preventDefault();
            
            // Check if any members are selected
            if ($('input[name="member_ids[]"]:checked').length === 0) {
                alert('Please select at least one member to assign');
                return;
            }

            $.ajax({
                url: 'process_assign_members.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Members assigned successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error assigning members');
                }
            });
        });
    });
    </script>

    <script>
    function unassignMember(memberId) {
        if (confirm('Are you sure you want to unassign this member?')) {
            // Create form data
            const formData = new FormData();
            formData.append('member_id', memberId);
            formData.append('trainer_id', <?php echo $staff_id; ?>);

            fetch('process_unassign.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Change to text instead of json
            })
            .then(result => {
                // Check if operation was successful
                if (result.includes('success')) {
                    alert('Member unassigned successfully');
                    window.location.reload(); // Reload the page
                } else {
                    alert('Failed to unassign member');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error unassigning member');
            });
        }
    }
    </script>
</body>
</html> 