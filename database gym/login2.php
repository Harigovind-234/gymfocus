<?php
session_start();
include 'connect.php';

$database = "focus_gym";

if($_SERVER['REQUEST_METHOD']=="POST"){
  $email = $_POST['email'];
  $password = $_POST['password'];
  
  // Use prepared statement to prevent SQL injection
  $sql = "SELECT l.*, r.full_name, r.address, r.mobile_no, r.gender, r.created_at, r.user_id, l.status
          FROM login l 
          JOIN register r ON l.user_id = r.user_id 
          WHERE l.email=?";
          
  $stmt = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($stmt, "s", $email);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if(mysqli_num_rows($result) > 0){
    $row = mysqli_fetch_assoc($result);
    
    if(password_verify($password, $row["password"])){
      if ($row['status'] === 'blocked') {
        $error = "Your account has been blocked. Please contact the administrator.";
      } else {
        // Start session and store user data
        session_start();
        $_SESSION['email'] = $row['email'];
        $_SESSION['name'] = $row['full_name'];
        $_SESSION['address'] = $row['address'];
        $_SESSION['mobile'] = $row['mobile_no'];
        $_SESSION['gender'] = $row['gender'];
        $_SESSION['date'] = $row['created_at'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['user_id'] = $row['user_id'];
        // Redirect based on role - use strtolower() for case-insensitive comparison
        $role = strtolower($row['role']);
        
        // Check if there's a redirect URL stored in session
        if (isset($_SESSION['redirect_url'])) {
          $redirect = $_SESSION['redirect_url'];
          unset($_SESSION['redirect_url']); // Clear the stored URL
          header("Location: " . $redirect);
          exit();
        }
        
        if($role === 'member')
        {
          header('Location: index.php');
          exit();
        } 
        elseif($role === 'admin') 
        {
          header('Location: admin.php');
          exit();
        } 
        elseif($role === 'staff')  
        {
          header('Location: staff.php');
          exit();
        }
      }
    } 
    else 
    {
      $_SESSION['error_message'] = "Invalid password!";
    }
  }
  else 
  {
    $_SESSION['error_message'] = "Email not found!";
  }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Premium Fitness Club</title>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
      rel="stylesheet"
    />
    <style>
      body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #000;
        color: #fff;
        overflow: hidden;
      }

      .main-banner {
        position: relative;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      #bg-video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1;
      }

      .video-overlay {
        position: absolute;
        background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.85));
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .form-container {
        background: rgba(255, 255, 255, 0.9);
        padding: 2rem;
        border-radius: 10px;
        width: 100%;
        max-width: 400px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        color: #000;
      }

      .form-container h3 {
        margin-bottom: 1.5rem;
        font-size: 1.8rem;
        color: #333;
      }

      .form-container input {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
      }

      .form-container button {
        width: 100%;
        padding: 10px;
        background: #ff5722;
        border: none;
        border-radius: 5px;
        color: #555;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.3s;
      }

      .form-container button:hover {
        background: #e64a19;
      }

      .form-container .toggle-link {
        display: block;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #007bff;
        text-decoration: none;
      }

      .form-container .toggle-link:hover {
        text-decoration: underline;
      }

      .error-message {
        background: #f44336;
        color: #fff;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
      }

      .logo-container {
        position: absolute;
        top: 20px;
        width: 100%;
        text-align: center;
        z-index: 1;
      }

      .logo-container img {
        width: 150px;
        max-width: 90%;
        filter: drop-shadow(0 0 10px rgba(0, 0, 0, 0.5));
      }

      .success-message {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border-radius: 10px;
        padding: 0;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        position: relative;
        display: flex;
        align-items: stretch;
      }

      .success-icon {
        background: rgba(255, 255, 255, 0.2);
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .success-icon i {
        font-size: 30px;
        color: white;
      }

      .success-content {
        padding: 15px;
        flex-grow: 1;
      }

      .success-content h4 {
        margin: 0 0 5px 0;
        font-size: 18px;
        font-weight: 600;
      }

      .success-content p {
        margin: 0;
        font-size: 14px;
        opacity: 0.9;
      }

      .close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        color: white;
        opacity: 0.7;
        cursor: pointer;
        transition: opacity 0.2s;
      }

      .close-btn:hover {
        opacity: 1;
      }
    </style>
  </head>
  <body>
    <div class="main-banner">
      <video autoplay muted loop id="bg-video">
        <source src="assets/images/gym-video.mp4" type="video/mp4" />
      </video>

      <div class="video-overlay">
        <div class="logo-container">
          <img src="focusgymlogo.png" alt="Gym Logo" />
        </div>
        <div class="form-container animate__animated animate__fadeIn">
          <h3>Login</h3>
          <?php if (isset($_SESSION['error_message'])): ?>
          <div class="error-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
          <?php endif; ?>

          <?php if(isset($_SESSION['payment_success'])): ?>
              <div class="success-message animate__animated animate__fadeInUp">
                  <div class="success-icon">
                      <i class="fas fa-check-circle"></i>
                  </div>
                  <div class="success-content">
                      <h4>Payment Successful!</h4>
                      <p><?php echo $_SESSION['payment_success']; ?></p>
                  </div>
                  <button type="button" class="close-btn" onclick="this.parentElement.style.display='none';">
                      <i class="fas fa-times"></i>
                  </button>
              </div>
              <?php unset($_SESSION['payment_success']); ?>
          <?php endif; ?>

          <form action="login2.php" method="POST">
            <input
              type="email"
              name="email"
              placeholder="Email Address"
              required
            />
            <input
              type="password"
              name="password"
              placeholder="Password"
              required
            />
            <button type="submit">Login</button>
          </form>

          <a href="register.php" class="toggle-link">
            Don't have an account? Register here
          </a>
          <a href="enhanced-gym-landing.php" class="toggle-link">
       Go to the beginning
      </a>
      <a href="forgot_password.php" class="toggle-link">
       Forgot password
      </a>
        </div>
      </div>
    </div>

    <?php if ($_SESSION['membership_status'] == 'expired'): ?>
        <div class="alert alert-warning">
            Your membership has expired. Please renew to continue accessing the gym.
            <a href="payment.php" class="btn btn-primary ml-3">Renew Membership</a>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            Next payment due: <?php echo date('d M Y', strtotime($_SESSION['next_payment_date'])); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['login_message'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['login_message'];
                unset($_SESSION['login_message']);
            ?>
        </div>
    <?php endif; ?>
  </body>
</html>
