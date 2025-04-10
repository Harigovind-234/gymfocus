<?php
session_start();
require_once 'connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Get basic transaction statistics from orders table - fixed stats query
$stats_query = "SELECT 
    SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as completed_amount,
    SUM(CASE WHEN payment_status = 'pending' THEN total_price ELSE 0 END) as pending_amount,
    SUM(CASE WHEN payment_status = 'failed' THEN total_price ELSE 0 END) as failed_amount,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_price ELSE 0 END) as today_amount,
    COUNT(CASE WHEN payment_method = 'razorpay' THEN 1 END) as online_count,
    COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) as cash_count
FROM orders";

// Execute stats query with proper error handling
$stats_result = mysqli_query($conn, $stats_query);
if (!$stats_result) {
    // Show error message for debugging
    echo "<div class='alert alert-danger mt-5'>Database Error: " . mysqli_error($conn) . "</div>";
    // Use default values as fallback
    $stats = [
        'completed_amount' => 0,
        'pending_amount' => 0,
        'failed_amount' => 0,
        'today_amount' => 0,
        'online_count' => 0,
        'cash_count' => 0
    ];
} else {
    $stats = mysqli_fetch_assoc($stats_result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Training Studio - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
    <style>
        /* Header and Navigation styles */
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

        /* Adjust main content to account for fixed header */
        .dashboard-container {
            margin-top: 100px;
            padding: 20px;
        }

        /* Dashboard styles */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .amount-text {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .status-badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
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
    <!-- Admin Header -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="admin.php" class="logo">Admin <em>Panel</em></a>
                        <ul class="nav">
                            <li><a href="admin.php">Home</a></li>
                            <li><a href="members.php">Members</a></li>
                            <li><a href="staff_management.php">Staff</a></li>
                            <li><a href="payments_check.php" class="active">Payments</a></li>
                            <li><a href="products.php">Products</a></li>
                            <li class="main-button"><a href="login2.php">Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <div class="dashboard-container">
        <!-- Amount Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h6 class="text-muted">Today's Transactions</h6>
                    <div class="amount-text">₹<?php echo number_format($stats['today_amount'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h6 class="text-muted">Completed Payments</h6>
                    <div class="amount-text text-success">₹<?php echo number_format($stats['completed_amount'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h6 class="text-muted">Pending Payments</h6>
                    <div class="amount-text text-warning">₹<?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <h6 class="text-muted">Failed Payments</h6>
                    <div class="amount-text text-danger">₹<?php echo number_format($stats['failed_amount'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Transaction Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="">All Methods</option>
                            <option value="razorpay">Razorpay</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="payment_status">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transaction History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Payment Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build query with filters for orders
                            $query = "SELECT 
                                o.order_id,
                                o.user_id,
                                o.product_id,
                                o.quantity,
                                o.total_price,
                                o.payment_method,
                                o.payment_status,
                                o.created_at,
                                o.razorpay_payment_id,
                                p.product_name,
                                r.full_name
                            FROM orders o
                            LEFT JOIN products p ON o.product_id = p.product_id
                            LEFT JOIN register r ON o.user_id = r.user_id
                            WHERE 1=1";

                            if (!empty($_GET['start_date'])) {
                                $query .= " AND DATE(o.created_at) >= '" . mysqli_real_escape_string($conn, $_GET['start_date']) . "'";
                            }
                            if (!empty($_GET['end_date'])) {
                                $query .= " AND DATE(o.created_at) <= '" . mysqli_real_escape_string($conn, $_GET['end_date']) . "'";
                            }
                            if (!empty($_GET['payment_method'])) {
                                $query .= " AND o.payment_method = '" . mysqli_real_escape_string($conn, $_GET['payment_method']) . "'";
                            }
                            if (!empty($_GET['payment_status'])) {
                                $query .= " AND o.payment_status = '" . mysqli_real_escape_string($conn, $_GET['payment_status']) . "'";
                            }

                            $query .= " ORDER BY o.created_at DESC LIMIT 50";

                            // Execute query with error checking
                            $result = mysqli_query($conn, $query);
                            if (!$result) {
                                echo "<tr><td colspan='8'>Query failed: " . mysqli_error($conn) . "</td></tr>";
                            } else {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $payment_status_class = match($row['payment_status']) {
                                        'paid' => 'bg-success',
                                        'pending' => 'bg-warning',
                                        'failed' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <tr>
                                        <td><?php echo $row['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                        <td>₹<?php echo number_format($row['total_price'], 2); ?></td>
                                        <td><?php echo ucfirst($row['payment_method']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $payment_status_class; ?> text-white">
                                                <?php echo ucfirst($row['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewOrder(<?php echo $row['order_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary" onclick="printReceipt(<?php echo $row['order_id']; ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Detail Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-receipt"></i> Purchase Receipt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="receiptContent">
                    <!-- Receipt content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrder(id) {
            fetch(`get_order.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('orderDetails').innerHTML = `
                        <div class="mb-3">
                            <strong>Order ID:</strong> ${data.order_id}
                        </div>
                        <div class="mb-3">
                            <strong>Product:</strong> ${data.product_name}
                        </div>
                        <div class="mb-3">
                            <strong>Customer:</strong> ${data.full_name}
                        </div>
                        <div class="mb-3">
                            <strong>Quantity:</strong> ${data.quantity}
                        </div>
                        <div class="mb-3">
                            <strong>Total Price:</strong> ₹${data.total_price}
                        </div>
                        <div class="mb-3">
                            <strong>Date:</strong> ${data.created_at}
                        </div>
                        <div class="mb-3">
                            <strong>Payment Method:</strong> ${data.payment_method}
                        </div>
                        <div class="mb-3">
                            <strong>Payment Status:</strong> 
                            <span class="badge bg-${data.payment_status === 'paid' ? 'success' : data.payment_status === 'pending' ? 'warning' : 'danger'}">
                                ${data.payment_status.charAt(0).toUpperCase() + data.payment_status.slice(1)}
                            </span>
                        </div>
                        ${data.razorpay_payment_id ? `
                        <div class="mb-3">
                            <strong>Razorpay Payment ID:</strong> ${data.razorpay_payment_id}
                        </div>` : ''}
                    `;
                    new bootstrap.Modal(document.getElementById('orderModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to fetch order details');
                });
        }

        function printReceipt(id) {
            fetch(`get_order.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    // Create a formatted receipt
                    const formattedDate = new Date(data.created_at).toLocaleDateString('en-IN', { 
                        day: '2-digit', 
                        month: '2-digit', 
                        year: 'numeric',
                        hour: '2-digit', 
                        minute: '2-digit'
                    });
                    
                    document.getElementById('receiptContent').innerHTML = `
                        <div class="receipt-container">
                            <div class="receipt-header text-center mb-4">
                                <h4 class="mb-0">Focus Gym Store</h4>
                                <p class="mb-2 text-muted small">Your Fitness Partner</p>
                                <p class="mb-0 small">Receipt #INV-${data.order_id}</p>
                                <p class="small">Date: ${formattedDate}</p>
                            </div>

                            <div class="customer-details mb-4">
                                <h6 class="border-bottom pb-2">Customer Information</h6>
                                <div class="row mb-1">
                                    <div class="col-4 text-muted">Name:</div>
                                    <div class="col-8">${data.full_name}</div>
                                </div>
                            </div>

                            <div class="order-details mb-4">
                                <h6 class="border-bottom pb-2">Order Details</h6>
                                <div class="product-row py-2">
                                    <div class="row">
                                        <div class="col-7 fw-bold">${data.product_name}</div>
                                        <div class="col-2 text-center">${data.quantity}</div>
                                        <div class="col-3 text-end">₹${(data.total_price / data.quantity).toFixed(2)}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-summary">
                                <h6 class="border-bottom pb-2">Payment Summary</h6>
                                <div class="row mb-2">
                                    <div class="col-8 text-muted">Subtotal:</div>
                                    <div class="col-4 text-end">₹${data.subtotal || (data.total_price * 0.85).toFixed(2)}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-8 text-muted">GST (18%):</div>
                                    <div class="col-4 text-end">₹${data.gst || (data.total_price * 0.15).toFixed(2)}</div>
                                </div>
                                <div class="row fw-bold">
                                    <div class="col-8">Total:</div>
                                    <div class="col-4 text-end text-primary">₹${data.total_price}</div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-8 text-muted">Payment Method:</div>
                                    <div class="col-4 text-end">${data.payment_method.charAt(0).toUpperCase() + data.payment_method.slice(1)}</div>
                                </div>
                                <div class="row">
                                    <div class="col-8 text-muted">Payment Status:</div>
                                    <div class="col-4 text-end">
                                        <span class="badge bg-${data.payment_status === 'paid' ? 'success' : data.payment_status === 'pending' ? 'warning' : 'danger'}">
                                            ${data.payment_status.charAt(0).toUpperCase() + data.payment_status.slice(1)}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4 mb-2">
                                <p class="mb-1">Thank you for your purchase!</p>
                                <p class="small text-muted mb-0">For any queries, please contact our support team.</p>
                            </div>
                        </div>
                    `;
                    
                    new bootstrap.Modal(document.getElementById('receiptModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to generate receipt');
                });
        }
    </script>
</body>
</html>

