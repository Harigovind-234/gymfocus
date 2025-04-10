<?php
include 'connect.php';
session_start();

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$orderId = isset($data['orderId']) ? intval($data['orderId']) : 0;

if ($orderId > 0) {
    // Update the payment status
    $query = "UPDATE orders SET 
              payment_status = 'paid',
              status = CASE 
                WHEN collection_status = 'collected' THEN 'completed'
                ELSE status 
              END,
              updated_at = NOW()
              WHERE order_id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    
    if (mysqli_stmt_execute($stmt)) {
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
}
?> 