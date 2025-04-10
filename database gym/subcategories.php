<?php
include 'connect.php';
session_start();

// Get the main category ID from URL
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Fetch main category details
$category_query = "SELECT category_name FROM categories WHERE category_id = ?";
$stmt = mysqli_prepare($conn, $category_query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$category_result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($category_result);

if (!$category) {
    header("Location: products.php");
    exit();
}

// Handle subcategory addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subcategory'])) {
    $subcategory_name = mysqli_real_escape_string($conn, $_POST['subcategory_name']);
    
    // Check if subcategory already exists
    $check_query = "SELECT category_id FROM categories WHERE category_name = ? AND parent_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "si", $subcategory_name, $category_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error_message = "This subcategory already exists!";
    } else {
        $insert_query = "INSERT INTO categories (category_name, parent_id, is_subcategory) VALUES (?, ?, 1)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "si", $subcategory_name, $category_id);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $success_message = "Subcategory added successfully!";
        } else {
            $error_message = "Error adding subcategory: " . mysqli_error($conn);
        }
    }
}

// Handle subcategory deletion
if (isset($_GET['delete']) && isset($_GET['subcategory_id'])) {
    $subcategory_id = intval($_GET['subcategory_id']);
    $delete_query = "DELETE FROM categories WHERE category_id = ? AND parent_id = ? AND is_subcategory = 1";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "ii", $subcategory_id, $category_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $success_message = "Subcategory deleted successfully!";
    } else {
        $error_message = "Error deleting subcategory: " . mysqli_error($conn);
    }
}

// Fetch subcategories for this category
$subcategories_query = "SELECT * FROM categories WHERE parent_id = ? AND is_subcategory = 1 ORDER BY category_name";
$stmt = mysqli_prepare($conn, $subcategories_query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$subcategories_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subcategories - Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
    <style>
        .subcategory-container {
            margin-top: 100px;
            padding: 20px;
        }
        
        .subcategory-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .subcategory-form {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .subcategory-list {
            margin-top: 20px;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .table th {
            background-color: #ed563b;
            color: white;
        }

        .btn-primary {
            background-color: #ed563b;
            border-color: #ed563b;
        }

        .btn-primary:hover {
            background-color: #da4528;
            border-color: #da4528;
        }

        .checkbox-group, .radio-group {
            margin-top: 10px;
        }

        .form-check {
            margin-bottom: 8px;
        }

        .category-attributes {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
        }

        input[type="color"] {
            height: 38px;
            padding: 2px;
        }

        .checkbox-group .form-check,
        .radio-group .form-check {
            display: inline-block;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    

    <div class="subcategory-container">
        <div class="container">
            <div class="back-button">
                <a href="products.php" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Products
                </a>
            </div>

            <div class="subcategory-card">
                <h2>Manage Classifications for <?php echo htmlspecialchars($category['category_name']); ?></h2>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="subcategory-form">
                    <h4>Add New Classification</h4>
                    <form method="POST" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" name="subcategory_name" class="form-control" 
                                   placeholder="Enter classification name (e.g., Whey Protein, Dumbbells, etc.)" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="add_subcategory" class="btn btn-primary">
                                Add Classification
                            </button>
                        </div>
                    </form>
                </div>

                <div class="subcategory-form">
                    <h4>Category-Specific Attributes</h4>
                    <div id="category-attributes" class="mt-4">
                        <?php
                        $category_name = htmlspecialchars($category['category_name']);
                        if ($category_name === 'Supplements'): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Flavor</label>
                                        <select class="form-control">
                                            <option value="">Select Flavor</option>
                                            <option value="Chocolate">Chocolate</option>
                                            <option value="Vanilla">Vanilla</option>
                                            <option value="Strawberry">Strawberry</option>
                                            <option value="Banana">Banana</option>
                                            <option value="Cookies & Cream">Cookies & Cream</option>
                                            <option value="Unflavored">Unflavored</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Weight/Size</label>
                                        <select class="form-control">
                                            <option value="">Select Size</option>
                                            <option value="1lb">1 lb</option>
                                            <option value="2lb">2 lbs</option>
                                            <option value="5lb">5 lbs</option>
                                            <option value="10lb">10 lbs</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Serving Size</label>
                                        <input type="number" class="form-control" placeholder="Number of servings">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Form</label>
                                        <select class="form-control">
                                            <option value="">Select Form</option>
                                            <option value="Powder">Powder</option>
                                            <option value="Capsule">Capsule</option>
                                            <option value="Tablet">Tablet</option>
                                            <option value="Liquid">Liquid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Goal</label>
                                        <select class="form-control">
                                            <option value="">Select Goal</option>
                                            <option value="Muscle Gain">Muscle Gain</option>
                                            <option value="Fat Loss">Fat Loss</option>
                                            <option value="Endurance">Endurance</option>
                                            <option value="Recovery">Recovery</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Dietary Tags</label>
                                        <div class="checkbox-group">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="vegan">
                                                <label class="form-check-label" for="vegan">Vegan</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="gluten-free">
                                                <label class="form-check-label" for="gluten-free">Gluten-Free</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="keto">
                                                <label class="form-check-label" for="keto">Keto</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="sugar-free">
                                                <label class="form-check-label" for="sugar-free">Sugar-Free</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($category_name === 'Equipment'): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Material</label>
                                        <select class="form-control">
                                            <option value="">Select Material</option>
                                            <option value="Rubber">Rubber</option>
                                            <option value="Steel">Steel</option>
                                            <option value="Iron">Iron</option>
                                            <option value="Foam">Foam</option>
                                            <option value="PVC">PVC</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Weight (kg)</label>
                                        <input type="number" class="form-control" placeholder="Weight in kg">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Dimensions (LxWxH)</label>
                                        <input type="text" class="form-control" placeholder="e.g., 100x50x150 cm">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Adjustable</label>
                                        <div class="radio-group">
                                            <div class="form-check">
                                                <input type="radio" name="adjustable" class="form-check-input" value="Yes">
                                                <label class="form-check-label">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" name="adjustable" class="form-check-input" value="No">
                                                <label class="form-check-label">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Indoor/Outdoor</label>
                                        <select class="form-control">
                                            <option value="">Select Usage</option>
                                            <option value="Indoor">Indoor</option>
                                            <option value="Outdoor">Outdoor</option>
                                            <option value="Both">Both</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Foldable/Portable</label>
                                        <div class="radio-group">
                                            <div class="form-check">
                                                <input type="radio" name="foldable" class="form-check-input" value="Yes">
                                                <label class="form-check-label">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" name="foldable" class="form-check-input" value="No">
                                                <label class="form-check-label">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($category_name === 'Accessories'): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Material</label>
                                        <select class="form-control">
                                            <option value="">Select Material</option>
                                            <option value="Cotton">Cotton</option>
                                            <option value="Nylon">Nylon</option>
                                            <option value="Leather">Leather</option>
                                            <option value="Polyester">Polyester</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Color</label>
                                        <input type="color" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Size</label>
                                        <select class="form-control">
                                            <option value="">Select Size</option>
                                            <option value="Free Size">Free Size</option>
                                            <option value="S">S</option>
                                            <option value="M">M</option>
                                            <option value="L">L</option>
                                            <option value="XL">XL</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($category_name === 'Clothing'): ?>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select class="form-control">
                                            <option value="">Select Gender</option>
                                            <option value="Men">Men</option>
                                            <option value="Women">Women</option>
                                            <option value="Unisex">Unisex</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Size</label>
                                        <select class="form-control">
                                            <option value="">Select Size</option>
                                            <option value="XS">XS</option>
                                            <option value="S">S</option>
                                            <option value="M">M</option>
                                            <option value="L">L</option>
                                            <option value="XL">XL</option>
                                            <option value="XXL">XXL</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Color</label>
                                        <input type="color" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Material</label>
                                        <select class="form-control">
                                            <option value="">Select Material</option>
                                            <option value="Cotton">Cotton</option>
                                            <option value="Polyester">Polyester</option>
                                            <option value="Spandex">Spandex</option>
                                            <option value="Nylon">Nylon</option>
                                            <option value="Blend">Blend</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="subcategory-list">
                    <h4>Current Classifications for <?php echo htmlspecialchars($category['category_name']); ?></h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Classification Name</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($subcategory = mysqli_fetch_assoc($subcategories_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subcategory['category_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($subcategory['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <a href="edit_subcategory.php?id=<?php echo $subcategory['category_id']; ?>&category_id=<?php echo $category_id; ?>" 
                                               class="btn btn-sm btn-primary">Edit</a>
                                            <a href="subcategories.php?category_id=<?php echo $category_id; ?>&delete=1&subcategory_id=<?php echo $subcategory['category_id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this classification?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if (mysqli_num_rows($subcategories_result) == 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No classifications found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.querySelector('form');
        const subcategoryInput = document.querySelector('input[name="subcategory_name"]');

        form.addEventListener('submit', function(e) {
            if (!subcategoryInput.value.trim()) {
                e.preventDefault();
                alert('Please enter a classification name');
                return;
            }
        });

        // Auto-hide alerts after 3 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 3000);
        });
    });
    </script>
</body>
</html>