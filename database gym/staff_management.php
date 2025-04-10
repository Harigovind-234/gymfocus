<?php
session_start();
if (!isset($_SESSION['user_id']) ) {
    header("Location: login2.php");
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connect.php';

// Check if user is admin


// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];

    $conn->begin_transaction();

    try {
        if ($action === 'approve') {
            // Update login table
            $stmt1 = $conn->prepare("UPDATE login SET role = 'staff' WHERE user_id = ?");
            $stmt1->bind_param("i", $user_id);
            $stmt1->execute();

            // Update register table
            $stmt2 = $conn->prepare("UPDATE register SET status = 'approved' WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
        } else if ($action === 'reject') {
            // Update status to rejected
            $stmt2 = $conn->prepare("UPDATE register SET status = 'rejected' WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
        }

        $conn->commit();
        $success_message = "Staff application " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error processing request: " . $e->getMessage();
    }
}

// Fetch pending staff applications
$query = "SELECT r.*, l.email, l.role 
          FROM register r 
          JOIN login l ON r.user_id = l.user_id 
          WHERE l.role = 'pending_staff' 
          ORDER BY r.user_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - FOCUS GYM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
   
    <style>
        body {
            background-color: #f4f6f9;
            /* font-family: 'Poppins', sans-serif;
            padding-top: 80px; Add padding for fixed header */
        }

        /* Header styles from members.php */
        .header-area {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #232d39 !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .header-area .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header-area .main-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .header-area .logo {
            color: #ed563b;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.5px;
        }

        .header-area .logo em {
            color: #fff;
            font-style: normal;
            font-weight: 300;
        }

        .header-area .nav {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .header-area .nav li {
            margin-left: 25px;
        }

        .header-area .nav li a {
            text-decoration: none;
            text-transform: uppercase;
            font-size: 13px;
            font-weight: 500;
            color: #fff;
            transition: color 0.3s ease;
        }

        .header-area .nav li a:hover,
        .header-area .nav li a.active {
            color: #ed563b;
        }

        .header-area .nav .main-button a {
            display: inline-block;
            background-color: #ed563b;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .header-area .nav .main-button a:hover {
            background-color: #f9735b;
        }
 /* Container styles */
 .members-container {
        max-width: 1200px;
        margin: 100px auto 30px;
        padding: 0 20px;
    }

    .members-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        padding: 30px;
    }

    .members-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #ed563b;
    }

    .members-title {
        color: #232d39;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        position: relative;
        padding-left: 15px;
    }

    .members-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 25px;
        background: #ed563b;
        border-radius: 2px;
    }

    .assign-members-btn {
        background: #ed563b;
        color: white;
        padding: 12px 25px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(237, 86, 59, 0.2);
    }

    .assign-members-btn:hover {
        background: #f9735b;
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }

    .members-count {
        background: #232d39;
        color: white;
        padding: 10px 20px;
        border-radius: 20px;
        font-size: 15px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .staff-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid #eee;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .staff-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #ed563b;
        border-radius: 2px;
    }

    .staff-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .staff-card h4 {
        color: #232d39;
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .staff-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .info-group {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #eee;
    }

    .info-group strong {
        color: #232d39;
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-group p {
        color: #666;
        margin: 0;
        font-size: 15px;
        line-height: 1.5;
    }

    .status-badge {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        text-transform: capitalize;
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .status-approved {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .status-rejected {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-success {
        background: #28a745;
        color: white;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
    }

    .btn-danger {
        background: #dc3545;
        color: white;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .staff-info {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
        }
    }

    .staff-container {
        padding: 20px;
        overflow-x: auto;
    }

    .staff-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .staff-table th,
    .staff-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .staff-table th {
        background-color: #f5f5f5;
        color: #333;
        font-weight: 600;
    }

    .staff-table tr:hover {
        background-color: #f9f9f9;
    }

    .staff-name {
        font-weight: 500;
        color: #2c3e50;
    }

    .action-buttons {
        white-space: nowrap;
    }

    .assign-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        background: #3498db;
        color: white;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.9em;
        transition: background-color 0.3s;
    }

    .assign-btn:hover {
        background: #2980b9;
    }

    .assign-btn i {
        font-size: 0.9em;
    }

    @media screen and (max-width: 768px) {
        .staff-table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
    }

    .pending-registrations-section {
        margin-bottom: 30px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-start;
    }

    .approve-btn, .reject-btn {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9em;
        transition: all 0.3s ease;
    }

    .approve-btn {
        background-color: #28a745;
        color: white;
    }

    .reject-btn {
        background-color: #dc3545;
        color: white;
    }

    .approve-btn:hover {
        background-color: #218838;
    }

    .reject-btn:hover {
        background-color: #c82333;
    }

    .staff-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .staff-table th {
        background-color: #f8f9fa;
        color: #232d39;
        padding: 12px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
    }

    .staff-table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
    }

    .staff-table tr:hover {
        background-color: #f8f9fa;
    }

    .section-title {
        color: #232d39;
        font-size: 1.5em;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ed563b;
    }

    @media screen and (max-width: 768px) {
        .staff-table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .action-buttons {
            flex-direction: column;
            gap: 5px;
        }
        
        .approve-btn, .reject-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Certificate Display Styles */
    .certificate-cell {
        max-width: 200px;
    }

    .certificate-list {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .certificate-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 8px;
        background: #f8f9fa;
        border-radius: 4px;
        font-size: 0.9em;
    }

    .certificate-item i {
        color: #ed563b;
    }

    .certificate-link {
        color: #2c3e50;
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .certificate-link:hover {
        color: #ed563b;
    }

    .no-certificates {
        color: #6c757d;
        font-style: italic;
        font-size: 0.9em;
    }

    /* Preview Modal Styles */
    .certificate-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1050;
    }

    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        max-width: 90%;
        max-height: 90vh;
        position: relative;
    }

    .close-modal {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #dc3545;
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 500;
        display: inline-block;
    }

    .status-badge.active {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .status-badge.blocked {
        background-color: #ffebee;
        color: #c62828;
    }

    /* Button Styles */
    .block-btn, .unblock-btn {
        min-width: 100px;
        margin: 0 5px;
    }

    .block-btn {
        background-color: #ff9800;
        border-color: #ff9800;
        color: white;
    }

    .block-btn:hover {
        background-color: #f57c00;
        border-color: #f57c00;
    }

    .unblock-btn {
        background-color: #4caf50;
        border-color: #4caf50;
        color: white;
    }

    .unblock-btn:hover {
        background-color: #388e3c;
        border-color: #388e3c;
    }

    .action-cell {
        white-space: nowrap;
        display: flex;
        gap: 8px;
    }

    .staff-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .staff-table th,
    .staff-table td {
        padding: 12px;
        border: 1px solid #ddd;
    }

    .staff-table th {
        background-color: #f5f5f5;
        font-weight: 600;
    }

    .staff-table tr:hover {
        background-color: #f8f9fa;
    }
    .header-area .nav .main-button {
          margin-left: 20px;
          display: flex;
          align-items: center;
        }

        .header-area .nav .main-button a {
          background-color: #ed563b;
          color: #fff !important;
          padding: 15px 30px !important;
          border-radius: 5px;
          font-weight: 600;
          font-size: 14px !important;
          text-transform: uppercase;
          transition: all 0.3s ease;
          display: inline-block;
          letter-spacing: 0.5px;
          line-height: 1.4;
          white-space: nowrap;
        }

        .header-area .nav .main-button a:hover {
          background-color: #f9735b;
          color: #fff !important;
          transform: translateY(-2px);
          box-shadow: 0 4px 15px rgba(237, 86, 59, 0.2);
        }

        /* Fix for mobile responsiveness */
        @media (max-width: 991px) {
          .header-area .nav .main-button a {
            padding: 12px 25px !important;
            font-size: 13px !important;
          }
        }

    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="admin.php" class="logo">Admin <em>Panel</em></a>
                        <ul class="nav">
                            <li><a href="admin.php">Home</a></li>
                            <li><a href="members.php">Members</a></li>
                            <li><a href="staff_management.php" class="active">Staff</a></li>
                            <li><a href="Payments_check.php">Payments</a></li>
                            <li><a href="products.php">Products</a></li>
                            <li class="main-button"><a href="logout.php">Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="members-container">
        <div class="members-card">
            <div class="members-header">
                <h2 class="members-title">Staff Management</h2>
               
            </div>

            <div class="members-header">
                <?php
                $staff_count = $conn->query("SELECT COUNT(*) as count FROM login WHERE role = 'staff'")->fetch_assoc()['count'];
                ?>
                <span class="members-count">Total Staff: <?php echo $staff_count; ?></span>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="staff-container">
                <!-- Add this before the approved staff table -->
                <div class="pending-registrations-section">
                    <h3 class="section-title">Staff Registration Requests</h3>
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Gender</th>
                                <th>Qualification</th>
                                <th>Experience</th>
                                <th>Specialization</th>
                                <th>Certificates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch pending staff registrations
                            $pending_sql = "SELECT r.*, l.email 
                                           FROM register r
                                           INNER JOIN login l ON r.user_id = l.user_id 
                                           WHERE l.role = 'pending_staff' 
                                           AND r.status = 'pending'
                                           ORDER BY r.created_at DESC";
                            $pending_result = mysqli_query($conn, $pending_sql);
                            
                            if (mysqli_num_rows($pending_result) > 0) {
                                while ($row = mysqli_fetch_assoc($pending_result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['mobile_no']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['qualification']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['experience']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
                                    echo "<td class='certificate-cell'>";
                                    if (!empty($row['certificates'])) {
                                        $certificates = explode(',', $row['certificates']);
                                        echo "<div class='certificate-list'>";
                                        foreach ($certificates as $cert) {
                                            $ext = pathinfo($cert, PATHINFO_EXTENSION);
                                            $icon = (strtolower($ext) == 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                                            
                                            echo "<div class='certificate-item'>";
                                            echo "<i class='fas {$icon}'></i>";
                                            echo "<a href='uploads/certificates/{$cert}' target='_blank' class='certificate-link'>";
                                            echo htmlspecialchars(substr($cert, strpos($cert, '_') + 1));
                                            echo "</a>";
                                            echo "</div>";
                                        }
                                        echo "</div>";
                                    } else {
                                        echo "<span class='no-certificates'>No certificates uploaded</span>";
                                    }
                                    echo "</td>";
                                    echo "<td class='action-buttons'>";
                                    echo "<form method='POST' style='display: inline-block;'>";
                                    echo "<input type='hidden' name='user_id' value='" . $row['user_id'] . "'>";
                                    echo "<input type='hidden' name='action' value='approve'>";
                                    echo "<button type='submit' class='approve-btn'><i class='fa fa-check'></i> Approve</button>";
                                    echo "</form>";
                                    echo "<form method='POST' style='display: inline-block;'>";
                                    echo "<input type='hidden' name='user_id' value='" . $row['user_id'] . "'>";
                                    echo "<input type='hidden' name='action' value='reject'>";
                                    echo "<button type='submit' class='reject-btn'><i class='fa fa-times'></i> Reject</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align: center;'>No pending staff registration requests</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Gender</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>Qualification</th>
                            <th>Certificates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $staff_sql = "SELECT r.*, l.email, l.status as login_status 
                                     FROM register r
                                     INNER JOIN login l ON r.user_id = l.user_id 
                                     WHERE l.role = 'staff'
                                     ORDER BY r.created_at DESC";
                        
                        $staff_result = mysqli_query($conn, $staff_sql);
                        
                        if (mysqli_num_rows($staff_result) > 0) {
                            while ($row = mysqli_fetch_assoc($staff_result)) {
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['mobile_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                    <td><?php echo htmlspecialchars($row['experience']); ?></td>
                                    <td><?php echo htmlspecialchars($row['qualification']); ?></td>
                                    <td class="certificate-cell">
                                        <?php if (!empty($row['certificates'])): ?>
                                            <div class="certificate-list">
                                                <?php 
                                                $certificates = explode(',', $row['certificates']);
                                                foreach ($certificates as $cert): 
                                                    $ext = strtolower(pathinfo($cert, PATHINFO_EXTENSION));
                                                    $icon = in_array($ext, ['jpg', 'jpeg', 'png']) ? 'fa-image' : 'fa-file-pdf';
                                                ?>
                                                    <div class="certificate-item">
                                                        <i class="fas <?php echo $icon; ?>"></i>
                                                        <a href="uploads/certificates/<?php echo htmlspecialchars($cert); ?>" 
                                                           class="certificate-link"
                                                           data-type="<?php echo $ext; ?>"
                                                           target="_blank">
                                                            <?php echo htmlspecialchars(substr($cert, strpos($cert, '_') + 1)); ?>
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-certificates">No certificates uploaded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="status-cell">
                                        <span class="status-badge <?php echo $row['login_status'] === 'active' ? 'active' : 'blocked'; ?>">
                                            <?php echo ucfirst($row['login_status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        <a href="assign_members.php?staff_id=<?php echo $row['user_id']; ?>" 
                                           class="btn btn-primary btn-sm assign-btn">
                                            <i class="fas fa-users"></i> Assign Members
                                        </a>
                                        <?php if ($row['login_status'] === 'active'): ?>
                                            <button class="btn btn-warning btn-sm block-btn" 
                                                    onclick="confirmStatusUpdate(<?php echo $row['user_id']; ?>, 'blocked')">
                                                <i class="fas fa-ban"></i> Block
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-sm unblock-btn" 
                                                    onclick="confirmStatusUpdate(<?php echo $row['user_id']; ?>, 'active')">
                                                <i class="fas fa-check-circle"></i> Unblock
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No staff members found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/popper.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    
    <!-- Add this new script for header behavior -->
    <script>
        window.addEventListener('scroll', function() {
            let header = document.querySelector('.header-area');
            if (window.scrollY > 0) {
                header.classList.add('header-sticky');
            } else {
                header.classList.remove('header-sticky');
            }
        });

        // Keep your existing search functionality
        document.getElementById('liveSearch').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const staffCards = document.getElementsByClassName('staff-card');
            
            Array.from(staffCards).forEach(card => {
                const name = card.querySelector('h4').textContent.toLowerCase();
                if (name.includes(searchText)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Update the certificate preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const certificateLinks = document.querySelectorAll('.certificate-link');
            
            certificateLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const fileType = this.getAttribute('data-type');
                    if(['jpg', 'jpeg', 'png'].includes(fileType)) {
                        e.preventDefault();
                        const modal = document.createElement('div');
                        modal.className = 'certificate-modal';
                        modal.innerHTML = `
                            <div class="modal-content">
                                <button class="close-modal">&times;</button>
                                <img src="${this.href}" style="max-width: 100%; max-height: 80vh; object-fit: contain;">
                            </div>
                        `;
                        document.body.appendChild(modal);
                        
                        // Close modal handlers
                        modal.querySelector('.close-modal').onclick = () => modal.remove();
                        modal.onclick = (e) => {
                            if(e.target === modal) modal.remove();
                        };
                    }
                });
            });
        });
    </script>

    <script>
    function confirmStatusUpdate(userId, status) {
        const action = status === 'blocked' ? 'block' : 'unblock';
        if (confirm(`Are you sure you want to ${action} this staff member?`)) {
            updateStaffStatus(userId, status);
        }
    }

    function updateStaffStatus(userId, status) {
        // Create form data
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('status', status);

        // Send request
        fetch('update_staff_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();  // Change to text() instead of json()
        })
        .then(data => {
            try {
                const result = JSON.parse(data);
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Unknown error occurred'));
                }
            } catch (e) {
                console.error('Error parsing response:', data);
                alert('An error occurred while processing the request');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating staff status');
        });
    }
    
    </script>
</body>
</html> 