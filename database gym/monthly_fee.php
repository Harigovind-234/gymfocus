<?php
session_start();
require_once 'connect.php';
require_once 'config/membership_rates.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's membership details
$user_id = $_SESSION['user_id'];
$membership_query = "SELECT 
    membership_status,
    payment_type,
    last_payment_date,
    next_payment_date,
    payment_amount
    FROM memberships 
    WHERE user_id = ? 
    AND payment_status = 'completed'
    ORDER BY membership_id DESC 
    LIMIT 1";

$stmt = $conn->prepare($membership_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$membership = $result->fetch_assoc();

// Calculate days until next payment
$days_remaining = 0;
$is_payment_due = false;
if ($membership) {
    $days_remaining = ceil((strtotime($membership['next_payment_date']) - time()) / (60 * 60 * 24));
    $is_payment_due = $days_remaining <= 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Fee Payment - GYM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body {
            background-color: #1a1a1a;
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #232d39;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }
        .payment-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .payment-header h2 {
            color: #ed563b;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .payment-details {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .detail-label {
            color: #aaa;
            font-size: 0.95rem;
        }
        .detail-value {
            font-weight: 500;
            font-size: 1.1rem;
        }
        .amount-due {
            font-size: 2rem;
            color: #ed563b;
            text-align: center;
            margin: 30px 0;
            font-weight: 700;
        }
        .btn-pay {
            background: linear-gradient(135deg, #ed563b, #ff8d6b);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(237, 86, 59, 0.4);
        }
        .back-link {
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #ed563b;
        }
        .payment-status {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .status-due {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .status-upcoming {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="payment-container">
            <div class="payment-header">
                <h2>Monthly Fee Payment</h2>
                <p>Manage your gym membership payment</p>
            </div>

            <?php if ($membership): ?>
                <div class="payment-details">
                    <div class="detail-row">
                        <span class="detail-label">Membership Status</span>
                        <span class="detail-value"><?php echo ucfirst($membership['membership_status']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Last Payment Date</span>
                        <span class="detail-value"><?php echo date('d M Y', strtotime($membership['last_payment_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Next Due Date</span>
                        <span class="detail-value"><?php echo date('d M Y', strtotime($membership['next_payment_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Days Remaining</span>
                        <span class="detail-value"><?php echo $days_remaining; ?> days</span>
                    </div>
                </div>

                <?php if ($is_payment_due): ?>
                    <div class="payment-status status-due">
                        <i class="fas fa-exclamation-circle"></i>
                        Payment is due now
                    </div>
                <?php else: ?>
                    <div class="payment-status status-upcoming">
                        <i class="fas fa-clock"></i>
                        Next payment in <?php echo $days_remaining; ?> days
                    </div>
                <?php endif; ?>

                <div class="amount-due">
                    ₹<?php echo number_format(MONTHLY_FEE, 2); ?>
                </div>

                <?php if ($is_payment_due): ?>
                    <button class="btn btn-primary btn-pay" onclick="initiatePayment()">
                        <i class="fas fa-credit-card me-2"></i>Pay Now
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center">
                    <p>No active membership found. Please join first.</p>
                    <a href="payment_membership.php" class="btn btn-primary">Join Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function initiatePayment() {
            fetch('create_razorpay_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount: <?php echo MONTHLY_FEE; ?>,
                    payment_type: 'monthly'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }

                const options = {
                    key: 'rzp_test_Fur0pLo5d2MztK',
                    amount: <?php echo MONTHLY_FEE * 100; ?>,
                    currency: 'INR',
                    name: 'GYM',
                    description: 'Monthly Fee Payment',
                    order_id: data.order_id,
                    handler: function(response) {
                        window.location.href = `payment_success.php?order_id=${data.order_id}&payment_id=${response.razorpay_payment_id}&payment_type=monthly`;
                    },
                    prefill: {
                        name: '<?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : ''; ?>',
                        email: '<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>'
                    },
                    theme: {
                        color: '#ed563b'
                    }
                };

                const rzp = new Razorpay(options);
                rzp.open();
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
    </script>
</body>
</html> 