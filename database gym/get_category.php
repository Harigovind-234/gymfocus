<?php
include 'connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if category_id is provided
if (!isset($_GET['category_id']) || empty($_GET['category_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Category ID is required'
    ]);
    exit;
}

$category_id = intval($_GET['category_id']);

// Prepare and execute query
$query = "SELECT c.*, p.category_name as parent_name 
          FROM categories c 
          LEFT JOIN categories p ON c.parent_id = p.category_id 
          WHERE c.category_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $category = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'data' => $category
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Category not found'
    ]);
}

$stmt->close();
$conn->close();
?> 