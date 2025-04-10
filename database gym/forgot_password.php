<?php
session_start();
include 'connect.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$error_msg = '';
$success_msg = '';

if($_SERVER['REQUEST_METHOD'] == "POST") {
    try {
        // Sanitize email input
        
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Prevent SQL injection using prepared statement
        $sql = "SELECT * FROM login WHERE email=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0) {
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_time'] = time();
            
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Debug: Print token details
            // echo "Generated Token: " . htmlspecialchars($token) . "<br>";
            // echo "Expiry Time: " . $expiry . "<br>";
            
            // Store token in database
            $update_sql = "UPDATE login SET reset_token=?, reset_token_expiry=? WHERE email=?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "sss", $token, $expiry, $email);
            $update_result = mysqli_stmt_execute($update_stmt);
            
            // Debug: Check if update was successful
            if($update_result) {
                // echo "Token stored successfully<br>";
            } else {
                // echo "Error storing token: " . mysqli_error($conn) . "<br>";
            }
            
            $mail = new PHPMailer(true);
            
            // SMTP configuration
            $mail->isSMTP();
            $mail->SMTPDebug = 0;                      // Turn off debug output
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'harigovindd0@gmail.com';  // Keep this email
            $mail->Password = 'htsk sgbq myvt lnpr';     // Your working App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Email settings
            $mail->setFrom('harigovindd0@gmail.com', 'Focus Gym');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - Focus Gym';
            
            // Updated email template with better styling
            $reset_link = "http://localhost/miniproject2/database%20gym/reset_password.php?token=" . $token;
            
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .button {
                            background: linear-gradient(45deg, #ff5722, #ff9800);
                            color: white;
                            padding: 12px 25px;
                            text-decoration: none;
                            border-radius: 5px;
                            display: inline-block;
                            margin: 20px 0;
                            font-weight: bold;
                        }
                        .footer { font-size: 12px; color: #666; margin-top: 30px; }
                        .logo { margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h2 style='color: #ff5722;'>Password Reset Request</h2>
                        <p>Hello,</p>
                        <p>We received a request to reset your password for your Focus Gym account.</p>
                        <p>Click the button below to reset your password. This link will expire in 24 hours.</p>
                        
                        <a href='{$reset_link}' class='button'>Reset Password</a>
                        
                        <p style='font-size: 13px;'>If the button doesn't work, copy and paste this link in your browser:</p>
                        <p style='font-size: 13px; color: #666;'>{$reset_link}</p>
                        
                        <div class='footer'>
                            <p>If you didn't request this password reset, please ignore this email.</p>
                            <p>For security reasons, this link will expire in 24 hours.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            if($mail->send()) {
                $success_msg = "Password reset instructions have been sent to your email";
            } else {
                throw new Exception("Failed to send email. Error: " . $mail->ErrorInfo);
            }
        } else {
            throw new Exception("Email not found in our records");
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
    <title>Forgot Password - Premium Fitness Club</title>
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
            background-image: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('assets/images/gym-background.jpg');
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

        .form-container input {
            width: 100%;
            padding: 14px;
            margin: 12px 0;
            border: 2px solid rgba(255, 87, 34, 0.3);
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-container input:focus {
            outline: none;
            border-color: #ff5722;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(255, 87, 34, 0.3);
        }

        .form-container button {
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
            box-shadow: 0 4px 15px rgba(255, 87, 34, 0.3);
        }

        .form-container button:hover {
            background: linear-gradient(45deg, #ff9800, #ff5722);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 87, 34, 0.4);
        }

        .error-message {
            background: linear-gradient(45deg, rgba(244, 67, 54, 0.9), rgba(255, 87, 34, 0.9));
            color: #fff;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            border: 1px solid rgba(244, 67, 54, 0.3);
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.2);
        }

        .success-message {
            background: linear-gradient(45deg, rgba(76, 175, 80, 0.9), rgba(139, 195, 74, 0.9));
            color: #fff;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            border: 1px solid rgba(76, 175, 80, 0.3);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }

        .toggle-link {
            display: block;
            margin-top: 20px;
            background: linear-gradient(45deg, #ff5722, #ff9800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .toggle-link:hover {
            transform: translateY(-1px);
            text-shadow: 0 2px 10px rgba(255, 87, 34, 0.3);
        }

        ::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body>
<div class="form-container">
            <h3>Forgot Password</h3>
            <?php if($error_msg): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            <?php if($success_msg): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            
            <form action="forgot_password.php" method="POST" onsubmit="return validateForm()">
                <input type="email" 
                       name="email" 
                       id="email"
                       placeholder="Enter your email address" 
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       title="Please enter a valid email address"
                       required />
                <button type="submit" id="reset-password-btn">Reset Password</button>
            </form>
            
            <a href="login2.php" class="toggle-link">Back to Login</a>
        </div>
        </body>
</html>

        <script>
        function validateForm() {
            const email = document.getElementById('email').value;
            const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
            
            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }
            return true;
        }
        </script>
