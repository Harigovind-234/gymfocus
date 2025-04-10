<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['full_name'] ?? '';
$mobile_no = $_POST['mobile_no'] ?? '';
$gender = $_POST['gender'] ?? '';
$address = $_POST['address'] ?? '';
$qualifications = $_POST['qualifications'] ?? '';

$query = "UPDATE register SET 
          full_name = ?,
          mobile_no = ?,
          gender = ?,
          address = ?,
          qualifications = ?
          WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssssi", 
    $full_name, $mobile_no, $gender, $address, $qualifications, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => mysqli_error($conn)
    ]);
} 