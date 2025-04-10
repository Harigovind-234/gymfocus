<?php
include 'connect.php';
session_start();

$subcategory_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Fetch subcategory details
$query = "SELECT * FROM categories WHERE category_id = ? AND is_subcategory = 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $subcategory_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subcategory = mysqli_fetch_assoc($result);

if (!$subcategory) {
    header("Location: subcategories.php?category_id=" . $category_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = mysqli_real_escape_string($conn, $_POST['subcategory_name']);
    
    $update_query = "UPDATE categories SET category_name = ? WHERE category_id = ? AND is_subcategory = 1";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "si", $new_name, $subcategory_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        header("Location: subcategories.php?category_id=" . $category_id);
        exit();
    } else {
        $error_message = "Error updating subcategory: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subcategory - Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
    <style>
        .edit-container {
            margin-top: 100px;
            padding: 20px;
        }
        
        .edit-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="edit-container">
        <div class="container">
            <div class="edit-card">
                <h2>Edit Subcategory</h2>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Subcategory Name</label>
                        <input type="text" name="subcategory_name" class="form-control" 
                               value="<?php echo htmlspecialchars($subcategory['category_name']); ?>" required>
                    </div>
                    
                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary">Update Subcategory</button>
                        <a href="subcategories.php?category_id=<?php echo $category_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>