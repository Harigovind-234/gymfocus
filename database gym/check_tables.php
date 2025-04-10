<?php
// This is a diagnostic script to check table structures

// Allow error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'connect.php';

echo "<h1>Database Table Checks</h1>";

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to print table structure
function printTableStructure($conn, $tableName) {
    echo "<h2>Table: $tableName</h2>";
    
    if (!tableExists($conn, $tableName)) {
        echo "<p style='color:red'>Table does not exist!</p>";
        return;
    }
    
    $result = $conn->query("DESCRIBE $tableName");
    
    if (!$result) {
        echo "<p style='color:red'>Error describing table: " . $conn->error . "</p>";
        return;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Check key tables
$tables = ['payments', 'memberships', 'register', 'login'];

foreach ($tables as $table) {
    printTableStructure($conn, $table);
}

// Test connection for process_payment.php
echo "<h2>Simulating process_payment.php</h2>";

try {
    // Sample data
    $user_id = 91; // Change to an actual user ID in your system
    $payment_id = 'test_payment_' . time();
    $amount = 999;
    $is_new_member = false;
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Insert into payments
    $stmt = $conn->prepare("INSERT INTO payments (user_id, payment_id, amount, is_new_member) VALUES (?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("isdi", $user_id, $payment_id, $amount, $is_new_member);
    
    if (!$stmt->execute()) {
        throw new Exception("Insert into payments failed: " . $stmt->error);
    }
    
    echo "<p style='color:green'>Successfully inserted into payments table</p>";
    
    // Get user email
    $email_query = "SELECT email FROM login WHERE user_id = ?";
    $stmt = $conn->prepare($email_query);
    
    if (!$stmt) {
        throw new Exception("Prepare email query failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("No user found with ID $user_id");
    }
    
    $user_email = $result->fetch_assoc()['email'];
    echo "<p>Found user email: " . htmlspecialchars($user_email) . "</p>";
    
    // Get joining date if available
    $join_query = "SELECT joining_date FROM memberships WHERE user_id = ? ORDER BY membership_id ASC LIMIT 1";
    $join_stmt = $conn->prepare($join_query);
    $join_stmt->bind_param("i", $user_id);
    $join_stmt->execute();
    $join_result = $join_stmt->get_result();
    $current_date = date('Y-m-d');
    $joining_date = $current_date;
    
    if ($join_result->num_rows > 0) {
        $joining_date = $join_result->fetch_assoc()['joining_date'];
        echo "<p>Found existing joining date: " . htmlspecialchars($joining_date) . "</p>";
    } else {
        echo "<p>No previous membership found. Using current date as joining date.</p>";
    }
    
    // Calculate next payment date
    $next_payment_date = date('Y-m-d', strtotime('+1 month'));
    
    // Get rates
    $joining_fee = 2000;
    $monthly_fee = 999;
    
    // Prepare membership insert
    $membership_stmt = $conn->prepare("INSERT INTO memberships 
        (user_id, email, joining_date, last_payment_date, next_payment_date, 
        membership_status, payment_amount, payment_type, payment_status, 
        payment_method, transaction_id, rate_joining_fee, rate_monthly_fee,
        rate_updated_at, rate_updated_by) 
        VALUES (?, ?, ?, ?, ?, 'active', ?, 'monthly', 'completed', 'razorpay', ?, ?, ?, NOW(), ?)");
    
    if (!$membership_stmt) {
        throw new Exception("Prepare membership insert failed: " . $conn->error);
    }
    
    $membership_stmt->bind_param("issssdsidi", 
        $user_id, 
        $user_email,
        $joining_date, 
        $current_date, 
        $next_payment_date, 
        $amount,
        $payment_id,
        $joining_fee,
        $monthly_fee,
        $user_id
    );
    
    if (!$membership_stmt->execute()) {
        throw new Exception("Insert into memberships failed: " . $membership_stmt->error);
    }
    
    echo "<p style='color:green'>Successfully inserted into memberships table</p>";
    
    // Rollback as this is just a test
    $conn->rollback();
    echo "<p>Transaction rolled back (this was just a test)</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    $conn->rollback();
}

$conn->close();
?> 