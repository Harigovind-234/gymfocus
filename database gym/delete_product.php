<?php
include 'connect.php';
session_start();

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // First get the image path to delete the file
    $query = "SELECT image_path FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    
    // Delete the image file if it exists
    if ($product && !empty($product['image_path']) && file_exists($product['image_path'])) {
        unlink($product['image_path']);
    }
    
    // Delete the product from database
    $delete_query = "DELETE FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: products.php?deleted=1");
    } else {
        header("Location: products.php?error=1");
    }
} else {
    header("Location: products.php");
}
exit(); 