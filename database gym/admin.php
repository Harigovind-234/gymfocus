<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login2.php");
  exit();
}
echo "Session Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "Session Status: " . session_status() . "<br>";

include 'connect.php';

// Get Members Statistics
$members_query = "SELECT COUNT(*) AS total FROM login WHERE role='Member'";
$members_result = mysqli_query($conn, $members_query);
$members_count = mysqli_fetch_assoc($members_result)['total'];
 
// Get Staff Statistics
$staff_query = "SELECT COUNT(*) AS total FROM login WHERE role='Staff'";
$staff_result = mysqli_query($conn, $staff_query);
$staff_count = mysqli_fetch_assoc($staff_result)['total'];

// Check if payments table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
if (mysqli_num_rows($table_check) == 0) {
    // Create payments table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        payment_id VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        is_new_member TINYINT(1) DEFAULT 0,
        payment_method VARCHAR(50) DEFAULT 'razorpay',
        status VARCHAR(20) DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        order_id INT NULL
    )";
    mysqli_query($conn, $create_table);
}

// Get Payment Statistics with error handling
$payment_query = "SELECT 
    COUNT(*) as total_transactions,
    IFNULL(SUM(amount), 0) as total_amount,
    0 as pending_amount
FROM payments";

$payment_result = mysqli_query($conn, $payment_query);
if ($payment_result) {
    $payment_stats = mysqli_fetch_assoc($payment_result);
} else {
    // Default values if query fails
    $payment_stats = [
        'total_transactions' => 0,
        'total_amount' => 0,
        'pending_amount' => 0
    ];
}

// Get Product Statistics
$product_query = "SELECT COUNT(*) AS total FROM products";
$product_result = mysqli_query($conn, $product_query);
$product_count = mysqli_fetch_assoc($product_result)['total'];
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Training Studio - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
    <style>
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
          transition: color 0.3s ease;
        }

        .header-area .nav li a:hover,
        .header-area .nav li a.active {
          color: #ed563b;
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

        .admin-dashboard {
          max-width: 1200px;
          margin-left: 20%;
          margin-top: 100px;
          padding: 0 15px;
        }

        .admin-card {
          background-color: #fff;
          border-radius: 5px;
          box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
          padding: 30px;
          margin-bottom: 30px;
        }

        .admin-card h3 {
          color: #232d39;
          margin-bottom: 20px;
          font-size: 23px;
          letter-spacing: 0.5px;
        }

        .admin-stats {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 20px;
        }

        .admin-stat-item {
          background-color: #f7f7f7;
          border-radius: 5px;
          padding: 20px;
          text-align: center;
        }

        .admin-stat-value {
          font-size: 36px;
          color: #ed563b;
          font-weight: 700;
        }

        .admin-stat-label {
          color:rgb(17, 15, 15);
          text-transform: uppercase;
          font-size: 13px;
        }
        

        .admin-table {
          width: 100%;
          border-collapse: collapse;
        }

        .admin-table th {
          background-color: #ed563b;
          color: white;
          padding: 12px;
          text-align: left;
          text-transform: uppercase;
          font-size: 13px;
        }

        .admin-table td {
          padding: 12px;
          border-bottom: 1px solid #eee;
          color: #232d39;
        }

        .admin-actions {
          display: flex;
          justify-content: space-between;
          margin-top: 20px;
        }

        .admin-button {
          display: inline-block;
          font-size: 13px;
          padding: 11px 17px;
          background-color: #ed563b;
          color: #fff;
          text-align: center;
          font-weight: 400;
          text-transform: uppercase;
          transition: all 0.3s;
          border: none;
          border-radius: 5px;
          cursor: pointer;
        }

        .admin-button:hover {
          background-color: #f9735b;
        }

        .view-details {
          background-color: #f9735b;
        }

        .view-details:hover {
          background-color: #f9735b;
        }

        .nav li a {
            cursor: pointer;
            color: #fff;
            text-decoration: none;
        }

        .nav li {
            list-style: none;
            margin: 0 15px;
        }

        .nav li a:hover {
            color: #ed563b;
        }

        /* Remove any interfering styles */
        .scroll-to-section {
            all: unset;
        }

        .dashboard-container {
          margin-top: 120px;
          padding: 0;
          max-width: 1400px;
          margin-left: auto;
          margin-right: auto;
          width: 100%;
        }

        .dashboard-section {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin: 0 30px 30px 30px;
            width: calc(100% - 60px);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 1.8rem;
            color: #2B3240;
            font-weight: 600;
            margin: 0;
        }

        .section-title i {
            color: #ed563b;
        }

        .stat-card {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            height: 100%;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, #ed563b, #ff8f6b);
            border-radius: 0 0 15px 15px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #ed563b;
            margin-bottom: 20px;
        }

        .stat-value {
            font-size: 2.8rem;
            font-weight: 700;
            color: #2B3240;
            margin: 10px 0;
            line-height: 1;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
        }

        .table thead th {
            background: #2B3240;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            padding: 15px 20px;
            border: none;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 15px 20px;
            vertical-align: middle;
            color: #444;
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }

        .badge {
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .bg-success {
            background: #28a745 !important;
        }

        .bg-warning {
            background: #ffc107 !important;
            color: #000 !important;
        }

        .action-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #ed563b;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #ff8f6b;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(237, 86, 59, 0.2);
        }

        .recent-activity {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .activity-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #ed563b;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.2rem;
        }

        .activity-details {
            flex-grow: 1;
        }

        .activity-details div:first-child {
            font-weight: 600;
            color: #2B3240;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }

        .chart-container {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            height: 300px;
            margin-top: 20px;
        }

        footer {
            background: #2B3240;
            padding: 25px 0;
            margin-top: 50px;
        }

        footer p {
            color: #fff;
            text-align: center;
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        @media (max-width: 992px) {
            .dashboard-container {
                padding: 0 20px;
            }
            
            .stat-card {
                margin-bottom: 20px;
            }
            
            .section-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .activity-item {
                flex-direction: column;
                text-align: center;
            }
            
            .activity-icon {
                margin: 0 0 15px 0;
            }
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: 700;
        }

        /* Specific style for the membership section */
        .dashboard-section:last-child {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            margin: 0 30px 50px 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            width: calc(100% - 60px);
        }

        /* Membership section header */
        .dashboard-section:last-child .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .dashboard-section:last-child .section-title {
            font-size: 1.8rem;
            color: #2B3240;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Add icon to membership title */
        .dashboard-section:last-child .section-title::before {
            content: '\f509'; /* Font Awesome membership card icon */
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: #ed563b;
            font-size: 1.6rem;
        }

        /* Style for membership action button */
        .dashboard-section:last-child .action-btn {
            background: #ed563b;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .dashboard-section:last-child .action-btn:hover {
            background: #ff8f6b;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(237, 86, 59, 0.2);
        }

        /* Add hover effect to the section */
        .dashboard-section:last-child:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-section:last-child {
                margin: 0 15px 30px 15px;
                width: calc(100% - 30px);
                padding: 20px;
            }

            .dashboard-section:last-child .section-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .dashboard-section:last-child .section-title {
                justify-content: center;
            }
        }

        /* Stats Cards with meaningful icons */
        .stat-card i {
            font-size: 2.5rem;
            color: #ed563b;
            margin-bottom: 15px;
            opacity: 0.9;
            transition: all 0.3s ease;
        }

        .stat-card:hover i {
            transform: scale(1.1);
            opacity: 1;
        }

        .section-title i {
            color: #ed563b;
            margin-right: 10px;
            font-size: 1.8rem;
            transition: all 0.3s ease;
        }

        .dashboard-section:hover .section-title i {
            transform: scale(1.1);
        }

        .action-btn i {
            font-size: 1rem;
            margin-right: 8px;
        }

        /* Activity icons */
        .activity-icon i {
            font-size: 1.4rem;
            color: white;
        }

        /* Recent transactions icon styling */
        .recent-activity .activity-item i {
            font-size: 1.2rem;
            color: #ed563b;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  </head>
  <body>
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
                  <li><a href="payments_check.php">Payments</a></li>
                  <li><a href="products.php">Products</a></li>
                  <li class="main-button"><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
          </div>
        </div>
      </div>
    </header>

    <div class="dashboard-container">
        <!-- Stats Cards with meaningful icons -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-users-line"></i> <!-- Modern group of users icon -->
                    <div class="stat-value"><?php echo $members_count; ?></div>
                    <div class="stat-label">Total Members</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-user-tie"></i> <!-- Professional staff icon -->
                    <div class="stat-value"><?php echo $staff_count; ?></div>
                    <div class="stat-label">Staff Members</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-indian-rupee-sign"></i> <!-- Rupee currency icon -->
                    <div class="stat-value">₹<?php echo number_format($payment_stats['total_amount'] ?? 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-dumbbell"></i> <!-- Gym equipment icon -->
                    <div class="stat-value"><?php echo $product_count; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
        </div>

        <!-- Member Management Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-users"></i>
                    Member Management
                </h2>
                <a href="members.php" class="action-btn">
                    <i class="fas fa-user-plus"></i> <!-- Add user icon -->
                    View All Members
                </a>
            </div>
            <div class="row">
                <!-- Recent Members -->
                <div class="col-md-8">
                    <h4>Recent Members</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Join Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_members_query = "SELECT * FROM Register ORDER BY created_at DESC LIMIT 5";
                                $recent_members_result = mysqli_query($conn, $recent_members_query);
                                while ($member = mysqli_fetch_assoc($recent_members_result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
                                    echo "<td>" . date('M d, Y', strtotime($member['created_at'])) . "</td>";
                                    echo "<td><span class='badge bg-success'>Active</span></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Member Stats -->
                <div class="col-md-4">
                    <div class="chart-container">
                        <!-- Add your chart here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Overview Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-money-bill"></i>
                    Payment Overview
                </h2>
                <a href="payments_check.php" class="action-btn">
                    <i class="fas fa-file-invoice-dollar"></i> <!-- Invoice icon -->
                    View All Payments
                </a>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h4>Recent Transactions</h4>
                        <div class="recent-activity">
                            <?php
                            // Check if needed tables exist
                            $orders_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
                            $has_orders_table = mysqli_num_rows($orders_table_check) > 0;

                            if ($has_orders_table) {
                                $recent_transactions_query = "SELECT 
                                    p.*, 
                                    p.user_id,
                                    IFNULL(r.full_name, 'Unknown User') as full_name,
                                    p.id as payment_date
                                FROM payments p
                                LEFT JOIN Register r ON p.user_id = r.user_id 
                                ORDER BY p.id DESC 
                                LIMIT 5";
                            } else {
                                // Same query for both cases
                                $recent_transactions_query = "SELECT 
                                    p.*, 
                                    p.user_id,
                                    IFNULL(r.full_name, 'Unknown User') as full_name,
                                    p.id as payment_date
                                FROM payments p
                                LEFT JOIN Register r ON p.user_id = r.user_id 
                                ORDER BY p.id DESC 
                                LIMIT 5";
                            }

                            $recent_transactions_result = mysqli_query($conn, $recent_transactions_query);
                            
                            if ($recent_transactions_result && mysqli_num_rows($recent_transactions_result) > 0) {
                                while ($transaction = mysqli_fetch_assoc($recent_transactions_result)) {
                                    ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="activity-details">
                                            <div><?php echo htmlspecialchars($transaction['full_name']); ?></div>
                                            <div class="activity-time">₹<?php echo number_format($transaction['amount'], 2); ?></div>
                                        </div>
                                        <span class="badge bg-success">
                                            Completed
                                        </span>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="text-center p-4">No recent transactions found</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <!-- Add payment chart here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Management Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-user-tie"></i>
                    Staff Management
                </h2>
                <a href="staff_management.php" class="action-btn">
                    <i class="fas fa-user-gear"></i> <!-- User settings icon -->
                    Manage Staff
                </a>
            </div>
            <!-- Add staff content -->
        </div>

        <!-- Product Inventory Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-dumbbell"></i>
                    Product Inventory
                </h2>
                <a href="products.php" class="action-btn">
                    <i class="fas fa-boxes-stacked"></i> <!-- Multiple boxes icon -->
                    Manage Products
                </a>
            </div>
            <!-- Add products content -->
        </div>

        <!-- Gym Memberships Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-id-card"></i>
                    Gym Memberships
                </h2>
                <a href="manage_memberships.php" class="action-btn">
                    <i class="fas fa-gear"></i> <!-- Settings icon -->
                    Manage Membership Plans
                </a>
            </div>
        </div>
    </div>

    <footer>
      <div class="container">
        <div class="row">
          <div class="col-lg-12">
            <p>Copyright &copy; 2025 FOCUS GYM Admin Panel</p>
          </div>
        </div>
      </div>
    </footer>

    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/popper.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Remove any classes that might interfere with navigation
        document.querySelectorAll('.scroll-to-section').forEach(el => {
            el.classList.remove('scroll-to-section');
        });
    });
    </script>
  </body>
</html>