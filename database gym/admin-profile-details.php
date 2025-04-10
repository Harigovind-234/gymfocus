<?php
include 'connect.php';

if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit();
}

$sql = "SELECT register.*, login.role,login.email FROM register INNER JOIN login ON register.user_id = login.user_id WHERE login.role = 'Member' ORDER BY register.created_at DESC";
            $result = mysqli_query($conn, $sql);
            
            if (!$result) {
                die("Query failed: " . mysqli_error($conn));
            }

if (!$result || mysqli_num_rows($result) === 0) {
    header('Location: admin.php');
    exit();
}

$member = mysqli_fetch_assoc($result);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_GET['user_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE register SET status = ? WHERE User_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $member_id);
    
    $response = ['success' => $stmt->execute()];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile - Training Studio</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #232d39;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header {
            background-color: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            position: relative;
        }

        .back-button {
            position: absolute;
            top: 30px;
            right: 30px;
            background-color: #ed563b;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #f9735b;
        }

        .profile-title {
            color: #232d39;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .member-status {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .info-card {
            background-color: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .info-card h3 {
            color: #ed563b;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
        }

        .info-item {
            margin-bottom: 20px;
        }

        .info-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #232d39;
            font-size: 16px;
            font-weight: 500;
        }

        .membership-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.3s;
        }

        .btn-block {
            background-color: #dc3545;
            color: white;
        }

        .btn-block:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
        // Add these styles
<style>
.status {
    padding: 5px 10px;
    border-radius: 4px;
    display: inline-block;
}

.status.blocked {
    background-color: #dc3545;
    color: white;
}

.btn-block {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-block:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}
</style>
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <a href="admin.php" class="back-button">Back to Dashboard</a>
            <h1 class="profile-title">Member Profile</h1>
            <span class="member-status">Active Member</span>
        </div>

        <div class="profile-grid">
            <div class="info-card">
                <h3>Personal Information</h3>
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($member['full_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($member['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Mobile Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($member['mobile_no']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($member['address']); ?></div>
                </div>
            </div>

            <div class="info-card">
                <h3>Membership Details</h3>
                <div class="info-item">
                    <div class="info-label">Member ID</div>
                    <div class="info-value">MEM<?php echo str_pad($member['user_id'], 4, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Join Date</div>
                    <div class="info-value"><?php echo date('F d, Y', strtotime($member['created_at'])); ?></div>
                </div>
                <!-- <div class="info-item">
                    <div class="info-label">Membership Type</div>
                    <div class="info-value"><?php echo htmlspecialchars($member['membership_type']); ?></div>
                </div> -->
<div class="membership-details">
    <div class="info-label">Membership Status</div>
    <div class="info-value status" id="memberStatus">
        <?php echo htmlspecialchars($member['status'] ?? 'Active'); ?>
    </div>
</div>
<div class="action-buttons">
    <button class="btn btn-block" id="blockButton" 
            <?php echo ($member['status'] === 'Blocked') ? 'disabled' : ''; ?>>
        Block Member
    </button>
</div>
            </div>

            
    </div>

<script>
document.getElementById('blockButton').addEventListener('click', function() {
    if (confirm('Are you sure you want to block this member?')) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'status=Blocked'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('memberStatus').textContent = 'Blocked';
                document.getElementById('memberStatus').classList.add('blocked');
                this.disabled = true;
            }
        });
    }
});
</script>
</body>
</html>