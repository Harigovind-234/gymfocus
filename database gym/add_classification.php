<?php
include 'connect.php';
header('Content-Type: application/json');

try {
    // Validate input parameters
    if (!isset($_POST['category_id']) || !isset($_POST['classification_name'])) {
        throw new Exception('Missing required parameters');
    }

    $category_id = intval($_POST['category_id']);
    $classification_name = trim($_POST['classification_name']);

    if (empty($classification_name)) {
        throw new Exception('Classification name cannot be empty');
    }

    // Check if classification already exists under this parent category
    $check_sql = "SELECT category_id FROM categories 
                 WHERE LOWER(category_name) = LOWER(?) 
                 AND parent_id = ? 
                 AND is_subcategory = 1";
                 
    $check_stmt = mysqli_prepare($conn, $check_sql);
    if (!$check_stmt) {
        throw new Exception('Failed to prepare check statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($check_stmt, "si", $classification_name, $category_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        throw new Exception('Classification already exists under this category');
    }
    mysqli_stmt_close($check_stmt);

    // Get parent category type for template
    $parent_query = "SELECT category_name FROM categories WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $parent_query);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $parent_category = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Insert new classification
    $sql = "INSERT INTO categories (category_name, parent_id, is_subcategory) VALUES (?, ?, 1)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare insert statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "si", $classification_name, $category_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to add classification: ' . mysqli_stmt_error($stmt));
    }
    
    $new_id = mysqli_insert_id($conn);
    
    echo json_encode([
        'success' => true,
        'classification_id' => $new_id,
        'classification_name' => $classification_name,
        'message' => 'Classification added successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>
