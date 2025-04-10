<?php
include 'connect.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['category_id'])) {
        throw new Exception('Category ID is required');
    }

    $category_id = intval($_GET['category_id']);
    
    // Add error checking for the category_id
    if ($category_id <= 0) {
        throw new Exception('Invalid category ID');
    }
    
    $query = "SELECT category_id, category_name, category_details 
              FROM categories 
              WHERE parent_id = ? AND is_subcategory = 1 
              ORDER BY category_name";
              
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute query: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    $subcategories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $subcategories[] = [
            'category_id' => intval($row['category_id']),
            'category_name' => htmlspecialchars($row['category_name']),
            'category_details' => $row['category_details']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $subcategories]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}