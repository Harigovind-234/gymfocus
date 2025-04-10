<?php
// Debugging script for Razorpay payment issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connect.php';

// Function to display errors in a formatted way
function showError($message) {
    echo '<div style="color: red; background: #ffeeee; padding: 10px; margin: 10px 0; border: 1px solid #ffcccc;">';
    echo $message;
    echo '</div>';
}

// Function to display success messages
function showSuccess($message) {
    echo '<div style="color: green; background: #eeffee; padding: 10px; margin: 10px 0; border: 1px solid #ccffcc;">';
    echo $message;
    echo '</div>';
}

echo '<h1>Payment Debug Utility</h1>';

// Check the payment_errors.log file
$logFile = __DIR__ . '/payment_errors.log';
echo '<h2>Payment Errors Log</h2>';

if (file_exists($logFile)) {
    $log = file_get_contents($logFile);
    echo '<pre>' . htmlspecialchars($log) . '</pre>';
    
    // Option to clear the log
    if (isset($_GET['clear_log']) && $_GET['clear_log'] == 1) {
        if (unlink($logFile)) {
            showSuccess('Log file cleared successfully.');
        } else {
            showError('Failed to clear log file.');
        }
    } else {
        echo '<p><a href="?clear_log=1" class="button">Clear Log File</a></p>';
    }
} else {
    echo '<p>No log file found.</p>';
}

// Check payments table
echo '<h2>Recent Payments</h2>';
$query = "SELECT * FROM payments ORDER BY id DESC LIMIT 10";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>ID</th><th>User ID</th><th>Payment ID</th><th>Amount</th><th>Is New Member</th><th>Date</th></tr>';
    
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['user_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['payment_id']) . '</td>';
        echo '<td>₹' . htmlspecialchars($row['amount']) . '</td>';
        echo '<td>' . ($row['is_new_member'] ? 'Yes' : 'No') . '</td>';
        echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
} else {
    echo '<p>No payment records found.</p>';
}

// Check memberships table
echo '<h2>Recent Memberships</h2>';
$query = "SELECT * FROM memberships ORDER BY membership_id DESC LIMIT 10";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>ID</th><th>User ID</th><th>Status</th><th>Payment Type</th><th>Amount</th><th>Transaction ID</th></tr>';
    
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['membership_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['user_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['membership_status']) . '</td>';
        echo '<td>' . htmlspecialchars($row['payment_type']) . '</td>';
        echo '<td>₹' . htmlspecialchars($row['payment_amount']) . '</td>';
        echo '<td>' . htmlspecialchars($row['transaction_id']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
} else {
    echo '<p>No membership records found.</p>';
}

// Test insert utility
echo '<h2>Test Insert Utility</h2>';

if (isset($_POST['test_insert'])) {
    try {
        $conn->begin_transaction();
        
        $user_id = (int)$_POST['user_id'];
        $payment_id = 'test_' . time();
        $amount = 999.00;
        $is_new_member = isset($_POST['is_new_member']) ? 1 : 0;
        $email = $_POST['email'];
        
        // Get dates
        $current_date = date('Y-m-d');
        $next_payment_date = date('Y-m-d', strtotime('+1 month'));
        $joining_date = $is_new_member ? $current_date : $_POST['joining_date'];
        
        // Payment type
        $payment_type = $is_new_member ? 'joining' : 'monthly';
        
        // Fixed values
        $joining_fee = 2000.00;
        $monthly_fee = 999.00;
        
        // Direct SQL approach
        $sql = "INSERT INTO memberships (
                user_id, 
                email, 
                joining_date, 
                last_payment_date, 
                next_payment_date, 
                membership_status, 
                payment_amount, 
                payment_type, 
                payment_status, 
                payment_method, 
                transaction_id, 
                rate_joining_fee, 
                rate_monthly_fee, 
                rate_updated_at,
                rate_updated_by
            ) VALUES (
                $user_id, 
                '$email', 
                '$joining_date', 
                '$current_date', 
                '$next_payment_date', 
                'active', 
                $amount, 
                '$payment_type', 
                'completed', 
                'razorpay', 
                '$payment_id', 
                $joining_fee, 
                $monthly_fee, 
                NOW(),
                $user_id
            )";
            
        if ($conn->query($sql)) {
            if (isset($_POST['commit']) && $_POST['commit'] == 1) {
                $conn->commit();
                showSuccess("Test insert successfully committed!");
            } else {
                $conn->rollback();
                showSuccess("Test insert successful but rolled back (test only).");
            }
        } else {
            $conn->rollback();
            showError("Failed to insert: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        showError("Error: " . $e->getMessage());
    }
}

// Show the test form
echo '<form method="post" action="">';
echo '<div style="margin: 10px 0;">';
echo '<label>User ID: </label>';
echo '<input type="number" name="user_id" value="91" required>';
echo '</div>';

echo '<div style="margin: 10px 0;">';
echo '<label>Email: </label>';
echo '<input type="email" name="email" value="test@example.com" required>';
echo '</div>';

echo '<div style="margin: 10px 0;">';
echo '<label>Joining Date: </label>';
echo '<input type="date" name="joining_date" value="' . date('Y-m-d') . '" required>';
echo '</div>';

echo '<div style="margin: 10px 0;">';
echo '<label><input type="checkbox" name="is_new_member" value="1"> Is New Member</label>';
echo '</div>';

echo '<div style="margin: 10px 0;">';
echo '<label><input type="checkbox" name="commit" value="1"> Commit Transaction (unchecked = test only)</label>';
echo '</div>';

echo '<div style="margin: 10px 0;">';
echo '<input type="submit" name="test_insert" value="Test Insert">';
echo '</div>';
echo '</form>';

// Style
echo '<style>
.button {
    background: #4CAF50;
    color: white;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
}
</style>';

$conn->close();
?> 