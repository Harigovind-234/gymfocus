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

            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="staff.php" class="btn back-btn me-2">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
                <a href="edit_profile_staff.php" class="btn edit-btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 