<?php
header('Content-Type: application/json');
session_start();
require_once 'connect.php';

// Check if user is authorized
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    // Add any additional checks here (e.g., only allow deletion of pending orders)
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting order: ' . $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No order ID provided']);
} 