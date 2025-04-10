<?php
include 'connect.php';
header('Content-Type: application/json');

if (isset($_GET['subcategory_id'])) {
    $subcategory_id = intval($_GET['subcategory_id']);
    $query = "SELECT * FROM categories WHERE parent_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $subcategory_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $classifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $classifications[] = $row;
    }
    
    echo json_encode($classifications);
} 