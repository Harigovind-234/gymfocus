<?php 
session_start();
include "connect.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: login2.php");
    exit();
}


// Function to check booking status
function checkBookingStatus($conn, $user_id) {
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    $current_hour = (int)date('H');

    // Function to check if session is over
    function isSessionOver($session_time, $current_hour) {
        if ($session_time === 'morning' && $current_hour >= 10) {
            return true; // Morning session is over after 10 AM
        }
        if ($session_time === 'evening' && $current_hour >= 21) {
            return true; // Evening session is over after 9 PM
        }
        return false;
    }

    // Get all active bookings for the user
    $query = "SELECT sb.*, r.preferred_session 
              FROM slot_bookings sb 
              JOIN register r ON sb.user_id = r.user_id 
              WHERE sb.user_id = ? 
              AND sb.cancelled_at IS NULL 
              AND sb.booking_date >= ? 
              ORDER BY sb.booking_date ASC";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        // Handle error - query preparation failed
        error_log("Error preparing query: " . $conn->error);
        return array(); // Return empty array instead of failing
    }
    $stmt->bind_param("is", $user_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = array();
    
    while ($row = $result->fetch_assoc()) {
        $booking_date = $row['booking_date'];
        $session_time = $row['time_slot'];
        
        // If it's current date, check if session is over
        if ($booking_date === $current_date) {
            if (isSessionOver($session_time, $current_hour)) {
                // Update the booking as completed
                $update_query = "UPDATE slot_bookings 
                               SET status = 'completed' 
                               WHERE id = ? 
                               AND cancelled_at IS NULL";
                $update_stmt = $conn->prepare($update_query);
                if (!$update_stmt) {
                    // Handle error - update query preparation failed
                    error_log("Error preparing update query: " . $conn->error);
                    continue; // Skip this update and continue with other bookings
                }
                $update_stmt->bind_param("i", $row['id']);
                $update_stmt->execute();
                
                continue; // Skip this booking as it's over
            }
        }
        
        // Add booking to array if it's not over
        $bookings[] = array(
            'id' => $row['user_id'],
            'date' => $booking_date,
            'session' => $session_time,
            'preferred_session' => $row['preferred_session']
        );
    }
    
    return $bookings;
}

// Initialize current_booking variable
$current_booking = null;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Get current booking status
        $current_booking = checkBookingStatus($conn, $_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Error getting booking status: " . $e->getMessage());
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

// Get user's current session
$user_id = $_SESSION['user_id'];
$current_session_query = "SELECT r.preferred_session, r.mobile_no 
                         FROM register r 
                         JOIN login l ON r.user_id = l.user_id 
                         WHERE r.user_id = ?";

$stmt = $conn->prepare($current_session_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$current_session = $user_data['preferred_session'];
$_SESSION['mobile_no'] = $user_data['mobile_no']; // Assuming mobile_no is the column name in your database

// Handle session change request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_session'])) {
    $new_session = $_POST['new_session'];
    
    // Prevent booking same session
    if ($new_session === $current_session) {
        $_SESSION['error'] = "You are already enrolled in this session.";
    } else {
        // Update the session
        $update_query = "UPDATE register SET preferred_session = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_session, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Training session updated successfully!";
            $current_session = $new_session; // Update current session
        } else {
            $_SESSION['error'] = "Failed to update training session.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <title>Training Studio - Free CSS Template</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
    <link rel="stylesheet" href="membership.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="assets/css/edit-profile.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
.schedule-section {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            padding: 80px 0;
            color: #fff;
        }

        .gradient-text {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .subtitle {
            color: #888;
            font-size: 1.2rem;
            margin-bottom: 40px;
        }

        .timetable-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .day-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
        }

        .day-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b6b;
        }

        .day-subtitle {
            font-size: 0.8rem;
            color: #888;
            margin: 5px 0;
        }

        .day-icon {
            font-size: 1.2rem;
            color: #4ecdc4;
            margin-top: 5px;
        }

        .workout-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            transition: transform 0.3s ease;
        }

        .workout-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .workout-icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4ecdc4;
        }

        .workout-info h4 {
            color: #fff;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .intensity {
            display: block;
            font-size: 0.8rem;
            color: #ff6b6b;
            margin-bottom: 3px;
        }

        .trainer {
            display: block;
            font-size: 0.8rem;
            color: #888;
        }

        .time-container {
            text-align: center;
            padding: 10px;
        }

        .time-main {
            display: block;
            font-size: 1rem;
            color: #4ecdc4;
            font-weight: 600;
        }

        .time-sub {
            display: block;
            font-size: 0.8rem;
            color: #888;
            margin-top: 5px;
        }

        /* Workout type specific colors */
        .cardio .workout-icon { color: #ff6b6b; }
        .strength .workout-icon { color: #4ecdc4; }
        .hiit .workout-icon { color: #ffd93d; }
        .weights .workout-icon { color: #6c5ce7; }
        .yoga .workout-icon { color: #a8e6cf; }
 
    <!-- Profile Section with Updated Design -->
    /* Updated Profile Avatar Styles */
    .profile-avatar {
        position: relative;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid #ed563b;
        box-shadow: 0 0 20px rgba(237,86,59,0.3);
        background: linear-gradient(45deg, #ed563b, #ff7d6b);
        cursor: pointer;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-initials {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: calc(90px * 0.4);
        font-weight: bold;
        color: white;
        text-transform: uppercase;
    }

    .avatar-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .avatar-overlay i {
        color: white;
        font-size: 24px;
    }

    .profile-avatar:hover .avatar-overlay {
        opacity: 1;
    }

    /* Update the modal form styles */
    .modal-content {
        max-height: 80vh;
        overflow-y: auto;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        padding: 20px;
    }
    <style>
    .product-card {
        background: linear-gradient(145deg,rgb(91, 88, 88),rgb(78, 73, 73));
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-5px);
    }

    .product-image {
        width: 100%;
        height: 250px;
        overflow: hidden;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        padding: 20px;
    }

    .product-info {
        padding: 20px;
    }

    .product-info h4 {
        color: #232d39;
        margin-bottom: 10px;
    }

    .category {
        color: #ed563b;
        font-size: 14px;
        display: block;
        margin-bottom: 10px;
    }

    .price {
        font-size: 24px;
        color: #232d39;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .description {
        color: #7a7a7a;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .modal-content {
        border-radius: 15px;
    }

    .modal-header {
        background-color: #ed563b;
        color: white;
        border-radius: 15px 15px 0 0;
    }

    .pickup-info {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
    }

    .total-price {
        font-size: 18px;
        padding: 10px 0;
        border-top: 1px solid #dee2e6;
    }
    /* Modal styles */
.modal {
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.2);
}

.modal-header {
    background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
    color: white;
    border-radius: 15px 15px 0 0;
}

.modal-header .close {
    color: white;
}

.form-group {
    margin-bottom: 1rem;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ddd;
}

.btn-primary {
    background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #4ecdc4, #ff6b6b);
}

/* Make modal visible */
.modal.show {
    display: block !important;
    opacity: 1 !important;
}

.modal-backdrop {
    opacity: 0.5;
}

/* Ensure modal is above other elements */
.modal {
    z-index: 1050 !important;
}

.modal-backdrop {

    z-index: 1040 !important;
}
// ... existing code ...
    <style>
        /* Add navbar styles at the top of the existing styles */
        .header-area {
            background-color: #232d39 !important;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        .header-area .main-nav .nav li a {
            color: #fff !important;
        }

        .header-area .main-nav .nav li a:hover,
        .header-area .main-nav .nav li a.active {
            color: #ed563b !important;
        }

        .header-area .main-nav .logo {
            color: #fff !important;
        }

        .header-area .main-nav .logo em {
            color: #ed563b !important;
        }

        .header-area.header-sticky {
            background-color: #232d39 !important;
            height: 80px !important;
        }



    </style>

<style>
.profile-preview-section {
    padding: 80px 0;
    background: #f8f9fa;
}

.profile-preview-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    max-width: 600px;
    margin: 0 auto;
    transition: transform 0.3s ease;
}

.profile-preview-card:hover {
    transform: translateY(-5px);
}

.profile-preview-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.profile-image {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #ed563b;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-initials {
    width: 100%;
    height: 100%;
    background: #ed563b;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2.5rem;
    font-weight: bold;
}

.profile-name {
    text-decoration: none;
    color: #232d39;
    transition: color 0.3s ease;
}

.profile-name:hover {
    color: #ed563b;
}

.profile-name h2 {
    margin: 0;
    font-size: 1.8rem;
}

.view-profile {
    font-size: 0.9rem;
    color: #ed563b;
    display: block;
    margin-top: 5px;
}

.profile-preview-details {
    border-top: 1px solid #eee;
    padding-top: 20px;
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.detail-row:hover {
    background: #f0f0f0;
}

.detail-row i {
    color: #ed563b;
    font-size: 1.2rem;
}

.view-full-profile-btn {
    display: block;
    text-align: center;
    background: #ed563b;
    color: white;
    padding: 12px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.view-full-profile-btn:hover {
    background: #da4a30;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(237, 86, 59, 0.3);
    color: white;
}

@media (max-width: 768px) {
    .profile-preview-card {
        margin: 0 20px;
    }

    .profile-preview-header {
        flex-direction: column;
        text-align: center;
    }

    .detail-row {
        flex-direction: column;
        text-align: center;
        gap: 5px;
    }
}
</style>
<style>
.hero-section {
    position: relative;
    height: 100vh;
    overflow: hidden;
    margin-top: 80px;
}

.slideshow-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transition: opacity 1s ease-in-out;
    animation: zoom 20s infinite;
}

.slide.active {
    opacity: 1;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(35, 45, 57, 0.7);
    background-image: linear-gradient(
        to bottom,
        rgba(35, 45, 57, 0.8),
        rgba(35, 45, 57, 0.6)
    );
}

@keyframes zoom {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.hero-content {
    position: relative;
    z-index: 2;
    padding-top: 150px;
}

.gym-name {
    font-size: 5rem;
    font-weight: 800;
    color: #fff;
    text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
}

.highlight {
    color: #ed563b;
}

.welcome-message h2 {
    color: #fff;
    font-size: 2.5rem;
    margin: 30px 0;
}

.hero-quote {
    color: #fff;
    font-size: 1.8rem;
    font-style: italic;
    margin-top: 30px;
}

.user-name {
    color: #ed563b;
    font-weight: 600;
}
</style>
<style>
.booking-buttons {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.main-button {
    display: inline-block;
    color: #fff !important;
    text-align: center;
    padding: 5px 5px;
    border-radius: 5px;
    text-decoration: none !important;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    font-weight: 500;
}

.main-button:hover {
    background-color: #f9735b;
    color: #fff !important;
}

.main-button a {
    color: #fff !important;
    text-decoration: none !important;
    padding: 0px;
}


</style>



    </head>
    <>
        <!-- ***** Preloader Start ***** -->
        <div id="js-preloader" class="js-preloader">
            <div class="preloader-inner">
                <span class="dot"></span>
                <div class="dots"></div>
            </div>
        </div>
        <!-- ***** Preloader End ***** -->
        <!-- ***** Header Area Start ***** -->
        <header class="header-area header-sticky">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <nav class="main-nav"><!-- ***** Logo Start ***** -->
                            <a href="index.html" class="logo" ><img src=""></a><!-- ***** Logo End ***** -->
                                <!-- ***** Menu Start ***** -->
                                <ul class="nav">
                                    <li class="scroll-to-section"><a href="#hero-section" class="active">Home</a></li>
                                    <li class="scroll-to-section"><a href="#profile-section">Profile</a></li>
                                    <li class="scroll-to-section"><a href="#our-classes">Classes</a></li>
                                    <li class="scroll-to-section"><a href="#schedule">Schedules</a></li>
                                    <li class="scroll-to-section"><a href="#memberships">Memberships</a></li>
                                    <li class="scroll-to-section"><a href="#products">Products</a></li> 
                                    <li class="main-button"><a href="logout.php">Logout</a></li>
                                </ul>        
                            <a class='menu-trigger'><span>Menu</span></a>
                            <!-- ***** Menu End ***** -->
                        </nav>
                    </div>
                </div>
            </div>
        </header>

        <section class="hero-section" id="hero-section">
    <!-- Background Slideshow -->
    <div class="slideshow-container">
        <div class="slide fade" style="background-image: url('./assets/images/imageintro4.jpg')"></div>
        <div class="slide fade" style="background-image: url('./assets/images/imageintro3.jpg')"></div>
        <div class="slide fade" style="background-image: url('./assets/images/gymintro.jpg')"></div>
        <div class="slide fade" style="background-image: url('./assets/images/imageintro5.jpg')"></div>
        
    </div>
    <!-- <div class="hero-overlay"></div> -->
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="hero-content text-center">
                    <h1 class="gym-name">FOCUS<span class="highlight">GYM</span></h1>
                    <div class="welcome-message">
                        <h2>Welcome, <span class="full_name"><?php echo htmlspecialchars($_SESSION['name']); ?></span></h2>
                    </div>
                    <p class="hero-quote">"The only bad workout is the one that didn't happen"</p>
                </div>
            </div>
        </div>
    </div>
</section>
<!--Background css-->
<!-- profile section -->
 
<section id="profile-section" class="profile-preview-section">
    <div class="container">
    <div class="col-lg-6 offset-lg-3">
                    <div class="section-heading">
                        <h2>Your <em>Profile</em></h2>
                        <img src="assets/images/line-dec.png" alt="">   
                    </div>
                    <div class="profile-preview-card">
            <div class="profile-preview-header">
                <div class="profile-image">
                    <?php
                    // Fetch the latest profile picture
                    $pic_query = "SELECT pic_url FROM profilepictures WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1";
                    $stmt = $conn->prepare($pic_query);
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $pic_result = $stmt->get_result();
                    $pic_data = $pic_result->fetch_assoc();
                    ?>
                    <?php if($pic_data && $pic_data['pic_url']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($pic_data['pic_url']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="profile-initials">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="profileuser.php" class="profile-name">
                    <h2><?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                    <span class="view-profile">View Full Profile</span>
                </a>
            </div>
            <div class="profile-preview-details">
                <!-- <div class="detail-row">
                    <i class="fas fa-id-card"></i>
                    <span>ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
                </div> -->
                <div class="detail-row">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                </div>
                <div class="detail-row">
                    <i class="fas fa-phone"></i>
                    <span><?php echo isset($_SESSION['mobile_no']) ? htmlspecialchars($_SESSION['mobile_no']) : 'Not available'; ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="membership-item">
                    <div class="item-label">
                        <i class="fas fa-calendar-check"></i>
                        Membership Status
                        <div class="item-value">
                            <?php
                            // Fetch membership status
                            $membership_query = "SELECT 
                                membership_status,
                                next_payment_date,
                                payment_amount,
                                payment_type
                            FROM memberships 
                            WHERE user_id = ? 
                            AND payment_status = 'completed'
                            ORDER BY membership_id DESC 
                            LIMIT 1";

                            try {
                                $stmt = $conn->prepare($membership_query);
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $membership = $result->fetch_assoc();
                                    $days_remaining = ceil((strtotime($membership['next_payment_date']) - time()) / (60 * 60 * 24));
                                    $next_payment_date = date('d M Y', strtotime($membership['next_payment_date']));
                                    
                                    if ($days_remaining > 0) {
                                        echo "<div class='status-text active'>Active</div>";
                                        echo "<div class='payment-info'>";
                                        echo "<span class='next-payment'>Next Payment: ₹" . number_format($membership['payment_amount']) . "</span>";
                                        echo "<span class='due-date'>Due Date: {$next_payment_date}</span>";
                                        echo "<span class='days-left " . ($days_remaining <= 5 ? 'expiring' : '') . "'>";
                                        echo "<i class='fas fa-clock'></i> {$days_remaining} days remaining";
                                        echo "</span>";
                                        echo "</div>";
                                    } else {
                                        echo "<div class='status-text expired'>Expired</div>";
                                        echo "<div class='payment-info'>";
                                        echo "<span class='renewal-text'>Please renew your membership</span>";
                                        echo "<a href='payment_membership.php' class='renew-btn'>Renew Now</a>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<div class='status-text inactive'>No Active Membership</div>";
                                    echo "<div class='payment-info'>";
                                    echo "<a href='payment_membership.php' class='join-btn'>Join Now</a>";
                                    echo "</div>";
                                }
                            } catch (Exception $e) {
                                error_log("Error fetching membership details: " . $e->getMessage());
                                echo "<div class='error-text'>Unable to fetch status</div>";
                            }
                            ?>
                        </div>
                    </div>

                    
                </div></div>
                <div class="detail-row">
                    <i class="fas fa-user-shield"></i>
                    Assigned Trainer
                    <div class="item-value">
                        <?php
                        // Fetch assigned trainer name from register table
                        $trainer_query = "SELECT r.full_name as trainer_name 
                                         FROM staffassignedmembers sam 
                                         JOIN register r ON sam.trainer_id = r.user_id 
                                         WHERE sam.member_id = ? 
                                         ORDER BY sam.assigned_date DESC 
                                         LIMIT 1";
                        
                        try {
                            $stmt = $conn->prepare($trainer_query);
                            if ($stmt) {
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $trainer = $result->fetch_assoc();
                                    echo '<span class="trainer-name">' . htmlspecialchars($trainer['trainer_name']) . '</span>';
                                } else {
                                    echo '<span class="text-muted">Not assigned yet</span>';
                                }
                            } else {
                                echo '<span class="text-danger">Error fetching trainer details</span>';
                            }
                        } catch (Exception $e) {
                            echo '<span class="text-danger">Error occurred</span>';
                        }
                        ?>
                    </div>
                </div>
            <a href="profileuser.php" class="view-full-profile-btn">View Complete Profile</a>
        </div>
    </div>
</section>

                </div>
            </div>
       
 

      
    <!--classes-->
    <!---->
    <section class="section" id="our-classes">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 offset-lg-3">
                    <div class="section-heading">
                        <h2>Our <em>Gym Rules</em></h2>
                        <img src="assets/images/line-dec.png" alt="">
                        <p>To keep our gym safe, clean, and enjoyable for everyone, we follow a few simple rules. Be respectful, clean your equipment, use machines properly, and prioritize safety at all times. Together, we build a positive fitness community! 💪</p>
                    </div>
                </div>
            </div>
            <div class="row" id="tabs">
              <div class="col-lg-4">
                <ul>
                  <li><a href='#tabs-1'><img src="assets/images/tabs-first-icon.png" alt="">General Etiquette</a></li>
                  <li><a href='#tabs-2'><img src="assets/images/tabs-first-icon.png" alt="">Equipment Use</a></li>
                  <li><a href='#tabs-3'><img src="assets/images/tabs-first-icon.png" alt="">Cleanliness & Hygiene</a></li>
                  <li><a href='#tabs-4'><img src="assets/images/tabs-first-icon.png" alt="">Safety Guidelines</a></li>
                  
                </ul>
              </div>
              <div class="col-lg-8">
                <section class='tabs-content'>
                  <article id='tabs-1'>
                    <img src="C:\xampp\htdocs\miniproject2\database gym\assets\images\General Etiquette.jpeg" alt="First Class">
                    <h4>General Etiquette</h4>
                    <p>Creating a respectful and inclusive environment is everyone's responsibility. Please be courteous to fellow gym members by keeping noise to a minimum and avoiding disruptive behavior. Share equipment fairly during peak hours, and always allow others to “work in” between sets. Use headphones for personal audio and keep mobile phone use away from training areas to maintain focus and atmosphere.</p>
                   
                  </article>
                  <article id='tabs-2'>
                    <img src="assets/images/training-image-02.jpg" alt="Second Training">
                    <h4>Equipment Use</h4>
                    <p>Take pride in our shared space by using gym equipment responsibly. Return all weights, dumbbells, and accessories to their designated racks after use. Avoid slamming or dropping weights unnecessarily. Only use machines for their intended purpose, and ask for help if you're unsure. If you notice any broken or faulty equipment, notify staff immediately so it can be fixed.</p>
                   
                  </article>
                  <article id='tabs-3'>
                    <img src="assets/images/training-image-03.jpg" alt="Third Class">
                    <h4>Cleanliness & Hygiene</h4>
                    <p>Cleanliness is key to a healthy workout environment. Please bring a towel and use it on benches and mats. Wipe down machines and equipment after each use using the sanitizing sprays provided throughout the facility. Wear clean workout clothes and closed-toe athletic shoes at all times. Refrain from using strong perfumes or colognes, as they may cause discomfort to others.</p>
                   
                  </article>
                  <article id='tabs-4'>
                    <img src="assets/images/training-image-04.jpg" alt="Fourth Training">
                    <h4>Safety Guidelines</h4>
                    <p>Your safety is our top priority. Always warm up before exercising and use correct form to prevent injuries. If you’re unfamiliar with a machine or exercise, ask a trainer for assistance. Stay hydrated, listen to your body, and don’t push beyond your limits. In the event of an emergency or if you feel unwell, alert a staff member immediately.</p>
                    
                  </article>
                </section>
              </div>
            </div>
        </div>
    </section>
    
    <!--schedule-->
    <section id="schedule" class="section schedule-section">
    <div class="container">
        <!-- Header Section -->
        <div class="section-title text-center mb-5">
            <h2 class="gradient-text">Weekly <em>Workout Schedule</em></h2>
            <p class="subtitle">Train Smart, Stay Strong</p>
            <div class="schedule-info mt-4">
                <div class="info-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Updated Weekly</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-users"></i>
                    <span>Limited Spots Available</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-dumbbell"></i>
                    <span>Professional Trainers</span>
                </div>
            </div>
        </div>

        
        
        <?php
// Add this query to get all active bookings for the user
$user_id = $_SESSION['user_id'];
$bookings_sql = "SELECT * FROM slot_bookings 
                 WHERE user_id = '$user_id' 
                 AND cancelled_at IS NULL 
                 ORDER BY booking_date ASC";
$bookings_result = mysqli_query($conn, $bookings_sql);
?>


<?php
// Add this where you want to display the user's session
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT preferred_session FROM register WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_session = $result->fetch_assoc();

    if($user_session) {
        echo '<div class="user-session-info">';
        if($user_session['preferred_session'] == 'morning') {
            echo '<div class="session-badge morning">
                    <i class="fas fa-sun"></i> Morning Session Member
                    <span class="session-time">6:00 AM - 8:00 AM</span>
                  </div>';
        } else {
            echo '<div class="session-badge evening">
                    <i class="fas fa-moon"></i> Evening Session Member
                    <span class="session-time">5:00 PM - 7:00 PM</span>
                  </div>';
        }
        echo '</div>';
    }
}
?>

<style>
.user-session-info {
    text-align: center;
    margin: 20px 0;
    padding: 15px;
}

.session-badge {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 16px;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.session-badge.morning {
    background: linear-gradient(135deg, #ff9966, #ff5e62);
    color: white;
}

.session-badge.evening {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    color: white;
}

.session-time {
    font-size: 14px;
    opacity: 0.9;
    margin-left: 10px;
    padding-left: 10px;
    border-left: 1px solid rgba(255,255,255,0.3);
}
</style>



<div class="bookings-container">
    <div class="status-header"id="status-header">
        <h3><i class="fas fa-calendar-check"></i> Your Booking Status</h3>
    </div>

    <div class="bookings-list">
        <?php if(mysqli_num_rows($bookings_result) > 0): ?>
            <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                <div class="booking-card <?php echo $booking['time_slot']; ?>-session">
                    <div class="booking-info">
                        <div class="session-icon">
                            <i class="fas fa-<?php echo $booking['time_slot'] === 'morning' ? 'sun' : 'moon'; ?>"></i>
                        </div>
                        <div class="booking-details">
                            <h4><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></h4>
                            <div class="session-time">
                                <i class="far fa-clock"></i>
                                <?php echo $booking['time_slot'] === 'morning' ? '6:00 AM - 10:00 AM' : '4:00 PM - 9:00 PM'; ?>
                            </div>
                            <div class="session-type">
                                <i class="fas fa-dumbbell"></i>
                                <?php echo ucfirst($booking['time_slot']); ?> Workout Session
                            </div>
                            <div class="booking-status">
                                <?php 
                                $today = date('Y-m-d');
                                if($booking['booking_date'] == $today): ?>
                                    <span class="status-badge today">Today's Session</span>
                                <?php elseif($booking['booking_date'] > $today): ?>
                                    <span class="status-badge upcoming">Upcoming</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="booking-actions">
                        <button class="cancel-booking-btn" onclick="cancelBooking('<?php echo $booking['booking_id']; ?>')">
                            <i class="fas fa-times-circle"></i> Cancel Booking
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-bookings">
                <div class="empty-state">
                    <i class="fas fa-calendar-day"></i>
                    <h4>No Active Bookings</h4>
                    <p>Select a session below to book your workout slot</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
   .booking_h2
   {
    color:black
   }
.bookings-container {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.status-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.booking-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.morning-session {
    border-left: 4px solid #ffc107;
}

.evening-session {
    border-left: 4px solid #6610f2;
}

.session-icon {
    background: #fff;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.morning-session .session-icon i {
    color: #ffc107;
}

.evening-session .session-icon i {
    color: #6610f2;
}

.booking-info {
    display: flex;
    align-items: center;
}

.booking-details h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.session-time, .session-type {
    color: #fff;
    margin: 5px 0;
}

.session-time i, .session-type i {
    margin-right: 8px;
    width: 16px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.85em;
    margin-top: 8px;
}

.status-badge.today {
    background: #28a745;
    color: white;
}

.status-badge.upcoming {
    background: #007bff;
    color: white;
}

.cancel-booking-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.cancel-booking-btn:hover {
    background: #c82333;
}

.no-bookings {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state i {
    font-size: 3em;
    color: #ddd;
    margin-bottom: 15px;
}
</style>


<div class="booking-status-container">
    <div class="status-header" id="status-header">
        <div class="status-title">
            <i class="fas fa-clipboard-check"></i>
            <h3>Your Booking Status</h3>
        </div>
        <div class="membership-badge">
            <i class="fas fa-star"></i>
            Active Member
        </div>
    </div>

    <div class="status-card">
        <div class="status-content">
            <div class="status-icon">
                <?php if($current_session === 'morning'): ?>
                    <i class="fas fa-sun"></i>
                <?php else: ?>
                    <i class="fas fa-moon"></i>
                <?php endif; ?>
            </div>
            <div class="status-details">
                <div class="enrollment-status">Currently enrolled in</div>
                <h4 class="session-title">
                    <?php echo ucfirst($current_session); ?> Session
                </h4>
                <div class="session-time">
                    <i class="far fa-clock"></i>
                    <span>
                        <?php 
                            echo ($current_session === 'morning') 
                                ? '6:00 AM - 8:00 AM' 
                                : '5:00 PM - 7:00 PM'; 
                        ?>
                    </span>
                </div>
                <div class="session-info">
                    <div class="info-tag">
                        <i class="fas fa-users"></i>
                        Group Training
                    </div>
                    <div class="info-tag">
                        <i class="fas fa-dumbbell"></i>
                        Full Access
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.booking-status-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}
.status-header h3 {
    color: #ff5e62;
}
.status-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-title i {
    font-size: 24px;
    color: #e74c3c;
}

.status-title h3 {
    font-size: 28px;
    color: #fff;
    font-weight: 600;
    margin: 0;
}

.membership-badge {
    background: linear-gradient(135deg, #f39c12, #e74c3c);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.membership-badge i {
    font-size: 12px;
}

.status-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.status-content {
    display: flex;
    padding: 30px;
    gap: 25px;
    position: relative;
}

.status-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    background: linear-gradient(135deg, #e74c3c, #f39c12);
    color: white;
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
}

.status-details {
    flex-grow: 1;
}

.enrollment-status {
    color: #7f8c8d;
    font-size: 15px;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.session-title {
    color: #2c3e50;
    font-size: 32px;
    font-weight: 700;
    margin: 10px 0;
}

.session-time {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    font-size: 16px;
    font-weight: 500;
    margin: 15px 0;
}

.session-info {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.info-tag {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    padding: 8px 16px;
    border-radius: 20px;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 500;
}

/* Animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.status-card {
    animation: slideIn 0.5s ease forwards;
}

/* Hover Effects */
.status-card:hover {
    transform: translateY(-5px);
    transition: transform 0.3s ease;
}

/* Responsive Design */
@media (max-width: 768px) {
    .status-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .status-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 20px;
    }

    .session-info {
        justify-content: center;
        flex-wrap: wrap;
    }

    .status-title {
        justify-content: center;
    }

    .status-title h3 {
        font-size: 24px;
    }

    .session-title {
        font-size: 28px;
    }
}
</style>



<div class="container">
    <h2 class="page-title">Training Session Management</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="current-status-card">
        <h3><i class="fas fa-calendar-check"></i> Your Current Session</h3>
        <div class="status-content">
            <p>You are currently enrolled in the: 
                <span class="highlight-text"><?php echo ucfirst($current_session); ?> Session</span>
                <span class="time-badge">
                    <?php 
                        echo ($current_session === 'morning') 
                            ? '(6:00 AM - 8:00 AM)' 
                            : '(5:00 PM - 7:00 PM)'; 
                    ?>
                </span>
            </p>
        </div>
    </div>

    <h3 class="section-title"><i class="fas fa-dumbbell"></i> Available Training Sessions</h3>
    <div class="sessions-container">
        <!-- Morning Session Card -->
        <div class="session-card <?php echo $current_session === 'morning' ? 'current-session session-unavailable' : ''; ?>">
            <div class="session-header morning-header">
                <h4><i class="fas fa-sun"></i> Morning Session</h4>
            </div>
            <div class="session-details">
                <p class="time-slot"><i class="far fa-clock"></i> Time: 6:00 AM - 8:00 AM</p>
                <?php if ($current_session !== 'morning'): ?>
                    <form method="POST">
                        <input type="hidden" name="new_session" value="morning">
                        <button type="submit" class="switch-btn"><i class="fas fa-exchange-alt"></i> Switch to Morning Session</button>
                    </form>
                <?php else: ?>
                    <p class="current-status-badge"><i class="fas fa-check-circle"></i> Current Session</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evening Session Card -->
        <div class="session-card <?php echo $current_session === 'evening' ? 'current-session session-unavailable' : ''; ?>">
            <div class="session-header evening-header">
                <h4><i class="fas fa-moon"></i> Evening Session</h4>
            </div>
            <div class="session-details">
                <p class="time-slot"><i class="far fa-clock"></i> Time: 5:00 PM - 7:00 PM</p>
                <?php if ($current_session !== 'evening'): ?>
                    <form method="POST">
                        <input type="hidden" name="new_session" value="evening">
                        <button type="submit" class="switch-btn"><i class="fas fa-exchange-alt"></i> Switch to Evening Session</button>
                    </form>
                <?php else: ?>
                    <p class="current-status-badge"><i class="fas fa-check-circle"></i> Current Session</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="rules-section">
        <h3><i class="fas fa-info-circle"></i> Session Change Rules</h3>
        <ul class="rules-list">
            <li><i class="fas fa-times-circle"></i> You cannot book the same session you are currently enrolled in</li>
            <li><i class="fas fa-bolt"></i> Session changes will take effect immediately</li>
            <li><i class="fas fa-exclamation-triangle"></i> You can only be enrolled in one session at a time</li>
        </ul>
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation before changing session
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to change your training session?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    });
});
</script>




        <!-- Sessions Container -->
        <div class="sessions-container">
            <!-- Morning Session -->
            <div class="schedule-wrapper session-content active" id="morning-session">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Time Slot</th>
                                <?php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                foreach ($days as $day) {
                                    echo "<th>
                                            <div class='day-container'>
                                                <span class='day-header'>$day</span>
                                                <span class='day-subtitle'>Daily Workout</span>
                                            </div>
                                          </th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="time-slot">
                                    <div class="time-container">
                                        <span class="time-main">6:00 AM - 8:00 AM</span>
                                        <span class="time-sub">Morning Session</span>
                                    </div>
                                </td>
                                <?php
                                foreach ($days as $day) {
                                    $dayLower = strtolower($day);
                                    $stmt = $conn->prepare("SELECT * FROM workout_schedule WHERE session_time = 'morning' AND day = ?");
                                    $stmt->bind_param("s", $dayLower);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $workout = $result->fetch_assoc();

                                    echo "<td class='workout-cell'>";
                                    if ($workout) {
                                        echo "<div class='workout-content'>
                                                <div class='workout-title'>" . htmlspecialchars($workout['workout_title']) . "</div>
                                                <div class='exercise-list'>";
                                    
                                    for ($i = 1; $i <= 3; $i++) {
                                        if (!empty($workout["exercise$i"])) {
                                            echo "<div class='exercise-item'>
                                                    " . htmlspecialchars($workout["exercise$i"]) . " 
                                                    ({$workout["sets$i"]} × {$workout["reps$i"]})
                                                  </div>";
                                        }
                                    }

                                    echo "</div>
                                          <div class='workout-details'>
                                              <div>Duration: {$workout['duration']} min</div>
                                              <div>Rest: {$workout['rest_period']}s</div>
                                          </div>
                                        </div>";
                                    } else {
                                        echo "<div class='workout-content no-workout'>
                                                <h4>No workout scheduled</h4>
                                                <span class='intensity'>-</span>
                                              </div>";
                                    }
                                    echo "</td>";
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Evening Session -->
            <div class="schedule-wrapper session-content" id="evening-session">
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Time Slot</th>
                                <?php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                foreach ($days as $day) {
                                    echo "<th>
                                            <div class='day-container'>
                                                <span class='day-header'>$day</span>
                                                <span class='day-subtitle'>Daily Workout</span>
                                            </div>
                                          </th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="time-slot">
                                    <div class="time-container">
                                        <span class="time-main">5:00 PM - 7:00 PM</span>
                                        <span class="time-sub">Evening Session</span>
                                    </div>
                                </td>
                                <?php
                                foreach ($days as $day) {
                                    $dayLower = strtolower($day);
                                    $stmt = $conn->prepare("SELECT * FROM workout_schedule WHERE session_time = 'evening' AND day = ?");
                                    $stmt->bind_param("s", $dayLower);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $workout = $result->fetch_assoc();

                                    echo "<td class='workout-cell'>";
                                    if ($workout) {
                                        echo "<div class='workout-content'>
                                                <div class='workout-title'>" . htmlspecialchars($workout['workout_title']) . "</div>
                                                <div class='exercise-list'>";
                                        
                                        for ($i = 1; $i <= 3; $i++) {
                                            if (!empty($workout["exercise$i"])) {
                                                echo "<div class='exercise-item'>
                                                        " . htmlspecialchars($workout["exercise$i"]) . " 
                                                        ({$workout["sets$i"]} × {$workout["reps$i"]})
                                                      </div>";
                                            }
                                        }

                                        echo "</div>
                                              <div class='workout-details'>
                                                  <div>Duration: {$workout['duration']} min</div>
                                                  <div>Rest: {$workout['rest_period']}s</div>
                                              </div>
                                            </div>";
                                    } else {
                                        echo "<div class='workout-content no-workout'>
                                                <h4>No workout scheduled</h4>
                                                <span class='intensity'>-</span>
                                              </div>";
                                    }
                                    echo "</td>";
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="container">
    <div class="row">
        <div class="col-lg-6 offset-lg-3">
            <div class="section-heading">
                <h2>Choose Your <em>Training Session</em></h2>
                <img src="assets/images/line-dec.png" alt="">
                <p>Select your preferred training time</p>
            </div>
        </div>
    </div>

    <!-- Session Selection -->
    <div class="session-selection-container">
        <div class="row">
            <div class="col-md-6">
                <div class="session-card" id="booking">
                    <div class="session-icon">
                        <i class="fas fa-sun"></i>
                    </div>
                    <h3>Morning Session</h3>
                    <p>6:00 AM - 11:00 AM</p>
                    <div class="booking-buttons" id="booking-buttons" >
                        <a href="book_slot.php?session=morning" class="main-button">
                            Book Morning Slot
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="session-card" id="evening">
                    <div class="session-icon">
                        <i class="fas fa-moon"></i>
                    </div>
                    <h3>Evening Session</h3>
                    <p>4:00 PM - 9:00 PM</p>
                    <div class="booking-buttons">
                        <a href="book_slot.php?session=evening" class="main-button">
                            Book Evening Slot
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Date Selection Popup -->
    <div id="dateSelectPopup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Select Date for <span id="sessionTitle"></span></h3>
                <button class="close-btn" onclick="closeDatePopup()">×</button>
            </div>
            <div class="popup-body">
                <form id="bookingForm" onsubmit="return bookSlot(event)">
                    <div class="date-input-group">
                        <label for="bookingDate">Choose Date:</label>
                        <input type="date" id="bookingDate" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                               class="form-control" required>
                    </div>
                    <div class="popup-actions">
                        <button type="submit" class="confirm-btn">Book Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
  </section>
<style>
  
.session-card {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.session-icon {
    font-size: 3rem;
    color: #ed563b;
    margin-bottom: 20px;
}

.session-card h3 {
    color: #232d39;
    margin-bottom: 15px;
}

.main-button {
    background: #ed563b;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.main-button:hover {
    background: #d64d35;
}

/* Popup Styles */
.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.popup-content {
    background: white;
    padding: 25px;
    border-radius: 15px;
    width: 90%;
    max-width: 400px;
}

.popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.popup-header h3 {
    margin: 0;
    color: #232d39;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #7a7a7a;
}

.date-input-group {
    margin-bottom: 20px;
}

.date-input-group label {
    display: block;
    margin-bottom: 10px;
    color: #232d39;
    font-weight: 500;
}

.date-input-group input {
    width: 100%;
    padding: 12px;
    border: 2px solid #ed563b;
    border-radius: 8px;
    font-size: 16px;
}

.popup-actions {
    text-align: center;
}

.confirm-btn {
    background: #ed563b;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s ease;
}

.confirm-btn:hover {
    background: #d64d35;
}

@media (max-width: 768px) {
    .popup-content {
        width: 95%;
        margin: 20px;
    }
}
</style>


    

<script>
function bookSlot(timeSlot) {
    if(confirm(`Confirm booking for ${timeSlot} session?`)) {
        fetch('process_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `time_slot=${timeSlot}&booking_date=<?php echo $today; ?>`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Booking successful!');
                location.reload();
            } else {
                alert(data.message || 'Booking failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing booking. Please try again.');
        });
    }
}
</script>

    <!--membership-->
    <section class="section" id="memberships">
    <div class="section-heading">
        <h2>Manage Your <em>Gym</em> <em>Membership</em></h2>
        <img src="assets/images/line-dec.png" alt="">
      <p>Renew your membership plan that aligns with your fitness goals and transform your lifestyle.</p>
    </div>
    <div class="container">
    <!-- Monthly Fee Status Box -->
    <div class="monthly-fee-box">
        <?php
        if(isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("
                SELECT 
                    membership_status,
                    last_payment_date,
                    next_payment_date,
                    payment_status,
                    rate_monthly_fee,
                    DATEDIFF(next_payment_date, CURRENT_DATE) as days_remaining,
                    DATEDIFF(CURRENT_DATE, last_payment_date) as days_since_payment
                FROM memberships 
                WHERE user_id = ? 
                ORDER BY membership_id DESC 
                LIMIT 1
            ");
            
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $membership = $result->fetch_assoc();

            if($membership) {
                $isOverdue = $membership['days_since_payment'] > 45; // 1.5 months
                $needsRenewal = $membership['membership_status'] == 'expired' || $isOverdue;
                ?>
                <div class="fee-status-container">
                    <div class="fee-header">
                        <h4>Monthly Fee Status</h4>
                        <span class="status-badge <?php echo $needsRenewal ? 'status-expired' : ($membership['days_remaining'] <= 5 ? 'status-warning' : 'status-active'); ?>">
                            <?php 
                            if($needsRenewal) {
                                echo 'Renewal Required';
                            } elseif($membership['days_remaining'] <= 5) {
                                echo 'Due Soon';
                            } else {
                                echo 'Active';
                            }
                            ?>
                        </span>
                    </div>

                    <div class="fee-details">
                        <div class="fee-row">
                            <span>Monthly Fee:</span>
                            <strong>₹<?php echo number_format($membership['rate_monthly_fee'], 2); ?></strong>
                        </div>
                        <div class="fee-row">
                            <span>Last Payment:</span>
                            <strong><?php echo date('d M Y', strtotime($membership['last_payment_date'])); ?></strong>
                        </div>
                        <div class="fee-row">
                            <span>Next Due Date:</span>
                            <strong class="<?php echo $membership['days_remaining'] <= 5 ? 'text-warning' : ''; ?>">
                                <?php echo date('d M Y', strtotime($membership['next_payment_date'])); ?>
                            </strong>
                        </div>
                    </div>

                    <div class="fee-action">
                        <?php if($needsRenewal): ?>
                            <div class="renewal-notice">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Your membership needs renewal. Please renew within 7 days to avoid registration fee.</p>
                            </div>
                            <a href="payment_membership.php?type=renewal" class="btn-renewal">
                                Renew Membership
                            </a>
                        <?php elseif($membership['days_remaining'] <= 5): ?>
                            <a href="payment_membership.php?type=monthly" class="btn-pay">
                                Pay Monthly Fee
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            } else {
                echo "<div class='no-membership'>No active membership found.</div>";
            }
        }
        ?>
    </div>

    <!-- Rest of your existing membership content -->
</div>

<style>
.monthly-fee-box {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    max-width: 600px;
    margin: 0 auto 30px;
}

.fee-status-container {
    padding: 20px;
}

.fee-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.fee-header h4 {
    margin: 0;
    color: #232d39;
    font-size: 1.2rem;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
}

.status-expired {
    background: #f8d7da;
    color: #721c24;
}

.fee-details {
    margin-bottom: 20px;
}

.fee-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px dashed #eee;
}

.fee-row:last-child {
    border-bottom: none;
}

.fee-row span {
    color: #6c757d;
}

.fee-row strong {
    color: #232d39;
}

.text-warning {
    color: #ffc107 !important;
}

.fee-action {
    text-align: center;
    margin-top: 20px;
}

.renewal-notice {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    color: #856404;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.renewal-notice i {
    font-size: 1.2rem;
}

.btn-renewal, .btn-pay {
    display: inline-block;
    padding: 10px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-renewal {
    background: #dc3545;
    color: white;
}

.btn-pay {
    background: #ed563b;
    color: white;
}

.btn-renewal:hover, .btn-pay:hover {
    transform: translateY(-2px);
    color: white;
}

.no-membership {
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

@media (max-width: 576px) {
    .fee-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .fee-row {
        flex-direction: column;
        text-align: center;
        gap: 5px;
    }
}
</style>

   
   
    <div class="plans-container">
        <?php
        // Include database connection if not already included
        if (!isset($conn)) {
            include 'connect.php';
        }
        
        // Load membership rates from config
        $config_file = __DIR__ . '/config/membership_rates.php';
        if (file_exists($config_file)) {
            include $config_file;
        } else {
            define('JOINING_FEE', 2000);
            define('MONTHLY_FEE', 999);
        }

        // Check if user has active membership
        $has_active_membership = false;
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM memberships 
                                      WHERE user_id = ? 
                                      AND membership_status = 'active' 
                                      AND payment_status = 'completed'");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_row()[0];
                $has_active_membership = ($count > 0);
            } catch (Exception $e) {
                error_log("Error checking membership status: " . $e->getMessage());
            }
        }

        // Define plans array (static data, no database query needed)
        $plans = [
            [
                'name' => 'Basic Membership',
                'price' => MONTHLY_FEE,
                'joining_fee' => JOINING_FEE,
                'is_premium' => false,
                'features' => [
                    'Access to gym equipment',
                    'Basic fitness assessment',
                    'Locker facility',
                    'Group workout sessions'
                ]
            ],
          
        ];

        // Display plans
        foreach ($plans as $plan):
        ?>
            <div class="plan-card <?php echo $plan['is_premium'] ? 'featured' : ''; ?>">
                <?php if ($plan['is_premium']): ?>
                    <div class="popular-badge">Best Value</div>
                <?php endif; ?>

                <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                <div class="price">
                    ₹<?php echo number_format($plan['price']); ?><span>/month</span>
                </div>
                <div class="joining-fee">
                    One-time joining fee: ₹<?php echo number_format($plan['joining_fee']); ?>
                </div>

                <ul class="features">
                    <?php foreach ($plan['features'] as $feature): ?>
                        <li><i class="fas fa-check"></i> <?php echo htmlspecialchars($feature); ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($has_active_membership): ?>
                        <div class="active-member-status">
                            <i class="fas fa-check-circle"></i>
                            <span>You have an active membership</span>
                        </div>
                    <?php else: ?>
                        <a href="payment_membership.php?type=<?php echo $plan['is_premium'] ? 'premium' : 'basic'; ?>" 
                           class="btn <?php echo $plan['is_premium'] ? 'btn-premium' : 'btn-primary'; ?>">
                            <?php echo $plan['is_premium'] ? 'Join Premium' : 'Join Now'; ?>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login2.php" class="btn <?php echo $plan['is_premium'] ? 'btn-premium' : 'btn-primary'; ?>">
                        Login to Join
                    </a>
                <?php endif; ?>

                <div class="gst-note">*GST applicable</div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<style>
.plans-container {
    display: flex;
    justify-content: center;
    gap: 30px;
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.plan-card {
    flex: 1;
    max-width: 350px;
    background: white;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.plan-card:hover {
    transform: translateY(-5px);
}

.plan-card.featured {
    border: 2px solid #ed563b;
}

.popular-badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: #ed563b;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.plan-card h3 {
    color: #232d39;
    font-size: 24px;
    margin-bottom: 20px;
}

.price {
    font-size: 36px;
    color: #ed563b;
    font-weight: 700;
    margin-bottom: 10px;
}

.price span {
    font-size: 16px;
    color: #666;
    font-weight: normal;
}

.duration {
    color: #28a745;
    font-weight: 600;
    margin: 10px 0;
    padding: 5px;
    background: rgba(40, 167, 69, 0.1);
    border-radius: 5px;
}

.features {
    list-style: none;
    padding: 0;
    margin: 25px 0;
    text-align: left;
}

.features li {
    margin: 15px 0;
    color: #666;
    display: flex;
    align-items: flex-start;
}

.features li i {
    color: #ed563b;
    margin-right: 10px;
    margin-top: 5px;
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    margin-top: 20px;
    width: 100%;
}

.btn-primary {
    background: #ed563b;
    color: white;
}

.btn-premium {
    background: #232d39;
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.gst-note {
    margin-top: 15px;
    color: #666;
    font-size: 12px;
}

@media (max-width: 768px) {
    .plans-container {
        flex-direction: column;
        align-items: center;
    }
    
    .plan-card {
        width: 100%;
        margin: 15px 0;
    }
}

</style>


      

   

    <!-- Products -->
    
    <section class="section" id="products">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 offset-lg-3">
                    <div class="section-heading">
                        <h2>Our <em>Products</em></h2>
                        <img src="assets/images/line-dec.png" alt="">
                        <p>Browse through our collection of fitness equipment and supplements</p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="my_orders.php" class="check-orders-btn">
                                <i class="fas fa-shopping-bag"></i>
                                Check My Orders
                                <span class="btn-hover-effect"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php
                $products_query = "SELECT p.*, c.category_name 
                                 FROM products p 
                                 LEFT JOIN categories c ON p.category_id = c.category_id 
                                 ORDER BY p.product_id DESC";
                $products_result = mysqli_query($conn, $products_query);

                if (mysqli_num_rows($products_result) > 0):
                    while ($product = mysqli_fetch_assoc($products_result)):
                ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="product-card <?php echo ($product['stock'] <= 0) ? 'out-of-stock' : ''; ?>">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                <span class="category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <p class="price">₹<?php echo number_format($product['price'], 2); ?></p>
                                <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                                
                                <div class="stock-availability">
                                    <?php
                                    // Fetch the latest stock value
                                    $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
                                    $stock_stmt->bind_param("i", $product['product_id']);
                                    $stock_stmt->execute();
                                    $current_stock = $stock_stmt->get_result()->fetch_assoc()['stock'];
                                    ?>
                                    <span class="stock-label">Available Stock:</span>
                                    <span class="stock-count <?php 
                                        if($current_stock <= 0) {
                                            echo 'out-of-stock';
                                        } elseif($current_stock < 5) {
                                            echo 'low-stock';
                                        } else {
                                            echo 'in-stock';
                                        }
                                    ?>">
                                        <?php 
                                        if($current_stock <= 0) {
                                            echo '<i class="fas fa-times-circle"></i> Out of Stock';
                                        } elseif($current_stock < 5) {
                                            echo '<i class="fas fa-exclamation-circle"></i> ' . $current_stock . ' items left';
                                        } else {
                                            echo '<i class="fas fa-check-circle"></i> ' . $current_stock . ' in stock';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <!-- Replace order button with link to dedicated order page -->
                                <?php if ($product['stock'] > 0): ?>
                                    <a href="order.php?id=<?php echo $product['product_id']; ?>" class="order-now-btn">
                                        Order Now
                                    </a>
                                <?php else: ?>
                                    <span class="order-now-btn">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="col-12 text-center">
                        <p>No products available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
       
    </section>
<style>
    .stock-availability {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 15px 0;
    padding: 10px 15px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    backdrop-filter: blur(5px);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.stock-availability:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.stock-label {
    font-size: 0.9rem;
    color: #a0a0a0;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stock-count {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.stock-count.in-stock {
    color: #2ecc71;
    background: rgba(46, 204, 113, 0.15);
    border: 1px solid rgba(46, 204, 113, 0.3);
}

.stock-count.low-stock {
    color: #f1c40f;
    background: rgba(241, 196, 15, 0.15);
    border: 1px solid rgba(241, 196, 15, 0.3);
    animation: pulse 2s infinite;
}

.stock-count.out-of-stock {
    color: #e74c3c;
    background: rgba(231, 76, 60, 0.15);
    border: 1px solid rgba(231, 76, 60, 0.3);
}

.stock-count i {
    font-size: 1.1rem;
    animation: fadeIn 0.5s ease-in;
}

/* Pulse animation for low stock */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.03);
    }
    100% {
        transform: scale(1);
    }
}

/* Fade in animation for icons */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hover effects */
.stock-availability:hover .stock-count {
    padding: 6px 15px;
}

.stock-availability:hover .stock-count.in-stock {
    background: rgba(46, 204, 113, 0.25);
}

.stock-availability:hover .stock-count.low-stock {
    background: rgba(241, 196, 15, 0.25);
}

.stock-availability:hover .stock-count.out-of-stock {
    background: rgba(231, 76, 60, 0.25);
}

/* Additional styles for dark theme compatibility */
@media (prefers-color-scheme: dark) {
    .stock-availability {
        background: rgba(0, 0, 0, 0.2);
    }
    
    .stock-label {
        color: #cccccc;
    }
}
.check-orders-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #ed563b;
    color: white;
    padding: 12px 25px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 20px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 2px solid #ed563b;
}

.check-orders-btn:hover {
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(237, 86, 59, 0.3);
}

.check-orders-btn i {
    font-size: 1.1em;
    transition: transform 0.3s ease;
}

.check-orders-btn:hover i {
    transform: translateX(-3px);
}

.btn-hover-effect {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(-100%);
    transition: transform 0.3s ease;
}

.check-orders-btn:hover .btn-hover-effect {
    transform: translateX(0);
}

/* Responsive styles */
@media (max-width: 768px) {
    .check-orders-btn {
        padding: 10px 20px;
        font-size: 0.9em;
    }
}

/* Animation for button appearance */
.check-orders-btn {
    animation: fadeInUp 0.5s ease 0.8s both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
        <!-- Optimized Order Modal -->
             <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <p>Copyright &copy; 2024 focusgym
                    
                    <!-- - Designed by <a rel="nofollow" href="https://templatemo.com" class="tm-text-link" target="_parent">TemplateMo</a><br> -->

                <!-- Distributed by <a rel="nofollow" href="https://themewagon.com" class="tm-text-link" target="_blank">ThemeWagon</a> -->
                
                </p>
                    
                    <!-- You shall support us a little via PayPal to info@templatemo.com -->
                    
                </div>
            </div>
        </div>
    </footer> 

    
<!-- End of page -->
    
  </body>
</html>

<!-- jQuery -->
<script src="assets/js/jquery-2.1.0.min.js"></script>

<!-- Bootstrap -->
<script src="assets/js/popper.js"></script>
<script src="assets/js/bootstrap.min.js"></script>

<!-- Plugins -->
<script src="assets/js/scrollreveal.min.js"></script>
<script src="assets/js/waypoints.min.js"></script>
<script src="assets/js/jquery.counterup.min.js"></script>
<script src="assets/js/imgfix.min.js"></script> 
<script src="assets/js/mixitup.js"></script> 
<script src="assets/js/accordions.js"></script>

<!-- Global Init -->
<script src="assets/js/custom.js"></script>
<script>
    // Form submission
    document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('update_profile.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin' // This ensures cookies are sent with the request
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating profile. Please try again.');
    });
});

    // // Modal control functions
    // function openEditModal() {
    //     document.getElementById('editProfileModal').style.display = 'block';
    //     document.body.style.overflow = 'hidden';
    // }

    // function closeModal() {
    //     document.getElementById('editProfileModal').style.display = 'none';
    //     document.body.style.overflow = 'auto';
    // }

    // // Close modal when clicking outside
    // window.onclick = function(event) {
    //     const modal = document.getElementById('editProfileModal');
    //     if (event.target === modal) {
    //         closeModal();
    //     }
    // }
    </script>
<script>
    let currentPrice = 0;

    function orderProduct(productId, productName, price, image, category) {
        currentPrice = price;
        document.getElementById('product_id').value = productId;
        document.getElementById('modal_product_name').textContent = productName;
        document.getElementById('modal_product_category').textContent = category;
        document.getElementById('modal_product_image').src = image;
        document.getElementById('product_price').textContent = '₹' + price.toFixed(2);
        updateTotalPrice();
        $('#orderModal').modal('show');
    }

    function updateTotalPrice() {
        const quantity = parseInt(document.getElementById('quantity').value);
        const total = currentPrice * quantity;
        document.getElementById('total_price').textContent = '₹' + total.toFixed(2);
    }

    // Update total when quantity changes
    document.getElementById('quantity').addEventListener('input', updateTotalPrice);

    function submitOrder() {
        const formData = new FormData(document.getElementById('orderForm'));
        
        fetch('process_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#orderModal').modal('hide');
                $('.order-number').text('Order #: ' + data.order_id);
                $('#successModal').modal('show');
                document.getElementById('orderForm').reset();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your order.');
        });
    }

    // Auto-close success modal after 3 seconds
    $('#successModal').on('shown.bs.modal', function () {
        setTimeout(() => {
            $('#successModal').modal('hide');
        }, 3000);
    });
    </script>
 
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

<script>

function showAlert(message, type = 'success') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;

    const alertPlaceholder = $('#alertPlaceholder');
    alertPlaceholder.html(alertHtml);

    setTimeout(function() {
        alertPlaceholder.find('.alert').alert('close');
    }, 3000);
}
// Test if jQuery is loaded
$(document).ready(function() {
    console.log('jQuery loaded:', typeof $);
    console.log('Bootstrap modal:', typeof $.fn.modal);

    // Manual modal trigger
    $('.edit-button').on('click', function() {
        console.log('Edit button clicked');
        $('#editProfileModal').modal('show');
    });

    // Modal event listeners
    $('#editProfileModal').on('show.bs.modal', function () {
        console.log('Modal is opening');
    }).on('shown.bs.modal', function () {
        console.log('Modal is fully opened');
    }).on('hide.bs.modal', function () {
        console.log('Modal is closing');
        
    });
});

function updateProfile() {
    const formData = new FormData(document.getElementById('profileForm'));

    fetch('update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Get raw response
    .then(text => {
        console.log('Raw response:', text); // Log raw response
        return JSON.parse(text); // Try parsing JSON
    })
    .then(data => {
        console.log('Parsed JSON:', data);
        if (data.status === 'success') {
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('JSON Parse Error:', error);
        showAlert('An error occurred. Check console.', 'danger');
    });
}


</script>

<!-- Update the Modal Script -->
<script>
$(document).ready(function() {
    const form = $('#profileForm');
    const saveBtn = $('#saveProfileBtn');
    const modal = $('#editProfileModal');
    const alertPlaceholder = $('#alertPlaceholder');

    function showAlert(message, type = 'success') {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        alertPlaceholder.html(alert);
    }

    saveBtn.on('click', function() {
        // Check if user is logged in
        if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
            showAlert('Please log in to update your profile', 'danger');
            return; // Stop execution if not logged in
        }

        // Proceed with the update
        updateProfile();
    });

    // Reset form and alerts when modal is closed
    modal.on('hidden.bs.modal', function() {
        form[0].reset();
        alertPlaceholder.empty();
    });
});
</script>

<!-- Add Debug Information -->
<div id="debug-info" style="display: none;">
    <p>Current Path: <span id="current-path"></span></p>
    <p>Update URL: <span id="update-url"></span></p>
</div>

<script>
// Debug information
$(document).ready(function() {
    $('#current-path').text(window.location.pathname);
    $('#update-url').text(window.location.pathname.replace('index.php', 'update_profile.php'));
    
    // Log server paths
    console.log('Current path:', window.location.pathname);
    console.log('Update URL:', window.location.pathname.replace('index.php', 'update_profile.php'));
});
</script>

<script>
$(document).ready(function() {
    $('#saveProfileBtn').on('click', function() {
        // Check if user is logged in
        if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
            showAlert('Please log in to update your profile', 'danger');
            return; // Stop execution if not logged in
        }

        // Proceed with the update
        updateProfile();
    });

    // Function to show alerts
    function showAlert(message, type = 'success') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        const alertPlaceholder = $('#alertPlaceholder');
        alertPlaceholder.html(alertHtml);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            alertPlaceholder.find('.alert').alert('close');
        }, 3000);
    }
});
</script>

<script>
$(document).ready(function() {
    // Profile update function
    function updateProfile() {
        // Show loading state
        const saveBtn = $('#saveProfileBtn');
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        // Get form data
        const formData = {
            full_name: $('#full_name').val().trim(),
            mobile_no: $('#mobile_no').val().trim(),
            address: $('#address').val().trim()
        };

        // Validate form data
        if (!formData.full_name || !formData.mobile_no || !formData.address) {
            showAlert('Please fill in all required fields', 'danger');
            saveBtn.prop('disabled', false).html('Save Changes');
            return;
        }

        // Make AJAX request
        $.ajax({
            url: 'update_profile.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response); // Debug log

                if (response && response.status === 'success') {
                    // Update UI
                    $('.profile-info h2').text(response.data.name);
                    $('#profile-phone').text(response.data.mobile);
                    $('#profile-address').text(response.data.address);
                    
                    // Show success message and close modal
                    showAlert(response.message, 'success');
                    $('#editProfileModal').modal('hide');
                } else {
                    // Show error message
                    showAlert(response.message || 'Failed to update profile', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.log('Response Text:', xhr.responseText); // Debug log

                // Check if the response is empty
                if (xhr.responseText.trim() === '') {
                    showAlert('Received an empty response from the server', 'danger');
                } else {
                    // Attempt to parse the response as JSON
                    try {
                        const response = JSON.parse(xhr.responseText);
                        showAlert(response.message || 'An error occurred while updating the profile', 'danger');
                    } catch (e) {
                        showAlert('An error occurred while updating the profile. Invalid response format.', 'danger');
                    }
                }
            },
            complete: function() {
                // Reset button state
                saveBtn.prop('disabled', false).html('Save Changes');
            }
        });
    }

    // Function to show alerts
    function showAlert(message, type = 'success') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        const alertPlaceholder = $('#alertPlaceholder');
        alertPlaceholder.html(alertHtml);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            alertPlaceholder.find('.alert').alert('close');
        }, 3000);
    }

    // Attach event listener to save button
    $('#saveProfileBtn').on('click', updateProfile);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let slideIndex = 0;
    const slides = document.querySelectorAll('.slide');
    
    function showSlides() {
        // Hide all slides
        slides.forEach(slide => slide.classList.remove('active'));
        
        // Increment slide index
        slideIndex++;
        if (slideIndex > slides.length) {
            slideIndex = 1;
        }
        
        // Show current slide
        slides[slideIndex-1].classList.add('active');
        
        // Change slide every 5 seconds
        setTimeout(showSlides, 5000);
    }
    
    // Show first slide immediately
    slides[0].classList.add('active');
    // Start slideshow
    setTimeout(showSlides, 5000);
});
</script>

<script>
function handleBooking(timeSlot) {
    if (confirm('Confirm booking for ' + timeSlot + ' session?')) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'book_slot.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert('Booking successful!');
                    window.location.href = window.location.href; // Refresh the page
                } else {
                    alert(response.message || 'Booking failed');
                }
            } else {
                alert('Error occurred while booking');
            }
        };
        
        xhr.send(JSON.stringify({ time_slot: timeSlot }));
    }
}

function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        // Create and submit a form directly to cancel_booking.php
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cancel_booking.php';
        
        const idField = document.createElement('input');
        idField.type = 'hidden';
        idField.name = 'booking_id';
        idField.value = bookingId;
        
        form.appendChild(idField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
/* Modern Schedule Section Styling */
.schedule-section {
    background: #1a1a1a;
    padding: 80px 0;
    position: relative;
}

.schedule-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #ed563b, #ff8d6b);
}

/* Section Title */
.section-title {
    margin-bottom: 50px !important;
}

.gradient-text {
    background: linear-gradient(45deg, #ed563b, #ff8d6b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 15px;
}

.subtitle {
    color: #ffffff;
    font-size: 18px;
    opacity: 0.8;
}

/* Schedule Info */
.schedule-info {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-top: 30px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #ffffff;
}

.info-item i {
    color: #ed563b;
    font-size: 20px;
}

/* Session Tabs */
.session-tabs {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
}

.session-tab {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    padding: 15px 30px;
    border-radius: 30px;
    color: #ffffff;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.session-tab i {
    margin-right: 8px;
}

.session-tab.active {
    background: #ed563b;
    box-shadow: 0 5px 15px rgba(237, 86, 59, 0.3);
}

.session-tab:hover:not(.active) {
    background: rgba(255, 255, 255, 0.2);
}

/* Table Styling */
.table-responsive {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
}

.custom-table {
    border-collapse: separate;
    border-spacing: 0 10px;
}

.custom-table th {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    padding: 15px;
    color: #ffffff;
}

.workout-cell {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.workout-cell:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.workout-content {
    padding: 20px;
}

.workout-title {
    color: #ed563b;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
}

.exercise-item {
    color: #ffffff;
    margin-bottom: 8px;
    font-size: 14px;
    display: flex;
    align-items: center;
}

.exercise-item::before {
    content: '•';
    color: #ed563b;
    margin-right: 8px;
}

/* Booking Section */
.booking-details {
    background: linear-gradient(145deg, rgba(35, 45, 57, 0.8), rgba(35, 45, 57, 0.95));
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.booking-details h4 {
    color: #ffffff;
    font-size: 20px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #ed563b;
}

.booking-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.booking-item:hover {
    transform: translateX(5px);
}

.book-btn, .cancel-btn {
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.book-btn {
    background: linear-gradient(45deg, #ed563b, #ff8d6b);
    border: none;
    color: white;
}

.book-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(237, 86, 59, 0.3);
}

.cancel-btn {
    background: transparent;
    border: 2px solid #dc3545;
    color: #dc3545;
}

.cancel-btn:hover {
    background: #dc3545;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .schedule-info {
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .session-tabs {
        flex-direction: column;
        gap: 10px;
    }

    .booking-item {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}
</style>

<script>
// Tab Switching Functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.session-tab');
    const sessions = document.querySelectorAll('.session-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs and sessions
            tabs.forEach(t => t.classList.remove('active'));
            sessions.forEach(s => s.classList.remove('active'));

            // Add active class to clicked tab and corresponding session
            tab.classList.add('active');
            const sessionType = tab.dataset.session;
            document.getElementById(`${sessionType}-session`).classList.add('active');
        });
    });
});
</script>

<style>
/* Professional Modal Styling */
.modal-content {
    background: #232d39;
    border: none;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
}

/* Modal Header */
.modal-header {
    background: linear-gradient(to right, #1a1a1a, #232d39);
    border: none;
    padding: 20px;
    border-radius: 20px 20px 0 0;
}

.product-preview {
    display: flex;
    align-items: center;
    gap: 15px;
}

.product-preview-image {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    overflow: hidden;
    background: #333;
}

.product-preview-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-preview-info {
    color: #fff;
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #fff;
}

.product-category {
    color: #ed563b;
    font-size: 0.9rem;
}

/* Modal Body */
.modal-body {
    padding: 25px;
    background: #232d39;
}

/* Order Configuration Section */
.order-config-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #1a1a1a;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 25px;
}

.price-display {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.price-label {
    color: #888;
    font-size: 0.9rem;
}

.price-amount {
    color: #ed563b;
    font-size: 1.5rem;
    font-weight: 600;
}

/* Quantity Selector */
.quantity-selector {
    display: flex;
    flex-direction: column;
    gap: 5px;
    align-items: flex-end;
}

.quantity-label {
    color: #888;
    font-size: 0.9rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    background: #333;
    border-radius: 10px;
    padding: 5px;
}

.qty-btn {
    width: 35px;
    height: 35px;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    transition: all 0.3s ease;
}

.qty-btn.minus {
    background: #444;
}

.qty-btn.plus {
    background: #ed563b;
}

#quantity {
    width: 50px;
    text-align: center;
    border: none;
    background: transparent;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 10px;
}

/* Order Summary */
.order-summary {
    background: #1a1a1a;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 25px;
}

.summary-header {
    background: #333;
    padding: 15px 20px;
    color: #fff;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.summary-content {
    padding: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    color: #fff;
    margin-bottom: 12px;
}

.summary-row.total {
    border-top: 1px solid #333;
    margin-top: 15px;
    padding-top: 15px;
    font-weight: 600;
    font-size: 1.1rem;
    color: #ed563b;
}

/* Pickup Information */
.pickup-info {
    display: flex;
    gap: 15px;
    background: #1a1a1a;
    border-radius: 15px;
    padding: 20px;
}

.info-icon {
    color: #ed563b;
    font-size: 24px;
}

.info-content h6 {
    color: #fff;
    margin: 0 0 8px 0;
}

.info-content p {
    color: #888;
    margin: 0;
}

.timing {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ed563b;
    margin-top: 10px;
    font-size: 0.9rem;
}

/* Modal Footer */
.modal-footer {
    background: #1a1a1a;
    border: none;
    padding: 20px;
    border-radius: 0 0 20px 20px;
}

.btn-secondary, .btn-primary {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 25px;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-secondary {
    background: #333;
    color: #fff;
    border: none;
}

.btn-primary {
    background: linear-gradient(45deg, #ed563b, #ff8d6b);
    color: #fff;
    border: none;
    padding-right: 15px;
}

.total-preview {
    border-left: 1px solid rgba(255,255,255,0.2);
    margin-left: 15px;
    padding-left: 15px;
}

/* Hover Effects */
.btn-secondary:hover {
    background: #444;
    transform: translateY(-1px);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(237, 86, 59, 0.3);
}

.qty-btn:hover {
    transform: scale(1.05);
}

/* Responsive Design */
@media (max-width: 576px) {
    .order-config-section {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
    }

    .quantity-selector {
        align-items: flex-start;
    }

    .modal-footer {
        flex-direction: column-reverse;
        gap: 10px;
    }

    .btn-secondary, .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Quantity Update Function
function updateQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let newValue = parseInt(quantityInput.value) + change;
    
    // Ensure minimum quantity is 1
    if (newValue < 1) newValue = 1;
    
    quantityInput.value = newValue;
    updateTotalPrice();
}

// Update Total Price
function updateTotalPrice() {
    const quantity = parseInt(document.getElementById('quantity').value);
    const price = parseFloat(document.getElementById('product_price').textContent.replace('₹', ''));
    
    const subtotal = quantity * price;
    const gst = subtotal * 0.18;
    const total = subtotal + gst;
    
    document.getElementById('subtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('gst').textContent = '₹' + gst.toFixed(2);
    document.getElementById('total_price').textContent = '₹' + total.toFixed(2);
}

// Initialize quantity input event listener
document.getElementById('quantity').addEventListener('input', updateTotalPrice);
</script>

<style>
/* Optimized Modal Styles */
.modal-dialog {
    max-width: 400px;
    margin: 1.75rem auto;
}

.modal-content {
    background: #232d39;
    border: none;
    border-radius: 15px;
    max-height: calc(100vh - 100px);
}

/* Compact Header */
.modal-header {
    background: linear-gradient(to right, #1a1a1a, #232d39);
    padding: 15px;
    border: none;
}

.product-preview {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-preview-image {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    overflow: hidden;
}

.product-preview-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.modal-title {
    font-size: 1.1rem;
    margin: 0;
    color: #fff;
}

.price-tag {
    color: #ed563b;
    font-weight: 600;
    font-size: 1rem;
    margin-top: 4px;
}

/* Compact Body */
.modal-body {
    padding: 15px;
    background: #232d39;
}

.compact-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Quantity Controls */
.quantity-selector {
    display: flex;
    justify-content: center;
    margin: 5px 0;
}

.quantity-controls {
    display: flex;
    align-items: center;
    background: #1a1a1a;
    padding: 5px;
    border-radius: 10px;
    gap: 10px;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #333;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

#quantity {
    width: 50px;
    text-align: center;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
}

/* Compact Summary */
.order-summary {
    background: #1a1a1a;
    border-radius: 10px;
    padding: 12px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    color: #fff;
    font-size: 0.9rem;
    padding: 4px 0;
}

.summary-row.total {
    border-top: 1px solid #333;
    margin-top: 8px;
    padding-top: 8px;
    font-weight: 600;
    color: #ed563b;
}

/* Compact Pickup Info */
.pickup-info {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #1a1a1a;
    padding: 10px;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #fff;
}

.pickup-info i {
    color: #ed563b;
}

/* Footer */
.modal-footer {
    padding: 15px;
    border: none;
    background: #1a1a1a;
    border-radius: 0 0 15px 15px;
}

.btn-secondary, .btn-primary {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-secondary {
    background: #333;
    color: #fff;
    border: none;
}

.btn-primary {
    background: linear-gradient(45deg, #ed563b, #ff8d6b);
    color: #fff;
    border: none;
}

.total-preview {
    border-left: 1px solid rgba(255,255,255,0.2);
    margin-left: 10px;
    padding-left: 10px;
}

/* Hover Effects */
.qty-btn:hover {
    background: #ed563b;
}

.btn-secondary:hover {
    background: #444;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(237, 86, 59, 0.3);
}

/* Remove Input Spinners */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}

/* Responsive Adjustments */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .modal-content {
        max-height: calc(100vh - 40px);
    }
}
</style>

<style>
/* Update product card styling */
.product-card {
    background: #232d39;
    border-radius: 15px;
    overflow: hidden;
    transition: transform 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-image {
    height: 250px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.product-info {
    padding: 20px;
    color: #fff;
}

.product-info h4 {
    margin: 0 0 10px;
    font-size: 1.25rem;
    color: #fff;
}

.category {
    display: inline-block;
    padding: 5px 10px;
    background: #ed563b;
    color: #fff;
    border-radius: 5px;
    font-size: 0.875rem;
    margin-bottom: 15px;
}

.price {
    font-size: 1.5rem;
    font-weight: 600;
    color: #ed563b;
    margin: 10px 0;
}

.description {
    color: #ccc;
    margin-bottom: 20px;
    font-size: 0.9rem;
    line-height: 1.5;
}

.order-btn {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(45deg, #ed563b, #ff8d6b);
    color: #fff;
    padding: 12px 20px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.order-btn:hover {
    background: linear-gradient(45deg, #ff8d6b, #ed563b);
    transform: translateY(-2px);
    color: #fff;
    text-decoration: none;
}

.btn-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-price {
    padding-left: 15px;
    border-left: 1px solid rgba(255,255,255,0.3);
    font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .product-card {
        margin-bottom: 20px;
    }
}
</style>

<script>
const quickBookModal = new bootstrap.Modal(document.getElementById('quickBookModal'));

function showQuickBookModal(session, date) {
    const sessionData = {
        morning: {
            title: 'Morning Session',
            time: '6:00 AM - 8:00 AM',
            trainer: 'John Smith',
            trainerImg: 'assets/images/trainer1.jpg',
            icon: '<i class="fas fa-sun"></i>'
        },
        evening: {
            title: 'Evening Session',
            time: '5:00 PM - 7:00 PM',
            trainer: 'Sarah Johnson',
            trainerImg: 'assets/images/trainer2.jpg',
            icon: '<i class="fas fa-moon"></i>'
        }
    };

    const data = sessionData[session];
    
    document.getElementById('sessionTitle').textContent = data.title;
    document.getElementById('sessionTime').textContent = data.time;
    document.getElementById('sessionIcon').innerHTML = data.icon;
    document.getElementById('trainerName').textContent = data.trainer;
    document.getElementById('trainerImage').src = data.trainerImg;
    document.getElementById('quickSessionType').value = session;
    
    document.querySelector('.session-preview').className = 
        `session-preview ${session}`;

    quickBookModal.show();
}

function confirmQuickBooking() {
    const form = document.getElementById('quickBookForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    
    fetch('process_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        quickBookModal.hide();
        if (data.success) {
            showSuccessToast('Booking confirmed successfully!');
            setTimeout(() => location.reload(), 2000);
        } else {
            showErrorToast(data.message || 'Booking failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast('Error processing booking. Please try again.');
    });
}

// Toast notifications
function showSuccessToast(message) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: "#28a745",
        stopOnFocus: true
    }).showToast();
}

function showErrorToast(message) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: "#dc3545",
        stopOnFocus: true
    }).showToast();
}
</script>

<script>
function switchSession(session) {
    // Update active tab
    document.querySelectorAll('.session-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-session="${session}"]`).classList.add('active');

    // Show corresponding schedule
    document.querySelectorAll('.schedule-wrapper').forEach(wrapper => {
        wrapper.classList.remove('active');
    });
    document.getElementById(`${session}-session`).classList.add('active');
}

function bookSlot(session) {
    if(!confirm(`Confirm booking for ${session} session today?`)) {
        return;
    }

    fetch('process_booking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `time_slot=${session}&booking_date=<?php echo $today; ?>`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast('success', 'Booking confirmed successfully!');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('error', data.message || 'Booking failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Error processing booking. Please try again.');
    });
}

function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        // Create and submit a form directly to cancel_booking.php
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cancel_booking.php';
        
        const idField = document.createElement('input');
        idField.type = 'hidden';
        idField.name = 'booking_id';
        idField.value = bookingId;
        
        form.appendChild(idField);
        document.body.appendChild(form);
        form.submit();
    }
}

function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<style>
/* Training Session Management Styles */

/* Page Title */
.page-title {
    color: #fff;
    font-size: 2.2rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
}

/* Alerts */
.alert {
    padding: 12px 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Current Status Card */
.current-status-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-left: 5px solid #3498db;
}

.current-status-card h3 {
    color: #2c3e50;
    margin-top: 0;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
}

.current-status-card h3 i {
    margin-right: 10px;
    color: #fff;
}

.status-content {
    padding: 10px 0;
}

.highlight-text {
    font-weight: 700;
    color: #3498db;
    font-size: 1.1rem;
}

.time-badge {
    background-color: #3498db;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.9rem;
    margin-left: 10px;
    display: inline-block;
}

/* Section Titles */
.section-title {
    color: #fff;
    margin: 30px 0 20px;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 10px;
    color: #3498db;
}

/* Sessions Container */
.sessions-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

/* Session Cards */
.session-card {
    flex: 1;
    min-width: 280px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background-color: white;
}

.session-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.session-header {
    padding: 15px;
    color: white;
}

.morning-header {
    background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%);
}

.evening-header {
    background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
}

.session-header h4 {
    margin: 0;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
}

.session-header h4 i {
    margin-right: 10px;
}

.session-details {
    padding: 20px;
}

.time-slot {
    display: flex;
    align-items: center;
    color: #555;
    font-weight: 500;
    margin-bottom: 15px;
}

.time-slot i {
    margin-right: 8px;
    color: #666;
}

/* Buttons */
.switch-btn {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.3s;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.switch-btn i {
    margin-right: 8px;
}

.switch-btn:hover {
    background-color: #2980b9;
}

/* Current Session Styling */
.current-session {
    border: 2px solid #3498db;
}

.session-unavailable {
    opacity: 0.8;
}

.current-status-badge {
    background-color: #e9f7fe;
    color: #3498db;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}

.current-status-badge i {
    margin-right: 8px;
}

/* Rules Section */
.rules-section {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.rules-section h3 {
    color:  #ff5e62;
    margin-top: 0;
    display: flex;
    align-items: center;
}

.rules-section h3 i {
    margin-right: 10px;
    color: #3498db;
}

.rules-list {
    padding-left: 10px;
}

.rules-list li {
    list-style-type: none;
    padding: 8px 0;
    display: flex;
    align-items: center;
}

.rules-list li i {
    margin-right: 10px;
    color: #e74c3c;
    min-width: 20px;
}
.rules-list li  {
    color: #000;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sessions-container {
        flex-direction: column;
    }
    
    .session-card {
        width: 100%;
    }
}
</style>

<style>
/* Enhanced Session Selection Styles */
.session-selection-container {
    margin: 50px 0;
}

.session-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 30px;
}

.session-card:hover {
    transform: translateY(-5px);
}

/* Session Card Variations */
.session-card.regular-session {
    border-top: 4px solid #2ecc71;
}

.session-card.different-session {
    border-top: 4px solid #f1c40f;
    opacity: 0.8;
}

.session-card.booked {
    border-top: 4px solid #3498db;
}

/* Session Icon */
.session-icon {
    width: 80px;
    height: 80px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2rem;
    transition: all 0.3s ease;
}

.regular-session .session-icon {
    color: #2ecc71;
}

.different-session .session-icon {
    color: #f1c40f;
}

.booked .session-icon {
    color: #3498db;
}

/* Session Title and Time */
.session-card h3 {
    color: #232d39;
    font-size: 1.5rem;
    margin-bottom: 10px;
    font-weight: 600;
}

.session-card p {
    color: #6c757d;
    margin-bottom: 20px;
}

/* Status Message */
.status-message {
    margin: 15px 0;
    padding: 10px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 500;
}

.status-message.regular {
    background: #e8f5e9;
    color: #2ecc71;
}

.status-message.different {
    background: #fff3e0;
    color: #f1c40f;
}

.status-message.booked {
    background: #e3f2fd;
    color: #3498db;
}

/* Button Styles */
.main-button.disabled {
    background: #dee2e6;
    cursor: not-allowed;
    opacity: 0.7;
}

.main-button.disabled:hover {
    transform: none;
    box-shadow: none;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .session-card {
        padding: 20px;
    }
    
    .session-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .session-card h3 {
        font-size: 1.3rem;
    }
}
</style>

<style>
.order-now-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: linear-gradient(45deg, #ed563b, #ff8d6b);
    color: #ffffff;
    padding: 16px 32px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 1px;
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all 0.4s ease;
    box-shadow: 0 6px 15px rgba(237, 86, 59, 0.3);
    width: 100%;
    max-width: 250px;
    margin: 15px auto;
}

/* Shine effect */
.order-now-btn::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(45deg);
    transition: all 0.5s ease;
    opacity: 0;
}

/* Hover effects */
.order-now-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(237, 86, 59, 0.4);
    color: #ffffff;
    text-decoration: none;
}

.order-now-btn:hover::before {
    opacity: 1;
    left: 100%;
}

/* Active state */
.order-now-btn:active {
    transform: translateY(1px);
    box-shadow: 0 4px 15px rgba(237, 86, 59, 0.3);
}

/* Icon styling */
.order-now-btn i {
    font-size: 18px;
    transition: all 0.3s ease;
}

.order-now-btn:hover i {
    transform: translateX(5px) scale(1.1);
}

/* Pulse animation */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.order-now-btn {
    animation: pulse 2s infinite;
}

.order-now-btn:hover {
    animation: none;
}

/* Out of stock state */
.order-now-btn.out-of-stock {
    background: linear-gradient(45deg, #808080, #a0a0a0);
    cursor: not-allowed;
    opacity: 0.7;
    animation: none;
}

/* Loading state */
.order-now-btn.loading {
    cursor: wait;
    opacity: 0.8;
}

.order-now-btn.loading::after {
    content: '';
    position: absolute;
    right: 20px;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: #ffffff;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive design */
@media (max-width: 768px) {
    .order-now-btn {
        padding: 14px 28px;
        font-size: 14px;
        max-width: 200px;
    }
}

</style>


