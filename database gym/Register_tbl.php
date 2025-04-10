<?php
include 'connect.php';


$database = "focus_gym";

// Select the database
mysqli_select_db($conn, $database);
$sql = "CREATE TABLE  IF NOT EXISTS Register (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    address VARCHAR(500) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    dob DATE NOT NULL ,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";


if (mysqli_query($conn, $sql)) {
    echo "Table created successfully";
} else {
   echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>

