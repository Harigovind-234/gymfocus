<?php
session_start();
// Add debugging for session
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Debug session
error_log('Current session data: ' . print_r($_SESSION, true));

// Add this to check session configuration
echo "<!-- Session path: " . session_save_path() . " -->";

// Handle capacity update without using AJAX
if (isset($_POST['update_capacity'])) {
    $new_capacity = intval($_POST['new_capacity']);
    if ($new_capacity >= 1 && $new_capacity <= 100) {
        $_SESSION['max_capacity'] = $new_capacity;
        $success_message = "Capacity updated successfully!";
    } else {
        $error_message = "Invalid capacity value. Must be between 1 and 100.";
    }
    
    // Redirect to maintain the current view
    header("Location: view_bookings.php?date={$_GET['date']}&session={$_GET['session']}");
    exit();
}

include "connect.php";

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login2.php");
    exit();
}

$trainer_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Get selected date and session
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;
$selected_session = isset($_GET['session']) ? $_GET['session'] : '';

// Add this PHP function near the top of the file, after the session_start()
function isWeekend($date) {
    return (date('N', strtotime($date)) >= 6); // 6 = Saturday, 7 = Sunday
}

// Check slot capacity and get current count
$capacity_query = "SELECT COUNT(*) as count 
                  FROM slot_bookings 
                  WHERE booking_date = ? 
                  AND time_slot = ? 
                  AND cancelled_at IS NULL";
$stmt = $conn->prepare($capacity_query);
$stmt->bind_param("ss", $selected_date, $selected_session);
$stmt->execute();
$slot_count = $stmt->get_result()->fetch_assoc()['count'];

// Get max capacity from session, default to 30 if not set
$max_capacity = isset($_SESSION['max_capacity']) ? (int)$_SESSION['max_capacity'] : 30;
$available_slots = $max_capacity - $slot_count;

echo "<!-- DEBUG: max_capacity = " . (isset($_SESSION['max_capacity']) ? $_SESSION['max_capacity'] : 'not set') . " -->";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // First check for existing booking
    if (checkExistingBooking($conn, $trainer_id, $selected_date, $selected_session)) {
        $error_message = "You have already booked this " . $selected_session . " session for " . date('d-m-Y', strtotime($selected_date));
    }
    // Check if session is full
    elseif ($available_slots <= 0) {
        $error_message = "Sorry, this session is full. Please choose another date or session.";
    }
    // Then continue with other validations
    elseif ($selected_date === $today) {
        // existing code...
    }
    // ...
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings - FOCUS GYM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
    .booking-card {
        transition: transform 0.2s;
        border-left: 4px solid #0d6efd;
    }
    .booking-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .badge {
        padding: 8px 12px;
        font-size: 0.9em;
    }
    .progress {
        background-color: #e9ecef;
        border-radius: 10px;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }
    .progress-bar {
        transition: width 0.3s ease;
        border-radius: 10px;
    }
    .alert {
        border: none;
        padding: 0.75rem;
    }
    .card-header.bg-light {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid rgba(0,0,0,0.125);
    }
    .border-start {
        border-left: 1px solid #dee2e6;
    }
    @media (max-width: 768px) {
        .border-start {
            border-left: none;
            border-top: 1px solid #dee2e6;
            margin-top: 1rem;
            padding-top: 1rem;
        }
    }
    .h3 {
        font-weight: 600;
    }
    .text-muted {
        font-size: 0.875rem;
    }
    .alert-warning {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        color: #856404;
        padding: 1rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
    }

    .alert-warning i {
        font-size: 1.2rem;
        margin-right: 0.5rem;
    }

    .me-2 {
        margin-right: 0.5rem;
    }
    .no-availability .capacity-text {
        color: #dc3545;
        font-weight: bold;
    }

    .no-availability .capacity-fill {
        background: #dc3545;
    }

    .no-availability .capacity-warning {
        color: #dc3545;
        font-weight: bold;
        padding: 8px;
        background-color: rgba(220, 53, 69, 0.1);
        border-radius: 5px;
        margin-top: 10px;
    }

    .booking-button:disabled {
        background: #6c757d !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
        transform: none !important;
        opacity: 0.7;
    }

    .capacity-indicator {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        text-align: center;
    }

    .no-availability .capacity-text {
        color: #dc3545;
        font-weight: bold;
    }

    .no-availability .capacity-fill {
        background: #dc3545;
    }

    .no-availability .capacity-warning {
        color: #dc3545;
        font-weight: bold;
        padding: 8px;
        background-color: rgba(220, 53, 69, 0.1);
        border-radius: 5px;
        margin-top: 10px;
    }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center mb-0">Session Bookings</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Date Selection -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Select Date</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-3">
                                    <input type="date" name="date" value="<?php echo $selected_date; ?>" 
                                           class="form-control" required 
                                           min="<?php echo date('Y-m-d'); ?>"
                                           id="datePicker">
                                </div>
                            </div>
                        </div>

                        <!-- Session Buttons -->
                        <div class="row mb-4">
                            <div class="col-12 text-center">
                                <a href="?date=<?php echo $selected_date; ?>&session=morning" 
                                   class="btn btn-lg <?php echo $selected_session === 'morning' ? 'btn-warning' : 'btn-outline-warning'; ?> me-3">
                                    <i class="fas fa-sun"></i> Morning Session
                                </a>
                                <a href="?date=<?php echo $selected_date; ?>&session=evening" 
                                   class="btn btn-lg <?php echo $selected_session === 'evening' ? 'btn-info' : 'btn-outline-info'; ?>">
                                    <i class="fas fa-moon"></i> Evening Session
                                </a>
                            </div>
                        </div>

                        <!-- Bookings Display -->
                        <?php if($selected_session): ?>
                            <div class="bookings-section">
                                <?php if(isWeekend($selected_date)): ?>
                                    <div class="alert alert-warning mb-4">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Weekend Notice:</strong> You are viewing <?php echo date('l', strtotime($selected_date)); ?>. 
                                        Note that gym sessions are not available on weekends.
                                    </div>
                                <?php endif; ?>

                                <h4 class="text-center mb-4">
                                    <?php echo ucfirst($selected_session); ?> Session Bookings for <?php echo date('d M Y', strtotime($selected_date)); ?>
                                </h4>

                                <?php
                                $bookings_query = "SELECT sb.*, r.full_name, r.mobile_no, r.preferred_session
                                                 FROM slot_bookings sb
                                                 JOIN register r ON sb.user_id = r.user_id
                                                 JOIN staffassignedmembers sam ON sb.user_id = sam.member_id
                                                 WHERE sb.booking_date = ?
                                                 AND sb.time_slot = ?
                                                 AND sam.trainer_id = ?
                                                 AND sb.cancelled_at IS NULL
                                                 ORDER BY sb.created_at ASC";

                                $stmt = $conn->prepare($bookings_query);
                                $stmt->bind_param("ssi", $selected_date, $selected_session, $trainer_id);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if($result->num_rows > 0):
                                    while($booking = $result->fetch_assoc()):
                                ?>
                                    <div class="card mb-3 booking-card">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-1 text-center">
                                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                                </div>
                                                <div class="col-md-4">
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($booking['full_name']); ?></h5>
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['mobile_no']); ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-4">
                                                    <span class="badge <?php echo $booking['time_slot'] === $booking['preferred_session'] ? 'bg-success' : 'bg-info'; ?>">
                                                        <?php echo $booking['time_slot'] === $booking['preferred_session'] ? 'Regular Session' : 'Additional Session'; ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">
                                                        Booked on: <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-3 text-end">
                                                    <?php if($booking['booking_date'] == $today): ?>
                                                        <span class="badge bg-warning">Today's Session</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary">Upcoming</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">
                                            <?php if(isWeekend($selected_date)): ?>
                                                No bookings available for weekends
                                            <?php else: ?>
                                                No bookings found for this session
                                            <?php endif; ?>
                                        </h5>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Move this after the bookings display section -->
                            <div class="card mt-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar text-primary me-2"></i>Session Capacity Overview
                                    </h5>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCapacityModal">
                                        <i class="fas fa-edit"></i> Edit Capacity
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="capacity-indicator <?php echo $available_slots <= 5 ? 'low-availability' : ''; ?> <?php echo $available_slots <= 0 ? 'no-availability' : ''; ?>">
                                        <div class="capacity-text">
                                            <i class="fas fa-users"></i>
                                            <span>Available Slots: <?php echo $available_slots; ?> of <?php echo $max_capacity; ?></span>
                                        </div>
                                        <div class="capacity-bar">
                                            <div class="capacity-fill" style="width: <?php echo ($slot_count/$max_capacity) * 100; ?>%"></div>
                                        </div>
                                        <?php if($available_slots <= 0): ?>
                                            <div class="capacity-warning">Session Full! No slots available.</div>
                                        <?php elseif($available_slots <= 5): ?>
                                            <div class="capacity-warning">Limited slots remaining!</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-hand-point-up fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Please select a session to view bookings</h5>
                            </div>
                        <?php endif; ?>

                        <!-- Back Button -->
                        <div class="text-center mt-4">
                            <a href="staff.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const datePicker = document.getElementById('datePicker');
        
        // Auto-update when date changes
        datePicker.addEventListener('change', function() {
            const selectedDate = this.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date', selectedDate);
            window.location.href = currentUrl.toString();
        });

        // Add this code for capacity check
        const availableSlots = <?php echo $available_slots; ?>;
        const bookingButton = document.getElementById('bookingButton');
        
        if (availableSlots <= 0) {
            bookingButton.disabled = true;
            bookingButton.title = "This session is at full capacity";
        }
    });
    </script>

    <!-- Add this modal code right before the closing </body> tag -->
    <!-- Add Modal for Editing Capacity -->
    <div class="modal fade" id="editCapacityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Session Capacity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_capacity" class="form-label">New Capacity Limit:</label>
                            <input type="number" class="form-control" id="new_capacity" name="new_capacity" 
                                   min="1" max="100" value="<?php echo $max_capacity; ?>">
                            <small class="text-muted">Set the maximum number of slots available (1-100)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_capacity" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 