<?php
session_start();
require_once 'connect.php';

// Check if request is POST and is JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$amount = $data['amount'] ?? 0;
$product_id = $data['product_id'] ?? 0;
$quantity = $data['quantity'] ?? 1;

if (!$amount || !$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

// Razorpay API credentials
$key_id = 'rzp_test_Fur0pLo5d2MztK';
$key_secret = 'TqC7xFxWWnBUsnAzznEB1YaT';

// Create Razorpay order
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount' => round($amount * 100), // Convert to paise
    'currency' => 'INR',
    'receipt' => 'order_' . time() . '_' . $product_id,
    'payment_capture' => 1
]));

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_status === 200) {
    $order_data = json_decode($response, true);
    echo json_encode(['order_id' => $order_data['id']]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create order']);
} 