<?php
include 'connect.php';

$sql = "ALTER TABLE register 
        ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL,
        ADD COLUMN IF NOT EXISTS reset_token_expiry DATETIME NULL";

if (mysqli_query($conn, $sql)) {
    echo "Columns added successfully";
} else {
    echo "Error adding columns: " . mysqli_error($conn);
}
?> 