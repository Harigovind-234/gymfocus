<?php
include 'connect.php';
session_start();

// Update the main products query
$query = "SELECT p.*,
          c.category_name as classification_name,
          c.parent_id,
          pc.category_name as category_name
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN categories pc ON c.parent_id = pc.category_id
          ORDER BY p.product_id DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching products: " . mysqli_error($conn));
}

// At the top of the file, after database connection
// Fetch categories for the dropdown
$categories_query = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_query);

if (!$categories_result) {
    die("Error fetching categories: " . mysqli_error($conn));
}

// Handle product addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $category_id = mysqli_real_escape_string($conn, $_POST['subcategory']); // Classification ID
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
    
    // Collect attributes based on product type
    $attributes = array();
    
    // Get category details (main category and classification)
    $category_query = "SELECT c.category_name AS classification_name, 
                              c.parent_id, 
                              pc.category_name AS category_name,
                              c.category_details AS category_details
                       FROM categories c 
                       LEFT JOIN categories pc ON c.parent_id = pc.category_id 
                       WHERE c.category_id = ?";
    
    $stmt = mysqli_prepare($conn, $category_query);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $category_result = mysqli_stmt_get_result($stmt);
    $category_data = mysqli_fetch_assoc($category_result);
    
    // Store category information in attributes
    $attributes['category_info'] = array(
        'category_id' => $category_id,
        'category_name' => $category_data['category_name'] ?? '',
        'classification_name' => $category_data['classification_name'] ?? ''
    );
    
    // If this is a clothing category, store the sizes in category details
    if ($category_data['category_name'] === 'Clothing' && isset($_POST['clothing_sizes'])) {
        $category_details = json_decode($category_data['category_details'] ?? '{}', true);
        $category_details['available_sizes'] = $_POST['clothing_sizes'];
        
        // Update the category details in the database
        $update_category_details = "UPDATE categories SET category_details = ? WHERE category_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_category_details);
        $category_details_json = json_encode($category_details);
        mysqli_stmt_bind_param($update_stmt, "si", $category_details_json, $category_id);
        mysqli_stmt_execute($update_stmt);
    }
    
    // Collect common attributes
    if (isset($_POST['flavor'])) $attributes['product_details']['flavor'] = $_POST['flavor'];
    if (isset($_POST['weight_size'])) $attributes['product_details']['weight_size'] = $_POST['weight_size'];
    if (isset($_POST['serving_size'])) $attributes['product_details']['serving_size'] = $_POST['serving_size'];
    if (isset($_POST['form'])) $attributes['product_details']['form'] = $_POST['form'];
    if (isset($_POST['primary_goal'])) $attributes['product_details']['primary_goal'] = $_POST['primary_goal'];
    if (isset($_POST['goals'])) $attributes['product_details']['goals'] = $_POST['goals'];
    if (isset($_POST['dietary_tags'])) $attributes['product_details']['dietary_tags'] = $_POST['dietary_tags'];
    if (isset($_POST['features'])) $attributes['product_details']['features'] = $_POST['features'];
    
    // Equipment attributes
    if (isset($_POST['material'])) $attributes['product_details']['material'] = $_POST['material'];
    if (isset($_POST['equipment_weight'])) $attributes['product_details']['equipment_weight'] = $_POST['equipment_weight'];
    if (isset($_POST['dimensions'])) $attributes['product_details']['dimensions'] = $_POST['dimensions'];
    
    // Accessory attributes
    if (isset($_POST['accessory_material'])) $attributes['product_details']['material'] = $_POST['accessory_material'];
    if (isset($_POST['color'])) $attributes['product_details']['color'] = $_POST['color'];
    if (isset($_POST['accessory_size'])) $attributes['product_details']['size'] = $_POST['accessory_size'];
    
    // Clothing attributes
    if (isset($_POST['clothing_sizes'])) {
        $attributes['product_details']['available_sizes'] = $_POST['clothing_sizes'];
    }
    if (isset($_POST['gender'])) {
        $attributes['product_details']['gender'] = $_POST['gender'];
    }
    if (isset($_POST['clothing_color'])) {
        $attributes['product_details']['color'] = $_POST['clothing_color'];
    }
    if (isset($_POST['clothing_material'])) {
        $attributes['product_details']['material'] = $_POST['clothing_material'];
    }
    
    // Convert attributes to JSON
    $attributes_json = json_encode($attributes, JSON_UNESCAPED_UNICODE);
    
    $validation_errors = [];
    
    // Validate inputs
    if (empty($product_name)) {
        $validation_errors[] = "Product name is required";
    }
    if (empty($description)) {
        $validation_errors[] = "Description is required";
    }
    if ($price === false || $price <= 0) {
        $validation_errors[] = "Price must be a valid number greater than 0";
    }
    if ($stock === false || $stock < 0) {
        $validation_errors[] = "Stock must be a valid number and cannot be negative";
    }
    
    if (empty($validation_errors)) {
        // Handle file upload
        $image_path = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            $target_dir = "uploads/products/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            }
        }

        // Prepare and execute the SQL statement
        $sql = "INSERT INTO products (product_name, description, price, category_id, image_path, stock, attributes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt === false) {
            die("Error preparing statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "ssdssss", $product_name, $description, $price, $category_id, $image_path, $stock, $attributes_json);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Product added successfully!";
            // Refresh the product list
            $result = mysqli_query($conn, $query);
            if (!$result) {
                die("Error refreshing products: " . mysqli_error($conn));
            }
        } else {
            $error_message = "Error adding product: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// Update the orders query section
$orders_query = "SELECT o.order_id, o.user_id, o.product_id, o.quantity, 
                        o.order_date, o.total_price, o.status,
                        o.payment_method, o.payment_status,
                        o.collection_time, o.collection_status,
                        o.created_at,
                        p.product_name, p.price,
                        r.full_name, r.mobile_no, r.address
                 FROM orders o
                 INNER JOIN products p ON o.product_id = p.product_id
                 INNER JOIN register r ON o.user_id = r.user_id
                 ORDER BY o.created_at DESC";

$orders_result = mysqli_query($conn, $orders_query);

// Add error debugging
if (!$orders_result) {
    echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
}
?>

<?php
// Add this debugging section at the bottom of your file
if (isset($error_message)) {
    echo "<div class='alert alert-danger'>Debug: $error_message</div>";
}

// Debug product query
echo "<div style='display:none;'>";
echo "Number of products found: " . mysqli_num_rows($result) . "<br>";
echo "SQL Query: $query<br>";
if (!$result) {
    echo "Query Error: " . mysqli_error($conn) . "<br>";
}
echo "</div>";

// Add this helper function for status colors
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'warning';
        case 'approved':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Add this helper function for payment status colors
function getPaymentStatusColor($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'warning';
        case 'paid':
            return 'success';
        case 'refunded':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Add this helper function for collection status colors
function getCollectionStatusColor($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'warning';
        case 'collected':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Training Studio - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
   
    <style>
        .header-area {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          background: #232d39 !important;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
          z-index: 1000;
        }

        

        .header-area .container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 0 15px;
        }

        .header-area .main-nav {
          display: flex;
          justify-content: space-between;
          align-items: center;
          height: 80px;
        }

        .header-area .logo {
          color: #ed563b;
          font-size: 24px;
          font-weight: 700;
          text-decoration: none;
          letter-spacing: 0.5px;
        }

        .header-area .logo em {
          color: #fff;
          font-style: normal;
          font-weight: 300;
        }

        .header-area .nav {
          display: flex;
          align-items: center;
          list-style: none;
          margin: 0;
          padding: 0;
        }

        .header-area .nav li {
          margin-left: 25px;
        }

        .header-area .nav li a {
          text-decoration: none;
          text-transform: uppercase;
          font-size: 13px;
          font-weight: 500;
          transition: color 0.3s ease;
        }

        .header-area .nav li a:hover,
        .header-area .nav li a.active {
          color: #ed563b;
        }

        .header-area .nav .main-button a {
          display: inline-block;
          background-color: #ed563b;
          color: #fff;
          padding: 10px 20px;
          border-radius: 5px;
          transition: background-color 0.3s ease;
        }

        .header-area .nav .main-button a:hover {
          background-color: #f9735b;
        }

        .admin-dashboard {
          max-width: 1200px;
          margin: 100px auto 0;
          padding: 0 15px;
        }

        .admin-card {
          background-color: #fff;
          border-radius: 5px;
          box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
          padding: 30px;
          margin-bottom: 30px;
        }

        .admin-card h3 {
          color: #232d39;
          margin-bottom: 20px;
          font-size: 23px;
          letter-spacing: 0.5px;
        }

        .admin-stats {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 20px;
        }

        .admin-stat-item {
          background-color: #f7f7f7;
          border-radius: 5px;
          padding: 20px;
          text-align: center;
        }

        .admin-stat-value {
          font-size: 36px;
          color: #ed563b;
          font-weight: 700;
        }

        .admin-stat-label {
          color:rgb(17, 15, 15);
          text-transform: uppercase;
          font-size: 13px;
        }

        .admin-table {
          width: 100%;
          border-collapse: collapse;
        }

        .admin-table th {
          background-color: #ed563b;
          color: white;
          padding: 12px;
          text-align: left;
          text-transform: uppercase;
          font-size: 13px;
        }

        .admin-table td {
          padding: 12px;
          border-bottom: 1px solid #eee;
          color: #232d39;
        }

        .admin-actions {
          display: flex;
          justify-content: space-between;
          margin-top: 20px;
        }

        .admin-button {
          display: inline-block;
          font-size: 13px;
          padding: 11px 17px;
          background-color: #ed563b;
          color: #fff;
          text-align: center;
          font-weight: 400;
          text-transform: uppercase;
          transition: all 0.3s;
          border: none;
          border-radius: 5px;
          cursor: pointer;
        }

        .admin-button:hover {
          background-color: #f9735b;
        }

        .view-details {
          background-color: #f9735b;
        }

        .view-details:hover {
          background-color: #f9735b;
        }

        .nav li a {
            cursor: pointer;
            color: #fff;
            text-decoration: none;
        }

        .nav li {
            list-style: none;
            margin: 0 15px;
        }

        .nav li a:hover {
            color: #ed563b;
        }

        /* Remove any interfering styles */
        .scroll-to-section {
            all: unset;
        }

        /* Main content spacing */
        .main-content {
            margin: 100px auto 30px;
            padding: 0 30px;
            max-width: 1400px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 40px; /* Increased gap between main sections */
        }
        
        .product-list {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        /* Orders List Styling */
        .orders-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        .orders-list h3 {
            color: #232d39;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ed563b;
        }

        /* Table Styling */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
            margin-bottom: 0;
        }

        .table thead th {
            background: #232d39;
            color: white;
            padding: 15px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
            border: none;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }

        .table tbody td {
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .table tbody tr:hover td {
            background: #f2f2f2;
            transform: scale(1.01);
        }

        .table tbody td:first-child {
            border-left: 1px solid #dee2e6;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .table tbody td:last-child {
            border-right: 1px solid #dee2e6;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        /* Badge Styling */
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 12px;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .bg-warning {
            background-color: #ffeeba !important;
            color: #856404 !important;
        }

        .bg-success {
            background-color: #d4edda !important;
            color: #155724 !important;
        }

        .bg-danger {
            background-color: #f8d7da !important;
            color: #721c24 !important;
        }

        .bg-info {
            background-color: #d1ecf1 !important;
            color: #0c5460 !important;
        }

        /* Action Buttons */
        .btn-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: nowrap;
        }

        .btn-group .btn {
            padding: 6px 12px;
            white-space: nowrap;
        }

        .btn-primary {
            background-color: #ed563b;
            border-color: #ed563b;
        }

        .btn-primary:hover {
            background-color: #f9735b;
            border-color: #f9735b;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(237, 86, 59, 0.2);
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }

        /* Center the container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            width: 100%;
        }

        /* Adjust responsive styles */
        @media (max-width: 1200px) {
            .main-content,
            .container,
            .admin-dashboard {
                padding: 0 20px;
            }
        }

        @media (max-width: 768px) {
            .main-content,
            .container,
            .admin-dashboard {
                padding: 0 15px;
            }
            
            .product-form,
            .product-list,
            .orders-list {
                padding: 20px;
            }

            .table-responsive {
                margin: 0;
            }
        }

        /* Individual section spacing */
        .product-form,
        .product-list,
        .orders-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px; /* Increased padding */
            margin-bottom: 0;
        }

        /* Section headers spacing */
        .product-form h3,
        .product-list h3,
        .orders-list h3 {
            color: #232d39;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px; /* Increased margin */
            padding-bottom: 15px;
            border-bottom: 2px solid #ed563b;
        }

        /* Form groups spacing */
        .form-group {
            margin-bottom: 25px; /* Increased margin between form elements */
        }

        /* Table container spacing */
        .table-responsive {
            margin-top: 20px;
        }

        /* Alert messages spacing */
        .alert {
            margin-bottom: 30px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                gap: 30px; /* Slightly reduced gap on mobile */
                padding: 0 20px;
            }

            .product-form,
            .product-list,
            .orders-list {
                padding: 20px;
            }
        }

        /* Improve spacing and alignment */
        .main-content {
            margin: 100px auto 30px;
            padding: 0 30px;
            max-width: 1400px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Consistent card styling */
        .product-form,
        .product-list,
        .orders-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 0; /* Remove bottom margin since we're using gap */
        }

        /* Enhance table actions */
        .btn-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: nowrap;
        }

        .btn-group .btn {
            padding: 6px 12px;
            white-space: nowrap;
        }

        /* Status badge enhancements */
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 12px;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        /* Add approval button styling */
        .btn-approve {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        /* Improve table cell alignment */
        .table td {
            vertical-align: middle !important;
        }

        .table td .small {
            margin-top: 4px;
        }

        /* Add these styles to your <style> section */
        .action-dropdown {
            position: relative;
            display: inline-block;
        }

        .action-btn {
            background: #ffc107;
            color: #856404;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #ffdb4d;
        }

        .action-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 1;
        }

        .action-dropdown-content a {
            color: #232d39;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .action-dropdown-content a:hover {
            background-color: #f8f9fa;
        }

        .action-dropdown:hover .action-dropdown-content {
            display: block;
        }

        .status-badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.pending {
            background: #ffeeba;
            color: #856404;
        }

        .status-badge.paid {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Add these styles to your <style> section */
        .payment-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
            align-items: flex-start;
        }

        .payment-method {
            font-size: 12px;
            padding: 2px 8px;
            background: #f8f9fa;
            border-radius: 12px;
            color: #6c757d;
        }

        .payment-method small {
            font-weight: 500;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .status-badge.pending {
            background: #ffeeba;
            color: #856404;
        }

        .status-badge.paid {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            margin-top: 5px;
            background: #ffc107;
            color: #856404;
            border: none;
            padding: 4px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #ffdb4d;
        }

        .action-dropdown-content {
            min-width: 140px;
            right: -10px;
            top: 100%;
        }
        .header-area .nav .main-button {
          margin-left: 20px;
          display: flex;
          align-items: center;
        }

        .header-area .nav .main-button a {
          background-color: #ed563b;
          color: #fff !important;
          padding: 15px 30px !important;
          border-radius: 5px;
          font-weight: 600;
          font-size: 14px !important;
          text-transform: uppercase;
          transition: all 0.3s ease;
          display: inline-block;
          letter-spacing: 0.5px;
          line-height: 1.4;
          white-space: nowrap;
        }

        .header-area .nav .main-button a:hover {
          background-color: #f9735b;
          color: #fff !important;
          transform: translateY(-2px);
          box-shadow: 0 4px 15px rgba(237, 86, 59, 0.2);
        }

        /* Fix for mobile responsiveness */
        @media (max-width: 991px) {
          .header-area .nav .main-button a {
            padding: 12px 25px !important;
            font-size: 13px !important;
          }
        }

        @media (max-width: 1200px) {
            .members-container {
                padding: 0 20px;
            }
        }

        @media (max-width: 768px) {
            .members-container {
                padding: 0 15px;
                margin-top: 90px;
            }
            
            .members-card {
                padding: 15px;
            }
        }

        #manage-category-btn {
            transition: all 0.3s ease;
        }

        #manage-category-btn .btn {
            width: 100%;
            text-align: left;
            padding: 10px 15px;
            background-color: #ed563b;
            border-color: #ed563b;
        }

        #manage-category-btn .btn:hover {
            background-color: #da4528;
            border-color: #da4528;
        }

        #manage-category-btn .fa {
            margin-right: 8px;
        }

        .category-specific-attrs {
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

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 5px;
        }

        .checkbox-group .form-check {
            min-width: 120px;
            margin: 0;
        }

        .form-check-input {
            margin-right: 5px;
        }

        .form-check-label {
            font-weight: normal;
            cursor: pointer;
        }

        .mt-3 {
            margin-top: 1rem;
        }

        .category-specific-attrs {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .category-specific-attrs .row + .row {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .form-group label {
            color: #232d39;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-check-input:checked {
            background-color: #ed563b;
            border-color: #ed563b;
        }

        .form-check-input:focus {
            border-color: #ed563b;
            box-shadow: 0 0 0 0.2rem rgba(237, 86, 59, 0.25);
        }

        .input-group-append .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        #new-classification-form .card {
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        #new-classification-form .card-body {
            padding: 15px;
        }

        #new-classification-form .input-group-append .btn {
            margin-left: 5px;
        }

        #add-classification-btn {
            background-color: #ed563b;
            border-color: #ed563b;
        }

        #add-classification-btn:hover {
            background-color: #da4528;
            border-color: #da4528;
        }

        #save-classification-btn {
            background-color: #28a745;
            border-color: #28a745;
        }

        #save-classification-btn:hover {
            background-color: #218838;
            border-color: #218838;
        }

        /* Add these to your existing styles */
        .category-info {
            line-height: 1.4;
        }

        .main-category {
            font-weight: 500;
            color: #232d39;
        }

        .text-muted {
            color: #6c757d;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .btn-group {
            display: flex;
            gap: 5px;
        }

        .btn-group .btn {
            padding: 4px 8px;
            font-size: 12px;
        }

        .btn-group .fa {
            margin-right: 3px;
        }

        .size-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .size-options .form-check {
            margin-right: 15px;
            margin-bottom: 5px;
        }
        
        .form-check-input:checked {
            background-color: #ed563b;
            border-color: #ed563b;
        }
        
        .form-check-label {
            padding-left: 5px;
        }
    </style>
</head>
<body>
<header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="admin.php" class="logo">Admin<em> Panel</em></a>
                        <ul class="nav">
                        <li><a href="admin.php">Home</a></li>
                        <li><a href="members.php">Members</a></li>
                        <li><a href="staff_management.php">Staff</a></li>
                        <li><a href="Payments_check.php">Payments</a></li>
                        <li><a href="products.php" class="active">Products</a></li>
                        <li class="main-button"><a href="login2.php">Logout</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>
<section class="main-content">
    <div class="main-content">
        <div class="container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="product-form">
                <h3>Add New Product</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Product Name</label>
                                <input type="text" name="product_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Price (₹)</label>
                                <input type="number" 
                                       name="price" 
                                       class="form-control" 
                                       step="0.01" 
                                       min="0.01"
                                       placeholder="Enter price"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" id="category" class="form-control" required>
                                    <option value="">Select a category</option>
                                    <?php 
                                    $categories_query = "SELECT category_id, category_name FROM categories WHERE is_subcategory = 0 ORDER BY category_name";
                                    $categories_result = mysqli_query($conn, $categories_query);
                                    
                                    while ($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                
                                
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Classification</label>
                                <div class="input-group">
                                    <select name="subcategory" id="subcategory" class="form-control" required>
                                        <option value="">Select Classification</option>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" id="add-classification-btn" style="display: none;">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Add New Classification Form -->
                                <div id="new-classification-form" style="display: none; margin-top: 10px;">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label>New Classification Name</label>
                                                <div class="input-group">
                                                    <input type="text" id="new-classification-name" class="form-control" 
                                                           placeholder="Enter new classification name">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-success" id="save-classification-btn">
                                                            Save
                                                        </button>
                                                        <button type="button" class="btn btn-danger" id="cancel-classification-btn">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Attributes Section -->
                    <div id="category-attributes" class="mt-4" style="display: none;">
                        <!-- Supplements Attributes -->
                        <div id="supplements-attributes" class="category-specific-attrs" data-category="supplements" style="display: none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Flavor</label>
                                        <select name="flavor" class="form-control">
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
                                        <select name="weight_size" class="form-control">
                                            <option value="">Select Size</option>
                                            <option value="1lb">1 kg</option>
                                            <option value="2lb">2 lkg</option>
                                            <option value="5lb">5 lkg</option>
                                            <option value="10lb">10 kgs</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Serving Size</label>
                                        <input type="number" name="serving_size" class="form-control" placeholder="Number of servings">
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Form</label>
                                        <select name="form" class="form-control">
                                            <option value="">Select Form</option>
                                            <option value="Powder">Powder</option>
                                            <option value="Capsule">Capsule</option>
                                            <option value="Tablet">Tablet</option>
                                            <option value="Liquid">Liquid</option>
                                            <option value="Gummy">Gummy</option>
                                            <option value="Bar">Bar</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Primary Goal</label>
                                        <select name="primary_goal" class="form-control">
                                            <option value="">Select Goal</option>
                                            <option value="Muscle Gain">Muscle Gain</option>
                                            <option value="Weight Loss">Weight Loss</option>
                                            <option value="Performance">Performance</option>
                                            <option value="Recovery">Recovery</option>
                                            <option value="Endurance">Endurance</option>
                                            <option value="General Health">General Health</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Secondary Goals</label>
                                        <div class="checkbox-group">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="goals[]" value="Energy" id="goal-energy">
                                                <label class="form-check-label" for="goal-energy">Energy</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="goals[]" value="Strength" id="goal-strength">
                                                <label class="form-check-label" for="goal-strength">Strength</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="goals[]" value="Recovery" id="goal-recovery">
                                                <label class="form-check-label" for="goal-recovery">Recovery</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Dietary Tags</label>
                                        <div class="checkbox-group">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="dietary_tags[]" value="Vegan" id="vegan">
                                                <label class="form-check-label" for="vegan">Vegan</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="dietary_tags[]" value="Vegetarian" id="vegetarian">
                                                <label class="form-check-label" for="vegetarian">Vegetarian</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="dietary_tags[]" value="Gluten-Free" id="gluten-free">
                                                <label class="form-check-label" for="gluten-free">Gluten-Free</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="dietary_tags[]" value="Dairy-Free" id="dairy-free">
                                                <label class="form-check-label" for="dairy-free">Dairy-Free</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="dietary_tags[]" value="Sugar-Free" id="sugar-free">
                                                <label class="form-check-label" for="sugar-free">Sugar-Free</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="dietary_tags[]" value="Keto" id="keto">
                                                <label class="form-check-label" for="keto">Keto</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Additional Features</label>
                                        <div class="checkbox-group">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="features[]" value="No Artificial Colors" id="no-artificial-colors">
                                                <label class="form-check-label" for="no-artificial-colors">No Artificial Colors</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="features[]" value="No Artificial Sweeteners" id="no-artificial-sweeteners">
                                                <label class="form-check-label" for="no-artificial-sweeteners">No Artificial Sweeteners</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="features[]" value="Non-GMO" id="non-gmo">
                                                <label class="form-check-label" for="non-gmo">Non-GMO</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="features[]" value="Organic" id="organic">
                                                <label class="form-check-label" for="organic">Organic</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Equipment Attributes -->
                        <div id="equipment-attributes" class="category-specific-attrs" data-category="equipment" style="display: none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Material</label>
                                        <select name="material" class="form-control">
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
                                        <input type="number" name="equipment_weight" class="form-control" placeholder="Weight in kg">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Dimensions</label>
                                        <input type="text" name="dimensions" class="form-control" placeholder="LxWxH in cm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accessories Attributes -->
                        <div id="accessories-attributes" class="category-specific-attrs" data-category="accessories" style="display: none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Material</label>
                                        <select name="accessory_material" class="form-control">
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
                                        <input type="color" name="color" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Size</label>
                                        <select name="accessory_size" class="form-control">
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
                        </div>

                        <!-- Clothing Attributes -->
                        <div id="clothing-attributes" class="category-specific-attrs" data-category="clothing" style="display: none;">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="">Select Gender</option>
                                            <option value="Men">Men</option>
                                            <option value="Women">Women</option>
                                            <option value="Unisex">Unisex</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Available Sizes</label>
                                        <div class="size-options">
                                            <?php 
                                            $size_options = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                                            foreach ($size_options as $size): 
                                            ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           name="clothing_sizes[]" 
                                                           value="<?php echo $size; ?>" 
                                                           id="size_<?php echo $size; ?>">
                                                    <label class="form-check-label" for="size_<?php echo $size; ?>">
                                                        <?php echo $size; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Color</label>
                                        <input type="color" name="clothing_color" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Material</label>
                                        <select name="clothing_material" class="form-control">
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
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" name="product_image" class="form-control" accept="image/*" required>
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" required min="0" value="0">
                    </div>

                    <button type="submit" class="btn btn-primary">Add Product</button>
                </form>
            </div>

            <div class="product-list">
                <h3>Product List</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Product Details</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result) > 0):
                                while ($row = mysqli_fetch_assoc($result)): 
                                    // Decode attributes if available
                                    $attributes = !empty($row['attributes']) ? json_decode($row['attributes'], true) : null;
                            ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                                             class="product-image" 
                                             alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td>
                                        <?php if (!empty($row['category_name']) || !empty($row['classification_name'])): ?>
                                            <div class="category-info">
                                                <?php if (!empty($row['category_name'])): ?>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                                <?php endif; ?>

                                                <?php if (!empty($row['classification_name'])): ?>
                                                    <br>
                                                    <span class="badge bg-info mt-1">
                                                        <?php echo htmlspecialchars($row['classification_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No category</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($attributes && isset($attributes['product_details']) && is_array($attributes['product_details'])):
                                            $count = 0;
                                            foreach ($attributes['product_details'] as $key => $value):
                                                if (!empty($value) && $count < 3): 
                                                    $count++;
                                        ?>
                                                    <div class="detail-item small">
                                                        <strong><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</strong>
                                                        <?php 
                                                        if (is_array($value)) {
                                                            if ($key === 'available_sizes') {
                                                                echo implode(', ', array_map(function($size) {
                                                                    return '<span class="badge bg-info">' . htmlspecialchars($size) . '</span>';
                                                                }, $value));
                                                            } else {
                                                                echo implode(', ', array_filter($value));
                                                            }
                                                        } else {
                                                            echo htmlspecialchars($value);
                                                        }
                                                        ?>
                                                    </div>
                                        <?php 
                                                endif;
                                            endforeach;
                                            
                                            if (count($attributes['product_details']) > 3):
                                        ?>
                                            <span class="text-muted small">+ <?php echo count($attributes['product_details']) - 3; ?> more</span>
                                        <?php
                                        endif;
                                    else:
                                        echo '<span class="text-muted small">No details available</span>';
                                    endif;
                                    ?>
                                    </td>
                                    <td>₹<?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <?php if ($row['stock'] > 0): ?>
                                            <span class="badge bg-success"><?php echo $row['stock']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Out of stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            <a href="delete_product.php?id=<?php echo $row['product_id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fa fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center">No products found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="orders-list">
                <h3>Orders</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <!-- <th>Collection</th> -->
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                    <tr>
                                        <td>
                                            #<?php echo htmlspecialchars($order['order_id']); ?>
                                            <div class="small text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['full_name']); ?>
                                            <div class="small text-muted">
                                                <?php echo htmlspecialchars($order['mobile_no']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            ₹<?php echo number_format($order['total_price'], 2); ?>
                                            <div class="small text-muted">
                                                Qty: <?php echo $order['quantity']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="payment-info">
                                                <span class="status-badge <?php echo strtolower($order['payment_status']); ?>">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </span>
                                                <div class="payment-method">
                                                    <small class="text-muted">
                                                        <?php echo ucfirst($order['payment_method']); ?>
                                                    </small>
                                                </div>
                                                <?php if ($order['payment_status'] === 'pending'): ?>
                                                    <?php if ($order['payment_method'] === 'cash'): ?>
                                                        <div class="action-dropdown">
                                                            <button class="action-btn">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                            <div class="action-dropdown-content">
                                                                <a href="#" class="approve-payment" data-order-id="<?php echo $order['order_id']; ?>">
                                                                    Confirm Cash Received
                                                                </a>
                                                                <a href="#" class="cancel-payment" data-order-id="<?php echo $order['order_id']; ?>">
                                                                    Cancel Payment
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($order['payment_method'] === 'razorpay'): ?>
                                                        <button class="btn btn-primary btn-sm mt-2 pay-now-btn" 
                                                                onclick="initiateRazorpayPayment(<?php echo $order['order_id']; ?>, <?php echo $order['total_price']; ?>, '<?php echo $order['razorpay_order_id']; ?>')">
                                                            Pay Now
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <!-- <td>
                                            <?php if ($order['collection_time']): ?>
                                                <div><?php echo date('d/m/Y H:i', strtotime($order['collection_time'])); ?></div>
                                            <?php endif; ?>
                                            <span class="badge bg-<?php echo getCollectionStatusColor($order['collection_status']); ?>">
                                                <?php echo ucfirst($order['collection_status'] ?? 'pending'); ?>
                                            </span>
                                        </td> -->
                                        <td>
                                            <?php if($order['status'] == 'pending'): ?>
                                                <span class="badge bg-success">Product Purchase</span>
                                            <?php elseif($order['status'] == 'cancelled'): ?>
                                                <div class="d-flex flex-column align-items-start">
                                                    <span class="badge bg-danger mb-2">Cancelled</span>
                                                    
                                                    <?php if($order['payment_status'] == 'paid'): ?>
                                                        <button class="btn btn-sm btn-primary process-refund-btn" 
                                                                data-order-id="<?php echo $order['order_id']; ?>"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#refundModal" 
                                                                data-amount="<?php echo $order['total_price']; ?>"
                                                                data-customer="<?php echo htmlspecialchars($order['full_name']); ?>">
                                                            <i class="fas fa-money-bill-wave"></i> Process Refund
                                                        </button>
                                                    <?php elseif($order['payment_status'] == 'pending_refund'): ?>
                                                        <span class="badge bg-info">Refund Pending</span>
                                                    <?php elseif($order['payment_status'] == 'refunded'): ?>
                                                        <span class="badge bg-success">Refunded</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-<?php echo getStatusColor($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="assets/js/payment.js"></script>
    <meta name="razorpay-key" content="<?php echo $razorpay_key; ?>">
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category');
        const subcategorySelect = document.getElementById('subcategory');
        const categoryAttributes = document.getElementById('category-attributes');
        const manageBtn = document.getElementById('manage-category-btn');
        const manageLink = document.getElementById('manage-subcategories-link');
        const addClassificationBtn = document.getElementById('add-classification-btn');
        const newClassificationForm = document.getElementById('new-classification-form');
        const saveClassificationBtn = document.getElementById('save-classification-btn');
        const cancelClassificationBtn = document.getElementById('cancel-classification-btn');
        const newClassificationInput = document.getElementById('new-classification-name');
        
        // Function to handle category change
        function handleCategoryChange() {
            const selectedCategoryId = categorySelect.value;
            const selectedCategory = categorySelect.options[categorySelect.selectedIndex].text;
            const attributesDivs = document.querySelectorAll('.category-specific-attrs');
            
            // Show/hide add classification button
            if (selectedCategoryId) {
                addClassificationBtn.style.display = 'block';
                
                // Update the manage subcategories link
                if (manageLink) {
                    manageLink.href = `subcategories.php?category_id=${selectedCategoryId}`;
                    manageLink.style.display = 'inline-block';
                }
            } else {
                addClassificationBtn.style.display = 'none';
                newClassificationForm.style.display = 'none';
                if (manageLink) {
                    manageLink.style.display = 'none';
                }
            }
            
            // Hide all attribute sections first
            attributesDivs.forEach(div => div.style.display = 'none');
            
            // Show the relevant attributes section
            if (selectedCategory) {
                categoryAttributes.style.display = 'block';
                
                // First try with exact name match
                const attributesId = selectedCategory.toLowerCase().replace(/\s+/g, '-') + '-attributes';
                let attributesDiv = document.getElementById(attributesId);
                
                // If not found, try with partial matching
                if (!attributesDiv) {
                    attributesDivs.forEach(div => {
                        const divCategory = div.getAttribute('data-category');
                        if (divCategory && selectedCategory.toLowerCase().includes(divCategory.toLowerCase())) {
                            attributesDiv = div;
                        }
                    });
                }
                
                if (attributesDiv) {
                    attributesDiv.style.display = 'block';
                }
                
                // Enable subcategory select and fetch subcategories
                loadSubcategories(selectedCategoryId);
            } else {
                categoryAttributes.style.display = 'none';
                subcategorySelect.disabled = true;
                subcategorySelect.innerHTML = '<option value="">Select Category First</option>';
            }
        }
        
        // Function to load subcategories/classifications
        function loadSubcategories(categoryId) {
            subcategorySelect.disabled = false;
            subcategorySelect.innerHTML = '<option value="">Loading...</option>';
            
            // Fetch subcategories using the selectedCategoryId
            fetch(`get_subcategories.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    subcategorySelect.innerHTML = '<option value="">Select Classification</option>';
                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(subcategory => {
                            const option = document.createElement('option');
                            option.value = subcategory.category_id;
                            option.textContent = subcategory.category_name;
                            subcategorySelect.appendChild(option);
                        });
                        // Enable the subcategory select after loading options
                        subcategorySelect.disabled = false;
                    } else {
                        subcategorySelect.innerHTML = '<option value="">No classifications found</option>';
                        subcategorySelect.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    subcategorySelect.innerHTML = '<option value="">Error loading classifications</option>';
                    subcategorySelect.disabled = true;
                });
        }
        
        // Add change event listener to category select
        categorySelect.addEventListener('change', handleCategoryChange);
        
        // Initial call to set up the form correctly
        if (categorySelect.value) {
            handleCategoryChange();
        }
        
        // Handle add classification button click
        addClassificationBtn.addEventListener('click', function() {
            newClassificationForm.style.display = 'block';
            newClassificationInput.focus();
        });
        
        // Handle save classification button click
        saveClassificationBtn.addEventListener('click', function() {
            const categoryId = categorySelect.value;
            const classificationName = newClassificationInput.value.trim();
            
            if (!classificationName) {
                alert('Please enter a classification name');
                return;
            }
            
            // Send AJAX request to save new classification
            fetch('add_classification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `category_id=${encodeURIComponent(categoryId)}&classification_name=${encodeURIComponent(classificationName)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Add new option to subcategory select
                    const option = document.createElement('option');
                    option.value = data.classification_id;
                    option.textContent = data.classification_name;
                    subcategorySelect.appendChild(option);
                    
                    // Select the new classification
                    subcategorySelect.value = data.classification_id;
                    
                    // Clear and hide the form
                    newClassificationInput.value = '';
                    newClassificationForm.style.display = 'none';
                    
                    // Show success message
                    alert('Classification added successfully!');
                } else {
                    throw new Error(data.message || 'Error adding classification');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding classification: ' + error.message);
            });
        });
        
        // Handle cancel button click
        cancelClassificationBtn.addEventListener('click', function() {
            newClassificationInput.value = '';
            newClassificationForm.style.display = 'none';
        });
    });

    function initiateRazorpayPayment(orderId, amount, razorpayOrderId) {
        var options = {
            "key": document.querySelector('meta[name="razorpay-key"]').content,
            "amount": amount * 100,
            "currency": "INR",
            "name": "Focus Gym",
            "description": "Order #" + orderId,
            "order_id": razorpayOrderId,
            "prefill": {
                "name": "Customer Name",
                "email": "customer@example.com",
                "contact": "9999999999"
            },
            "theme": {
                "color": "#F37254"
            }
        };

        var rzp = initializeRazorpay({
            ...options,
            order_id: orderId // Pass the local order ID for verification
        });
        var rzp = new Razorpay(options);
        rzp.open();
    }

    // Add this to your existing JavaScript section
    document.querySelector('form').addEventListener('submit', function(e) {
        const priceInput = document.querySelector('input[name="price"]');
        const price = parseFloat(priceInput.value);
        
        if (isNaN(price) || price <= 0) {
            e.preventDefault();
            alert('Please enter a valid price greater than 0');
            priceInput.focus();
            return false;
        }
        
        // Format the price to 2 decimal places
        priceInput.value = price.toFixed(2);
    });

    // Add input validation for price field
    document.querySelector('input[name="price"]').addEventListener('input', function(e) {
        let value = this.value;
        
        // Remove any non-numeric characters except decimal point
        value = value.replace(/[^\d.]/g, '');
        
        // Ensure only one decimal point
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Limit to 2 decimal places
        if (parts.length === 2 && parts[1].length > 2) {
            value = parseFloat(value).toFixed(2);
        }
        
        this.value = value;
    });

    // Refund Modal Functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Handle refund button clicks
        const refundButtons = document.querySelectorAll('.process-refund-btn');
        
        refundButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const amount = this.getAttribute('data-amount');
                const customer = this.getAttribute('data-customer');
                
                document.getElementById('refund-order-id').value = orderId;
                document.getElementById('display-order-id').textContent = '#' + orderId;
                document.getElementById('refund-amount').textContent = parseFloat(amount).toFixed(2);
                document.getElementById('refund-customer').textContent = customer;
            });
        });
        
        // Handle refund form submission
        document.getElementById('submitRefund').addEventListener('click', function() {
            const confirmCheckbox = document.getElementById('confirmRefund');
            
            if (confirmCheckbox.checked) {
                document.getElementById('refundForm').submit();
            } else {
                alert('Please confirm that you want to process this refund.');
            }
        });
    });
    </script>

    <!-- Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refundModalLabel">Process Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="refundForm" action="process_refund.php" method="POST">
                        <input type="hidden" name="order_id" id="refund-order-id">
                        
                        <div class="mb-3">
                            <label class="form-label">Order ID</label>
                            <div class="form-control" id="display-order-id" readonly></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <div class="form-control" id="refund-customer" readonly></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Refund Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <div class="form-control" id="refund-amount" readonly></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="refund-notes" class="form-label">Refund Notes (Optional)</label>
                            <textarea class="form-control" id="refund-notes" name="notes" rows="3" placeholder="Add notes about this refund"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmRefund" required>
                            <label class="form-check-label" for="confirmRefund">
                                I confirm that this refund should be processed.
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitRefund">Process Refund</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


