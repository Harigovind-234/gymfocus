<?php
session_start();
include "connect.php";

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$session = $data['session'] ?? '';
$day = $data['day'] ?? '';

if (empty($session) || empty($day)) {
    die(json_encode(['success' => false, 'message' => 'Invalid parameters']));
}

// Delete the workout
$stmt = $conn->prepare("DELETE FROM workout_schedule WHERE session_time = ? AND day = ?");
$stmt->bind_param("ss", $session, $day);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting workout']);
}

$stmt->close();
$conn->close();
?> 