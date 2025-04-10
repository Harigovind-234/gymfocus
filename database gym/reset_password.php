<?php
session_start();
include 'connect.php';

$error_msg = '';
$success_msg = '';
$valid_token = false;
$email = '';

// Get token from either GET or POST
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : null);

// Debug: Print the token
if($token) {
    // echo "Token: " . htmlspecialchars($token) . "<br>";
    
    // Check if token exists and is not expired
    $sql = "SELECT email, reset_token, reset_token_expiry FROM login WHERE reset_token = ? AND reset_token_expiry > NOW()";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Debug: Print query results
    if($result) {
        // echo "Query executed successfully<br>";
        // echo "Number of rows found: " . mysqli_num_rows($result) . "<br>";
        
        if($row = mysqli_fetch_assoc($result)) {
            // echo "Token from DB: " . htmlspecialchars($row['reset_token']) . "<br>";
            // echo "Token Expiry: " . $row['reset_token_expiry'] . "<br>";
            // echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";
            
            $valid_token = true;
            $email = $row['email'];
        } else {
            // echo "No matching row found<br>";
            $error_msg = "Invalid or expired reset link. Please try again.";
        }
    } else {
        // echo "Query failed: " . mysqli_error($conn) . "<br>";
        $error_msg = "Database error occurred";
    }
} else {
    // echo "No token provided<br>";
    $error_msg = "Invalid reset link. Please try again.";
}

if($_SERVER['REQUEST_METHOD'] == "POST" && $valid_token) {
    try {
        // Validate passwords
        if(empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
            throw new Exception("Both password fields are required");
        }

        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Check password match
        if($new_password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        // Validate password strength
        if(strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        // Hash password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password and clear reset token
        $update_sql = "UPDATE login SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE email = ? AND reset_token = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        
        if(!$update_stmt) {
            throw new Exception("Database error occurred");
        }

        mysqli_stmt_bind_param($update_stmt, "sss", $hashed_password, $email, $token);
        
        if(mysqli_stmt_execute($update_stmt)) {
            if(mysqli_affected_rows($conn) > 0) {
                $success_msg = "Password updated successfully! Redirecting to login...";
                // Redirect after 2 seconds
                header("refresh:2;url=login2.php");
            } else {
                throw new Exception("Failed to update password");
            }
        } else {
            throw new Exception("Error executing update");
        }

    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Premium Fitness Club</title>
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #000;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('assets/images/gym-background.jpg');
            background-size: cover;
            background-position: center;
        }

        .form-container {
            background: linear-gradient(145deg, rgba(35, 35, 35, 0.95), rgba(25, 25, 25, 0.95));
            padding: 2.5rem;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            color: #fff;
            margin: 50px auto;
            border: 1px solid rgba(255, 87, 34, 0.2);
            backdrop-filter: blur(10px);
        }

        .form-container h3 {
            background: linear-gradient(45deg, #ff5722, #ff9800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .password-container {
            position: relative;
            width: 100%;
            margin: 10px 0;
        }

        .form-container input {
            width: 100%;
            padding: 14px;
            padding-right: 40px;
            border: 2px solid rgba(255, 87, 34, 0.3);
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-container input:focus {
            outline: none;
            border-color: #ff5722;
            box-shadow: 0 0 15px rgba(255, 87, 34, 0.3);
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #ff5722;
            padding: 5px;
            z-index: 10;
        }

        .toggle-password:hover {
            color: #ff9800;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(45deg, #ff5722, #ff9800);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            background: linear-gradient(45deg, #ff9800, #ff5722);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 87, 34, 0.4);
        }

        .error-message, .success-message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            animation: fadeIn 0.5s ease;
        }

        .error-message {
            background: linear-gradient(45deg, rgba(244, 67, 54, 0.9), rgba(255, 87, 34, 0.9));
        }

        .success-message {
            background: linear-gradient(45deg, rgba(76, 175, 80, 0.9), rgba(139, 195, 74, 0.9));
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .validation-message {
            font-size: 0.8rem;
            margin-top: 5px;
            text-align: left;
            min-height: 20px;
        }

        .validation-message.error {
            color: #ff4444;
        }

        .validation-message.success {
            color: #00C851;
        }

        .password-container {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h3>Reset Password</h3>
    <?php if($error_msg): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>
    <?php if($success_msg): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    
    <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" onsubmit="return validateForm()">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <div class="password-container">
            <input type="password" 
                   name="new_password" 
                   id="new_password"
                   placeholder="Enter new password" 
                   required 
                   minlength="8"
                   oninput="validatePasswordStrength(this)" />
            <i class="toggle-password fas fa-eye" onclick="togglePassword('new_password')"></i>
            <div id="password-strength" class="validation-message"></div>
        </div>
        
        <div class="password-container">
            <input type="password" 
                   name="confirm_password" 
                   id="confirm_password"
                   placeholder="Confirm new password" 
                   required 
                   oninput="validatePasswordMatch()" />
            <i class="toggle-password fas fa-eye" onclick="togglePassword('confirm_password')"></i>
            <div id="password-match" class="validation-message"></div>
        </div>
        
        <button type="submit" id="reset-password-btn">Update Password</button>
    </form>
</div>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- Add JavaScript for password toggle -->
<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function validatePasswordStrength(input) {
    const password = input.value;
    const strengthDiv = document.getElementById('password-strength');
    
    // Reset classes
    strengthDiv.classList.remove('error', 'success');
    
    if (password.length === 0) {
        strengthDiv.textContent = '';
        return false;
    }
    
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const isLongEnough = password.length >= 8;
    
    if (!isLongEnough || !hasLetter || !hasNumber) {
        strengthDiv.textContent = 'Password must be at least 8 characters long and include both letters and numbers';
        strengthDiv.classList.add('error');
        return false;
    }
    
    strengthDiv.textContent = 'Password strength: Good';
    strengthDiv.classList.add('success');
    return true;
}

function validatePasswordMatch() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('password-match');
    
    // Reset classes
    matchDiv.classList.remove('error', 'success');
    
    if (confirmPassword.length === 0) {
        matchDiv.textContent = '';
        return false;
    }
    
    if (password !== confirmPassword) {
        matchDiv.textContent = 'Passwords do not match';
        matchDiv.classList.add('error');
        return false;
    }
    
    matchDiv.textContent = 'Passwords match';
    matchDiv.classList.add('success');
    return true;
}

function validateForm() {
    const isPasswordValid = validatePasswordStrength(document.getElementById('new_password'));
    const isPasswordMatch = validatePasswordMatch();
    
    if (!isPasswordValid) {
        alert('Please ensure your password meets the requirements');
        return false;
    }
    
    if (!isPasswordMatch) {
        alert('Passwords do not match');
        return false;
    }
    
    return true;
}
</script>
</body>
</html>