<?php
// Start output buffering to prevent unwanted output
ob_start();

session_start();
include 'connect.php';

// Enable full error reporting but log to file only
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Log errors to a file for debugging
function logError($message) {
    $logFile = __DIR__ . '/payment_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Clean any buffered output
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        $payment_id = $_POST['payment_id'] ?? '';
        $user_id = $_SESSION['user_id'] ?? $_POST['user_id'] ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $is_new_member = $_POST['is_new_member'] === 'true' ? 1 : 0;
        
        // Log incoming payment data
        logError("Payment data received: payment_id=$payment_id, user_id=$user_id, amount=$amount, is_new_member=$is_new_member");
        
        // Validate data
        if (empty($payment_id) || !$user_id || !$amount) {
            logError("Missing required payment data");
            echo json_encode(['status' => 'error', 'message' => 'Missing required payment data']);
            exit;
        }
        
        // Sanitize payment_id
        $payment_id = mysqli_real_escape_string($conn, $payment_id);
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Get user email
        $email_query = "SELECT email FROM login WHERE user_id = ?";
        $stmt = $conn->prepare($email_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_email = '';
        
        if ($row = $result->fetch_assoc()) {
            $user_email = $row['email'];
        } else {
            logError("User email not found for user_id: $user_id");
        }
        $stmt->close();
        
        // Insert into payments table (create if not exists)
        $payments_check = $conn->query("SHOW TABLES LIKE 'payments'");
        if ($payments_check->num_rows === 0) {
            // Create payments table
            $conn->query("CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                payment_id VARCHAR(255) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                is_new_member TINYINT(1) DEFAULT 0,
                payment_method VARCHAR(50) DEFAULT 'razorpay',
                status VARCHAR(20) DEFAULT 'completed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // Insert payment
        $payment_stmt = $conn->prepare("INSERT INTO payments (user_id, payment_id, amount, is_new_member) VALUES (?, ?, ?, ?)");
        $payment_stmt->bind_param("isdi", $user_id, $payment_id, $amount, $is_new_member);
        
        if (!$payment_stmt->execute()) {
            throw new Exception("Failed to insert payment: " . $payment_stmt->error);
        }
        $payment_stmt->close();
        
        // Set membership dates
        $current_date = date('Y-m-d');
        $next_payment_date = date('Y-m-d', strtotime('+1 month'));
        
        // Determine payment type based on is_new_member
        $payment_type = $is_new_member ? 'joining' : 'monthly';
        
        // Get joining date for existing members
        $joining_date = $current_date; // Default for new members
        
        if (!$is_new_member) {
            $join_query = "SELECT joining_date FROM memberships WHERE user_id = ? ORDER BY membership_id ASC LIMIT 1";
            $join_stmt = $conn->prepare($join_query);
            $join_stmt->bind_param("i", $user_id);
            $join_stmt->execute();
            $join_result = $join_stmt->get_result();
            
            if ($join_row = $join_result->fetch_assoc()) {
                $joining_date = $join_row['joining_date'];
            }
            $join_stmt->close();
        }
        
        // Update existing memberships to expired
        if ($is_new_member) {
            $conn->query("UPDATE memberships SET membership_status = 'expired' WHERE user_id = $user_id");
        }
        
        // Fixed rate values (using the defaults from the table definition)
        $joining_fee = 2000.00;
        $monthly_fee = 999.00;
        
        // Direct SQL approach with all required fields
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
                '$user_email', 
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
        
        logError("Executing SQL: $sql");
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to insert membership: " . $conn->error);
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Success response
        echo json_encode(['status' => 'success']);
        
    } catch (Exception $e) {
        logError("Exception: " . $e->getMessage());
        
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
        }
        
        echo json_encode(['status' => 'error', 'message' => 'Payment processing error: ' . $e->getMessage()]);
    } catch (Error $e) {
        logError("PHP Error: " . $e->getMessage());
        
        if (isset($conn) && $conn->ping()) {
            $conn->rollback();
        }
        
        echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
    }
    
    if (isset($conn)) {
        $conn->close();
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

// End any remaining output buffering
while (ob_get_level() > 0) {
    ob_end_clean();
}
