<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $joining_fee = filter_input(INPUT_POST, 'joining_fee', FILTER_VALIDATE_FLOAT);
    $monthly_fee = filter_input(INPUT_POST, 'monthly_fee', FILTER_VALIDATE_FLOAT);
    
    if ($joining_fee === false || $monthly_fee === false) {
        exit(json_encode(['status' => 'error', 'message' => 'Invalid fee values']));
    }
    
    if ($joining_fee < 0 || $monthly_fee < 0) {
        exit(json_encode(['status' => 'error', 'message' => 'Fees cannot be negative']));
    }
    
    try {
        $conn->begin_transaction();

        // Update rates in memberships table
        $admin_id = $_SESSION['user_id'];
        $update_query = "UPDATE memberships 
                        SET rate_joining_fee = ?,
                            rate_monthly_fee = ?,
                            rate_updated_at = CURRENT_TIMESTAMP,
                            rate_updated_by = ?
                        WHERE membership_status = 'active'";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ddi", $joining_fee, $monthly_fee, $admin_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update rates in database");
        }

        // Create config directory if it doesn't exist
        $config_dir = __DIR__ . '/config';
        if (!file_exists($config_dir)) {
            if (!mkdir($config_dir, 0755, true)) {
                throw new Exception("Failed to create config directory");
            }
        }

        // Update the constants in configuration file
        $config_file = $config_dir . '/membership_rates.php';
        $config_content = "<?php\n";
        $config_content .= "// Auto-generated configuration file - DO NOT EDIT DIRECTLY\n";
        $config_content .= "define('JOINING_FEE', {$joining_fee});\n";
        $config_content .= "define('MONTHLY_FEE', {$monthly_fee});\n";
        
        if (file_put_contents($config_file, $config_content) === false) {
            throw new Exception("Failed to write config file");
        }

        $conn->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Rate update error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 