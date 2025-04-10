<?php
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

// Load membership rates from config
$config_file = __DIR__ . '/config/membership_rates.php';
if (file_exists($config_file)) {
    include $config_file;
} else {
    // Default values if config doesn't exist
    define('JOINING_FEE', 2000);
    define('MONTHLY_FEE', 999);
}

$JOINING_FEE = JOINING_FEE;
$MONTHLY_FEE = MONTHLY_FEE;

// Get user details
$user_id = $_SESSION['user_id'];
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

// Check if the user already has an active membership
$membership_query = "SELECT * FROM memberships WHERE user_id = ? AND membership_status = 'active'";
$stmt = mysqli_prepare($conn, $membership_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$membership_result = mysqli_stmt_get_result($stmt);

$is_new_member = mysqli_num_rows($membership_result) === 0;
$amount_to_pay = $is_new_member ? ($JOINING_FEE + $MONTHLY_FEE) : $MONTHLY_FEE;

// Razorpay API credentials
$razorpay_key_id = "rzp_test_Fur0pLo5d2MztK";
$razorpay_key_secret = "TqC7xFxWWnBUsnAzznEB1YaT";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membership Payment</title>
    
    <!-- Use CDN links for CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Add jQuery from CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Add Bootstrap from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Add Razorpay JS -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
                    <h1 class="text-white mb-0">Membership Payment</h1>
                    <span class="header-subtitle">Secure payment for your gym membership</span>
                </div>
                <div style="width: 135px;"></div>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <section class="section" id="payment-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title text-center mb-4">Membership Details</h3>
                            
                            <div class="member-info mb-4">
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Name:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($user_name); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Email:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($user_email); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Phone:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($user_phone); ?></div>
                                </div>
                            </div>
                            
                            <div class="payment-summary">
                                <h4 class="mb-3">Payment Summary</h4>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Description</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($is_new_member): ?>
                                            <tr>
                                                <td>Joining Fee (One time)</td>
                                                <td class="text-end">₹<?php echo number_format($JOINING_FEE, 2); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td>Monthly Membership Fee</td>
                                                <td class="text-end">₹<?php echo number_format($MONTHLY_FEE, 2); ?></td>
                                            </tr>
                                            <tr class="table-secondary">
                                                <td class="fw-bold">Total Amount</td>
                                                <td class="text-end fw-bold">₹<?php echo number_format($amount_to_pay, 2); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="payment-action text-center mt-4">
                                <button id="pay-button" class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i> Pay Now
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-info mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-info-circle text-primary me-2"></i> Payment Information
                                </h5>
                                <ul class="mb-0">
                                    <li>Membership begins immediately upon successful payment</li>
                                    <li>Monthly fees will be due on the same date each month</li>
                                    <li>All payments are processed securely through Razorpay</li>
                                    <li>For payment issues, please contact our support team</li>
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

    .table {
        margin: 0;
    }

    .table th {
        background: var(--primary-color);
        color: white;
        font-weight: 500;
        padding: 15px;
        border: none;
        font-size: 0.95rem;
    }

    .table td {
        padding: 15px;
        vertical-align: middle;
        color: var(--text-color);
        border-color: rgba(0, 0, 0, 0.05);
    }

    .member-info {
        background: var(--light-bg);
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid var(--accent-color);
    }

    .payment-info ul {
        padding-left: 20px;
    }

    .payment-info li {
        margin-bottom: 8px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem;
        }
        
        .table th, .table td {
            padding: 10px;
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
    }

    #pay-button {
        min-width: 200px;
        min-height: 50px;
    }

    .payment-successful {
        display: none;
        text-align: center;
        padding: 20px;
    }

    .success-icon {
        font-size: 60px;
        color: var(--success);
        margin-bottom: 20px;
    }
    </style>

    <script>
    $(document).ready(function() {
        $('#pay-button').click(function() {
            // Razorpay configuration
            var options = {
                "key": "<?php echo $razorpay_key_id; ?>",
                "amount": "<?php echo $amount_to_pay * 100; ?>", // in paisa (smallest currency unit)
                "currency": "INR",
                "name": "Fitness Studio",
                "description": "<?php echo $is_new_member ? 'New Membership Payment' : 'Membership Renewal'; ?>",
                "image": "https://i.postimg.cc/0jQVJD3j/gym-logo.png", // Use a valid image URL with fixed dimensions
                "prefill": {
                    "name": "<?php echo htmlspecialchars($user_name); ?>",
                    "email": "<?php echo htmlspecialchars($user_email); ?>",
                    "contact": "<?php echo htmlspecialchars($user_phone); ?>"
                },
                "theme": {
                    "color": "#ed563b"
                },
                "handler": function(response) {
                    // This function is called when payment is successful
                    processPayment(response);
                }
            };
            
            var rzp = new Razorpay(options);
            rzp.open();
        });
        
        function processPayment(response) {
            // Show a loading indicator or message
            $('#pay-button').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');
            
            // Send payment details to server for verification and database update
            $.ajax({
                url: 'process_payment.php',
                type: 'POST',
                dataType: 'json', // Explicitly expect JSON
                data: {
                    payment_id: response.razorpay_payment_id,
                    user_id: <?php echo $user_id; ?>,
                    amount: <?php echo $amount_to_pay; ?>,
                    is_new_member: <?php echo $is_new_member ? 'true' : 'false'; ?>
                },
                success: function(result) {
                    // No need to parse JSON as dataType is set to 'json'
                    if (result.status === 'success') {
                        // Show success message and redirect
                        window.location.href = 'payment_success.php?payment_id=' + response.razorpay_payment_id;
                    } else {
                        $('#pay-button').prop('disabled', false).html('<i class="fas fa-credit-card me-2"></i> Pay Now');
                        alert('Payment verification failed: ' + (result.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    // Enable button again
                    $('#pay-button').prop('disabled', false).html('<i class="fas fa-credit-card me-2"></i> Pay Now');
                    
                    // Try to get more information about the error
                    var errorMessage = 'Payment processing error. ';
                    
                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            errorMessage += response.message || '';
                        } catch (e) {
                            // If can't parse as JSON, show first 100 chars of response
                            errorMessage += 'Server responded with: ' + xhr.responseText.substring(0, 100);
                        }
                    } else {
                        errorMessage += error || 'Unknown error';
                    }
                    
                    console.error('AJAX error:', errorMessage);
                    alert(errorMessage + ' Please contact support.');
                }
            });
        }
    });
    </script>
</body>
</html> 