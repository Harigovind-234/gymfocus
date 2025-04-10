<?php
include 'connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = floatval($_POST['price']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $stock = intval($_POST['stock']);
    
    // Validate inputs
    if (empty($product_name) || empty($description) || $price <= 0 || $stock < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill all required fields correctly'
        ]);
        exit;
    }
    
    // Handle file upload
    $image_path = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error uploading image'
            ]);
            exit;
        }
    }

    // Insert product into database
    $sql = "INSERT INTO products (product_name, description, price, category_id, image_path, stock) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssdssi", $product_name, $description, $price, $category, $image_path, $stock);
        
        if (mysqli_stmt_execute($stmt)) {
            // Get the inserted product details
            $product_id = mysqli_insert_id($conn);
            $query = "SELECT p.*, c.category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.category_id 
                     WHERE p.product_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);
            
            echo json_encode([
                'success' => true,
                'message' => 'Product added successfully!',
                'product' => $product
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error adding product: ' . mysqli_stmt_error($stmt)
            ]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error preparing statement: ' . mysqli_error($conn)
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>