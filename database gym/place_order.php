<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: index.php");
    exit(); // Stop further execution
}

include "connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['user_id'];
    $quantity = intval($_POST['quantity']);
    $order_date = date('Y-m-d H:i:s');
    
    // Get the product price
    $price_query = "SELECT price FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $price_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $price_result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($price_result);
    
    $total_price = $product['price'] * $quantity;
    
    // Insert the order
    $sql = "INSERT INTO orders (user_id, product_id, quantity, order_date, total_price, status) 
            VALUES (?, ?, ?, ?, ?, 'Pending')";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiids", $user_id, $product_id, $quantity, $order_date, $total_price);
    
    if (mysqli_stmt_execute($stmt)) {
        // Notify admin
        $notify_sql = "INSERT INTO notifications (user_id, message, type, created_at) 
                      VALUES (?, 'New order placed', 'order', NOW())";
        $notify_stmt = mysqli_prepare($conn, $notify_sql);
        mysqli_stmt_bind_param($notify_stmt, "i", $user_id);
        mysqli_stmt_execute($notify_stmt);
        
        echo json_encode(['success' => true, 'message' => 'Order placed successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error placing order: ' . mysqli_error($conn)]);
    }
}