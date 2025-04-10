<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $query = "SELECT l.username, l.email, r.full_name, r.mobile_no, r.address 
              FROM login l 
              LEFT JOIN register r ON l.user_id = r.user_id 
              WHERE l.user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    $pic_query = "SELECT pic_url FROM profilepictures WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1";
    $pic_stmt = $conn->prepare($pic_query);
    $pic_stmt->bind_param("i", $user_id);
    $pic_stmt->execute();
    $pic_result = $pic_stmt->get_result();
    $pic_data = $pic_result->fetch_assoc();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - FOCUS GYM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .edit-profile-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .profile-image-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            position: relative;
        }
        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid #ed563b;
            overflow: hidden;
        }
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .change-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #ed563b;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .change-photo-btn:hover {
            background: #dc472e;
            transform: scale(1.1);
        }
        .form-control:focus {
            border-color: #ed563b;
            box-shadow: 0 0 0 0.2rem rgba(237, 86, 59, 0.25);
        }
        .error-feedback {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .success-feedback {
            color: #198754;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .loading-spinner {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.8);
            width: 100%;
            height: 100%;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="edit-profile-container">
            <h2 class="text-center mb-4">Edit Profile</h2>

            <!-- Profile Picture Section -->
            <div class="profile-image-container">
                <div class="profile-image">
                    <img src="<?php 
                        echo !empty($pic_data['pic_url']) 
                            ? 'uploads/' . htmlspecialchars($pic_data['pic_url']) 
                            : 'assets/images/default-avatar.png'; 
                    ?>" alt="Profile Picture" id="profileImage">
                    <div class="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <label for="profile_pic" class="change-photo-btn">
                    <i class="fas fa-camera"></i>
                </label>
                <input type="file" id="profile_pic" accept="image/*" style="display: none;">
            </div>

            <!-- Edit Form -->
            <form id="edit-profile-form" class="mt-4">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                    <div class="feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
                    <div class="feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    <div class="feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="mobile_no" class="form-label">Mobile Number</label>
                    <input type="tel" class="form-control" id="mobile_no" name="mobile_no" 
                           value="<?php echo htmlspecialchars($user_data['mobile_no'] ?? ''); ?>" required>
                    <div class="feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php 
                        echo htmlspecialchars($user_data['address'] ?? ''); 
                    ?></textarea>
                    <div class="feedback"></div>
                </div>

                <div class="text-center mt-4">
                    <a href="profileuser.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation patterns
        const validations = {
            full_name: {
                pattern: /^[a-zA-Z\s]{3,50}$/,
                message: 'Name should be 3-50 characters long and contain only letters and spaces'
            },
            username: {
                pattern: /^[a-zA-Z0-9_]{3,20}$/,
                message: 'Username should be 3-20 characters and contain only letters, numbers, and underscores'
            },
            email: {
                pattern: /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/,
                message: 'Please enter a valid email address'
            },
            mobile_no: {
                pattern: /^[0-9]{10}$/,
                message: 'Mobile number should be 10 digits'
            },
            address: {
                pattern: /^.{5,200}$/,
                message: 'Address should be between 5 and 200 characters'
            }
        };

        // Live validation function
        function validateField(field) {
            const value = field.value.trim();
            const validation = validations[field.name];
            const feedbackDiv = field.nextElementSibling;

            if (!value) {
                showError(field, feedbackDiv, 'This field is required');
                return false;
            }

            if (validation && !validation.pattern.test(value)) {
                showError(field, feedbackDiv, validation.message);
                return false;
            }

            showSuccess(field, feedbackDiv);
            return true;
        }

        function showError(field, feedbackDiv, message) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            feedbackDiv.className = 'error-feedback';
            feedbackDiv.textContent = message;
        }

        function showSuccess(field, feedbackDiv) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            feedbackDiv.className = 'success-feedback';
            feedbackDiv.textContent = 'Looks good!';
        }

        // Add live validation to all fields
        document.querySelectorAll('#edit-profile-form .form-control').forEach(field => {
            field.addEventListener('input', () => validateField(field));
            field.addEventListener('blur', () => validateField(field));
        });

        // Handle profile picture upload
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate file type and size
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please upload only JPG, PNG or GIF images');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('File size should be less than 5MB');
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                }
                reader.readAsDataURL(file);

                // Show loading spinner
                const spinner = document.querySelector('.loading-spinner');
                spinner.style.display = 'flex';

                // Upload file
                const formData = new FormData();
                formData.append('profile_pic', file);

                fetch('update_profile_pic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Profile picture updated successfully!');
                    } else {
                        throw new Error(data.error || 'Failed to update profile picture');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update profile picture');
                })
                .finally(() => {
                    spinner.style.display = 'none';
                });
            }
        });

        // Handle form submission
        document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate all fields
            let isValid = true;
            this.querySelectorAll('.form-control').forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                alert('Please correct all errors before submitting');
                return;
            }

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server error: ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    // Check user role and redirect accordingly
                    fetch('check_role.php')
                        .then(response => response.json())
                        .then(roleData => {
                            if (roleData.role === 'staff') {
                                window.location.href = 'profilestaff.php';
                            } else {
                                window.location.href = 'profileuser.php';
                            }
                        })
                        .catch(() => {
                            // Default to profileuser.php if role check fails
                            window.location.href = 'profileuser.php';
                        });
                } else {
                    throw new Error(data.error || 'Failed to update profile');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update profile: ' + error.message);
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Save Changes';
            });
        });
    </script>
</body>
</html> 