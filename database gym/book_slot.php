<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}
include "connect.php";

$user_id = $_SESSION['user_id'];
$session = isset($_GET['session']) ? $_GET['session'] : '';
$booking_date = isset($_POST['booking_date']) ? $_POST['booking_date'] : date('Y-m-d');

// Get user's details and preferred session from register table
$user_query = "SELECT r.preferred_session, r.full_name, r.mobile_no 
               FROM register r 
               WHERE r.user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$preferred_session = $user_data['preferred_session'];

// Check existing booking for the selected date
$check_booking = "SELECT sb.*, r.full_name, r.mobile_no 
                 FROM slot_bookings sb 
                 JOIN register r ON sb.user_id = r.user_id 
                 WHERE sb.user_id = ? 
                 AND sb.booking_date = ? 
                 AND sb.cancelled_at IS NULL";
$stmt = $conn->prepare($check_booking);
$stmt->bind_param("is", $user_id, $booking_date);
$stmt->execute();
$existing_booking = $stmt->get_result()->fetch_assoc();

// Initialize error message
$error_message = "";

// Define current time and date
$current_time = date('H:i:s');
$current_hour = (int)date('H');
$current_date = date('Y-m-d');
$tomorrow_date = date('Y-m-d', strtotime('+1 day'));
$selected_date = isset($_POST['booking_date']) ? $_POST['booking_date'] : $tomorrow_date;

// Modify the booking window check
$is_valid_booking_time = true; // Always allow bookings for future dates
$gym_in_session = false; // Remove gym session restriction

// Check if user is trying to book their current session
$user_session = $user_data['preferred_session'];
$is_current_session = ($session === $user_session);

// Check if user is trying to book today's session
$is_today = ($selected_date === $current_date);

// Check if user is trying to book their session for today
$is_own_session_today = ($is_today && $session === $user_session);

// Check if user is trying to book evening session for today (morning members)
$is_evening_today = ($is_today && $session === 'evening' && $user_session === 'morning');

// Check if user is trying to book morning session for tomorrow (evening members)
$is_morning_tomorrow = ($selected_date === $tomorrow_date && $session === 'morning' && $user_session === 'evening');

// Check if user is enrolled in the session
$is_enrolled = ($session === $user_session);

// Check slot capacity and get current count
$capacity_query = "SELECT COUNT(*) as count 
                  FROM slot_bookings 
                  WHERE booking_date = ? 
                  AND time_slot = ? 
                  AND cancelled_at IS NULL";
$stmt = $conn->prepare($capacity_query);
$stmt->bind_param("ss", $booking_date, $session);
$stmt->execute();
$slot_count = $stmt->get_result()->fetch_assoc()['count'];

$max_capacity = 30;
$available_slots = $max_capacity - $slot_count;

// Add this right after you define $selected_date
$is_weekend = date('N', strtotime($selected_date)) >= 6; // 6 = Saturday, 7 = Sunday

// Function to check if session is over for today
function isSessionOver($session, $current_hour) {
    if ($session === 'morning' && $current_hour >= 10) {
        return true; // Morning session is over after 10 AM
    }
    if ($session === 'evening' && $current_hour >= 21) {
        return true; // Evening session is over after 9 PM
    }
    return false;
}

// Add this function at the top of the file
function checkExistingBooking($conn, $user_id, $booking_date, $session) {
    $query = "SELECT * FROM slot_bookings 
              WHERE user_id = ? 
              AND booking_date = ? 
              AND time_slot = ? 
              AND cancelled_at IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $user_id, $booking_date, $session);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // First check for existing booking
    if (checkExistingBooking($conn, $user_id, $selected_date, $session)) {
        $error_message = "You have already booked this " . $session . " session for " . date('d-m-Y', strtotime($selected_date));
    }
    // Then continue with other validations
    elseif ($selected_date === $current_date) {
        if (($user_session === 'morning' && $session === 'morning') || 
            ($user_session === 'evening' && $session === 'evening')) {
            $error_message = "You cannot book your regular session time for today.";
        }
        elseif ($current_hour < 10 || $current_hour >= 16) {
            $error_message = "Current day booking is only available between 10 AM - 3:59 PM.";
        }
    }
    elseif ($selected_date === $tomorrow_date) {
        if ($user_session === 'evening' && $session === 'morning') {
            if ($current_hour < 10 || $current_hour >= 16) {
                $error_message = "Next day morning session booking is only available between 10 AM - 3:59 PM.";
            }
        }
    }
    elseif ($existing_booking) {
        $error_message = "You already have a booking for " . date('d-m-Y', strtotime($booking_date));
    }
    else {
        // Proceed with booking if slots are available
        if ($available_slots > 0) {
            $insert_query = "INSERT INTO slot_bookings (user_id, time_slot, booking_date) 
                           VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iss", $user_id, $session, $booking_date);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Booking successful for " . date('d-m-Y', strtotime($booking_date));
                header("Location: index.php#status-header");
                exit();
            } else {
                $error_message = "Error making booking. Please try again.";
            }
        } else {
            $error_message = "No slots available for this session.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo ucfirst($session); ?> Session</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="booking-container" data-session="<?php echo $session; ?>" data-enrolled="<?php echo $is_enrolled ? 'true' : 'false'; ?>">
        <div class="booking-header">
            <div class="session-icon">
                <i class="fas <?php echo $session === 'morning' ? 'fa-sun' : 'fa-moon'; ?>"></i>
            </div>
            <h2>Book <?php echo ucfirst($session); ?> Session</h2>
            <div class="badge-container">
                <div class="booking-badge">
                    <i class="fas fa-clock"></i> Next Available
                </div>
            </div>
        </div>

        <div class="session-info">
            <?php if($session === 'morning'): ?>
                <h3>Morning Session</h3>
                <p>6:00 AM - 10:00 AM</p>
                <div class="booking-window">
                    <span class="window-label">Booking Window:</span>
                    <div class="window-time <?php echo $is_valid_booking_time ? 'open' : 'closed'; ?>">
                        <i class="fas <?php echo $is_valid_booking_time ? 'fa-unlock' : 'fa-lock'; ?>"></i>
                        10:00 AM - 3:59 PM & 9:00 PM - 9:59 PM
                    </div>
                </div>
            <?php else: ?>
                <h3>Evening Session</h3>
                <p>4:00 PM - 9:00 PM</p>
                <div class="booking-window">
                    <span class="window-label">Booking Window:</span>
                    <div class="window-time <?php echo $is_valid_booking_time ? 'open' : 'closed'; ?>">
                        <i class="fas <?php echo $is_valid_booking_time ? 'fa-unlock' : 'fa-lock'; ?>"></i>
                        10:00 AM - 3:59 PM & 9:00 PM - 9:59 PM
                    </div>
                </div>
            <?php endif; ?>
            <div class="capacity-indicator <?php echo $available_slots <= 5 ? 'low-availability' : ''; ?>">
                <div class="capacity-text">
                    <i class="fas fa-users"></i>
                    <span>Available Slots: <?php echo $available_slots; ?> of <?php echo $max_capacity; ?></span>
                </div>
                <div class="capacity-bar">
                    <div class="capacity-fill" style="width: <?php echo ($slot_count/$max_capacity) * 100; ?>%"></div>
                </div>
                <?php if($available_slots <= 5 && $available_slots > 0): ?>
                    <div class="capacity-warning">Limited slots remaining!</div>
                <?php elseif($available_slots <= 0): ?>
                    <div class="capacity-warning">Session Full!</div>
                <?php endif; ?>
            </div>
            <p class="user-session">Your Regular Session: <span><?php echo ucfirst($preferred_session); ?></span></p>
            <?php if($is_enrolled): ?>
                <div class="enrolled-badge">
                    <i class="fas fa-check-circle"></i> You are enrolled in this session
                    <div class="enrolled-tooltip">
                        <i class="fas fa-info-circle"></i>
                        <span>This is your regular session time. You cannot book additional slots for this session.</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="booking-status-container">
            <div class="status-item <?php echo $gym_in_session ? 'active' : 'inactive'; ?>">
                <div class="status-icon">
                    <i class="fas <?php echo $gym_in_session ? 'fa-running' : 'fa-pause'; ?>"></i>
                </div>
                <div class="status-text">
                    <?php if($gym_in_session): ?>
                        <span>Gym Session in Progress</span>
                        <small>Booking is restricted during active hours</small>
                    <?php else: ?>
                        <span>Gym Not in Session</span>
                        <small>Booking is available during specified windows</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="info-message">
            <i class="fas fa-info-circle"></i>
            <div>
                <p><strong>Booking Hours:</strong></p>
                <ul>
                    <li>✅ Morning Window: 10:00 AM - 3:59 PM</li>
                    <li>✅ Evening Window: 9:00 PM - 9:59 PM</li>
                </ul>
                <p><strong>Session Times:</strong></p>
                <ul>
                    <li>Morning Session: 6:00 AM - 10:00 AM</li>
                    <li>Evening Session: 4:00 PM - 9:00 PM</li>
                </ul>
                <small><i class="fas fa-calendar-times"></i> Note: No gym sessions available on Saturdays and Sundays.</small>
            </div>
        </div>

        <?php if($is_weekend): ?>
            <div class="weekend-error">
                <i class="fas fa-calendar-times"></i>
                Gym sessions are not available on weekends (Saturday & Sunday). Please select a weekday.
            </div>
        <?php endif; ?>

        <form class="booking-form" method="POST" id="bookingForm">
            <div class="form-group">
                <label for="booking_date">Choose Date:</label>
                <input type="date" 
                       id="booking_date" 
                       name="booking_date" 
                       min="<?php echo $tomorrow_date; ?>" 
                       value="<?php echo $tomorrow_date; ?>" 
                       required>
                <div class="date-hint">Select a weekday date</div>
            </div>

            <button type="submit" class="booking-button" id="bookingButton">
                <i class="fas <?php echo $session === 'morning' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                BOOK <?php echo strtoupper($session); ?> SESSION
            </button>
        </form>
        
        <a href="index.php#booking" class="back-to-dashboard">
            <i class="fas fa-arrow-left"></i> Back To Dashboard
        </a>
    </div>

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(145deg, #f8f9fa, #e9ecef);
    margin: 0;
    padding: 40px 20px;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.booking-container {
    max-width: 550px;
    width: 100%;
    margin: 0 auto;
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.booking-container:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 8px;
    background: linear-gradient(90deg, #ed563b, #f75c46);
}

.booking-container[data-session="evening"]:before {
    background: linear-gradient(90deg, #2c3e50, #4a6b8a);
}

.booking-header {
    text-align: center;
    margin-bottom: 30px;
    position: relative;
}

.badge-container {
    display: flex;
    justify-content: center;
    margin-top: 10px;
}

.booking-badge {
    background: #ed563b;
    color: white;
    font-size: 14px;
    padding: 5px 15px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
    box-shadow: 0 3px 10px rgba(237, 86, 59, 0.2);
}

.booking-container[data-session="evening"] .booking-badge {
    background: #2c3e50;
    box-shadow: 0 3px 10px rgba(44, 62, 80, 0.2);
}

.session-icon {
    width: 80px;
    height: 80px;
    background: #ed563b;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 5px 15px rgba(237, 86, 59, 0.3);
    transition: all 0.3s ease;
}

.booking-container[data-session="evening"] .session-icon {
    background: #2c3e50;
    box-shadow: 0 5px 15px rgba(44, 62, 80, 0.3);
}

.session-icon i {
    font-size: 40px;
    color: white;
}

.booking-header h2 {
    font-weight: 600;
    color: #232d39;
    margin: 0;
    font-size: 28px;
}

.session-info {
    text-align: center;
    background: #f8f9fa;
    padding: 25px;
    border-radius: 12px;
    margin: 25px 0;
    position: relative;
    box-shadow: 0 5px 15px rgba(0,0,0,0.03);
}

.session-info h3 {
    color: #ed563b;
    margin: 0 0 10px 0;
    font-size: 22px;
    font-weight: 600;
}

.booking-container[data-session="evening"] .session-info h3 {
    color: #2c3e50;
}

.session-info p {
    margin: 5px 0 15px;
    color: #666;
    font-size: 16px;
}

.user-session {
    color: #232d39 !important;
    font-weight: 500 !important;
    margin-top: 15px !important;
    padding-top: 15px;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.user-session span {
    color: #ed563b;
    font-weight: 600;
}

.booking-container[data-session="evening"] .user-session span {
    color: #2c3e50;
}

.booking-window {
    margin: 15px 0;
    padding: 10px 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}

.window-label {
    font-size: 14px;
    color: #666;
    display: block;
    margin-bottom: 8px;
}

.window-time {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.window-time.open {
    color: #28a745;
    background: rgba(40, 167, 69, 0.1);
}

.window-time.closed {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}

.booking-status-container {
    margin: 25px 0;
}

.status-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.status-item.active {
    background: rgba(220, 53, 69, 0.1);
    border-left: 4px solid #dc3545;
}

.status-item.inactive {
    background: rgba(40, 167, 69, 0.1);
    border-left: 4px solid #28a745;
}

.status-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    margin-right: 15px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

.status-item.active .status-icon i {
    color: #dc3545;
}

.status-item.inactive .status-icon i {
    color: #28a745;
}

.status-text span {
    display: block;
    color: #232d39;
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 5px;
}

.status-text small {
    color: #6c757d;
    font-size: 14px;
}

.form-group {
    margin-bottom: 25px;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    color: #232d39;
    font-weight: 500;
    font-size: 16px;
}

.date-hint {
    font-size: 13px;
    color: #6c757d;
    margin-top: 8px;
}

input[type="date"] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.3s ease;
    color: #495057;
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
}

input[type="date"]:focus {
    border-color: #ed563b;
    box-shadow: 0 0 0 3px rgba(237, 86, 59, 0.1);
    outline: none;
}

.booking-container[data-session="evening"] input[type="date"]:focus {
    border-color: #2c3e50;
    box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
}

.booking-button {
    width: 100%;
    padding: 16px;
    background: #ed563b;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(237, 86, 59, 0.2);
}

.booking-button:hover {
    background: #da442a;
    transform: translateY(-2px);
}

.booking-button:disabled {
    background: #868e96;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.booking-container[data-session="evening"] .booking-button {
    background: #2c3e50;
    box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);
}

.booking-container[data-session="evening"] .booking-button:hover {
    background: #34495e;
}

.booking-container[data-session="evening"] .booking-button:disabled {
    background: #868e96;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 15px;
    line-height: 1.5;
    box-shadow: 0 3px 10px rgba(0,0,0,0.03);
}

.error-message i {
    color: #dc3545;
    font-size: 18px;
    margin-top: 3px;
}

.info-message {
    display: flex;
    background: #e2f3ff;
    color: #0c5460;
    padding: 15px;
    border-radius: 10px;
    margin: 25px 0;
    align-items: flex-start;
    gap: 12px;
    font-size: 14px;
    line-height: 1.6;
    box-shadow: 0 3px 10px rgba(0,0,0,0.03);
}

.info-message i {
    color: #17a2b8;
    font-size: 18px;
    margin-top: 3px;
}

.info-message p {
    margin: 0 0 8px 0;
}

.info-message small {
    display: block;
    margin-top: 8px;
    color: #0c5460;
    opacity: 0.9;
}

.back-to-dashboard {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    text-decoration: none;
    font-weight: 500;
    margin-top: 20px;
    padding: 10px 0;
    transition: all 0.3s ease;
}

.back-to-dashboard:hover {
    color: #ed563b;
}

.booking-container[data-session="evening"] .back-to-dashboard:hover {
    color: #2c3e50;
}

.enrolled-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #28a745;
    color: white;
    padding: 12px 20px;
    border-radius: 50px;
    font-size: 14px;
    margin-top: 15px;
    box-shadow: 0 3px 10px rgba(40, 167, 69, 0.2);
    position: relative;
    cursor: help;
}

.enrolled-badge i {
    font-size: 16px;
}

.enrolled-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #2c3e50;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 13px;
    width: 250px;
    margin-bottom: 10px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    z-index: 100;
}

.enrolled-tooltip::before {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid #2c3e50;
}

.enrolled-badge:hover .enrolled-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-5px);
}

.enrolled-tooltip i {
    margin-right: 8px;
    color: #17a2b8;
}

.enrolled-tooltip span {
    display: block;
    line-height: 1.4;
}

/* Disable booking button when enrolled */
.booking-container[data-enrolled="true"] .booking-button {
    background: #6c757d;
    cursor: not-allowed;
    box-shadow: none;
}

.booking-container[data-enrolled="true"] .booking-button:hover {
    background: #6c757d;
    transform: none;
}

.booking-container[data-enrolled="true"] .booking-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    pointer-events: none;
}

@media (max-width: 576px) {
    body {
        padding: 20px 15px;
    }
    
    .booking-container {
        padding: 25px 20px;
    }
    
    .session-icon {
        width: 70px;
        height: 70px;
    }
    
    .session-icon i {
        font-size: 30px;
    }
    
    .booking-header h2 {
        font-size: 24px;
    }
}

.capacity-indicator {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    text-align: center;
}

.capacity-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 10px;
    color: #232d39;
    font-weight: 500;
}

.capacity-text i {
    color: #ed563b;
}

.booking-container[data-session="evening"] .capacity-text i {
    color: #2c3e50;
}

.capacity-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin: 8px 0;
}

.capacity-fill {
    height: 100%;
    background: #28a745;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.capacity-warning {
    color: #dc3545;
    font-size: 14px;
    font-weight: 500;
    margin-top: 8px;
}

.low-availability .capacity-fill {
    background: #dc3545;
}

.low-availability .capacity-text {
    color: #dc3545;
}

.low-availability .capacity-text i {
    color: #dc3545;
}

.weekend-error {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    border-radius: 8px;
    margin: 15px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.weekend-error i {
    color: #dc3545;
    font-size: 18px;
}

.weekend-day {
    background-color: #ffeeee !important;
    color: #d9534f !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('booking_date');
    
    // Remove jQuery datepicker - it conflicts with HTML5 date input
    $(dateInput).datepicker('destroy');
    
    // Set minimum date to today correctly
    const today = new Date();
    const formattedToday = today.toISOString().split('T')[0];
    dateInput.min = formattedToday;
    
    // Make sure the input type is "date"
    dateInput.type = "date";
    
    // Set default value to today if not already set
    if (!dateInput.value) {
        dateInput.value = formattedToday;
    }
});
</script>
</body>
</html>