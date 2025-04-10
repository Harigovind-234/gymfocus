<?php
include 'connect.php';

// Check table structure
$sql = "DESCRIBE register";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "<h3>Table Structure:</h3>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Field: " . $row['Field'] . 
             ", Type: " . $row['Type'] . 
             ", Null: " . $row['Null'] . 
             ", Key: " . $row['Key'] . 
             ", Default: " . $row['Default'] . 
             "<br>";
    }
} else {
    echo "Error checking table structure: " . mysqli_error($conn);
}

// Check existing tokens
$sql = "SELECT email, reset_token, reset_token_expiry FROM Register WHERE reset_token IS NOT NULL";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "<h3>Existing Tokens:</h3>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Email: " . htmlspecialchars($row['email']) . 
             ", Token: " . htmlspecialchars($row['reset_token']) . 
             ", Expiry: " . $row['reset_token_expiry'] . 
             "<br>";
    }
} else {
    echo "Error checking tokens: " . mysqli_error($conn);
}
?> 