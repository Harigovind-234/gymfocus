<?php
session_start(); // Added session_start at the very beginning
include 'connect.php';

// Initialize variables
$errors = array();
$fields = array(
    'fullname' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => '',
    'address' => '',
    'mobile' => '',
    'gender' => '',
    'dob' => '',
    'preferred_session' => ''
);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize all inputs
    $fields = [
        'fullname' => trim($_POST['fullname'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'mobile' => trim($_POST['mobile'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'dob' => trim($_POST['dob'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'preferred_session' => trim($_POST['preferred_session'] ?? '')
    ];

    // Validation
    if (empty($fields['fullname'])) $errors['fullname'] = "Full name is required";
    if (empty($fields['email'])) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    if (empty($fields['password'])) {
        $errors['password'] = "Password is required";
    } elseif (strlen($fields['password']) < 6) {
        $errors['password'] = "Password must be at least 6 characters";
    }
    if ($fields['password'] !== $fields['confirm_password']) {
        $errors['confirm_password'] = "Passwords do not match";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // First insert into register table
            $register_sql = "INSERT INTO register (
                full_name, 
                mobile_no, 
                gender, 
                dob, 
                address,
                preferred_session, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending')";

            $register_stmt = $conn->prepare($register_sql);
            
            if (!$register_stmt) {
                throw new Exception("Prepare failed for register table: " . $conn->error);
            }

            $register_stmt->bind_param("ssssss", 
                $fields['fullname'],
                $fields['mobile'],
                $fields['gender'],
                $fields['dob'],
                $fields['address'],
                $fields['preferred_session']
            );

            if (!$register_stmt->execute()) {
                throw new Exception("Execute failed for register table: " . $register_stmt->error);
            }

            // Get the inserted user_id
            $user_id = $conn->insert_id;

            // Hash the password
            $hashed_password = password_hash($fields['password'], PASSWORD_DEFAULT);

            // Then insert into login table
            $login_sql = "INSERT INTO login (
                user_id,
                username,
                email,
                password,
                role,
                status
            ) VALUES (?, ?, ?, ?, 'member', 'active')";

            $login_stmt = $conn->prepare($login_sql);
            
            if (!$login_stmt) {
                throw new Exception("Prepare failed for login table: " . $conn->error);
            }

            $login_stmt->bind_param("isss", 
                $user_id,
                $fields['fullname'], // Using fullname as username
                $fields['email'],
                $hashed_password
            );

            if (!$login_stmt->execute()) {
                throw new Exception("Execute failed for login table: " . $login_stmt->error);
            }

            // If everything is successful, commit the transaction
            $conn->commit();

            $_SESSION['user_id'] = $user_id; // Set user_id in session
            $_SESSION['email'] = $fields['email'];
            $_SESSION['role'] = 'member';
            $_SESSION['full_name'] = $fields['fullname'];
            header("Location: payment_membership.php"); // Redirect to payment page
            exit();

        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $conn->rollback();
            $errors['general'] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Premium Fitness Club</title>
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
      rel="stylesheet"
    />
    <style>
      /* Previous styles remain the same */
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
        display: block;
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
        padding: 1.2rem;
        border-radius: 10px;
        width: 100%;
        max-width: 400px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        color: #000;
        margin: 15px auto;
        position: relative;
        z-index: 1;
        overflow: hidden;
      }

      .form-container h3 {
        margin-bottom: 0.8rem;
        font-size: 1.4rem;
      }

      .form-container input,
      .form-container select {
        width: 100%;
        padding: 8px;
        margin: 2px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 0.9rem;
        box-sizing: border-box;
      }

      .form-container button {
        margin-top: 6px;
        padding: 8px;
        background: #ff5722;
        border: none;
        border-radius: 5px;
        color: #fff;
        font-size: 0.9rem;
        cursor: pointer;
        transition: background 0.3s;
      }

      .form-container button:hover {
        background: #e64a19;
      }

      .form-container .toggle-link {
        display: block;
        margin: 8px auto;
        font-size: 0.8rem;
        color: #007bff;
        text-decoration: none;
      }

      .form-container .toggle-link:hover {
        text-decoration: underline;
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

      /* New styles for field-specific error messages */
      .form-group {
        margin-bottom: 8px;
        text-align: left;
        width: 100%;
      }

      .error-text {
        color: #f44336;
        font-size: 0.7rem;
        margin-top: 1px;
        display: block;
        text-align: left;
        min-height: 0.7em;
      }

      input.error, select.error {
        border-color: #f44336;
      }

      .logo-container {
        position: absolute;
        width: 100%;
        text-align: center;
        z-index: 2;
      }

      .logo-container img {
        width: 200px; /* Increase size */
        max-width: 100%;
        filter: drop-shadow(0 0 15px rgba(0, 0, 0, 0.7));
      }

      /* Add a container for links */
      .links-container {
        margin-top: 10px;
        padding-top: 5px;
        border-top: 1px solid #eee;  /* Optional: adds a subtle separator */
      }

      /* Add these styles for the session field */
      select[name="preferred_session"] {
        width: 100%;
        padding: 8px;
        margin: 2px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 0.9rem;
        background-color: white;
        color: #333;
      }

      select[name="preferred_session"]:focus {
        outline: none;
        border-color: #ff5722;
      }

      select[name="preferred_session"].error {
        border-color: #f44336;
      }

      .session-icon {
        margin-right: 8px;
      }
    </style>
  </head>
  <body>
    <div class="main-banner">
  <video autoplay muted loop id="bg-video">
    <source src="./assets/images/gym-video.mp4" type="video/mp4" />
  </video>
  <div class="video-overlay">
    
    <div class="form-container animate_animated animate_fadeIn">
      <h3>Register</h3>
      <?php
          include 'connect.php';
          $errors = array();
          $fields = array('fullname' => '', 'email' => '', 'password' => '', 'confirm_password' => '', 
                         'address' => '', 'mobile' => '', 'gender' => '', 'dob' => '');

          if ($_SERVER["REQUEST_METHOD"] == "POST") {
              foreach ($fields as $field => $value) {
                  $fields[$field] = isset($_POST[$field]) ? htmlspecialchars(trim($_POST[$field])) : '';
              }

              // Validation
              if (empty($fields['fullname'])) {
                  $errors['fullname'] = "Full name is required.";
              } elseif (!preg_match("/^[a-zA-Z\s]+$/", $fields['fullname'])) {
                  $errors['fullname'] = "Name can only contain letters and spaces.";
              }

              if (empty($fields['email'])) {
                  $errors['email'] = "Email is required.";
              } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
                  $errors['email'] = "Invalid email address.";
              }

              if (empty($fields['address'])) {
                  $errors['address'] = "Address is required.";
              }

              if (empty($fields['gender'])) {
                  $errors['gender'] = "Please select a gender.";
              }

              if (empty($fields['dob'])) {
                  $errors['dob'] = "Date of birth is required.";
              }

              if (empty($fields['mobile'])) {
                  $errors['mobile'] = "Mobile number is required.";
              } elseif (!preg_match("/^[1-9][0-9]{9}$/", $fields['mobile'])) {
                  $errors['mobile'] = "Mobile number must be 10 digits and cannot start with 0.";
              }

              if (empty($fields['password'])) {
                  $errors['password'] = "Password is required.";
              } elseif (strlen($fields['password']) < 6) {
                  $errors['password'] = "Password must be at least 6 characters long.";
              }

              if (empty($fields['confirm_password'])) {
                  $errors['confirm_password'] = "Please confirm your password.";
              } elseif ($fields['password'] !== $fields['confirm_password']) {
                  $errors['confirm_password'] = "Passwords do not match.";
              }

              if (empty($fields['preferred_session'])) {
                  $errors['preferred_session'] = "Please select your training session";
              } elseif (!in_array($fields['preferred_session'], ['morning', 'evening'])) {
                  $errors['preferred_session'] = "Invalid session selected";
              }

              if (empty($errors)) {
                $hashed_password = password_hash($fields['password'], PASSWORD_DEFAULT);
            
                // Start transaction to ensure both inserts succeed or none does
                $conn->begin_transaction();
                
                try {
                    // First insert into register table
                    $stmt1 = $conn->prepare("INSERT INTO register (full_name, address, mobile_no, gender, dob, preferred_session) 
                                            VALUES (?, ?, ?, ?, ?, ?)");
                    
                    if (!$stmt1) {
                        throw new Exception("Error in register table query: " . $conn->error);
                    }
                    
                    $stmt1->bind_param("ssssss", 
                        $fields['fullname'],
                        $fields['address'], 
                        $fields['mobile'], 
                        $fields['gender'], 
                        $fields['dob'],
                        $fields['preferred_session']
                    );
                    
                    if (!$stmt1->execute()) {
                        throw new Exception("Error inserting into register: " . $stmt1->error);
                    }
                    
                    // Get the last inserted ID from register table
                    $user_id = $conn->insert_id;
                    
                    // Modified login table insert with hardcoded enum value
                    $stmt2 = $conn->prepare("INSERT INTO login (user_id, email, password, role) VALUES (?, ?, ?, 'member')");
                    
                    if (!$stmt2) {
                        throw new Exception("Error in login table query: " . $conn->error);
                    }
                    
                    $stmt2->bind_param("iss", 
                        $user_id,
                        $fields['email'],
                        $hashed_password
                    );
                    
                    if (!$stmt2->execute()) {
                        throw new Exception("Error inserting into login: " . $stmt2->error);
                    }
                    
                    // If we get here, both inserts were successful
                    $conn->commit();
                    
                    // Set success message in session
                    $_SESSION['registration_success'] = "Account created successfully! Please login.";
                    
                    // Redirect to login page
                    $_SESSION['user_id'] = $user_id; // Set user_id in session
                    $_SESSION['email'] = $fields['email'];
                    $_SESSION['role'] = 'member';
                    $_SESSION['full_name'] = $fields['fullname'];
                    header("Location: payment_membership.php"); // Redirect to payment page
                    exit(); // Important to prevent further code execution
                    
                } catch (Exception $e) {
                    // If any error occurs, roll back the transaction
                    $conn->rollback();
                    $errors['general'] = "Registration failed: " . $e->getMessage();
                } finally {
                    // Only close statements if they were successfully created
                    if (isset($stmt1) && $stmt1 !== false) $stmt1->close();
                    if (isset($stmt2) && $stmt2 !== false) $stmt2->close();
                }
            }
            
          }
      ?>
     <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
            <div class="form-group">
              <input
                type="text"
                name="fullname"
                placeholder="Full Name"
                value="<?php echo $fields['fullname']; ?>"
                class="<?php echo isset($errors['fullname']) ? 'error' : ''; ?>"
                required
              />
              <span class="error-text"><?php echo isset($errors['fullname']) ? $errors['fullname'] : ''; ?></span>
            </div>

            <div class="form-group">
              <input
                type="email"
                name="email"
                placeholder="Email Address"
                value="<?php echo $fields['email']; ?>"
                class="<?php echo isset($errors['email']) ? 'error' : ''; ?>"
                required
              />
              <span class="error-text"><?php echo isset($errors['email']) ? $errors['email'] : ''; ?></span>
            </div>

            <div class="form-group">
              <input
                type="text"
                name="address"
                placeholder="Address"
                value="<?php echo $fields['address']; ?>"
                class="<?php echo isset($errors['address']) ? 'error' : ''; ?>"
                required
              />
              <span class="error-text"><?php echo isset($errors['address']) ? $errors['address'] : ''; ?></span>
            </div>

            <div class="form-group">
              <select name="gender" class="<?php echo isset($errors['gender']) ? 'error' : ''; ?>" required>
                <option value="" disabled <?php echo empty($fields['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                <option value="male" <?php echo ($fields['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo ($fields['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                <option value="other" <?php echo ($fields['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
              </select>
              <span class="error-text"><?php echo isset($errors['gender']) ? $errors['gender'] : ''; ?></span>
            </div>

            <div class="form-group">
              <input
                type="date"
                name="dob"
                placeholder="Date of Birth"
                value="<?php echo $fields['dob']; ?>"
                class="<?php echo isset($errors['dob']) ? 'error' : ''; ?>"
                required
              />
              <span class="error-text"><?php echo isset($errors['dob']) ? $errors['dob'] : ''; ?></span>
            </div>

            <div class="form-group">
              <input
                type="text"
                name="mobile"
                placeholder="Mobile Number (10 digits, can't start with 0)"
                value="<?php echo $fields['mobile']; ?>"
                class="<?php echo isset($errors['mobile']) ? 'error' : ''; ?>"
                required
              />
              <span class="error-text"><?php echo isset($errors['mobile']) ? $errors['mobile'] : ''; ?></span>
            </div>

            <div class="form-group">
              <input
                type="password"
                name="password"
                placeholder="Password"
                class="<?php echo isset($errors['password']) ? 'error' : ''; ?>"
                required
              />
              <span class="error-text"><?php echo isset($errors['password']) ? $errors['password'] : ''; ?></span>
            </div>

            <div class="form-group">
              <input
                type="password"
                name="confirm_password"
                placeholder="Confirm Password"
                class="<?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>"
                required
              />
              <span class="error-text"><?php echo isset($errors['confirm_password']) ? $errors['confirm_password'] : ''; ?></span>
            </div>

            <div class="form-group">
              <select name="preferred_session" required>
                <option value="" selected disabled>Select Training Session</option>
                <option value="morning">Morning Session (6:00 AM - 8:00 AM)</option>
                <option value="evening">Evening Session (5:00 PM - 7:00 PM)</option>
              </select>
              <span class="error-text"></span>
            </div>

            <?php if (isset($errors['general'])): ?>
              <div class="error-text" style="text-align: center; margin-bottom: 10px;">
                <?php echo $errors['general']; ?>
              </div>
            <?php endif; ?>

            <button type="submit">Register</button>
          </form>

      <div class="links-container">
          <a href="login2.php" class="toggle-link">Already have an account? Login here</a>
          <a href="forgot-password.php" class="toggle-link">Forgot Password?</a>
          <a href="enhanced-gym-landing.php" class="toggle-link">Back to Website</a>
          <a href="staff_register.php" class="toggle-link">staff registration</a>

      </div>
    </div>
  </div>
</div>
</body>
<script>
  // Add this script right before the closing body tag
const validationRules = {
    fullname: {
        pattern: /^[a-zA-Z\s]+$/,
        minLength: 2,
        message: {
            required: "Full name is required.",
            pattern: "Name can only contain letters and spaces.",
            minLength: "Name must be at least 2 characters long."
        }
    },
    email: {
        pattern:/^[^\s][a-zA-Z0-9._%+-]+@[a-zA-Z-]+(\.[a-zA-Z]{2,})+$/,
        message: {
            required: "Email is required.",
            pattern: "Invalid email address."
        }
    },
    address: {
        minLength: 5,
        message: {
            required: "Address is required.",
            minLength: "Address must be at least 5 characters long."
        }
    },
    gender: {
        message: {
            required: "Please select a gender."
        }
    },
    dob: {
        validate: function(value) {
            const dob = new Date(value);
            const today = new Date();
            
            // Check if date is valid
            if (isNaN(dob.getTime())) {
                return false;
            }
            
            // Calculate age
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            // Check if age is between 18 and 65
            return age >= 18 && age <= 65;
        },
        message: {
            required: "Date of birth is required.",
            validate: "Age must be between 18 and 65 years."
        }
    },
    mobile: {
    pattern: /^[6-9]\d{9}$/, // Starts with 6,7,8,9 and exactly 10 digits
    message: {
        required: "Mobile number is required.",
        pattern: "Mobile number must be exactly 10 digits and start with 6, 7, 8, or 9."
    }
},
    password: {
        minLength: 6,
        message: {
            required: "Password is required.",
            minLength: "Password must be at least 6 characters long."
        }
    },
    confirm_password: {
        message: {
            required: "Please confirm your password.",
            match: "Passwords do not match."
        }
    },
    preferred_session: {
        validate: function(value) {
            return ['morning', 'evening'].includes(value);
        },
        message: {
            required: "Please select your training session",
            validate: "Please select a valid training session"
        }
    }
};

function validateField(input) {
    const field = input.name;
    const value = input.value.trim();
    const rules = validationRules[field];
    const errorSpan = input.nextElementSibling;
    
    // Skip if no validation rules for this field
    if (!rules) return true;
    
    // Remove existing error class
    input.classList.remove('error');
    
    // Required check
    if (!value) {
        input.classList.add('error');
        errorSpan.textContent = rules.message.required;
        return false;
    }

    // Special validation for DOB
    if (field === 'dob') {
        if (!rules.validate(value)) {
            input.classList.add('error');
            errorSpan.textContent = rules.message.validate;
            return false;
        }
    }
    
    // Pattern check
    if (rules.pattern && !rules.pattern.test(value)) {
        input.classList.add('error');
        errorSpan.textContent = rules.message.pattern;
        return false;
    }
    
    // Minimum length check
    if (rules.minLength && value.length < rules.minLength) {
        input.classList.add('error');
        errorSpan.textContent = rules.message.minLength;
        return false;
    }
    
    // Password match check
    if (field === 'confirm_password') {
        const password = document.querySelector('input[name="password"]').value;
        if (value !== password) {
            input.classList.add('error');
            errorSpan.textContent = rules.message.match;
            return false;
        }
    }
    
    // Clear error message if validation passes
    errorSpan.textContent = '';
    return true;
}

// Add event listeners to all form inputs
document.querySelectorAll('.form-group input, .form-group select').forEach(input => {
    ['input', 'blur', 'change'].forEach(eventType => {
        input.addEventListener(eventType, () => {
            validateField(input);
            
            // Special case for confirm password
            if (input.name === 'password') {
                const confirmPassword = document.querySelector('input[name="confirm_password"]');
                if (confirmPassword.value !== '') {
                    validateField(confirmPassword);
                }
            }
        });
    });
});

// Form submit validation
document.querySelector('form').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Validate all fields
    this.querySelectorAll('.form-group input, .form-group select').forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const registerContainer = document.getElementById("register-container");

    // registerContainer.addEventListener("scroll", function () {
    //     if (registerContainer.scrollTop + registerContainer.clientHeight >= registerContainer.scrollHeight) {
    //         console.log("Scrolled to the bottom!");
    //     }
    // });
});

// Add specific event listener for DOB field
document.querySelector('input[name="dob"]').addEventListener('change', function() {
    validateField(this);
});

// Optional: Add max date restriction to prevent future dates
document.querySelector('input[name="dob"]').max = new Date().toISOString().split('T')[0];
</script>
</html>