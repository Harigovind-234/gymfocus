<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
try {
    $query = "SELECT l.username, l.email, r.full_name, r.mobile_no, r.address 
              FROM login l 
              LEFT JOIN register r ON l.user_id = r.user_id 
              WHERE l.user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    // Fetch profile picture
    $pic_query = "SELECT pic_url FROM profilepictures WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1";
    $pic_stmt = $conn->prepare($pic_query);
    $pic_stmt->bind_param("i", $user_id);
    $pic_stmt->execute();
    $pic_result = $pic_stmt->get_result();
    $pic_data = $pic_result->fetch_assoc();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - FOCUS GYM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 3px solid #ed563b;
            position: relative;
        }
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .info-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: 600;
            color: #ed563b;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-size: 1.1em;
        }
        .edit-btn {
            background-color: #ed563b;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .edit-btn:hover {
            background-color: #dc472e;
            color: white;
            transform: translateY(-2px);
        }
        .back-btn {
            background-color: #6c757d;
            color: white;
        }
        .back-btn:hover {
            background-color: #5a6268;
            color: white;
        }
        .membership-info-card {
            margin-top: 20px;
        }
        .membership-details {
            padding: 15px;
            position: relative;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .status-badge.active {
            background: #28a745;
            color: white;
        }
        .status-badge.inactive {
            background: #dc3545;
            color: white;
        }
        .membership-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .membership-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .item-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .item-label i {
            color: #ed563b;
            margin-right: 5px;
        }
        .item-value {
            font-weight: 600;
            color: #333;
        }
        .days-remaining {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #e8f5e9;
            color: #2e7d32;
            margin-top: 20px;
        }
        .days-remaining.expiring {
            background: #fff3e0;
            color: #e65100;
        }
        .days-remaining i {
            margin-right: 8px;
        }
        .no-membership {
            text-align: center;
            padding: 30px;
        }
        .no-membership i {
            font-size: 3em;
            color: #ccc;
            margin-bottom: 15px;
        }
        .no-membership p {
            color: #666;
            margin-bottom: 20px;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-image">
                    <img src="<?php 
                        echo !empty($pic_data['pic_url']) 
                            ? 'uploads/' . htmlspecialchars($pic_data['pic_url']) 
                            : 'assets/images/default-avatar.png'; 
                    ?>" alt="Profile Picture" id="profileImage">
                </div>
                <h2 class="mt-3"><?php echo htmlspecialchars($user_data['full_name'] ?? 'User'); ?></h2>
                <p class="text-muted">@<?php echo htmlspecialchars($user_data['username'] ?? 'username'); ?></p>
            </div>

            <!-- Profile Information -->
            <div class="row">
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($user_data['email'] ?? 'Not set'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="info-label">Mobile Number</div>
                        <div class="info-value">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($user_data['mobile_no'] ?? 'Not set'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <div class="info-label">Address</div>
                <div class="info-value">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <?php echo nl2br(htmlspecialchars($user_data['address'] ?? 'Not set')); ?>
                </div>
            </div>

            <!-- Add this section after the address info card -->
            <div class="row">
                <div class="col-12">
                    <div class="info-card membership-info-card">
                        <div class="info-label">Membership Status</div>
                        <?php
                        // Fetch latest membership details
                        $membership_query = "SELECT 
                            membership_status,
                            payment_type,
                            last_payment_date,
                            next_payment_date,
                            payment_amount,
                            payment_method,
                            transaction_id
                        FROM memberships 
                        WHERE user_id = ? 
                        AND payment_status = 'completed'
                        ORDER BY membership_id DESC 
                        LIMIT 1";

                        try {
                            $stmt = $conn->prepare($membership_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $membership = $result->fetch_assoc();
                                $days_remaining = ceil((strtotime($membership['next_payment_date']) - time()) / (60 * 60 * 24));
                                ?>
                                <div class="membership-details">
                                    <div class="status-badge <?php echo $membership['membership_status']; ?>">
                                        <?php echo ucfirst($membership['membership_status']); ?>
                                    </div>

                                    <div class="membership-grid">
                                        <div class="membership-item">
                                            <div class="item-label">
                                                <i class="fas fa-tag"></i> Plan Type
                                            </div>
                                            <div class="item-value">
                                                <?php echo ucfirst($membership['payment_type']); ?> Membership
                                            </div>
                                        </div>

                                        <div class="membership-item">
                                            <div class="item-label">
                                                <i class="fas fa-rupee-sign"></i> Last Payment
                                            </div>
                                            <div class="item-value">
                                                ₹<?php echo number_format($membership['payment_amount'], 2); ?>
                                            </div>
                                        </div>

                                        <div class="membership-item">
                                            <div class="item-label">
                                                <i class="fas fa-calendar-check"></i> Payment Date
                                            </div>
                                            <div class="item-value">
                                                <?php echo date('d M Y', strtotime($membership['last_payment_date'])); ?>
                                            </div>
                                        </div>

                                        <div class="membership-item">
                                            <div class="item-label">
                                                <i class="fas fa-calendar-alt"></i> Next Due Date
                                            </div>
                                            <div class="item-value">
                                                <?php echo date('d M Y', strtotime($membership['next_payment_date'])); ?>
                                            </div>
                                        </div>

                                        <div class="membership-item">
                                            <div class="item-label">
                                                <i class="fas fa-credit-card"></i> Payment Method
                                            </div>
                                            <div class="item-value">
                                                <?php echo ucfirst($membership['payment_method']); ?>
                                            </div>
                                        </div>

                                        <div class="membership-item">
                                            <div class="item-label">
                                                <i class="fas fa-receipt"></i> Transaction ID
                                            </div>
                                            <div class="item-value">
                                                <?php echo $membership['transaction_id']; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="days-remaining <?php echo ($days_remaining <= 5) ? 'expiring' : ''; ?>">
                                        <?php if ($days_remaining > 0): ?>
                                            <i class="fas fa-clock"></i>
                                            <?php echo $days_remaining; ?> days remaining until next payment
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Membership Expired
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="no-membership">
                                    <i class="fas fa-user-times"></i>
                                    <p>No active membership found</p>
                                    <a href="payment_membership.php" class="btn edit-btn">
                                        <i class="fas fa-plus"></i> Join Now
                                    </a>
                                </div>
                                <?php
                            }
                        } catch (Exception $e) {
                            error_log("Error fetching membership details: " . $e->getMessage());
                            echo '<div class="error-message">Unable to fetch membership details</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="index.php" class="btn back-btn me-2">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
                <a href="edit_profile_user.php" class="btn edit-btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 