<?php
session_start();
if (!isset($_SESSION['user_id']) ) {
  header("Location: login2.php");
  exit();
}
include 'connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login2.php');
    exit();
}

// Check if plan type is provided
if (!isset($_GET['type']) || !in_array($_GET['type'], ['basic', 'premium'])) {
    header('Location: manage_memberships.php');
    exit();
}

$plan_type = $_GET['type'];
$plan_name = ($plan_type === 'premium') ? 'Premium Membership' : 'Basic Membership';

// Fetch plan details
$query = "SELECT * FROM membership_plans WHERE plan_name = '$plan_name'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    // If plan doesn't exist, create it with default values
    $default_values = [
        'basic' => [
            'price' => 999,
            'duration' => 1499,
            'features' => "Access to gym equipment\nBasic fitness assessment\nLocker facility\nGroup workout sessions"
        ],
        'premium' => [
            'price' => 1999,
            'duration' => 2999,
            'features' => "All Basic features\nPersonal trainer (2 sessions/week)\nCustomized diet plan\nSteam & Sauna access\nSupplement consultation"
        ]
    ];

    $default = $default_values[$plan_type];
    $insert_query = "INSERT INTO membership_plans (plan_name, price, duration, features) 
                     VALUES ('$plan_name', {$default['price']}, {$default['duration']}, '{$default['features']}')";
    
    if (!mysqli_query($conn, $insert_query)) {
        die("Error creating plan: " . mysqli_error($conn));
    }
    
    // Fetch the newly created plan
    $result = mysqli_query($conn, $query);
}

$plan = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    
    // Validate inputs
    $error = false;
    if (!is_numeric($price) || $price <= 0) {
        $error = true;
        $message = "Invalid price value";
    } elseif (!is_numeric($duration) || $duration < 1 || $duration > 12) {
        $error = true;
        $message = "Duration must be between 1 and 12 months";
    }

    if (!$error) {
        // Update only price and duration
        $update_query = "UPDATE membership_plans 
                        SET price = '$price',
                            duration = '$duration'
                        WHERE plan_name = '$plan_name'";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success_message'] = "Plan updated successfully!";
            header('Location: manage_memberships.php');
            exit();
        } else {
            $error = true;
            $message = "Error updating plan: " . mysqli_error($conn);
        }
    }
}

// Calculate savings percentage
$savings_percentage = 0;
if ($plan['duration'] > 0) {
    $savings_percentage = round((($plan['duration'] - $plan['price']) / $plan['duration']) * 100);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit <?php echo $plan_name; ?></title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
</head>
<body>
    <div class="edit-membership-container">
        <!-- Back Button -->
        <div class="back-section">
            <a href="manage_memberships.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Memberships
            </a>
        </div>

        <!-- Main Content -->
        <div class="edit-content">
            <div class="edit-header">
                <h2>Edit <?php echo $plan_name; ?></h2>
                <div class="line-dec"></div>
            </div>

            <!-- Edit Form -->
            <div class="edit-form-container">
                <?php if (isset($error) && $error): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="editPlanForm">
                    <!-- Price Fields -->
                    <div class="price-fields">
                        <div class="form-group">
                            <label>Current Price</label>
                            <div class="price-input">
                                <span class="currency">₹</span>
                                <input type="number" name="price" id="currentPrice" 
                                       value="<?php echo $plan['price']; ?>" required
                                       min="1" step="any">
                                <div class="validation-message" id="priceValidation"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Duration (Months)</label>
                            <div class="duration-input">
                                <input type="number" name="duration" id="duration" 
                                       value="<?php echo isset($plan['duration']) ? $plan['duration'] : 1; ?>" 
                                       required min="1" max="12">
                                <span class="duration-suffix"></span>
                                <div class="validation-message" id="durationValidation"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="manage_memberships.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary" id="saveButton">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    body {
        background-color: #f8f9fa;
        font-family: 'Poppins', sans-serif;
    }

    .edit-membership-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }

    /* Back Button Styling */
    .back-section {
        margin-bottom: 30px;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        color: #232d39;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .back-button:hover {
        color: #ed563b;
        text-decoration: none;
        transform: translateX(-5px);
    }

    .back-button i {
        margin-right: 8px;
    }

    /* Header Styling */
    .edit-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .edit-header h2 {
        color: #232d39;
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 15px;
    }

    .line-dec {
        width: 60px;
        height: 3px;
        background-color: #ed563b;
        margin: 0 auto;
    }

    /* Form Container */
    .edit-form-container {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }

    /* Form Fields Styling */
    .price-fields {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        color: #232d39;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .price-input {
        position: relative;
        display: flex;
        align-items: center;
    }

    .currency {
        position: absolute;
        left: 15px;
        color: #666;
        font-weight: 600;
    }

    input[type="number"] {
        width: 100%;
        padding: 12px 15px 12px 35px;
        border: 2px solid #eee;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    input[type="number"]:focus {
        border-color: #ed563b;
        outline: none;
        box-shadow: 0 0 0 3px rgba(237,86,59,0.1);
    }

    .features-group {
        margin-bottom: 30px;
    }

    .features-hint {
        display: block;
        color: #666;
        margin-bottom: 8px;
        font-size: 14px;
    }

    textarea {
        width: 100%;
        padding: 15px;
        border: 2px solid #eee;
        border-radius: 8px;
        font-family: monospace;
        font-size: 14px;
        resize: vertical;
        transition: all 0.3s ease;
    }

    textarea:focus {
        border-color: #ed563b;
        outline: none;
        box-shadow: 0 0 0 3px rgba(237,86,59,0.1);
    }

    /* Action Buttons */
    .form-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 40px;
    }

    .save-button {
        background: #ed563b;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .save-button:hover {
        background: #da442a;
        transform: translateY(-2px);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .edit-membership-container {
            margin: 20px auto;
        }

        .edit-form-container {
            padding: 25px;
        }

        .price-fields {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

        .save-button {
            width: 100%;
            justify-content: center;
        }
    }

    .validation-message {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    .form-control.is-invalid {
        background-image: none;
    }

    /* Add these styles to your existing CSS */
    .duration-input {
        position: relative;
        display: flex;
        align-items: center;
    }

    .duration-input input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #eee;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .duration-input input:focus {
        border-color: #ed563b;
        outline: none;
        box-shadow: 0 0 0 3px rgba(237,86,59,0.1);
    }

    .duration-suffix {
        position: absolute;
        right: 15px;
        color: #666;
        font-weight: 600;
    }
    </style>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script>
    $(document).ready(function() {
        const form = $('#editPlanForm');
        const currentPrice = $('#currentPrice');
        const originalPrice = $('#originalPrice');
        const features = $('#features');
        const saveButton = $('#saveButton');

        function validateForm() {
            let isValid = true;
            
            // Validate Current Price
            // const price = parseFloat(currentPrice.val());
            // if (!price || price <= 0) {
            //     $('#priceValidation').text('Price must be greater than 0').addClass('text-danger');
            //     currentPrice.addClass('is-invalid');
            //     isValid = false;
            // } else {
            //     $('#priceValidation').text('').removeClass('text-danger');
            //     currentPrice.removeClass('is-invalid');
            // }

            // Validate Duration
            const duration = parseInt($('#duration').val());
            if (!duration || duration < 1 || duration > 12) {
                $('#durationValidation').text('Duration must be between 1 and 12 months').addClass('text-danger');
                $('#duration').addClass('is-invalid');
                isValid = false;
            } else {
                $('#durationValidation').text('').removeClass('text-danger');
                $('#duration').removeClass('is-invalid');
            }

            // Validate Features
            const featuresList = features.val().trim().split('\n').filter(f => f.trim());
            if (featuresList.length < 2) {
                $('#featuresValidation').text('Please enter at least 2 features').addClass('text-danger');
                features.addClass('is-invalid');
                isValid = false;
            } else {
                $('#featuresValidation').text('').removeClass('text-danger');
                features.removeClass('is-invalid');
            }

            return isValid;
        }

        // Live validation
        currentPrice.on('input', validateForm);
        $('#duration').on('input', validateForm);
        features.on('input', validateForm);

        // Form submission
        form.on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }

            saveButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        });
    });
    </script>
</body>
</html> 