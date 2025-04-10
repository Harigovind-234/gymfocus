<?php
session_start();
include "connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("UPDATE workout_schedule 
                           SET description = ?, intensity = ? 
                           WHERE session_time = ? AND day = ?");
    
    $stmt->bind_param("ssss", 
        $data['description'], 
        $data['intensity'], 
        $data['session'], 
        $data['day']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to update workout");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 