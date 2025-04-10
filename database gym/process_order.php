<?php
// Turn off error display - add these at the very top
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'connect.php';

// Set JSON header
header('Content-Type: application/json');

// Function to log errors
function logError($message, $error_details = null) {
    $log_file = 'logs/order_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not logged in';
    $log_message = "[{$timestamp}] User ID: {$user_id} - {$message}";
    if ($error_details) {
        $log_message .= " | Details: " . print_r($error_details, true);
    }
    error_log($log_message . PHP_EOL, 3, $log_file);
}

// Function to validate order data
function validateOrderData($product_id, $quantity, $payment_method) {
    $errors = [];
    
    if (!is_numeric($product_id) || $product_id <= 0) {
        $errors[] = "Invalid product ID";
    }
    
    if (!is_numeric($quantity) || $quantity <= 0) {
        $errors[] = "Invalid quantity";
    }
    
    if (!in_array($payment_method, ['cash', 'upi'])) {
        $errors[] = "Invalid payment method";
    }
    
    return $errors;
}

// Main process
try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to place an order');
    }

    // Get POST data
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
    $user_id = $_SESSION['user_id'];
    $sizes = isset($_POST['sizes']) ? implode(',', $_POST['sizes']) : null;

    // For Razorpay payments, get additional parameters
    $razorpay_payment_id = isset($_POST['razorpay_payment_id']) ? $_POST['razorpay_payment_id'] : null;
    $razorpay_order_id = isset($_POST['razorpay_order_id']) ? $_POST['razorpay_order_id'] : null;
    $razorpay_signature = isset($_POST['razorpay_signature']) ? $_POST['razorpay_signature'] : null;

    // Validate inputs
    if ($product_id <= 0 || $quantity <= 0) {
        throw new Exception('Invalid product or quantity');
    }

    // Additional validation for Razorpay payments
    if ($payment_method === 'razorpay' && (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature)) {
        throw new Exception('Invalid Razorpay payment details');
    }

    // Check product existence and stock
    $stmt = $conn->prepare("SELECT price, stock FROM products WHERE product_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        throw new Exception('Product not found');
    }

    if ($product['stock'] < $quantity) {
        throw new Exception("Insufficient stock. Available: {$product['stock']}");
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Calculate prices
        $subtotal = $product['price'] * $quantity;
        $gst = $subtotal * 0.18;
        $total_price = $subtotal + $gst;

        // Generate order reference
        $order_reference = 'ORD' . date('Ymd') . rand(1000, 9999);

        // Set initial payment status based on payment method
        $payment_status = ($payment_method === 'cash') ? 'paid' : 
                         ($payment_method === 'razorpay' && $razorpay_payment_id ? 'paid' : 'pending');

        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders (
                user_id, product_id, quantity, subtotal, gst, 
                total_price, status, order_reference, 
                payment_method, payment_status, collection_status,
                razorpay_payment_id, razorpay_order_id, razorpay_signature,
                sizes, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, 'pending', ?, 
                ?, ?, 'pending',
                ?, ?, ?,
                ?, NOW(), NOW()
            )
        ");

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param(
            "iiidddsssssss",
            $user_id, $product_id, $quantity, 
            $subtotal, $gst, $total_price, 
            $order_reference, $payment_method, $payment_status,
            $razorpay_payment_id, $razorpay_order_id, $razorpay_signature,
            $sizes
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to create order: ' . $stmt->error);
        }

        $order_id = $conn->insert_id;

        // Update stock
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?");
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param("iii", $quantity, $product_id, $quantity);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update stock: ' . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('Stock update failed');
        }

        // Commit transaction
        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $order_id,
            'order_reference' => $order_reference,
            'total_price' => $total_price,
            'payment_status' => $payment_status
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500); // Changed from 400 to 500 for server errors
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    // Log the error
    error_log("Order processing error: " . $e->getMessage());
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?> 