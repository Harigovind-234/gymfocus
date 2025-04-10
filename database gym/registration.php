<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Premium Fitness Club</title>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
      rel="stylesheet"
        href="style.css"
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
  margin-bottom: 2rem;
}

.logo-container img {
  width: 200px;
  filter: drop-shadow(0 0 10px rgba(0, 0, 0, 0.5));
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

    </style>
  </head>
  <body>
  <div class="main-banner">
  <video autoplay muted loop id="bg-video">
    <source src="./assets/images/gym-video.mp4" type="video/mp4" />
  </video>

  <div class="video-overlay">
    <div class="logo-container">
      <img src="focusgymlogo.png" alt="Gym Logo" />
    </div>
    <div class="form-container animate__animated animate__fadeIn">
      <h3>Register</h3>
      <?php
      $error_msg = "";
      $fullname = $email = $password = $confirm_password = "";

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
          $fullname = htmlspecialchars(trim($_POST['fullname']));
          $email = htmlspecialchars(trim($_POST['email']));
          $password = htmlspecialchars(trim($_POST['password']));
          $confirm_password = htmlspecialchars(trim($_POST['confirm_password']));

          if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
              $error_msg = "All fields are required.";
          } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
              $error_msg = "Invalid email address.";
          } elseif ($password !== $confirm_password) {
              $error_msg = "Passwords do not match.";
          } elseif (strlen($password) < 6) {
              $error_msg = "Password must be at least 6 characters long.";
          } else {
              // Process registration (e.g., save to the database)
              // Redirect or display success message (not included here for simplicity)
              $error_msg = "Registration successful!";
          }
      }
      ?>

      <?php if (!empty($error_msg)): ?>
      <div class="error-message"> <?php echo $error_msg; ?> </div>
      <?php endif; ?>

      <form action="" method="POST">
        <input
          type="text"
          name="fullname"
          placeholder="Full Name"
          value="<?php echo $fullname; ?>"
          required
        />
        <input
          type="email"
          name="email"
          placeholder="Email Address"
          value="<?php echo $email; ?>"
          required
        />
        <input
          type="password"
          name="password"
          placeholder="Password"
          required
        />
        <input
          type="password"
          name="confirm_password"
          placeholder="Confirm Password"
          required
        />
        <button type="submit">Register</button>
      </form>

      <a href="http://localhost/miniproject2/training-studio-1.0.0/login.php" class="toggle-link">
        Already have an account? Login here
      </a>
    </div>
  </div>
</div>
