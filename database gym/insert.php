<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $position = $_POST['position'];

    $sql = "INSERT INTO employees (name, email, position) VALUES ('$name', '$email', '$position')";

    if (mysqli_query($conn, $sql)) {
        // Redirect to view.php after successful insertion
        header("Location: view.php");
        //header("Location: view1.php");
        exit; // Make sure to call exit after header to stop the script
    } else {
        echo "Error: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Add Employee</h2>
    <form method="POST">
        Name: <input type="text" name="name" required><br>
        Email: <input type="email" name="email" required><br>
        Position: <input type="text" name="position" required><br>
        <button type="submit">Add Employee</button>
    </form>
</body>
</html>

