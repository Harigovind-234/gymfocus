<?php
session_start();
require_once 'connect.php';

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

if (!$staff) {
    header('Location: staff_management.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assigned Members</title>
    <!-- Include your CSS files -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header Area -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="admin.php" class="logo">Admin<em> Panel</em></a>
                        <ul class="nav">
                            <li><a href="admin.php">Home</a></li>
                            <li><a href="members.php">Members</a></li>
                            <li><a href="staff_management.php">Staff</a></li>
                            <li><a href="Payments_check.php">Payments</a></li>
                            <li><a href="products.php">Products</a></li>
                            <li class="main-button"><a href="login2.php">Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-12">
                <div class="admin-card">
                    <h3>Members Assigned to <?php echo htmlspecialchars($staff['full_name']); ?></h3>
                    <p>Email: <?php echo htmlspecialchars($staff['email']); ?></p>
                    
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Workout Plan</th>
                                <th>Progress</th>
                                <th>Assigned Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $members_query = "SELECT r.full_name, r.email, r.mobile_no, 
                                            wp.Wplan_name, sam.assigned_date,
                                            COUNT(CASE WHEN ws.status = 'Completed' THEN 1 END) as completed_sessions,
                                            COUNT(ws.schedule_id) as total_sessions
                                            FROM StaffAssignedMembers sam
                                            JOIN register r ON sam.member_id = r.user_id
                                            LEFT JOIN WorkoutSchedule ws ON r.user_id = ws.member_id
                                            LEFT JOIN WorkoutPlans wp ON ws.Wplan_id = wp.Wplan_id
                                            WHERE sam.trainer_id = ?
                                            GROUP BY r.user_id";
                            
                            $stmt = mysqli_prepare($conn, $members_query);
                            mysqli_stmt_bind_param($stmt, "i", $staff_id);
                            mysqli_stmt_execute($stmt);
                            $members_result = mysqli_stmt_get_result($stmt);
                            
                            while ($member = mysqli_fetch_assoc($members_result)) {
                                $progress = ($member['total_sessions'] > 0) 
                                    ? round(($member['completed_sessions'] / $member['total_sessions']) * 100) 
                                    : 0;
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($member['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($member['mobile_no']) . "</td>";
                                echo "<td>" . htmlspecialchars($member['Wplan_name'] ?? 'No plan assigned') . "</td>";
                                echo "<td>
                                        <div class='progress'>
                                            <div class='progress-bar' role='progressbar' 
                                                 style='width: {$progress}%' 
                                                 aria-valuenow='{$progress}' 
                                                 aria-valuemin='0' 
                                                 aria-valuemax='100'>
                                                {$progress}%
                                            </div>
                                        </div>
                                      </td>";
                                echo "<td>" . date('Y-m-d', strtotime($member['assigned_date'])) . "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <div class="mt-4">
                        <a href="staff_management.php" class="admin-button">Back to Staff Management</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    .progress {
        height: 20px;
        background-color: #f8f9fa;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 0;
    }

    .progress-bar {
        background-color: #ed563b;
        transition: width 0.3s ease;
    }

    .admin-button {
        display: inline-block;
        background-color: #ed563b;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .admin-button:hover {
        background-color: #f9735b;
        color: #fff;
        text-decoration: none;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
        margin: 0 2px;
    }

    .view-details {
        background-color: #17a2b8;
    }

    .view-details:hover {
        background-color: #138496;
    }
    </style>

    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html> 