<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$order_id = (int)$data['order_id'];
$amount = (float)$data['amount'];
$payment_method = $data['payment_method'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert into transactions table
    $stmt = $conn->prepare("
        INSERT INTO transactions (user_id, payment_date, amount, payment_method, status) 
        VALUES (?, CURDATE(), ?, ?, ?)
    ");

    $status = $payment_method === 'cash' ? 'Pending' : 'Completed';
    $stmt->bind_param("idss", $_SESSION['user_id'], $amount, $payment_method, $status);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create transaction");
    }

    $transaction_id = $conn->insert_id;

    // Update order status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'confirmed', 
            transaction_id = ? 
        WHERE order_id = ? AND user_id = ?
    ");
    
    $stmt->bind_param("iii", $transaction_id, $order_id, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update order");
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'transaction_id' => $transaction_id,
        'message' => 'Payment processed successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 