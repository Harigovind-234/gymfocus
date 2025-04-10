<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainer_id = $_SESSION['user_id'];
    $plan_name = $_POST['plan_name'];
    $description = $_POST['description'];
    $duration_weeks = intval($_POST['duration_weeks']);

    $sql = "INSERT INTO WorkoutPlans (trainer_id, Wplan_name, description, duration_weeks) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issi", $trainer_id, $plan_name, $description, $duration_weeks);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Workout plan created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating workout plan']);
    }
} 