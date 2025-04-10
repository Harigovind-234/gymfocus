<?php
include 'connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch categories and subcategories
$categories_query = "SELECT category_id, category_name, parent_id, is_subcategory 
                    FROM categories 
                    ORDER BY category_name";
$categories_result = mysqli_query($conn, $categories_query);

if (!$categories_result) {
    die("Error fetching categories: " . mysqli_error($conn));
}

// Get product details
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $product_query = "SELECT p.*, 
              c.category_name as classification_name,
              c.parent_id,
              pc.category_name as parent_category_name,
              pc.category_id as parent_category_id
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.category_id 
              LEFT JOIN categories pc ON c.parent_id = pc.category_id
              WHERE p.product_id = ?";
              
    $stmt = mysqli_prepare($conn, $product_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    
    if (!$product) {
        $_SESSION['error'] = "Product not found";
        header('Location: products.php');
        exit();
    }
}

// After fetching the product details, decode the attributes
$attributes = [];
$product_details = [];
if (isset($product['attributes']) && !empty($product['attributes'])) {
    $attributes = json_decode($product['attributes'], true);
    if (is_array($attributes)) {
        // Check for different attribute structures
        if (isset($attributes['product_details']) && is_array($attributes['product_details'])) {
            // Structure: { "product_details": { "size": "M", ... } }
            $product_details = $attributes['product_details']; 
        } else {
            // Structure: { "size": "M", ... }
            $product_details = $attributes;
        }
    }
}

// Debug info
error_log('Product attributes: ' . print_r($attributes, true));
error_log('Product details: ' . print_r($product_details, true));

// Add a check for if product is null
if (!isset($product) || !is_array($product)) {
    echo "Error: Product not found or invalid product data";
    exit();
}

// Debugging aid - only shown if debug parameter is present
if (isset($_GET['debug'])) {
    echo '<div style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd;">';
    echo '<h4>Debug Information</h4>';
    echo '<p><strong>Product ID:</strong> ' . $product['product_id'] . '</p>';
    echo '<p><strong>Category ID:</strong> ' . $product['category_id'] . '</p>';
    echo '<p><strong>Parent Category ID:</strong> ' . ($product['parent_category_id'] ?? 'N/A') . '</p>';
    echo '<p><strong>Classification Name:</strong> ' . ($product['classification_name'] ?? 'N/A') . '</p>';
    
    echo '<h5>Attributes Structure:</h5>';
    echo '<pre>' . htmlspecialchars(print_r($attributes, true)) . '</pre>';
    
    echo '<h5>Product Details Structure:</h5>';
    echo '<pre>' . htmlspecialchars(print_r($product_details, true)) . '</pre>';
    
    echo '<h5>Form POST Data:</h5>';
    echo '<pre>' . htmlspecialchars(print_r($_POST, true)) . '</pre>';
    echo '</div>';
}

// Add this at the top of your PHP processing section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Form submitted: ' . print_r($_POST, true));
}

// Handle form submission
if (isset($_POST['submit']) || isset($_POST['product_name'])) {
    // Get form data
    $productId = $_POST['product_id'];
    $productName = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    // Use the classification (subcategory) instead of the parent category
    $category_id = isset($_POST['classification']) && !empty($_POST['classification']) ? 
                   $_POST['classification'] : 
                   $_POST['category_id']; // Fallback to parent category if no classification selected
    
    // Debug info for form submission
    error_log('Form data: ' . print_r($_POST, true));
    error_log('Selected category_id: ' . $category_id);
    
    // Validate that the category_id exists in the database
    $check_category_query = "SELECT category_id FROM categories WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_category_query);
    $check_stmt->bind_param("i", $category_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        // Category ID doesn't exist - display error and stop execution
        echo "Error: Selected category (ID: $category_id) does not exist in the database.<br>";
        echo "Classification value: " . (isset($_POST['classification']) ? $_POST['classification'] : 'Not set') . "<br>";
        echo "Category value: " . (isset($_POST['category_id']) ? $_POST['category_id'] : 'Not set') . "<br>";
        
        // List available categories for debugging
        echo "<p>Available categories:</p>";
        $all_categories = $conn->query("SELECT category_id, category_name, parent_id FROM categories ORDER BY category_name");
        echo "<ul>";
        while ($cat = $all_categories->fetch_assoc()) {
            echo "<li>ID: {$cat['category_id']}, Name: {$cat['category_name']}, Parent: {$cat['parent_id']}</li>";
        }
        echo "</ul>";
        exit();
    }
    
    $stock = isset($_POST['stock']) ? $_POST['stock'] : 0;

    // Verify the category exists and get its information
    $categoryQuery = "SELECT c.*, pc.category_name as parent_name 
                     FROM categories c
                     LEFT JOIN categories pc ON c.parent_id = pc.category_id
                     WHERE c.category_id = ?";
    $stmt = $conn->prepare($categoryQuery);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $categoryResult = $stmt->get_result();
    $category = $categoryResult->fetch_assoc();

    // Collect product details/attributes based on category
    $productDetails = [];
    
    // Process category-specific attributes
    if (isset($category) && is_array($category)) {
        $parentName = isset($category['parent_name']) ? $category['parent_name'] : '';
        $categoryName = isset($category['category_name']) ? $category['category_name'] : '';
        
        if ($parentName == 'Supplements' || $categoryName == 'Supplements') {
            if (isset($_POST['flavor']) && !empty($_POST['flavor'])) {
                $productDetails['flavor'] = $_POST['flavor'];
            }
            if (isset($_POST['weight_size']) && !empty($_POST['weight_size'])) {
                $productDetails['weight_size'] = $_POST['weight_size'];
            }
            if (isset($_POST['serving_size']) && !empty($_POST['serving_size'])) {
                $productDetails['serving_size'] = $_POST['serving_size'];
            }
            if (isset($_POST['form']) && !empty($_POST['form'])) {
                $productDetails['form'] = $_POST['form'];
            }
            if (isset($_POST['primary_goal']) && !empty($_POST['primary_goal'])) {
                $productDetails['primary_goal'] = $_POST['primary_goal'];
            }
        } 
        elseif ($parentName == 'Equipment' || $categoryName == 'Equipment') {
            if (isset($_POST['material']) && !empty($_POST['material'])) {
                $productDetails['material'] = $_POST['material'];
            }
            if (isset($_POST['equipment_weight']) && !empty($_POST['equipment_weight'])) {
                $productDetails['weight'] = $_POST['equipment_weight'];
            }
            if (isset($_POST['dimensions']) && !empty($_POST['dimensions'])) {
                $productDetails['dimensions'] = $_POST['dimensions'];
            }
        } 
        elseif ($parentName == 'Clothing' || $categoryName == 'Clothing') {
            if (isset($_POST['gender']) && !empty($_POST['gender'])) {
                $productDetails['gender'] = $_POST['gender'];
            }
            if (isset($_POST['clothing_size']) && !empty($_POST['clothing_size'])) {
                $productDetails['size'] = $_POST['clothing_size'];
            }
            if (isset($_POST['clothing_color']) && !empty($_POST['clothing_color'])) {
                $productDetails['color'] = $_POST['clothing_color'];
            }
            if (isset($_POST['clothing_material']) && !empty($_POST['clothing_material'])) {
                $productDetails['material'] = $_POST['clothing_material'];
            }
        } 
        elseif ($parentName == 'Accessories' || $categoryName == 'Accessories') {
            if (isset($_POST['accessory_material']) && !empty($_POST['accessory_material'])) {
                $productDetails['material'] = $_POST['accessory_material'];
            }
            if (isset($_POST['accessory_color']) && !empty($_POST['accessory_color'])) {
                $productDetails['color'] = $_POST['accessory_color'];
            }
            if (isset($_POST['accessory_size']) && !empty($_POST['accessory_size'])) {
                $productDetails['size'] = $_POST['accessory_size'];
            }
        }
    }

    // Check if we need to maintain the nested structure
    $final_attributes = [];
    if (isset($attributes['product_details'])) {
        // Keep the nested structure if it existed before
        $final_attributes['product_details'] = $productDetails;
        // Preserve any other top-level attributes that might exist
        foreach ($attributes as $key => $value) {
            if ($key !== 'product_details') {
                $final_attributes[$key] = $value;
            }
        }
    } else {
        // Use flat structure if that's what was used before
        $final_attributes = $productDetails;
    }
    
    // Convert to JSON
    $details_json = json_encode($final_attributes);
    
    // Debug the JSON
    error_log('Final attributes: ' . print_r($final_attributes, true));
    error_log('JSON to save: ' . $details_json);

    // Check if an image was uploaded
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
        // Image upload handling
        $target_dir = "uploads/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $target_file = $target_dir . uniqid() . "." . $imageFileType;
        
        // Valid file extensions
        $extensions_arr = ["jpg", "jpeg", "png", "gif"];
        
        // Check extension
        if (in_array($imageFileType, $extensions_arr)) {
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Update product with new image
                $stmt = $conn->prepare("UPDATE products SET 
                                       product_name = ?, 
                                       description = ?, 
                                       price = ?, 
                                       category_id = ?, 
                                       image_path = ?, 
                                       stock = ?,
                                       attributes = ?
                                       WHERE product_id = ?");
                $stmt->bind_param("ssdisisi", $productName, $description, $price, $category_id, $target_file, $stock, $details_json, $productId);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Product updated successfully!";
                    header("Location: products.php");
                    exit();
                } else {
                    echo "Error: " . $stmt->error;
                }
            } else {
                echo "Failed to upload image";
            }
        } else {
            echo "Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.";
        }
    } else {
        // Update product without changing the image
        $stmt = $conn->prepare("UPDATE products SET 
                               product_name = ?, 
                               description = ?, 
                               price = ?, 
                               category_id = ?, 
                               stock = ?,
                               attributes = ?
                               WHERE product_id = ?");
        $stmt->bind_param("ssdiisi", $productName, $description, $price, $category_id, $stock, $details_json, $productId);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Product updated successfully!";
            header("Location: products.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}

// Add this right before the HTML section to get the category for loading attributes
$category_query = "SELECT c.category_name, pc.category_name as parent_category_name 
                  FROM categories c 
                  LEFT JOIN categories pc ON c.parent_id = pc.category_id
                  WHERE c.category_id = ?";
$cat_stmt = mysqli_prepare($conn, $category_query);
mysqli_stmt_bind_param($cat_stmt, "i", $product['category_id']);
mysqli_stmt_execute($cat_stmt);
$cat_result = mysqli_stmt_get_result($cat_stmt);
$category_data = mysqli_fetch_assoc($cat_result);

// Get product attributes for the form
$product_details = isset($attributes['product_details']) ? $attributes['product_details'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        .is-valid {
            border-color: #28a745 !important;
            padding-right: calc(1.5em + 0.75rem) !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right calc(0.375em + 0.1875rem) center !important;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
        }

        .is-invalid {
            border-color: #dc3545 !important;
            padding-right: calc(1.5em + 0.75rem) !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='%23dc3545' viewBox='-2 -2 7 7'%3e%3cpath stroke='%23dc3545' d='M0 0l3 3m0-3L0 3'/%3e%3ccircle r='.5'/%3e%3ccircle cx='3' r='.5'/%3e%3ccircle cy='3' r='.5'/%3e%3ccircle cx='3' cy='3' r='.5'/%3e%3c/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right calc(0.375em + 0.1875rem) center !important;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
        }

        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 80%;
        }

        .is-invalid ~ .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h2>Edit Product</h2>
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Product Details Overview -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Product Details Overview</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="toggleDetailsBtn">
                            Show Details
                        </button>
                    </div>
                    <div class="card-body" id="productDetailsSection" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Basic Information</h5>
                                <p><strong>Product ID:</strong> <?php echo $product['product_id']; ?></p>
                                <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['product_name']); ?></p>
                                <p><strong>Price:</strong> ₹<?php echo $product['price']; ?></p>
                                <p><strong>Stock:</strong> <?php echo $product['stock']; ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['parent_category_name'] ?? ''); ?></p>
                                <p><strong>Classification:</strong> <?php echo htmlspecialchars($product['classification_name'] ?? ''); ?></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Product Attributes</h5>
                                <?php if (!empty($product_details)): ?>
                                    <div class="attributes-list">
                                        <?php foreach($product_details as $key => $value): ?>
                                            <?php if (!empty($value)): ?>
                                                <p>
                                                    <strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong>
                                                    <?php 
                                                    if ($key == 'color') {
                                                        echo '<span style="display:inline-block; width:15px; height:15px; background-color:'.htmlspecialchars($value).'; border:1px solid #ccc; margin-right:5px;"></span>';
                                                    }
                                                    echo is_array($value) ? implode(', ', $value) : htmlspecialchars($value); 
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No specific attributes</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <h5>Product Image</h5>
                                <?php if ($product['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                         class="img-thumbnail" 
                                         style="max-width: 150px;">
                                <?php else: ?>
                                    <p class="text-muted">No image available</p>
                                <?php endif; ?>
                                <h5 class="mt-3">Description</h5>
                                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" id="editProductForm" novalidate>
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Product Name*</label>
                                <input type="text" 
                                       name="product_name" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                       required 
                                       minlength="3">
                                <div class="invalid-feedback">Product name must be at least 3 characters long</div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Price (₹)*</label>
                                <input type="number" 
                                       name="price" 
                                       class="form-control" 
                                       step="0.01" 
                                       value="<?php echo $product['price']; ?>" 
                                       required 
                                       min="0">
                                <div class="invalid-feedback">Price must be greater than 0</div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Stock*</label>
                                <input type="number" 
                                       name="stock" 
                                       class="form-control" 
                                       value="<?php echo $product['stock']; ?>" 
                                       required 
                                       min="0">
                                <div class="invalid-feedback">Stock cannot be negative</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Category*</label>
                                <select name="category_id" class="form-control" id="categorySelect" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while ($category = mysqli_fetch_assoc($categories_result)):
                                        if (!$category['parent_id'] && !$category['is_subcategory']):
                                            $selected = '';
                                            if (isset($product['parent_category_id']) && $product['parent_category_id'] == $category['category_id']) {
                                                $selected = 'selected';
                                            }
                                    ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </select>
                                <div class="invalid-feedback">Please select a category</div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Classification*</label>
                                <select name="classification" class="form-control" id="classificationSelect" required>
                                    <option value="">Select Classification</option>
                                    <?php if (isset($product['category_id']) && $product['category_id']): ?>
                                    <option value="<?php echo $product['category_id']; ?>" selected>
                                        <?php echo htmlspecialchars($product['classification_name'] ?? 'Current Classification'); ?>
                                    </option>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">Please select a classification</div>
                            </div>

                            <div class="form-group mb-3">
                                <label>Current Image</label>
                                <div class="mb-2">
                                    <?php if ($product['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                             class="img-thumbnail" 
                                             style="max-width: 200px;">
                                    <?php else: ?>
                                        <p class="text-muted">No image available</p>
                                    <?php endif; ?>
                                </div>
                                <label>New Image</label>
                                <input type="file" 
                                       name="image" 
                                       class="form-control" 
                                       accept="image/*">
                                <div class="invalid-feedback">Please select a valid image file (max 5MB)</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>Description*</label>
                        <textarea name="description" 
                                  class="form-control" 
                                  rows="4" 
                                  required 
                                  minlength="10"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        <div class="invalid-feedback">Description must be at least 10 characters long</div>
                    </div>

                    <!-- Product Attributes Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Product Specifications</h4>
                        </div>
                        <div class="card-body">
                            <!-- Supplements Attributes -->
                            <div id="supplements-attributes" class="category-specific-attrs" style="display: none;">
                                <h5>Supplement Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Flavor</label>
                                        <select name="flavor" class="form-control">
                                            <option value="">Select Flavor</option>
                                            <option value="Chocolate" <?php echo isset($product_details['flavor']) && $product_details['flavor'] === 'Chocolate' ? 'selected' : ''; ?>>Chocolate</option>
                                            <option value="Vanilla" <?php echo isset($product_details['flavor']) && $product_details['flavor'] === 'Vanilla' ? 'selected' : ''; ?>>Vanilla</option>
                                            <option value="Strawberry" <?php echo isset($product_details['flavor']) && $product_details['flavor'] === 'Strawberry' ? 'selected' : ''; ?>>Strawberry</option>
                                            <option value="Unflavored" <?php echo isset($product_details['flavor']) && $product_details['flavor'] === 'Unflavored' ? 'selected' : ''; ?>>Unflavored</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Weight/Size</label>
                                        <select name="weight_size" class="form-control">
                                            <option value="">Select Weight/Size</option>
                                            <option value="250g" <?php echo isset($product_details['weight_size']) && $product_details['weight_size'] === '250g' ? 'selected' : ''; ?>>250g</option>
                                            <option value="500g" <?php echo isset($product_details['weight_size']) && $product_details['weight_size'] === '500g' ? 'selected' : ''; ?>>500g</option>
                                            <option value="1kg" <?php echo isset($product_details['weight_size']) && $product_details['weight_size'] === '1kg' ? 'selected' : ''; ?>>1kg</option>
                                            <option value="2kg" <?php echo isset($product_details['weight_size']) && $product_details['weight_size'] === '2kg' ? 'selected' : ''; ?>>2kg</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Serving Size</label>
                                        <input type="text" name="serving_size" class="form-control" value="<?php echo htmlspecialchars($product_details['serving_size'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Form</label>
                                        <select name="form" class="form-control">
                                            <option value="">Select Form</option>
                                            <option value="Powder" <?php echo isset($product_details['form']) && $product_details['form'] === 'Powder' ? 'selected' : ''; ?>>Powder</option>
                                            <option value="Capsule" <?php echo isset($product_details['form']) && $product_details['form'] === 'Capsule' ? 'selected' : ''; ?>>Capsule</option>
                                            <option value="Tablet" <?php echo isset($product_details['form']) && $product_details['form'] === 'Tablet' ? 'selected' : ''; ?>>Tablet</option>
                                            <option value="Liquid" <?php echo isset($product_details['form']) && $product_details['form'] === 'Liquid' ? 'selected' : ''; ?>>Liquid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label>Primary Goal</label>
                                    <select name="primary_goal" class="form-control">
                                        <option value="">Select Primary Goal</option>
                                        <option value="Muscle Growth" <?php echo isset($product_details['primary_goal']) && $product_details['primary_goal'] === 'Muscle Growth' ? 'selected' : ''; ?>>Muscle Growth</option>
                                        <option value="Weight Loss" <?php echo isset($product_details['primary_goal']) && $product_details['primary_goal'] === 'Weight Loss' ? 'selected' : ''; ?>>Weight Loss</option>
                                        <option value="Recovery" <?php echo isset($product_details['primary_goal']) && $product_details['primary_goal'] === 'Recovery' ? 'selected' : ''; ?>>Recovery</option>
                                        <option value="Energy" <?php echo isset($product_details['primary_goal']) && $product_details['primary_goal'] === 'Energy' ? 'selected' : ''; ?>>Energy</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Equipment Attributes -->
                            <div id="equipment-attributes" class="category-specific-attrs" style="display: none;">
                                <h5>Equipment Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Material</label>
                                        <select name="material" class="form-control">
                                            <option value="">Select Material</option>
                                            <option value="Steel" <?php echo isset($product_details['material']) && $product_details['material'] === 'Steel' ? 'selected' : ''; ?>>Steel</option>
                                            <option value="Cast Iron" <?php echo isset($product_details['material']) && $product_details['material'] === 'Cast Iron' ? 'selected' : ''; ?>>Cast Iron</option>
                                            <option value="Rubber" <?php echo isset($product_details['material']) && $product_details['material'] === 'Rubber' ? 'selected' : ''; ?>>Rubber</option>
                                            <option value="Plastic" <?php echo isset($product_details['material']) && $product_details['material'] === 'Plastic' ? 'selected' : ''; ?>>Plastic</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Weight</label>
                                        <input type="text" name="equipment_weight" class="form-control" value="<?php echo htmlspecialchars($product_details['weight'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label>Dimensions</label>
                                    <input type="text" name="dimensions" class="form-control" value="<?php echo htmlspecialchars($product_details['dimensions'] ?? ''); ?>">
                                </div>
                            </div>

                            <!-- Clothing Attributes -->
                            <div id="clothing-attributes" class="category-specific-attrs" style="display: none;">
                                <h5>Clothing Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="">Select Gender</option>
                                            <option value="Men" <?php echo isset($product_details['gender']) && $product_details['gender'] === 'Men' ? 'selected' : ''; ?>>Men</option>
                                            <option value="Women" <?php echo isset($product_details['gender']) && $product_details['gender'] === 'Women' ? 'selected' : ''; ?>>Women</option>
                                            <option value="Unisex" <?php echo isset($product_details['gender']) && $product_details['gender'] === 'Unisex' ? 'selected' : ''; ?>>Unisex</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Size</label>
                                        <select name="clothing_size" class="form-control">
                                            <option value="">Select Size</option>
                                            <option value="XS" <?php echo isset($product_details['size']) && $product_details['size'] === 'XS' ? 'selected' : ''; ?>>XS</option>
                                            <option value="S" <?php echo isset($product_details['size']) && $product_details['size'] === 'S' ? 'selected' : ''; ?>>S</option>
                                            <option value="M" <?php echo isset($product_details['size']) && $product_details['size'] === 'M' ? 'selected' : ''; ?>>M</option>
                                            <option value="L" <?php echo isset($product_details['size']) && $product_details['size'] === 'L' ? 'selected' : ''; ?>>L</option>
                                            <option value="XL" <?php echo isset($product_details['size']) && $product_details['size'] === 'XL' ? 'selected' : ''; ?>>XL</option>
                                            <option value="XXL" <?php echo isset($product_details['size']) && $product_details['size'] === 'XXL' ? 'selected' : ''; ?>>XXL</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Color</label>
                                        <input type="color" name="clothing_color" class="form-control" value="<?php echo htmlspecialchars($product_details['color'] ?? '#000000'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Material</label>
                                        <select name="clothing_material" class="form-control">
                                            <option value="">Select Material</option>
                                            <option value="Cotton" <?php echo isset($product_details['material']) && $product_details['material'] === 'Cotton' ? 'selected' : ''; ?>>Cotton</option>
                                            <option value="Polyester" <?php echo isset($product_details['material']) && $product_details['material'] === 'Polyester' ? 'selected' : ''; ?>>Polyester</option>
                                            <option value="Nylon" <?php echo isset($product_details['material']) && $product_details['material'] === 'Nylon' ? 'selected' : ''; ?>>Nylon</option>
                                            <option value="Spandex" <?php echo isset($product_details['material']) && $product_details['material'] === 'Spandex' ? 'selected' : ''; ?>>Spandex</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Accessories Attributes -->
                            <div id="accessories-attributes" class="category-specific-attrs" style="display: none;">
                                <h5>Accessory Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Material</label>
                                        <select name="accessory_material" class="form-control">
                                            <option value="">Select Material</option>
                                            <option value="Nylon" <?php echo isset($product_details['material']) && $product_details['material'] === 'Nylon' ? 'selected' : ''; ?>>Nylon</option>
                                            <option value="Leather" <?php echo isset($product_details['material']) && $product_details['material'] === 'Leather' ? 'selected' : ''; ?>>Leather</option>
                                            <option value="Silicone" <?php echo isset($product_details['material']) && $product_details['material'] === 'Silicone' ? 'selected' : ''; ?>>Silicone</option>
                                            <option value="Cotton" <?php echo isset($product_details['material']) && $product_details['material'] === 'Cotton' ? 'selected' : ''; ?>>Cotton</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Color</label>
                                        <input type="color" name="accessory_color" class="form-control" value="<?php echo htmlspecialchars($product_details['color'] ?? '#000000'); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label>Size</label>
                                    <select name="accessory_size" class="form-control">
                                        <option value="">Select Size</option>
                                        <option value="S" <?php echo isset($product_details['size']) && $product_details['size'] === 'S' ? 'selected' : ''; ?>>S</option>
                                        <option value="M" <?php echo isset($product_details['size']) && $product_details['size'] === 'M' ? 'selected' : ''; ?>>M</option>
                                        <option value="L" <?php echo isset($product_details['size']) && $product_details['size'] === 'L' ? 'selected' : ''; ?>>L</option>
                                        <option value="XL" <?php echo isset($product_details['size']) && $product_details['size'] === 'XL' ? 'selected' : ''; ?>>XL</option>
                                        <option value="One Size" <?php echo isset($product_details['size']) && $product_details['size'] === 'One Size' ? 'selected' : ''; ?>>One Size</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" name="submit" class="btn btn-primary">Update Product</button>
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editProductForm');
        const inputs = form.querySelectorAll('input, textarea, select');
        const categorySelect = document.getElementById('categorySelect');
        const classificationSelect = document.getElementById('classificationSelect');

        // Validation rules
        const validationRules = {
            product_name: {
                min: 3,
                message: 'Product name must be at least 3 characters long'
            },
            price: {
                min: 0.01,
                message: 'Price must be greater than 0'
            },
            stock: {
                min: 0,
                message: 'Stock cannot be negative'
            },
            description: {
                min: 10,
                message: 'Description must be at least 10 characters long'
            }
        };

        // Validate single input
        function validateInput(input) {
            const rule = validationRules[input.name];
            let isValid = true;
            let message = '';

            if (input.required && !input.value) {
                isValid = false;
                message = 'This field is required';
            } else if (rule) {
                if (input.type === 'number') {
                    isValid = parseFloat(input.value) >= rule.min;
                } else {
                    isValid = input.value.length >= rule.min;
                }
                message = rule.message;
            } else if (input.type === 'file' && input.files.length > 0) {
                const file = input.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB

                isValid = validTypes.includes(file.type) && file.size <= maxSize;
                message = !validTypes.includes(file.type) ? 
                    'Please select a valid image file (JPEG, PNG, or GIF)' : 
                    'File size must be less than 5MB';
            }

            input.classList.toggle('is-valid', isValid);
            input.classList.toggle('is-invalid', !isValid);

            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = message;
            }

            return isValid;
        }

        // Add validation on input
        inputs.forEach(input => {
            input.addEventListener('input', () => validateInput(input));
            input.addEventListener('blur', () => validateInput(input));
        });

        // Category change handler
        categorySelect.addEventListener('change', function() {
            loadClassifications(this.value, null);
        });

        // Form submission
        form.addEventListener('submit', function(event) {
            let isValid = true;

            inputs.forEach(input => {
                if (input.required && !input.value) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else if (input.type === 'file' && input.files.length > 0) { // Skip empty file input
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                } else if (input.required && !validateInput(input)) {
                    isValid = false;
                }
            });

            // Specifically check for required classification
            const classificationField = document.getElementById('classificationSelect');
            if (!classificationField.value) {
                classificationField.classList.add('is-invalid');
                isValid = false;
                console.error('Classification not selected');
            } else {
                console.log('Selected classification ID:', classificationField.value);
            }

            if (!isValid) {
                console.warn('Form has validation errors, but will submit anyway for debugging');
                // For debugging, we'll let the form submit anyway
                // event.preventDefault();
            } else {
                console.log('Form is valid, submitting...');
            }
        });

        // Function to load classifications
        async function loadClassifications(categoryId, selectedClassificationId) {
            console.log('Loading classifications for category ID:', categoryId);
            console.log('Selected classification ID:', selectedClassificationId);
            
            if (!categoryId) {
                classificationSelect.innerHTML = '<option value="">Select Classification</option>';
                return;
            }

            try {
                // Debug the URL being fetched
                const url = `get_subcategories.php?category_id=${categoryId}`;
                console.log('Fetching subcategories from:', url);
                
                const response = await fetch(url);
                const data = await response.json();
                
                console.log('Subcategories response:', data);
                
                classificationSelect.innerHTML = '<option value="">Select Classification</option>';
                
                let foundMatch = false;
                
                if (data.success && data.data) {
                    data.data.forEach(classification => {
                        const option = document.createElement('option');
                        option.value = classification.category_id;
                        option.textContent = classification.category_name;
                        
                        // Compare as strings to ensure proper comparison
                        if (selectedClassificationId && classification.category_id.toString() === selectedClassificationId.toString()) {
                            option.selected = true;
                            foundMatch = true;
                            console.log('Found matching classification:', classification.category_name);
                        }
                        
                        classificationSelect.appendChild(option);
                    });
                }
                
                // If we have a selected ID but no matching option was found
                if (selectedClassificationId && !foundMatch) {
                    console.log('No matching classification found, adding current product category');
                    
                    // Add the current product category as an option
                    const option = document.createElement('option');
                    option.value = selectedClassificationId;
                    option.textContent = '<?php echo isset($product["classification_name"]) ? htmlspecialchars($product["classification_name"]) : "Current Category"; ?>';
                    option.selected = true;
                    classificationSelect.appendChild(option);
                }
                
                // Trigger classification change event to update attribute sections
                const event = new Event('change');
                classificationSelect.dispatchEvent(event);
                
            } catch (error) {
                console.error('Error loading classifications:', error);
                
                // Add fallback option if AJAX fails
                if (selectedClassificationId) {
                    const option = document.createElement('option');
                    option.value = selectedClassificationId;
                    option.textContent = '<?php echo isset($product["classification_name"]) ? htmlspecialchars($product["classification_name"]) : "Current Category"; ?>';
                    option.selected = true;
                    classificationSelect.appendChild(option);
                }
            }
        }

        // Load initial classifications if category is selected
        const initialCategoryId = categorySelect.value;
        const initialClassificationId = <?php echo isset($product['category_id']) ? $product['category_id'] : 'null'; ?>;
        
        console.log('Initial category ID:', initialCategoryId);
        console.log('Initial classification ID:', initialClassificationId);
        
        if (initialCategoryId && initialClassificationId) {
            loadClassifications(initialCategoryId, initialClassificationId);
        } else {
            console.warn('Missing initial category or classification ID - cannot load classifications automatically');
            // If no initial classification, but we have a category, load classifications for that category
            if (initialCategoryId) {
                loadClassifications(initialCategoryId, null);
            }
        }

        // Update classifications when category changes
        categorySelect.addEventListener('change', function() {
            loadClassifications(this.value, null);
        });
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the stored category and attributes
        const storedCategory = '<?php echo isset($product["parent_category_name"]) ? htmlspecialchars($product["parent_category_name"]) : ""; ?>';
        const attributes = <?php echo !empty($attributes) ? json_encode($attributes) : '{}'; ?>;
        
        // Function to show category-specific attributes
        function showCategoryAttributes(category) {
            // Hide all attribute sections first
            document.querySelectorAll('.category-specific-attrs').forEach(div => {
                div.style.display = 'none';
            });

            if (!category) return;

            // Show the relevant attributes section based on category
            const categoryLower = category.toLowerCase();
            if (categoryLower.includes('supplement')) {
                const supplementsDiv = document.getElementById('supplements-attributes');
                if (supplementsDiv) {
                    supplementsDiv.style.display = 'block';
                    // Populate supplements attributes
                    if (attributes) {
                        const flavorSelect = document.querySelector('select[name="flavor"]');
                        if (flavorSelect) flavorSelect.value = attributes.flavor || '';
                        
                        const weightSelect = document.querySelector('select[name="weight_size"]');
                        if (weightSelect) weightSelect.value = attributes.weight_size || '';
                        
                        const servingInput = document.querySelector('input[name="serving_size"]');
                        if (servingInput) servingInput.value = attributes.serving_size || '';
                        
                        const formSelect = document.querySelector('select[name="form"]');
                        if (formSelect) formSelect.value = attributes.form || '';
                        
                        const goalSelect = document.querySelector('select[name="primary_goal"]');
                        if (goalSelect) goalSelect.value = attributes.primary_goal || '';
                    }
                }
            } else if (categoryLower.includes('equipment')) {
                const equipmentDiv = document.getElementById('equipment-attributes');
                if (equipmentDiv) {
                    equipmentDiv.style.display = 'block';
                    // Populate equipment attributes
                    if (attributes) {
                        const materialSelect = document.querySelector('select[name="material"]');
                        if (materialSelect) materialSelect.value = attributes.material || '';
                        
                        const weightInput = document.querySelector('input[name="equipment_weight"]');
                        if (weightInput) weightInput.value = attributes.weight || '';
                        
                        const dimInput = document.querySelector('input[name="dimensions"]');
                        if (dimInput) dimInput.value = attributes.dimensions || '';
                    }
                }
            } else if (categoryLower.includes('accessories')) {
                const accessoriesDiv = document.getElementById('accessories-attributes');
                if (accessoriesDiv) {
                    accessoriesDiv.style.display = 'block';
                    // Populate accessories attributes
                    if (attributes) {
                        const materialSelect = document.querySelector('select[name="accessory_material"]');
                        if (materialSelect) materialSelect.value = attributes.material || '';
                        
                        const colorInput = document.querySelector('input[name="accessory_color"]');
                        if (colorInput) colorInput.value = attributes.color || '#000000';
                        
                        const sizeSelect = document.querySelector('select[name="accessory_size"]');
                        if (sizeSelect) sizeSelect.value = attributes.size || '';
                    }
                }
            } else if (categoryLower.includes('clothing')) {
                const clothingDiv = document.getElementById('clothing-attributes');
                if (clothingDiv) {
                    clothingDiv.style.display = 'block';
                    // Populate clothing attributes
                    if (attributes) {
                        const genderSelect = document.querySelector('select[name="gender"]');
                        if (genderSelect) genderSelect.value = attributes.gender || '';
                        
                        const sizeSelect = document.querySelector('select[name="clothing_size"]');
                        if (sizeSelect) sizeSelect.value = attributes.size || '';
                        
                        const colorInput = document.querySelector('input[name="clothing_color"]');
                        if (colorInput) colorInput.value = attributes.color || '#000000';
                        
                        const materialSelect = document.querySelector('select[name="clothing_material"]');
                        if (materialSelect) materialSelect.value = attributes.material || '';
                    }
                }
            }
        }

        // Initial load of attributes based on stored category
        if (storedCategory) {
            showCategoryAttributes(storedCategory);
        }

        // Add event listener for category changes
        const categorySelect = document.getElementById('categorySelect');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                const selectedCategory = this.options[this.selectedIndex]?.text || '';
                showCategoryAttributes(selectedCategory);
            });
        }
    });
    </script>
    <!-- JavaScript for Category-based Attribute Display -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('categorySelect');
        const classificationSelect = document.getElementById('classificationSelect');
        
        if (!categorySelect || !classificationSelect) return;
        
        // Function to toggle category-specific attribute sections
        function toggleAttributeSections() {
            // Hide all attribute sections first
            document.querySelectorAll('.category-specific-attrs').forEach(section => {
                section.style.display = 'none';
            });
            
            // Check both category and classification
            const selectedCategory = categorySelect.options[categorySelect.selectedIndex]?.text || '';
            const selectedClassification = classificationSelect.options[classificationSelect.selectedIndex]?.text || '';
            
            console.log('Selected category:', selectedCategory);
            console.log('Selected classification:', selectedClassification);
            
            // Show the appropriate section based on category or classification
            if (selectedCategory.includes('Supplements') || selectedClassification.includes('Supplements')) {
                const suppDiv = document.getElementById('supplements-attributes');
                if (suppDiv) suppDiv.style.display = 'block';
            } else if (selectedCategory.includes('Equipment') || selectedClassification.includes('Equipment')) {
                const equDiv = document.getElementById('equipment-attributes');
                if (equDiv) equDiv.style.display = 'block';
            } else if (selectedCategory.includes('Clothing') || selectedClassification.includes('Clothing')) {
                const clothDiv = document.getElementById('clothing-attributes');
                if (clothDiv) clothDiv.style.display = 'block';
            } else if (selectedCategory.includes('Accessories') || selectedClassification.includes('Accessories')) {
                const accDiv = document.getElementById('accessories-attributes');
                if (accDiv) accDiv.style.display = 'block';
            }
            
            // Log the selected sections for debugging
            console.log('Attribute sections after toggle:', 
                document.querySelectorAll('.category-specific-attrs').length,
                Array.from(document.querySelectorAll('.category-specific-attrs')).filter(el => el.style.display === 'block').map(el => el.id)
            );
        }
        
        // Initial setup - try to determine the category from PHP data
        setTimeout(function() {
            const parentCategory = '<?php echo isset($product["parent_category_name"]) ? htmlspecialchars($product["parent_category_name"]) : ""; ?>';
            const subCategory = '<?php echo isset($product["classification_name"]) ? htmlspecialchars($product["classification_name"]) : ""; ?>';
            
            console.log('Parent category from PHP:', parentCategory);
            console.log('Subcategory from PHP:', subCategory);
            
            if (parentCategory.includes('Supplements') || subCategory.includes('Supplements')) {
                const suppDiv = document.getElementById('supplements-attributes');
                if (suppDiv) suppDiv.style.display = 'block';
            } else if (parentCategory.includes('Equipment') || subCategory.includes('Equipment')) {
                const equDiv = document.getElementById('equipment-attributes');
                if (equDiv) equDiv.style.display = 'block';
            } else if (parentCategory.includes('Clothing') || subCategory.includes('Clothing')) {
                const clothDiv = document.getElementById('clothing-attributes');
                if (clothDiv) clothDiv.style.display = 'block';
            } else if (parentCategory.includes('Accessories') || subCategory.includes('Accessories')) {
                const accDiv = document.getElementById('accessories-attributes');
                if (accDiv) accDiv.style.display = 'block';
            }
        }, 100);
        
        // Add event listeners for dropdown changes
        categorySelect.addEventListener('change', toggleAttributeSections);
        classificationSelect.addEventListener('change', toggleAttributeSections);
        
        // Use MutationObserver to detect when options are loaded into classification dropdown
        const observer = new MutationObserver(toggleAttributeSections);
        observer.observe(classificationSelect, { childList: true });
    });
    </script>
    <!-- Add JavaScript at the bottom to toggle product details visibility -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleDetailsBtn');
            const detailsSection = document.getElementById('productDetailsSection');
            
            toggleBtn.addEventListener('click', function() {
                if (detailsSection.style.display === 'none') {
                    detailsSection.style.display = 'block';
                    toggleBtn.textContent = 'Hide Details';
                } else {
                    detailsSection.style.display = 'none';
                    toggleBtn.textContent = 'Show Details';
                }
            });
        });
    </script>
</body>
</html>