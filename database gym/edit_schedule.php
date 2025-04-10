<?php
session_start();
include "connect.php";

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login2.php");
    exit();
}

// Get session and day from URL parameters
$session = $_GET['session'] ?? '';
$day = $_GET['day'] ?? '';

// Fetch current schedule data
$stmt = $conn->prepare("SELECT * FROM workout_schedule WHERE session_time = ? AND day = ?");
$stmt->bind_param("ss", $session, $day);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule - Focus Gym</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas <?php echo $session === 'morning' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                            Edit <?php echo ucfirst($session); ?> Schedule - <?php echo ucfirst($day); ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="timetable-card">
                            <form action="update_schedule.php" method="POST">
                                <input type="hidden" name="session" value="<?php echo htmlspecialchars($session); ?>">
                                <input type="hidden" name="day" value="<?php echo htmlspecialchars($day); ?>">
                                
                                <table class="table table-hover custom-table">
                                    <thead>
                                        <tr>
                                            <th class="time-column">Time Slot</th>
                                            <th>Workout Details</th>
                                            <th>Exercise Details</th>
                                            <th>Training Parameters</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="time-slot">
                                                <div class="time-container">
                                                    <span class="time-main">
                                                        <?php echo $session === 'morning' ? '6:00 AM - 8:00 AM' : '6:00 PM - 8:00 PM'; ?>
                                                    </span>
                                                    <span class="time-sub"><?php echo ucfirst($session); ?> Session</span>
                                                    <div class="form-group mt-3">
                                                        <label>Maximum Capacity</label>
                                                        <input type="number" 
                                                               class="form-control" 
                                                               name="max_capacity" 
                                                               value="<?php echo htmlspecialchars($schedule['max_capacity'] ?? '20'); ?>"
                                                               min="1" max="50">
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="workout-cell">
                                                <div class="form-group">
                                                    <label>Workout Title</label>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="workout_title" 
                                                           value="<?php echo htmlspecialchars($schedule['workout_title'] ?? 'Chest, Biceps & Shoulders'); ?>">
                                                </div>
                                                
                                                <div class="form-group mt-3">
                                                    <label>Intensity Level</label>
                                                    <select class="form-control" name="intensity">
                                                        <option value="High Intensity" <?php echo ($schedule['intensity'] ?? '') === 'High Intensity' ? 'selected' : ''; ?>>High Intensity</option>
                                                        <option value="Medium Intensity" <?php echo ($schedule['intensity'] ?? '') === 'Medium Intensity' ? 'selected' : ''; ?>>Medium Intensity</option>
                                                        <option value="Low Intensity" <?php echo ($schedule['intensity'] ?? '') === 'Low Intensity' ? 'selected' : ''; ?>>Low Intensity</option>
                                                    </select>
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label>Target Muscle Groups</label>
                                                    <select class="form-control" name="target_muscles[]" multiple>
                                                        <option value="chest" <?php echo (isset($schedule['target_muscles']) && strpos($schedule['target_muscles'], 'chest') !== false) ? 'selected' : ''; ?>>Chest</option>
                                                        <option value="biceps" <?php echo (isset($schedule['target_muscles']) && strpos($schedule['target_muscles'], 'biceps') !== false) ? 'selected' : ''; ?>>Biceps</option>
                                                        <option value="triceps" <?php echo (isset($schedule['target_muscles']) && strpos($schedule['target_muscles'], 'triceps') !== false) ? 'selected' : ''; ?>>Triceps</option>
                                                        <option value="shoulders" <?php echo (isset($schedule['target_muscles']) && strpos($schedule['target_muscles'], 'shoulders') !== false) ? 'selected' : ''; ?>>Shoulders</option>
                                                        <option value="back" <?php echo (isset($schedule['target_muscles']) && strpos($schedule['target_muscles'], 'back') !== false) ? 'selected' : ''; ?>>Back</option>
                                                        <option value="legs" <?php echo (isset($schedule['target_muscles']) && strpos($schedule['target_muscles'], 'legs') !== false) ? 'selected' : ''; ?>>Legs</option>
                                                        <option value="core" <?php echo (isset($schedule['target_muscles']) && strpos($schedule['target_muscles'], 'core') !== false) ? 'selected' : ''; ?>>Core</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td class="exercise-cell">
                                                <div class="exercise-list">
                                                    <div class="form-group">
                                                        <label>Exercise 1</label>
                                                        <input type="text" class="form-control" name="exercises[]" value="<?php echo htmlspecialchars($schedule['exercise1'] ?? 'Bench Press'); ?>">
                                                        <div class="input-group mt-2">
                                                            <input type="number" class="form-control" name="sets[]" placeholder="Sets" value="<?php echo htmlspecialchars($schedule['sets1'] ?? '3'); ?>">
                                                            <input type="number" class="form-control" name="reps[]" placeholder="Reps" value="<?php echo htmlspecialchars($schedule['reps1'] ?? '12'); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="form-group mt-3">
                                                        <label>Exercise 2</label>
                                                        <input type="text" class="form-control" name="exercises[]" value="<?php echo htmlspecialchars($schedule['exercise2'] ?? 'Bicep Curls'); ?>">
                                                        <div class="input-group mt-2">
                                                            <input type="number" class="form-control" name="sets[]" placeholder="Sets" value="<?php echo htmlspecialchars($schedule['sets2'] ?? '3'); ?>">
                                                            <input type="number" class="form-control" name="reps[]" placeholder="Reps" value="<?php echo htmlspecialchars($schedule['reps2'] ?? '12'); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="form-group mt-3">
                                                        <label>Exercise 3</label>
                                                        <input type="text" class="form-control" name="exercises[]" value="<?php echo htmlspecialchars($schedule['exercise3'] ?? 'Shoulder Press'); ?>">
                                                        <div class="input-group mt-2">
                                                            <input type="number" class="form-control" name="sets[]" placeholder="Sets" value="<?php echo htmlspecialchars($schedule['sets3'] ?? '3'); ?>">
                                                            <input type="number" class="form-control" name="reps[]" placeholder="Reps" value="<?php echo htmlspecialchars($schedule['reps3'] ?? '12'); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="parameters-cell">
                                                <div class="form-group">
                                                    <label>Workout Duration (minutes)</label>
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="duration" 
                                                           value="<?php echo htmlspecialchars($schedule['duration'] ?? '60'); ?>"
                                                           min="30" max="120">
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label>Rest Between Sets (seconds)</label>
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="rest_period" 
                                                           value="<?php echo htmlspecialchars($schedule['rest_period'] ?? '60'); ?>"
                                                           min="30" max="180">
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label>Equipment Required</label>
                                                    <select class="form-control" name="equipment[]" multiple>
                                                        <option value="dumbbells" <?php echo (isset($schedule['equipment']) && strpos($schedule['equipment'], 'dumbbells') !== false) ? 'selected' : ''; ?>>Dumbbells</option>
                                                        <option value="barbell" <?php echo (isset($schedule['equipment']) && strpos($schedule['equipment'], 'barbell') !== false) ? 'selected' : ''; ?>>Barbell</option>
                                                        <option value="machines" <?php echo (isset($schedule['equipment']) && strpos($schedule['equipment'], 'machines') !== false) ? 'selected' : ''; ?>>Machines</option>
                                                        <option value="cables" <?php echo (isset($schedule['equipment']) && strpos($schedule['equipment'], 'cables') !== false) ? 'selected' : ''; ?>>Cables</option>
                                                        <option value="bodyweight" <?php echo (isset($schedule['equipment']) && strpos($schedule['equipment'], 'bodyweight') !== false) ? 'selected' : ''; ?>>Bodyweight</option>
                                                    </select>
                                                </div>

                                                <div class="form-group mt-3">
                                                    <label>Additional Notes</label>
                                                    <textarea class="form-control" 
                                                              name="notes" 
                                                              rows="3"><?php echo htmlspecialchars($schedule['notes'] ?? ''); ?></textarea>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <a href="staff.php" class="btn btn-secondary ml-2">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .card {
        border-radius: 15px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #eee;
        padding: 20px;
    }
    .card-title {
        color: #232d39;
        margin: 0;
    }
    .card-title i {
        color: #ed563b;
        margin-right: 10px;
    }
    .btn-primary {
        background: #ed563b;
        border-color: #ed563b;
    }
    .btn-primary:hover {
        background: #dc4c31;
        border-color: #dc4c31;
    }
    .time-slot {
        background: #f8f9fa;
        width: 200px;
    }
    .exercise-list .input-group {
        display: flex;
        gap: 10px;
    }

    .exercise-list .input-group input {
        width: 80px;
    }

    select[multiple] {
        height: 120px;
    }

    .parameters-cell {
        width: 250px;
    }

    .exercise-cell {
        width: 300px;
    }

    textarea.form-control {
        resize: vertical;
    }
    </style>

    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/popper.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html> 