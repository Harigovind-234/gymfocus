<?php
session_start();
include "connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login2.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get basic information
    $session = $_POST['session'];
    $day = $_POST['day'];
    $workout_title = $_POST['workout_title'];
    $intensity = $_POST['intensity'];
    $max_capacity = $_POST['max_capacity'];
    
    // Handle arrays
    $target_muscles = isset($_POST['target_muscles']) ? implode(',', $_POST['target_muscles']) : '';
    $equipment = isset($_POST['equipment']) ? implode(',', $_POST['equipment']) : '';
    
    // Get exercises information and ensure they're variables for bind_param
    $exercise1 = $_POST['exercises'][0] ?? '';
    $sets1 = intval($_POST['sets'][0] ?? 0);
    $reps1 = intval($_POST['reps'][0] ?? 0);
    
    $exercise2 = $_POST['exercises'][1] ?? '';
    $sets2 = intval($_POST['sets'][1] ?? 0);
    $reps2 = intval($_POST['reps'][1] ?? 0);
    
    $exercise3 = $_POST['exercises'][2] ?? '';
    $sets3 = intval($_POST['sets'][2] ?? 0);
    $reps3 = intval($_POST['reps'][2] ?? 0);
    
    // Get other parameters
    $duration = intval($_POST['duration']);
    $rest_period = intval($_POST['rest_period']);
    $notes = $_POST['notes'] ?? '';

    try {
        // Check if record exists
        $check = $conn->prepare("SELECT id FROM workout_schedule WHERE session_time = ? AND day = ?");
        $check->bind_param("ss", $session, $day);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // Update existing record
            $sql = "UPDATE workout_schedule SET 
                    workout_title = ?,
                    intensity = ?,
                    max_capacity = ?,
                    target_muscles = ?,
                    equipment = ?,
                    exercise1 = ?,
                    sets1 = ?,
                    reps1 = ?,
                    exercise2 = ?,
                    sets2 = ?,
                    reps2 = ?,
                    exercise3 = ?,
                    sets3 = ?,
                    reps3 = ?,
                    duration = ?,
                    rest_period = ?,
                    notes = ?
                    WHERE session_time = ? AND day = ?";
        } else {
            // Insert new record
            $sql = "INSERT INTO workout_schedule 
                    (workout_title, intensity, max_capacity, target_muscles, equipment,
                     exercise1, sets1, reps1, exercise2, sets2, reps2, exercise3, sets3, reps3,
                     duration, rest_period, notes, session_time, day)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("ssisssisisisissiiss",
            $workout_title,
            $intensity,
            $max_capacity,
            $target_muscles,
            $equipment,
            $exercise1,
            $sets1,
            $reps1,
            $exercise2,
            $sets2,
            $reps2,
            $exercise3,
            $sets3,
            $reps3,
            $duration,
            $rest_period,
            $notes,
            $session,
            $day
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Schedule updated successfully!";
        } else {
            throw new Exception("Error executing statement: " . $stmt->error);
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }

    header("Location: staff.php");
    exit();
}
?> 