<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : null;

// Get user and payment details
$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT r.full_name 
              FROM register r 
              WHERE r.user_id = ?";

$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($user_result)) {
    $user_name = $row['full_name'];
} else {
    $user_name = "Member";
}

// Get membership details
$membership_query = "SELECT * FROM memberships WHERE user_id = ? AND membership_status = 'active'";
$stmt = mysqli_prepare($conn, $membership_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$membership_result = mysqli_stmt_get_result($stmt);

if ($membership = mysqli_fetch_assoc($membership_result)) {
    $next_payment_date = date('d M Y', strtotime($membership['next_payment_date']));
} else {
    $next_payment_date = "Unknown";
}

// Get payment details if payment_id is provided
$payment_details = null;
if ($payment_id) {
    $payment_query = "SELECT * FROM payment_history WHERE payment_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $payment_query);
    mysqli_stmt_bind_param($stmt, "si", $payment_id, $user_id);
    mysqli_stmt_execute($stmt);
    $payment_result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($payment_result)) {
        $payment_details = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Successful - Fitness Studio</title>
    
    <!-- Fix CSS paths -->
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.css" rel="stylesheet">
    <link href="../assets/css/templatemo-training-studio.css" rel="stylesheet">
    
    <!-- Add jQuery from CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Add Bootstrap from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Add Font Awesome from CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Header Section -->
    <div class="admin-header">
        <div class="container">
            <div class="admin-nav">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
                <div class="header-title text-center">
                    <h1 class="text-white mb-0">Payment Successful</h1>
                    <span class="header-subtitle">Your membership has been activated</span>
                </div>
                <div style="width: 135px;"></div>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <section class="section" id="success-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            
                            <h2 class="mb-4">Thank You, <?php echo htmlspecialchars($user_name); ?>!</h2>
                            
                            <p class="lead">Your payment has been processed successfully.</p>
                            
                            <div class="payment-details mt-4">
                                <?php if ($payment_details): ?>
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <div class="detail-card">
                                            <div class="row mb-2">
                                                <div class="col-6 text-start">Transaction ID:</div>
                                                <div class="col-6 text-end"><?php echo htmlspecialchars($payment_id); ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 text-start">Amount Paid:</div>
                                                <div class="col-6 text-end">₹<?php echo number_format($payment_details['amount'], 2); ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 text-start">Payment Date:</div>
                                                <div class="col-6 text-end"><?php echo date('d M Y', strtotime($payment_details['payment_date'])); ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 text-start">Next Payment Due:</div>
                                                <div class="col-6 text-end"><?php echo $next_payment_date; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-success">
                                    Your membership is now active until <?php echo $next_payment_date; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-5">
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="whats-next mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">
                                    <i class="fas fa-list-ul me-2 text-primary"></i> What's Next?
                                </h4>
                                <ul class="what-next-list">
                                    <li>
                                        <span class="icon"><i class="fas fa-calendar-check"></i></span>
                                        <span class="text">Schedule your first training session</span>
                                    </li>
                                    <li>
                                        <span class="icon"><i class="fas fa-user-friends"></i></span>
                                        <span class="text">Meet with our fitness experts for a personalized plan</span>
                                    </li>
                                    <li>
                                        <span class="icon"><i class="fas fa-dumbbell"></i></span>
                                        <span class="text">Explore our wide range of fitness equipment and classes</span>
                                    </li>
                                    <li>
                                        <span class="icon"><i class="fas fa-mobile-alt"></i></span>
                                        <span class="text">Download our mobile app to track your progress</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
    :root {
        --primary-color: #232d39;
        --accent-color: #ed563b;
        --text-color: #666666;
        --light-bg: #f8f9fa;
        --success: #2ecc71;
        --danger: #e74c3c;
        --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    body {
        background-color: #f4f6f9;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--text-color);
    }

    .admin-header {
        background: linear-gradient(135deg, #000000 0%, #555555 100%);
        padding: 1.5rem 0;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .admin-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-title {
        flex-grow: 1;
        text-align: center;
        padding: 0 20px;
    }

    .header-title h1 {
        font-size: 2rem;
        font-weight: 600;
        margin: 0;
        color: white;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .header-subtitle {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1rem;
        display: block;
        margin-top: 0.25rem;
    }

    .back-link {
        color: white;
        text-decoration: none;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.1);
        width: 135px;
    }

    .back-link:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateX(-5px);
        color: white;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        transition: transform 0.3s ease;
        background: white;
        margin-bottom: 30px;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-body {
        padding: 2rem;
    }

    .card-title {
        color: var(--primary-color);
        font-weight: 600;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .success-icon {
        font-size: 80px;
        color: var(--success);
        margin-bottom: 20px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }

    .detail-card {
        background: var(--light-bg);
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        margin-bottom: 20px;
    }

    .btn {
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary {
        background: var(--accent-color);
        border: none;
        box-shadow: 0 4px 15px rgba(237, 86, 59, 0.2);
    }

    .btn-primary:hover {
        background: #dc4c31;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(237, 86, 59, 0.3);
    }

    .what-next-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .what-next-list li {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .what-next-list li:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .what-next-list .icon {
        background: rgba(237, 86, 59, 0.1);
        color: var(--accent-color);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .what-next-list .text {
        font-size: 1rem;
        color: var(--text-color);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem;
        }
        
        .admin-nav {
            flex-direction: column;
            gap: 1rem;
        }
        
        .back-link {
            width: auto;
        }
        
        .header-title {
            padding: 1rem 0;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
        }
        
        .header-subtitle {
            font-size: 0.9rem;
        }
        
        .admin-nav > div:last-child {
            display: none;
        }
        
        .success-icon {
            font-size: 60px;
        }
    }
    </style>
</body>
</html> 