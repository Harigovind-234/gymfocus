<?php
session_start();
require_once 'connect.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details with category and classification
$stmt = $conn->prepare("SELECT p.*, 
                       c.category_name as classification_name,
                       c.parent_id,
                       pc.category_name as category_name
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.category_id 
                       LEFT JOIN categories pc ON c.parent_id = pc.category_id 
                       WHERE p.product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// Decode the attributes from JSON
$attributes = !empty($product['attributes']) ? json_decode($product['attributes'], true) : null;

// Get category details
$category_query = "SELECT c.category_name AS classification_name, 
                          c.parent_id, 
                          pc.category_name AS category_name,
                          c.category_details AS category_details
                   FROM categories c 
                   LEFT JOIN categories pc ON c.parent_id = pc.category_id 
                   WHERE c.category_id = ?";

$stmt = mysqli_prepare($conn, $category_query);
mysqli_stmt_bind_param($stmt, "i", $product['category_id']);
mysqli_stmt_execute($stmt);
$category_result = mysqli_stmt_get_result($stmt);
$category_data = mysqli_fetch_assoc($category_result);

// Get available sizes from category details if it's a clothing product
$available_sizes = [];
if ($category_data['category_name'] === 'Clothing') {
    $category_details = json_decode($category_data['category_details'] ?? '{}', true);
    $available_sizes = $category_details['available_sizes'] ?? ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
}

// Enhanced stock check - redirect immediately if product not found or out of stock
if (!$product || $product['stock'] <= 0) {
    $_SESSION['error'] = "Product is out of stock!";
    header("Location: index.php#products");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order - <?php echo htmlspecialchars($product['product_name']); ?></title>
    
    <!-- Load CSS files first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css" rel="stylesheet">
    
    <!-- Load JavaScript files in correct order -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #232d39;
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        .main-container {
            padding: 80px 0;
            min-height: calc(100vh - 80px);
        }

        .order-card {
            background: #1a1a1a;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .product-side {
            background: linear-gradient(135deg, #ed563b, #ff8d6b);
            padding: 40px;
            height: 100%;
        }

        .product-image {
            width: 100%;
            height: 350px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .product-info h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0;
            color: #fff;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .categories-info {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .category-badge {
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.3s ease;
        }

        .category-badge i {
            font-size: 0.8rem;
        }

        .category-badge.main-category {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        .category-badge.sub-category {
            background: rgba(237,86,59,0.25);
            color: #fff;
        }

        .category-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .order-side {
            padding: 40px;
        }

        .price-tag {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ed563b;
            margin-bottom: 30px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            background: #2a2a2a;
            padding: 20px;
            border-radius: 15px;
        }

        .qty-btn {
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 12px;
            background: #ed563b;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .qty-btn:hover {
            background: #ff8d6b;
            transform: translateY(-2px);
        }

        #quantity {
            width: 80px;
            height: 45px;
            text-align: center;
            border: 2px solid #333;
            border-radius: 12px;
            background: transparent;
            color: white;
            font-size: 1.2rem;
        }

        .order-summary {
            background: #2a2a2a;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .summary-row.total {
            border-top: 2px solid #333;
            margin-top: 15px;
            padding-top: 15px;
            font-size: 1.3rem;
            font-weight: 600;
            color: #ed563b;
        }

        .place-order-btn {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, #ed563b, #ff8d6b);
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .place-order-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(237, 86, 59, 0.4);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
            margin-bottom: 30px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #ed563b;
            transform: translateX(-5px);
        }

        /* Success Modal */
        .success-modal .modal-content {
            background: #232d39;
            border-radius: 20px;
            color: #fff;
        }

        .success-modal .modal-header {
            border-bottom: 1px solid #333;
        }

        .success-modal .modal-footer {
            border-top: 1px solid #333;
        }

        .success-icon {
            font-size: 5rem;
            color: #ed563b;
            margin-bottom: 20px;
        }

        .payment-method-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option {
            background: #2a2a2a;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .payment-option.selected {
            border-color: #ed563b;
            background: #333;
        }

        .payment-icon {
            font-size: 24px;
            color: #ed563b;
        }

        .check-icon {
            color: #ed563b;
            display: none;
        }

        .payment-option.selected .check-icon {
            display: block;
        }

        .upi-info {
            background: #2a2a2a;
            border: 1px solid #333;
        }

        .text-primary {
            color: #ed563b !important;
        }

        .modal-content {
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #ed563b, #ff8d6b);
            border: none;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(237, 86, 59, 0.4);
        }

        .order-summary {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .payment-options {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .payment-option {
            padding: 15px;
            border: 2px solid transparent;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            background: #333;
        }

        .payment-option input:checked + label {
            color: #ed563b;
        }

        .order-confirmation {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 10px;
        }

        .form-check-input:checked {
            background-color: #ed563b;
            border-color: #ed563b;
        }

        .place-order-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none !important;
        }

        .price-container {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            padding: 10px;
            background: #2d2d2d;
            border-radius: 5px;
            width: fit-content;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: #ed563b;
            color: white;
            font-size: 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        #quantity {
            width: 50px;
            text-align: center;
            border: none;
            background: transparent;
            color: white;
            font-size: 18px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: white;
        }

        .total {
            border-top: 1px solid #ed563b;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
            color: #ed563b;
        }

        .place-order-btn {
            width: 100%;
            padding: 15px;
            background: #ed563b;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }

        .place-order-btn:disabled {
            background: #666;
            cursor: not-allowed;
        }

        .stock-warning {
            color: #ffd700;
            margin: 10px 0;
            font-size: 14px;
        }

        .product-attributes {
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            padding: 20px;
            margin-top: 10px;
            backdrop-filter: blur(5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .product-attributes h5 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ed563b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-attributes h5 i {
            font-size: 1rem;
        }

        .attributes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .attribute-item {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .attribute-item:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .attribute-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 5px;
        }

        .attribute-value {
            font-size: 1rem;
            color: #fff;
            font-weight: 500;
        }

        .product-description {
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .product-description h5 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ed563b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-description h5 i {
            font-size: 1rem;
        }

        .product-description p {
            font-size: 1rem;
            line-height: 1.7;
            color: rgba(255,255,255,0.9);
            margin-bottom: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 991px) {
            .product-side {
                padding: 30px;
            }
            
            .product-image {
                height: 280px;
            }
            
            .attributes-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        @media (max-width: 767px) {
            .product-info h2 {
                font-size: 1.8rem;
            }
            
            .product-image {
                height: 250px;
            }
            
            .attributes-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .attribute-item {
                padding: 10px;
            }
        }

        /* Product Information Layout */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .product-info h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0;
            color: #fff;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Category Badges */
        .categories-info {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .category-badge {
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .category-badge i {
            font-size: 0.8rem;
        }

        .category-badge.main-category {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        .category-badge.sub-category {
            background: rgba(237,86,59,0.25);
            color: #fff;
        }

        /* Professional Specifications Section */
        .specs-container {
            background: rgba(237, 86, 59, 0.25);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .specs-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .specs-title i {
            font-size: 1rem;
            color: #fff;
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .specs-item {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
        }

        .specs-label {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 8px;
        }

        .specs-value {
            font-size: 1.1rem;
            color: #fff;
            font-weight: 500;
            word-break: break-word;
        }

        /* Professional Description Section */
        .desc-container {
            background: rgba(237, 86, 59, 0.25);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .desc-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .desc-title i {
            font-size: 1rem;
            color: #fff;
        }

        .desc-content {
            font-size: 1rem;
            line-height: 1.7;
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
        }

        /* Responsive adjustments */
        @media (max-width: 991px) {
            .product-side {
                padding: 30px;
            }
            
            .product-image {
                height: 280px;
            }
            
            .specs-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .product-info h2 {
                font-size: 1.8rem;
            }
            
            .product-image {
                height: 250px;
            }
            
            .specs-item {
                padding: 12px;
            }
            
            .specs-label {
                font-size: 0.8rem;
            }
            
            .specs-value {
                font-size: 1rem;
            }
        }

        /* Color display styling */
        .color-display {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-swatch {
            display: inline-block;
            width: 25px;
            height: 25px;
            border-radius: 5px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

        /* Update receipt styling to match the image design */
        .receipt-container {
            background: #1e2126;
            color: #fff;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
        }

        .modal-content {
            background: #1e2126 !important;
            color: #fff !important;
            border-radius: 0;
            border: none;
        }

        .modal-header {
            background: #1e2126;
            color: #fff;
            border-bottom: none;
            padding: 15px 20px;
        }

        .modal-header h5 {
            font-size: 18px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header .btn-close {
            color: #fff;
            opacity: 0.8;
        }

        .receipt-header {
            text-align: center;
            padding: 20px 0;
        }

        .receipt-header h4 {
            color: #fff;
            font-size: 24px;
            font-weight: 500;
        }

        .text-muted {
            color: #9aa0a9 !important;
        }

        .customer-details h6,
        .order-details h6,
        .payment-summary h6 {
            font-size: 16px;
            font-weight: 500;
            color: #fff;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .border-bottom {
            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
        }

        .product-row {
            background: transparent;
            padding: 0;
            margin-bottom: 5px;
        }

        .specs-summary {
            color: #9aa0a9;
            font-size: 13px;
        }

        .payment-summary {
            background: transparent;
            border-radius: 0;
            padding: 0;
            margin-top: 20px;
        }

        .badge.bg-success {
            background-color: #28a745 !important;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
        }

        .text-primary {
            color: #ff5722 !important;
        }

        .modal-body {
            padding: 0 20px 20px 20px;
        }

        .modal-footer {
            border-top: none;
            justify-content: center;
            padding-top: 0;
            padding-bottom: 20px;
        }

        .row {
            margin-bottom: 8px;
        }

        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }
            
            .receipt-container, .receipt-container * {
                visibility: visible;
            }
            
            .receipt-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white !important;
                color: black !important;
            }
            
            .modal-header, .modal-footer {
                display: none !important;
            }
            
            .text-muted {
                color: #6c757d !important;
            }
            
            .text-primary {
                color: #ff5722 !important;
            }
        }

        .size-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .size-option {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: #2a2a2a;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .size-option:hover {
            background: #333;
        }

        .size-option input[type="checkbox"] {
            margin: 0;
            cursor: pointer;
        }

        .size-option label {
            margin: 0;
            cursor: pointer;
        }
    </style>

    <!-- Add this before closing </head> tag -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <div class="main-container">
        <div class="container">
            <a href="index.php#products" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Products
            </a>

            <div class="order-card">
                <div class="row g-0">
                    <!-- Product Preview Side -->
                    <div class="col-lg-6">
                        <div class="product-side">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            </div>
                            <div class="product-info">
                                <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
                                <div class="categories-info">
                                    <?php if (!empty($product['category_name'])): ?>
                                        <div class="category-badge main-category">
                                            <i class="fas fa-folder"></i>
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($product['classification_name'])): ?>
                                        <div class="category-badge sub-category">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($product['classification_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Professional Product Attributes Display -->
                                <?php 
                                if ($attributes && isset($attributes['product_details']) && !empty($attributes['product_details'])): 
                                ?>
                                <div class="specs-container">
                                    <h3 class="specs-title">
                                        <i class="fas fa-list-ul"></i> Product Specifications
                                    </h3>
                                    <div class="specs-grid">
                                        <?php foreach ($attributes['product_details'] as $key => $value): ?>
                                            <?php 
                                            // Only include color for clothing products
                                            $isColor = strtolower($key) == 'color';
                                            $isClothing = $category_data['category_name'] === 'Clothing';
                                            
                                            if (!empty($value) && (!$isColor || ($isColor && $isClothing))): 
                                            ?>
                                            <div class="specs-item">
                                                <div class="specs-label">
                                                    <?php echo strtoupper(str_replace('_', ' ', $key)); ?>
                                                </div>
                                                <div class="specs-value">
                                                    <?php 
                                                    // Special handling for color values
                                                    if (strtolower($key) == 'color' && preg_match('/#[0-9a-f]{6}/i', $value)) {
                                                        // Display color as both a swatch and hex value
                                                        echo '<div class="color-display">
                                                                <span class="color-swatch" style="background-color:'.htmlspecialchars($value).'"></span>
                                                                <span>'.htmlspecialchars($value).'</span>
                                                              </div>';
                                                    } elseif (is_array($value)) {
                                                        echo implode(', ', array_filter($value));
                                                    } else {
                                                        echo htmlspecialchars($value);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Professional Description Display -->
                                <div class="desc-container">
                                    <h3 class="desc-title">
                                        <i class="fas fa-info-circle"></i> Description
                                    </h3>
                                    <div class="desc-content">
                                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Form Side -->
                    <div class="col-lg-6">
                        <div class="order-side">
                            <div class="price-tag">
                                ₹<?php echo number_format($product['price'], 2); ?>
                            </div>

                            <div class="stock-status mb-3">
                                <?php if ($product['stock'] > 5): ?>
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i> In Stock
                                    </span>
                                <?php elseif ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                    <span class="text-warning">
                                        <i class="fas fa-exclamation-circle"></i> Low Stock (Only <?php echo $product['stock']; ?> left)
                                    </span>
                                <?php else: ?>
                                    <span class="text-danger">
                                        <i class="fas fa-times-circle"></i> Out of Stock
                                    </span>
                                <?php endif; ?>
                            </div>

                            <form id="orderForm">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                               
 
                                <div class="quantity-control">
                                    <button type="button" class="qty-btn decrement">-</button>
                                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                    <button type="button" class="qty-btn increment">+</button>
                                </div>
                              <!-- Price display section -->
                              <div class="price-summary">
                                    <div class="price-row">
                                        <span>Subtotal</span>
                                        <span id="subtotalPrice">₹0.00</span>
                                    </div>
                                    <div class="price-row">
                                        <span>GST (18%)</span>
                                        <span id="gst">₹<?php echo number_format($product['price'] * 0.18, 2); ?></span>
                                    </div>
                                    <div class="price-row total">
                                        <span>Total</span>
                                        <span id="total">₹<?php echo number_format($product['price'] * 1.18, 2); ?></span>
                                    </div>
                                </div>

                                <!-- Add Payment Method Selection -->
                                <div class="payment-methods mb-4">
                                    <h5>Select Payment Method</h5>
                                    <div class="payment-options">
                                        <div class="form-check payment-option">
                                            <input class="form-check-input" type="radio" name="payment_method" id="razorpayPayment" value="razorpay">
                                            <label class="form-check-label" for="razorpayPayment">
                                                <i class="fas fa-credit-card"></i> Pay Online (Razorpay)
                                                <small class="d-block text-muted">Credit/Debit Card, Net Banking, UPI</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Order Confirmation -->
                                <div class="order-confirmation mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirmOrder" required>
                                        <label class="form-check-label" for="confirmOrder">
                                            I confirm this order and agree to pay the total amount
                                        </label>
                                    </div>
                                </div>

                                <!-- Size Selection -->
                                <div class="form-group" id="sizeSelection" style="display: none;">
                                    <label>Select Sizes <span class="required">*</span></label>
                                    <div class="size-options">
                                        <?php foreach ($available_sizes as $size): ?>
                                        <div class="size-option">
                                            <input type="checkbox" name="sizes[]" value="<?php echo htmlspecialchars($size); ?>" id="size_<?php echo htmlspecialchars($size); ?>">
                                            <label for="size_<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <button type="submit" class="place-order-btn" id="submitOrder" disabled>
                                    <i class="fas fa-shopping-cart"></i>
                                    Place Order
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade success-modal" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h4 class="mb-3">Thank you for your order!</h4>
                    <p class="mb-0">Your order has been placed successfully.</p>
                </div>
                <div class="modal-footer">
                    <a href="index.php#products" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">y
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Select Payment Method</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Payment Methods -->
                    <div class="payment-methods">
                        <div class="payment-method-card" onclick="selectPaymentMethod('upi')">
                            <div class="d-flex align-items-center p-3 rounded payment-option" data-method="upi">
                                <i class="fas fa-qrcode payment-icon me-3"></i>
                                <div>
                                    <h6 class="mb-1">UPI Payment</h6>
                                    <p class="mb-0 text-muted">Pay using any UPI app</p>
                                </div>
                                <i class="fas fa-check-circle ms-auto check-icon"></i>
                            </div>
                        </div>

                        <!-- UPI Section -->
                        <div id="upiSection" class="text-center mt-4" style="display: none;">
                            <div class="upi-info p-4 rounded">
                                <i class="fas fa-mobile-alt fa-3x mb-3 text-primary"></i>
                                <h6 class="mb-3">UPI Payment Details</h6>
                                <p class="mb-2">UPI ID: yourgym@upi</p>
                                <p class="mb-0 text-muted">Open your UPI app and complete the payment</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary mt-4">
                        <h6 class="mb-3">Order Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="modalSubtotal"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>GST (18%):</span>
                            <span id="modalGst"></span>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top mt-2">
                            <strong>Total Amount:</strong>
                            <strong id="modalTotal" class="text-primary"></strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmOrder()">
                        <i class="fas fa-check-circle me-2"></i>Confirm Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill/Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title"><i class="fas fa-receipt"></i> Purchase Receipt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="receipt-container">
                        <!-- Store Logo/Header -->
                        <div class="receipt-header text-center mb-4">
                            <h4 class="mb-0">Focus Gym Store</h4>
                            <p class="mb-2 text-muted small">Your Fitness Partner</p>
                            <p class="mb-0 small">Receipt #<span id="receipt-number">--</span></p>
                            <p class="small">Date: <span id="receipt-date">--</span></p>
                        </div>

                        <!-- Customer Details -->
                        <div class="customer-details mb-4">
                            <h6 class="border-bottom pb-2">Customer Information</h6>
                            <div class="row mb-1">
                                <div class="col-4 text-muted">Name:</div>
                                <div class="col-8" id="customer-name">
                                    <?php 
                                    if (isset($_SESSION['user_id'])) {
                                        // Fetch user details from database to ensure we have current data
                                        $user_id = $_SESSION['user_id'];
                                        $user_query = "SELECT full_name, mobile_no FROM register WHERE user_id = ?";
                                        $stmt = $conn->prepare($user_query);
                                        $stmt->bind_param("i", $user_id);
                                        $stmt->execute();
                                        $user_result = $stmt->get_result();
                                        
                                        if ($user_data = $user_result->fetch_assoc()) {
                                            echo htmlspecialchars($user_data['full_name']);
                                            // Store mobile number for use below
                                            $mobile_no = $user_data['mobile_no'];
                                        } else {
                                            echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : '--';
                                        }
                                    } else {
                                        echo '--';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="row mb-1">
                                <div class="col-4 text-muted">Contact:</div>
                                <div class="col-8" id="customer-contact">
                                    <?php 
                                    if (isset($mobile_no)) {
                                        echo htmlspecialchars($mobile_no);
                                    } else if (isset($_SESSION['mobile_no'])) {
                                        echo htmlspecialchars($_SESSION['mobile_no']);
                                    } else {
                                        echo '--';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Order Details -->
                        <div class="order-details mb-4">
                            <h6 class="border-bottom pb-2">Order Details</h6>
                            <div class="product-row py-2">
                                <div class="row">
                                    <div class="col-7 fw-bold" id="receipt-product-name">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </div>
                                    <div class="col-2 text-center" id="receipt-quantity">1</div>
                                    <div class="col-3 text-end" id="receipt-product-price">
                                        ₹<?php echo number_format($product['price'], 2); ?>
                                    </div>
                                </div>
                                <div class="specs-summary small text-muted mt-1" id="receipt-specs">
                                    <!-- Product specs summary will be populated here -->
                                </div>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="payment-summary">
                            <h6 class="border-bottom pb-2">Payment Summary</h6>
                            <div class="row mb-2">
                                <div class="col-8 text-muted">Subtotal:</div>
                                <div class="col-4 text-end" id="receipt-subtotal">₹0.00</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-8 text-muted">GST (18%):</div>
                                <div class="col-4 text-end" id="receipt-gst">₹0.00</div>
                            </div>
                            <div class="row fw-bold">
                                <div class="col-8">Total:</div>
                                <div class="col-4 text-end text-primary" id="receipt-total">₹0.00</div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-8 text-muted">Payment Method:</div>
                                <div class="col-4 text-end" id="receipt-payment-method">--</div>
                            </div>
                            <div class="row">
                                <div class="col-8 text-muted">Payment Status:</div>
                                <div class="col-4 text-end">
                                    <span class="badge bg-success" id="receipt-payment-status">Paid</span>
                                </div>
                            </div>
                        </div>

                        <!-- Thank You Message -->
                        <div class="text-center mt-4 mb-2">
                            <p class="mb-1">Thank you for your purchase!</p>
                            <p class="small text-muted mb-0">For any queries, please contact our support team.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="print-receipt">
                        <i class="fas fa-print me-2"></i>Print Receipt
                    </button>
                    <a href="my_orders.php" class="btn btn-outline-light">
                        <i class="fas fa-shopping-bag me-2"></i>My Orders
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productPrice = <?php echo $product['price']; ?>;
            const maxStock = <?php echo $product['stock']; ?>;
            const quantityInput = document.getElementById('quantity');
            const decrementBtn = document.querySelector('.decrement');
            const incrementBtn = document.querySelector('.qty-btn.increment');

            // Decrement button click handler
            decrementBtn.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                    updateTotalPrice();
                }
            });

            // Increment button click handler
            incrementBtn.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value);
                if (currentValue < maxStock) {
                    quantityInput.value = currentValue + 1;
                    updateTotalPrice();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Maximum Stock Limit',
                        text: `Only ${maxStock} items available in stock.`
                    });
                }
            });

            // Direct input validation
            quantityInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value < 1) {
                    this.value = 1;
                } else if (value > maxStock) {
                    this.value = maxStock;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Maximum Stock Limit',
                        text: `Only ${maxStock} items available in stock.`
                    });
                }
                updateTotalPrice();
            });

            function updateTotalPrice() {
                const quantity = parseInt(document.getElementById('quantity').value);
                const subtotal = productPrice * quantity;
                const gst = subtotal * 0.18;
                const total = subtotal + gst;

                document.getElementById('subtotalPrice').textContent = '₹' + subtotal.toFixed(2);
                document.getElementById('gst').textContent = '₹' + gst.toFixed(2);
                document.getElementById('total').textContent = '₹' + total.toFixed(2);
                return total;
            }

            const submitButton = document.getElementById('submitOrder');
            const confirmCheckbox = document.getElementById('confirmOrder');
            const orderForm = document.getElementById('orderForm');

            // Enable/disable submit button based on confirmation
            confirmCheckbox.addEventListener('change', function() {
                submitButton.disabled = !this.checked;
            });

            // Show/hide size selection based on category
            const categoryName = '<?php echo $category_data['category_name'] ?? ''; ?>';
            const sizeSelection = document.getElementById('sizeSelection');
            if (categoryName === 'Clothing') {
                sizeSelection.style.display = 'block';
            }

            // Add validation for size selection
            orderForm.addEventListener('submit', function(e) {
                if (categoryName === 'Clothing') {
                    const selectedSizes = document.querySelectorAll('input[name="sizes[]"]:checked');
                    if (selectedSizes.length === 0) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Size Selection Required',
                            text: 'Please select at least one size for your clothing item.'
                        });
                        return;
                    }
                }
            });

            // Update the processRazorpayPayment and processCashPayment functions to include sizes
            function processRazorpayPayment(paymentData) {
                const formData = new FormData(orderForm);
                formData.append('payment_method', 'razorpay');
                formData.append('razorpay_payment_id', paymentData.razorpay_payment_id);
                formData.append('razorpay_order_id', paymentData.razorpay_order_id);
                formData.append('razorpay_signature', paymentData.razorpay_signature);
                
                // Add selected sizes to formData
                const selectedSizes = document.querySelectorAll('input[name="sizes[]"]:checked');
                selectedSizes.forEach(size => {
                    formData.append('sizes[]', size.value);
                });
                
                return fetch('process_order.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json());
            }

            function processCashPayment() {
                const formData = new FormData(orderForm);
                formData.append('payment_method', 'cash');
                
                // Add selected sizes to formData
                const selectedSizes = document.querySelectorAll('input[name="sizes[]"]:checked');
                selectedSizes.forEach(size => {
                    formData.append('sizes[]', size.value);
                });
                
                return fetch('process_order.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json());
            }

            // Function to show the receipt modal
            function showReceipt(orderData) {
                // Generate a receipt number (current timestamp with prefix)
                const receiptNumber = 'INV-' + Math.floor(Math.random() * 90000000 + 10000000);
                document.getElementById('receipt-number').textContent = receiptNumber;
                
                // Set the current date with time
                const today = new Date();
                const formattedDate = today.toLocaleDateString('en-IN', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric'
                }) + ', ' + today.toLocaleTimeString('en-IN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('receipt-date').textContent = formattedDate;
                
                // Product details
                document.getElementById('receipt-product-name').textContent = '<?php echo htmlspecialchars($product['product_name']); ?>';
                document.getElementById('receipt-quantity').textContent = orderData.quantity || 1;
                
                // Format price
                const price = parseFloat('<?php echo $product['price']; ?>');
                document.getElementById('receipt-product-price').textContent = '₹' + price.toFixed(2);
                
                // Populate product specifications
                const specsElement = document.getElementById('receipt-specs');
                specsElement.innerHTML = '';
                
                <?php if ($attributes && isset($attributes['product_details'])): ?>
                // Format specifications as a single line with key details
                const specDetails = [];
                
                <?php foreach($attributes['product_details'] as $key => $value): ?>
                    <?php 
                    // Include color only for clothing products, exclude for other categories
                    $isColor = strtolower($key) === 'color';
                    $isCategoryClothing = $category_data['category_name'] === 'Clothing';
                    
                    if (!empty($value) && (!$isColor || ($isColor && $isCategoryClothing))): 
                    ?>
                        <?php 
                        // Format the output based on whether value is array or string
                        if (is_array($value)) {
                            $formatted_value = implode(', ', array_filter($value));
                        } else {
                            $formatted_value = $value;
                        }
                        ?>
                        
                        specDetails.push('<?php echo ucwords(str_replace('_', ' ', $key)); ?>: <?php echo htmlspecialchars($formatted_value); ?>');
                    <?php endif; ?>
                <?php endforeach; ?>
                
                specsElement.textContent = specDetails.join(' • ');
                <?php endif; ?>
                
                // Payment details
                const quantity = parseInt(orderData.quantity || 1);
                const subtotal = price * quantity;
                const gst = subtotal * 0.18;
                const total = subtotal + gst;
                
                document.getElementById('receipt-subtotal').textContent = '₹' + subtotal.toFixed(2);
                document.getElementById('receipt-gst').textContent = '₹' + gst.toFixed(2);
                document.getElementById('receipt-total').textContent = '₹' + total.toFixed(2);
                
                // Payment method
                document.getElementById('receipt-payment-method').textContent = 
                    orderData.payment_method === 'razorpay' ? 'Online Payment' : 'Cash on Delivery';
                
                // Show the receipt modal
                const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                receiptModal.show();
            }

            // Update the order form submission handler to show the receipt
            orderForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                // Check stock before processing
                const currentStock = <?php echo $product['stock']; ?>;
                if (currentStock <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Out of Stock',
                        text: 'This product is no longer available.'
                    });
                    return;
                }
                
                if (!confirmCheckbox.checked) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Confirmation Required',
                        text: 'Please confirm your order before proceeding.'
                    });
                    return;
                }

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                try {
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                    const quantity = parseInt(document.getElementById('quantity').value);
                    const total = updateTotalPrice();

                    if (paymentMethod === 'razorpay') {
                        // Create Razorpay order
                        const orderResponse = await fetch('create_razorpay_order.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                amount: total,
                                product_id: <?php echo $product['product_id']; ?>,
                                quantity: quantity
                            })
                        }).then(res => res.json());

                        if (!orderResponse.order_id) {
                            throw new Error('Failed to create payment order');
                        }

                        // Initialize Razorpay
                        const options = {
                            key: "rzp_test_Fur0pLo5d2MztK",
                            amount: total * 100,
                            currency: "INR",
                            name: "Focus Gym",
                            description: "Product Order Payment",
                            order_id: orderResponse.order_id,
                            handler: async function(response) {
                                try {
                                    const result = await processRazorpayPayment(response);
                                    if (result.success) {
                                        // Show receipt instead of alert
                                        showReceipt({
                                            order_id: result.order_id,
                                            quantity: quantity,
                                            payment_method: 'razorpay',
                                            total_price: total
                                        });
                                    } else {
                                        throw new Error(result.message || 'Payment processing failed');
                                    }
                                } catch (error) {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: error.message || 'Failed to process order.'
                                    });
                                }
                            },
                            prefill: {
                                name: "<?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : ''; ?>",
                                email: "<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>",
                                contact: "<?php echo isset($_SESSION['mobile_no']) ? $_SESSION['mobile_no'] : ''; ?>"
                            },
                            theme: {
                                color: "#ed563b"
                            },
                            modal: {
                                ondismiss: function() {
                                    submitButton.disabled = false;
                                    submitButton.innerHTML = '<i class="fas fa-shopping-cart"></i> Place Order';
                                }
                            }
                        };

                        const rzp = new Razorpay(options);
                        rzp.open();
                    } else {
                        // Process cash payment
                        const result = await processCashPayment();
                        if (result.success) {
                            // Show receipt instead of alert
                            showReceipt({
                                order_id: result.order_id,
                                quantity: quantity,
                                payment_method: 'cash',
                                total_price: total
                            });
                        } else {
                            throw new Error(result.message || 'Failed to place order');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to process order. Please try again.'
                    });
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-shopping-cart"></i> Place Order';
                }
            });

            // Add event listener for print receipt button
            document.getElementById('print-receipt').addEventListener('click', function() {
                window.print();
            });
        });
    </script>

    <!-- Add these hidden fields to your form -->
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
</body>
</html>