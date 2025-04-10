<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

// Razorpay credentials
define('RAZORPAY_KEY_ID', 'rzp_test_Fur0pLo5d2MztK');
define('RAZORPAY_KEY_SECRET', 'TqC7xFxWWnBUsnAzznEB1YaT');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $amount = $data['amount'] ?? 0;
    $product_id = $data['product_id'] ?? 0;
    $quantity = $data['quantity'] ?? 1;

    // Validate inputs
    if (!$amount || !$product_id || !isset($_SESSION['user_id'])) {
        throw new Exception('Invalid request parameters');
    }

    // Create Razorpay order
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $order_data = [
        'amount' => $amount * 100, // Convert to paise
        'currency' => 'INR',
        'receipt' => 'order_' . time() . '_' . $product_id,
        'payment_capture' => 1
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status === 200) {
        $razorpay_order = json_decode($response, true);
        
        // Start transaction
        $conn->begin_transaction();

        // Create order in database
        $sql = "INSERT INTO orders (user_id, product_id, quantity, total_price, status, payment_method, payment_status, razorpay_order_id) 
                VALUES (?, ?, ?, ?, 'pending', 'razorpay', 'pending', ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing order insert: " . $conn->error);
        }

        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("iiids", $user_id, $product_id, $quantity, $amount, $razorpay_order['id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating order: " . $stmt->error);
        }

        $order_id = $conn->insert_id;

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'status' => 'success',
            'order_id' => $order_id,
            'razorpay_order_id' => $razorpay_order['id'],
            'amount' => $amount,
            'key' => RAZORPAY_KEY_ID
        ]);

    } else {
        throw new Exception('Failed to create Razorpay order');
    }

} catch (Exception $e) {
    // Rollback transaction if started
    if ($conn->inTransaction()) {
        $conn->rollback();
    }

    // Log error
    error_log("Order creation error: " . $e->getMessage());

    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 