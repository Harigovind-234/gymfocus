<?php
include 'connect.php';

$database = "focus_gym_online";
$sql = "CREATE DATABASE $database";

if (mysqli_query($conn, $sql)) {
    echo "Database created successfully";
} else {
    echo "Error creating database: " . mysqli_error($conn);
}
$conn->select_db('focus_gym_online');
mysqli_close($conn);
?>

