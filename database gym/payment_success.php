<?php
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

// Check if payment ID is provided
if (!isset($_GET['payment_id'])) {
    header("Location: index.php");
    exit();
}

$payment_id = $_GET['payment_id'];
$user_id = $_SESSION['user_id'];

// Get user details
$query = "SELECT r.full_name, r.mobile_no, l.email 
          FROM register r 
          INNER JOIN login l ON r.user_id = l.user_id 
          WHERE r.user_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $user_name = $row['full_name'];
    $user_email = $row['email'];
    $user_phone = $row['mobile_no'];
} else {
    die("Unable to retrieve user information");
}

// Get payment details
$payment_query = "SELECT * FROM payments WHERE payment_id = ? AND user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $payment_query);
mysqli_stmt_bind_param($stmt, "si", $payment_id, $user_id);
mysqli_stmt_execute($stmt);
$payment_result = mysqli_stmt_get_result($stmt);

if ($payment = mysqli_fetch_assoc($payment_result)) {
    $amount = $payment['amount'];
    $is_new_member = $payment['is_new_member'];
} else {
    // Fallback if payment details can't be found
    $amount = 0;
    $is_new_member = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Successful</title>
    
    <!-- Use CDN links for CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Add jQuery from CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Add Bootstrap from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
                    <span class="header-subtitle">Thank you for your payment</span>
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
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="card-title mb-4">Payment Successfully Completed</h3>
                            
                            <div class="payment-details mb-4">
                                <p class="lead mb-1">Thank you for your payment, <?php echo htmlspecialchars($user_name); ?>!</p>
                                <p>Your membership is now active and ready to use.</p>
                                
                                <div class="details-box mt-4">
                                    <div class="row mb-2">
                                        <div class="col-md-6 text-md-end fw-bold">Payment ID:</div>
                                        <div class="col-md-6 text-md-start"><?php echo htmlspecialchars($payment_id); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6 text-md-end fw-bold">Amount Paid:</div>
                                        <div class="col-md-6 text-md-start">₹<?php echo number_format($amount, 2); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6 text-md-end fw-bold">Payment Date:</div>
                                        <div class="col-md-6 text-md-start"><?php echo date('d M Y'); ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6 text-md-end fw-bold">Membership Type:</div>
                                        <div class="col-md-6 text-md-start"><?php echo $is_new_member ? 'New Membership' : 'Membership Renewal'; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-buttons mt-4">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-home me-1"></i> Go to Home
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="next-steps mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-clipboard-list text-primary me-2"></i> Next Steps
                                </h5>
                                <ul class="mb-0">
                                    <li>Download your membership receipt from your profile</li>
                                    <li>Schedule your first workout session</li>
                                    <li>Complete your fitness assessment</li>
                                    <li>Check out our latest fitness classes and programs</li>
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

    .success-icon {
        font-size: 5rem;
        color: var(--success);
        margin-bottom: 1.5rem;
    }

    .details-box {
        background-color: var(--light-bg);
        padding: 1.5rem;
        border-radius: 10px;
        margin: 2rem auto;
        max-width: 500px;
    }

    .btn {
        padding: 12px 24px;
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

    .next-steps ul {
        padding-left: 20px;
    }

    .next-steps li {
        margin-bottom: 10px;
    }

    @media (max-width: 768px) {
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
        
        .admin-nav > div:last-child {
            display: none;
        }
    }
    </style>
</body>
</html> 