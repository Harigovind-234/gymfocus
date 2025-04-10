<?php
session_start();
require_once 'connect.php';

// Check if user is admin
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header('Location: login2.php');
//     exit();
// }

if (isset($_POST['make_staff'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);

    // Update user role to staff
    $update_query = "UPDATE login SET role = 'staff' WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $update_query);

    if ($result) {
        // Send email notification
        $to = $email;
        $subject = "Staff Role Assignment - Focus Gym";
        $message = "
        <html>
        <head>
            <title>Staff Role Assignment</title>
        </head>
        <body>
            <h2>Welcome to Focus Gym Staff!</h2>
            <p>Dear $name,</p>
            <p>Congratulations! You have been assigned as a staff member at Focus Gym.</p>
            <p>Please fill out your qualification details using the link below:</p>
            <p><a href='http://yourdomain.com/staff_qualification.php?email=" . urlencode($email) . "'>
                Complete Your Staff Profile
            </a></p>
            <p>Thank you for joining our team!</p>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: Focus Gym <noreply@focusgym.com>' . "\r\n";

        mail($to, $subject, $message, $headers);

        // Redirect back to members page with success message
        $_SESSION['message'] = "Successfully updated role to staff and sent notification email.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating role: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
    }

    header('Location: members.php');
    exit();
}

// Add message display in members.php
if (isset($_SESSION['message'])) {
    echo "<div class='alert alert-" . $_SESSION['message_type'] . "'>" . 
         $_SESSION['message'] . 
         "</div>";
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!-- Add this CSS to members.php -->
<style>
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-error {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.staff-btn {
    background-color: #232d39;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.staff-btn:hover {
    background-color: #ed563b;
}

.action-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

.action-buttons form {
    margin: 0;
}
</style> 