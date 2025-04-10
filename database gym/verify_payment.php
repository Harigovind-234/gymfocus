<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

function verifyRazorpaySignature($attributes) {
    $key_secret = 'TqC7xFxWWnBUsnAzznEB1YaT';
    $expected_signature = hash_hmac('sha256', $attributes['razorpay_order_id'] . '|' . $attributes['razorpay_payment_id'], $key_secret);
    
    return hash_equals($expected_signature, $attributes['razorpay_signature']);
}

try {
    // Get the Razorpay payment details from POST
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? null;
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? null;
    $razorpay_signature = $_POST['razorpay_signature'] ?? null;
    $order_id = $_POST['order_id'] ?? null;

    // Validate required parameters
    if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature || !$order_id) {
        throw new Exception('Missing required payment parameters');
    }

    // Verify the payment signature
    $attributes = array(
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature' => $razorpay_signature
    );

    if (!verifyRazorpaySignature($attributes)) {
        throw new Exception('Invalid payment signature');
    }

    // Start transaction
    $conn->begin_transaction();

    // Update order status and payment details
    $update_sql = "UPDATE orders SET 
                   payment_status = 'paid',
                   status = 'approved',
                   transaction_id = ?,
                   updated_at = NOW()
                   WHERE order_id = ?";
                   
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        throw new Exception("Error preparing update statement: " . $conn->error);
    }

    $stmt->bind_param("si", $razorpay_payment_id, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating order: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment verified successfully',
        'payment_id' => $razorpay_payment_id,
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback transaction if started
    if ($conn->inTransaction()) {
        $conn->rollback();
    }

    // Log error
    error_log("Payment verification error: " . $e->getMessage());

    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 