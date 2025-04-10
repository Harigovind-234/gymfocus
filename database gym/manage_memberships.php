<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login2.php");
    exit();
}
include 'connect.php';

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

// Fetch all memberships with user details - Fixed Query
$query = "SELECT m.*, r.full_name, r.mobile_no, l.email 
          FROM memberships m 
          INNER JOIN register r ON m.user_id = r.user_id 
          INNER JOIN login l ON r.user_id = l.user_id 
          ORDER BY m.membership_id DESC";

$result = mysqli_query($conn, $query);

// Add error handling
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membership Management - Admin Dashboard</title>
    
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
                <a href="admin.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <div class="header-title text-center">
                    <h1 class="text-white mb-0">Membership Management</h1>
                    <span class="header-subtitle">Manage rates and member subscriptions</span>
                </div>
                <div style="width: 135px;"></div>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <section class="section" id="membership-management">
        <div class="container">
            <!-- Current Membership Rates Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="card-title">Current Membership Rates</h3>
                                <button class="btn btn-primary edit-rates-btn">
                                    <i class="fas fa-edit"></i> Edit Rates
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="fee-card">
                                        <h4>Joining Fee</h4>
                                        <div class="amount" id="joining-fee-display">₹<?php echo number_format($JOINING_FEE, 2); ?></div>
                                        <div class="amount-edit d-none">
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" id="joining-fee-input" value="<?php echo $JOINING_FEE; ?>" min="0">
                                            </div>
                                        </div>
                                        <p>One-time payment for new members</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="fee-card">
                                        <h4>Monthly Fee</h4>
                                        <div class="amount" id="monthly-fee-display">₹<?php echo number_format($MONTHLY_FEE, 2); ?></div>
                                        <div class="amount-edit d-none">
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" id="monthly-fee-input" value="<?php echo $MONTHLY_FEE; ?>" min="0">
                                            </div>
                                        </div>
                                        <p>Regular monthly membership fee</p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3 save-cancel-buttons d-none">
                                <button class="btn btn-success save-rates-btn">
                                    <i class="fas fa-check"></i> Save Changes
                                </button>
                                <button class="btn btn-secondary cancel-edit-btn ml-2">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Members List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title">Member Subscriptions</h3>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Member Name</th>
                                            <th>Contact</th>
                                            <th>Joining Date</th>
                                            <th>Last Payment</th>
                                            <th>Next Due</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($member = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($member['mobile_no']); ?><br>
                                                    <small><?php echo htmlspecialchars($member['email']); ?></small>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($member['joining_date'])); ?></td>
                                                <td><?php echo date('d M Y', strtotime($member['last_payment_date'])); ?></td>
                                                <td><?php echo date('d M Y', strtotime($member['next_payment_date'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $member['membership_status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ucfirst($member['membership_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="viewPaymentHistory(<?php echo $member['user_id']; ?>)">
                                                        View History
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment History Modal -->
    <div class="modal fade" id="paymentHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment History</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="paymentHistoryContent">
                    <!-- Payment history will be loaded here -->
                </div>
            </div>
        </div>
    </div>

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

    .fee-card {
        background: var(--light-bg);
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .fee-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .fee-card h4 {
        color: var(--primary-color);
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .fee-card .amount {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--accent-color);
        margin: 15px 0;
        text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.1);
    }

    .fee-card p {
        color: #888;
        margin-top: 15px;
        font-size: 0.95rem;
    }

    .btn {
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
    }

    .btn-primary {
        background: var(--accent-color);
        border: none;
        box-shadow: 0 4px 15px rgba(237, 86, 59, 0.2);
    }

    .btn-primary:hover {
        background: #dc4c31;
        transform: translateY(-2px);
    }

    .btn-success {
        background: var(--success);
        border: none;
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.2);
    }

    .btn-success:hover {
        background: #27ae60;
        transform: translateY(-2px);
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

    .badge {
        padding: 8px 15px;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .bg-success {
        background: var(--success) !important;
    }

    .bg-danger {
        background: var(--danger) !important;
    }

    .modal-content {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
    }

    .modal-header {
        background: var(--primary-color);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 20px 30px;
    }

    .modal-body {
        padding: 30px;
    }

    .amount-edit .input-group {
        max-width: 250px;
        margin: 0 auto;
    }

    .amount-edit input {
        text-align: center;
        font-size: 1.8rem;
        font-weight: 600;
        padding: 10px;
        border: 2px solid #eee;
        border-radius: 8px;
    }

    .input-group-text {
        background: var(--primary-color);
        color: white;
        border: none;
        font-weight: 600;
    }

    /* Animation for status badges */
    .badge {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem;
        }
        
        .fee-card {
            padding: 20px;
        }
        
        .fee-card .amount {
            font-size: 2rem;
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

    /* Alert styles */
    .alert-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
    }

    .alert {
        margin-bottom: 1rem;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    </style>

    <script>
    function viewPaymentHistory(userId) {
        $.ajax({
            url: 'get_payment_history.php',
            type: 'POST',
            data: { user_id: userId },
            success: function(response) {
                $('#paymentHistoryContent').html(response);
                $('#paymentHistoryModal').modal('show');
            }
        });
    }

    $(document).ready(function() {
        // Store original values
        let originalJoiningFee = $('#joining-fee-display').text();
        let originalMonthlyFee = $('#monthly-fee-display').text();

        $('.edit-rates-btn').click(function() {
            // Show edit mode
            $('.amount').addClass('d-none');
            $('.amount-edit').removeClass('d-none');
            $('.save-cancel-buttons').addClass('active');
            $(this).addClass('d-none');
        });

        $('.cancel-edit-btn').click(function() {
            // Reset to original values
            $('#joining-fee-input').val(originalJoiningFee.replace('₹', '').replace(',', ''));
            $('#monthly-fee-input').val(originalMonthlyFee.replace('₹', '').replace(',', ''));
            
            // Hide edit mode
            $('.amount').removeClass('d-none');
            $('.amount-edit').addClass('d-none');
            $('.save-cancel-buttons').removeClass('active');
            $('.edit-rates-btn').removeClass('d-none');
        });

        $('.save-rates-btn').click(function() {
            const joiningFee = $('#joining-fee-input').val();
            const monthlyFee = $('#monthly-fee-input').val();

            if (confirm('Are you sure you want to update the membership rates?')) {
                $.ajax({
                    url: 'update_rates.php',
                    type: 'POST',
                    data: {
                        joining_fee: joiningFee,
                        monthly_fee: monthlyFee
                    },
                    success: function(response) {
                        console.log('Response:', response); // Debug line
                        try {
                            const result = JSON.parse(response);
                            if (result.status === 'success') {
                                // Format the numbers with commas and decimals
                                const formattedJoiningFee = new Intl.NumberFormat('en-IN', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(joiningFee);

                                const formattedMonthlyFee = new Intl.NumberFormat('en-IN', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(monthlyFee);

                                // Update the display values
                                $('#joining-fee-display').html('₹' + formattedJoiningFee);
                                $('#monthly-fee-display').html('₹' + formattedMonthlyFee);
                                
                                // Reset to display mode
                                $('.amount').removeClass('d-none');
                                $('.amount-edit').addClass('d-none');
                                $('.save-cancel-buttons').addClass('d-none');
                                $('.edit-rates-btn').removeClass('d-none');
                                
                                alert('Rates updated successfully!');
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                            alert('Error updating rates. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax error:', error);
                        alert('Error connecting to server. Please try again.');
                    }
                });
            }
        });
    });
    </script>
</body>
</html>