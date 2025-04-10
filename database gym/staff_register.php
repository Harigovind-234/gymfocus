<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connect.php';

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
    'qualification' => '',
    'experience' => '',
    'specialization' => ''
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form data
    $fields = array(
        'fullname' => $_POST['fullname'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'address' => $_POST['address'] ?? '',
        'mobile' => $_POST['mobile'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'dob' => $_POST['dob'] ?? '',
        'qualification' => $_POST['qualification'] ?? '',
        'experience' => $_POST['experience'] ?? '',
        'specialization' => $_POST['specialization'] ?? ''
    );

    // Validation code starts here
    if (empty($fields['fullname'])) {
        $errors['fullname'] = "Full name is required.";
    }
    
    if (empty($fields['email'])) {
        $errors['email'] = "Email is required.";
    }
    
    if (empty($fields['password'])) {
        $errors['password'] = "Password is required.";
    }
    
    if ($fields['password'] !== $fields['confirm_password']) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (empty($fields['qualification'])) {
        $errors['qualification'] = "Qualification is required.";
    }

    if (empty($fields['experience'])) {
        $errors['experience'] = "Experience is required.";
    }

    if (empty($fields['specialization'])) {
        $errors['specialization'] = "Specialization is required.";
    }

    // Certificate validation
    if(empty($_FILES['certificates']['name'][0])) {
        $errors['certificates'] = "Please upload at least one certificate";
    } else {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        foreach($_FILES['certificates']['tmp_name'] as $key => $tmp_name) {
            $fileType = $_FILES['certificates']['type'][$key];
            $fileSize = $_FILES['certificates']['size'][$key];
            
            if(!in_array($fileType, $allowedTypes)) {
                $errors['certificates'] = "Invalid file type. Please upload PDF, JPG, or PNG files only.";
                break;
            }
            
            if($fileSize > $maxSize) {
                $errors['certificates'] = "File size too large. Maximum size is 5MB.";
                break;
            }
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($fields['password'], PASSWORD_DEFAULT);
        
        $conn->begin_transaction();
        
        try {
            $uploadDir = 'uploads/certificates/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $certificateNames = [];
            foreach($_FILES['certificates']['tmp_name'] as $key => $tmp_name) {
                $fileName = $_FILES['certificates']['name'][$key];
                $newFileName = uniqid() . '_' . $fileName;
                $destination = $uploadDir . $newFileName;
                
                if(move_uploaded_file($tmp_name, $destination)) {
                    $certificateNames[] = $newFileName;
                }
            }

            // Convert array to comma-separated string for database
            $certificatesString = implode(',', $certificateNames);

            // Insert into register table with status
            $stmt1 = $conn->prepare("INSERT INTO register (full_name, address, mobile_no, gender, dob, 
                                   qualification, experience, specialization, status, certificates) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            
            $stmt1->bind_param("sssssssss", 
                $fields['fullname'],
                $fields['address'], 
                $fields['mobile'], 
                $fields['gender'], 
                $fields['dob'],
                $fields['qualification'],
                $fields['experience'],
                $fields['specialization'],
                $certificatesString
            );
            
            if (!$stmt1->execute()) {
                throw new Exception("Error inserting into register: " . $stmt1->error);
            }
            
            $user_id = $conn->insert_id;
            
            // Insert into login table with pending staff role
            $stmt2 = $conn->prepare("INSERT INTO login (user_id, email, password, role) VALUES (?, ?, ?, 'pending_staff')");
            
            $stmt2->bind_param("iss", 
                $user_id,
                $fields['email'],
                $hashed_password
            );
            
            if (!$stmt2->execute()) {
                throw new Exception("Error inserting into login: " . $stmt2->error);
            }
            
            $conn->commit();
            
            $_SESSION['registration_success'] = "Your application has been submitted and is pending admin approval.";
            header("Location: login2.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors['general'] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!-- HTML form similar to Register.php but with additional fields -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration - FOCUS GYM</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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
            overflow-y: auto;
            max-height: 90vh;
        }

        .form-container h3 {
            margin-bottom: 0.8rem;
            font-size: 1.4rem;
        }

        .form-group {
            margin-bottom: 8px;
            text-align: left;
            width: 100%;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            margin: 2px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.9rem;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .error-text {
            color: #f44336;
            font-size: 0.7rem;
            margin-top: 1px;
            display: block;
            text-align: left;
            min-height: 0.7em;
        }

        button {
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            background: #ff5722;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #e64a19;
        }

        .links-container {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px solid #eee;
        }

        .toggle-link {
            display: block;
            margin: 8px auto;
            font-size: 0.8rem;
            color: #007bff;
            text-decoration: none;
        }

        .toggle-link:hover {
            text-decoration: underline;
        }

        input.error, select.error, textarea.error {
            border-color: #f44336;
        }

        .certificate-upload-box {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            position: relative;
            cursor: pointer;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .certificate-upload-box:hover {
            border-color: #ed563b;
            background: #fff;
        }

        .certificate-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .upload-content i {
            font-size: 2.5rem;
            color: #ed563b;
        }

        .upload-content span {
            color: #333;
            font-size: 1rem;
        }

        .upload-content small {
            color: #666;
            font-size: 0.8rem;
        }

        .file-list {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-info i {
            color: #ed563b;
        }

        .file-name {
            font-size: 0.9rem;
            color: #333;
        }

        .remove-file {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 4px;
        }

        .remove-file:hover {
            color: #c82333;
        }

        .certificate-upload-box.dragover {
            border-color: #ed563b;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="main-banner">
        <video autoplay muted loop id="bg-video">
            <source src="./assets/images/gym-video.mp4" type="video/mp4">
        </video>
        <div class="video-overlay">
            <div class="form-container animate__animated animate__fadeIn">
                <h3>Staff Registration</h3>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="text" name="fullname" placeholder="Full Name" 
                            value="<?php echo $fields['fullname']; ?>"
                            class="<?php echo isset($errors['fullname']) ? 'error' : ''; ?>" required>
                        <span class="error-text"><?php echo isset($errors['fullname']) ? $errors['fullname'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email Address" 
                            value="<?php echo $fields['email']; ?>"
                            class="<?php echo isset($errors['email']) ? 'error' : ''; ?>" required>
                        <span class="error-text"><?php echo isset($errors['email']) ? $errors['email'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <input type="text" name="mobile" placeholder="Mobile Number" 
                            value="<?php echo $fields['mobile']; ?>"
                            class="<?php echo isset($errors['mobile']) ? 'error' : ''; ?>" required>
                        <span class="error-text"><?php echo isset($errors['mobile']) ? $errors['mobile'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <textarea name="address" placeholder="Address" 
                            class="<?php echo isset($errors['address']) ? 'error' : ''; ?>" 
                            required><?php echo $fields['address']; ?></textarea>
                        <span class="error-text"><?php echo isset($errors['address']) ? $errors['address'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <select name="gender" class="<?php echo isset($errors['gender']) ? 'error' : ''; ?>" required>
                            <option value="" disabled <?php echo empty($fields['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                            <option value="male" <?php echo $fields['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $fields['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $fields['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <span class="error-text"><?php echo isset($errors['gender']) ? $errors['gender'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <input type="date" name="dob" placeholder="Date of Birth" 
                            value="<?php echo $fields['dob']; ?>"
                            class="<?php echo isset($errors['dob']) ? 'error' : ''; ?>" required>
                        <span class="error-text"><?php echo isset($errors['dob']) ? $errors['dob'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <textarea name="qualification" placeholder="Educational Qualifications" 
                            class="<?php echo isset($errors['qualification']) ? 'error' : ''; ?>" 
                            required><?php echo $fields['qualification']; ?></textarea>
                        <span class="error-text"><?php echo isset($errors['qualification']) ? $errors['qualification'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <textarea name="experience" placeholder="Work Experience" 
                            class="<?php echo isset($errors['experience']) ? 'error' : ''; ?>" 
                            required><?php echo $fields['experience']; ?></textarea>
                        <span class="error-text"><?php echo isset($errors['experience']) ? $errors['experience'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <textarea name="specialization" placeholder="Specialization/Skills" 
                            class="<?php echo isset($errors['specialization']) ? 'error' : ''; ?>" 
                            required><?php echo $fields['specialization']; ?></textarea>
                        <span class="error-text"><?php echo isset($errors['specialization']) ? $errors['specialization'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <label for="certificates">Professional Certificates <span class="text-danger">*</span></label>
                        <div class="certificate-upload-box">
                            <input type="file" name="certificates[]" id="certificates" 
                                   class="certificate-input" 
                                   accept=".pdf,.jpg,.jpeg,.png" 
                                   multiple 
                                   required>
                            <div class="upload-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload or drag and drop</span>
                                <small>PDF, JPG or PNG (Max. 5MB each)</small>
                            </div>
                        </div>
                        <div id="fileList" class="file-list"></div>
                        <?php if(isset($errors['certificates'])): ?>
                            <span class="error-text"><?php echo $errors['certificates']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" 
                            class="<?php echo isset($errors['password']) ? 'error' : ''; ?>" required>
                        <span class="error-text"><?php echo isset($errors['password']) ? $errors['password'] : ''; ?></span>
                    </div>

                    <div class="form-group">
                        <input type="password" name="confirm_password" placeholder="Confirm Password" 
                            class="<?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" required>
                        <span class="error-text"><?php echo isset($errors['confirm_password']) ? $errors['confirm_password'] : ''; ?></span>
                    </div>

                    <?php if (isset($errors['general'])): ?>
                        <div class="error-text" style="text-align: center; margin-bottom: 10px;">
                            <?php echo $errors['general']; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit">Submit Application</button>
                </form>

                <div class="links-container">
                    <a href="login2.php" class="toggle-link">Already have an account? Login here</a>
                    <a href="enhanced-gym-landing.php" class="toggle-link">Back to Website</a>
                </div>
            </div>
        </div>
    </div>

    <script>
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
            pattern: /^[^\s][a-zA-Z0-9._%+-]+@[a-zA-Z-]+(\.[a-zA-Z]{2,})+$/,
            message: {
                required: "Email is required.",
                pattern: "Invalid email address."
            }
        },
        mobile: {
            pattern: /^[6-9]\d{9}$/,
            message: {
                required: "Mobile number is required.",
                pattern: "Mobile number must be exactly 10 digits and start with 6, 7, 8, or 9."
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
                const date = new Date(value);
                const now = new Date();
                const age = now.getFullYear() - date.getFullYear();
                return age >= 18 && age <= 65;
            },
            message: {
                required: "Date of birth is required.",
                validate: "Age must be between 18 and 65 years."
            }
        },
        qualification: {
            minLength: 10,
            message: {
                required: "Qualification is required.",
                minLength: "Please provide more details about your qualifications (min 10 characters)."
            }
        },
        experience: {
            minLength: 10,
            message: {
                required: "Experience is required.",
                minLength: "Please provide more details about your experience (min 10 characters)."
            }
        },
        specialization: {
            minLength: 10,
            message: {
                required: "Specialization is required.",
                minLength: "Please provide more details about your specialization (min 10 characters)."
            }
        },
        password: {
            pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
            message: {
                required: "Password is required.",
                pattern: "Password must contain at least 8 characters, including uppercase, lowercase, number and special character."
            }
        },
        confirm_password: {
            validate: function(value) {
                return value === document.querySelector('input[name="password"]').value;
            },
            message: {
                required: "Please confirm your password.",
                validate: "Passwords do not match."
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

        // Custom validation
        if (rules.validate && !rules.validate(value)) {
            input.classList.add('error');
            errorSpan.textContent = rules.message.validate;
            return false;
        }

        // Clear error message if validation passes
        errorSpan.textContent = '';
        return true;
    }

    // Add event listeners for live validation
    document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(input => {
        ['input', 'blur', 'change'].forEach(eventType => {
            input.addEventListener(eventType, () => {
                validateField(input);
                
                // Special case for confirm password
                if (input.name === 'password') {
                    const confirmPassword = document.querySelector('input[name="confirm_password"]');
                    if (confirmPassword.value) {
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
        this.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please correct all errors before submitting.');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const certificateInput = document.getElementById('certificates');
        const uploadBox = document.querySelector('.certificate-upload-box');
        const fileList = document.getElementById('fileList');
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

        // Drag and drop handlers
        uploadBox.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadBox.classList.add('dragover');
        });

        uploadBox.addEventListener('dragleave', () => {
            uploadBox.classList.remove('dragover');
        });

        uploadBox.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadBox.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        certificateInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            fileList.innerHTML = ''; // Clear existing files
            Array.from(files).forEach(file => {
                // Validate file type
                if (!allowedTypes.includes(file.type)) {
                    alert(`Invalid file type: ${file.name}. Please upload PDF, JPG, or PNG files only.`);
                    return;
                }

                // Validate file size
                if (file.size > maxFileSize) {
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                    return;
                }

                // Create file preview
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                const fileInfo = document.createElement('div');
                fileInfo.className = 'file-info';
                
                const icon = document.createElement('i');
                icon.className = `fas fa-${file.type === 'application/pdf' ? 'file-pdf' : 'file-image'}`;
                
                const fileName = document.createElement('span');
                fileName.className = 'file-name';
                fileName.textContent = file.name;
                
                const removeButton = document.createElement('button');
                removeButton.className = 'remove-file';
                removeButton.innerHTML = '<i class="fas fa-times"></i>';
                removeButton.onclick = () => fileItem.remove();

                fileInfo.appendChild(icon);
                fileInfo.appendChild(fileName);
                fileItem.appendChild(fileInfo);
                fileItem.appendChild(removeButton);
                fileList.appendChild(fileItem);
            });
        }
    });
    </script>
</body>
</html> 