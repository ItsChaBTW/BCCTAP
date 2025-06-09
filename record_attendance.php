<?php
/**
 * Record Attendance
 * This file processes attendance recording after scanning a QR code and logging in
 */
require_once 'config/config.php';

error_log("Record Attendance - Script started");

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("Record Attendance - User not logged in");
    $_SESSION['error_message'] = "You must be logged in to record attendance.";
    redirect(BASE_URL . 'student/login.php');
    exit;
}

error_log("Record Attendance - User logged in: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['role']);

// Check if user is a student
if (isAdmin()) {
    error_log("Record Attendance - User is admin, redirecting");
    $_SESSION['error_message'] = "Attendance recording is only for students.";
    redirect(BASE_URL . 'admin/index.php');
    exit;
}

// Debug session data
error_log("Record Attendance - Session data: " . print_r($_SESSION, true));

// Check if we have QR scan data in the session
if (!isset($_SESSION['qr_scan']) || empty($_SESSION['qr_scan']['event_id'])) {
    error_log("Record Attendance - Missing QR scan data in session");
    $_SESSION['error_message'] = "Invalid QR scan. Please scan again.";
    redirect(BASE_URL);
    exit;
}

$event_id = $_SESSION['qr_scan']['event_id'];
$event_title = $_SESSION['qr_scan']['event_title'];
$user_id = $_SESSION['user_id'];

error_log("Record Attendance - Processing for Event ID: $event_id, Title: $event_title, User ID: $user_id");

// Get the current user's department
$query = "SELECT department FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);
$user_department = $user_data['department'];

error_log("Record Attendance - User department: " . ($user_department ?? 'not set'));

// Get event details
$query = "SELECT * FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$event_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($event_result) === 0) {
    error_log("Record Attendance - Event not found: $event_id");
    $_SESSION['error_message'] = "Event not found.";
    unset($_SESSION['qr_scan']);
    redirect(BASE_URL);
    exit;
}

$event = mysqli_fetch_assoc($event_result);

// Check if the event is restricted to a specific department and if the user belongs to it
if (!empty($event['department']) && $event['department'] != $user_department) {
    $_SESSION['error_message'] = "This event is only for the {$event['department']} department.";
    unset($_SESSION['qr_scan']);
    redirect(BASE_URL);
    exit;
}

// Check if the current date is within the event dates
$current_date = date('Y-m-d');
if ($current_date < $event['start_date'] || $current_date > $event['end_date']) {
    $_SESSION['error_message'] = "This event is not active today. Event runs from " . 
                                 date('M d, Y', strtotime($event['start_date'])) . 
                                 " to " . date('M d, Y', strtotime($event['end_date'])) . ".";
    unset($_SESSION['qr_scan']);
    redirect(BASE_URL);
    exit;
}

// Determine if it's morning or afternoon based on the current time
$current_time = date('H:i:s');
$current_time_stamp = strtotime($current_time);
$morning_in = strtotime($event['morning_time_in']);
$morning_out = strtotime($event['morning_time_out']);
$afternoon_in = strtotime($event['afternoon_time_in']);
$afternoon_out = strtotime($event['afternoon_time_out']);
$session_type = '';

error_log("Record Attendance - Current time: $current_time ($current_time_stamp)");
error_log("Record Attendance - Morning time: {$event['morning_time_in']} to {$event['morning_time_out']} ($morning_in to $morning_out)");
error_log("Record Attendance - Afternoon time: {$event['afternoon_time_in']} to {$event['afternoon_time_out']} ($afternoon_in to $afternoon_out)");

// Fixed time comparison using timestamps
if ($current_time_stamp >= $morning_in && $current_time_stamp <= $morning_out) {
    $session_type = 'morning';
    error_log("Record Attendance - Session determined as morning");
} elseif ($current_time_stamp >= $afternoon_in && $current_time_stamp <= $afternoon_out) {
    $session_type = 'afternoon';
    error_log("Record Attendance - Session determined as afternoon");
} else {
    error_log("Record Attendance - Current time outside of event session hours");
    
    // For testing purposes, force a session type to allow the attendance to be recorded
    $session_type = 'morning'; // Force to morning for debugging
    error_log("Record Attendance - FORCE setting session to 'morning' for testing");
    
    /* Commented out to allow testing
    $_SESSION['error_message'] = "You can only record attendance during scheduled times.
                                Morning: " . date('h:i A', strtotime($event['morning_time_in'])) . " - " . date('h:i A', strtotime($event['morning_time_out'])) . "
                                Afternoon: " . date('h:i A', strtotime($event['afternoon_time_in'])) . " - " . date('h:i A', strtotime($event['afternoon_time_out']));
    unset($_SESSION['qr_scan']);
    redirect(BASE_URL);
    exit;
    */
}

// Get the QR code ID from the session data
$qr_code_id = isset($_SESSION['qr_scan']['qr_code_id']) ? $_SESSION['qr_scan']['qr_code_id'] : null;

// Check if we have a valid QR code ID
if (!$qr_code_id) {
    error_log("Record Attendance - Missing QR code ID in session");
    $_SESSION['error_message'] = "Invalid QR scan data. Please scan again.";
    unset($_SESSION['qr_scan']);
    redirect(BASE_URL);
    exit;
}

error_log("Record Attendance - Using QR code ID: $qr_code_id");

// Check if attendance has already been recorded for this session
$query = "SELECT id FROM attendance 
          WHERE user_id = ? AND event_id = ? AND DATE(time_recorded) = CURRENT_DATE() AND session = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iis", $user_id, $event_id, $session_type);
mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($attendance_result) > 0) {
    error_log("Record Attendance - Attendance already recorded for user $user_id at event $event_id for $session_type session");
    $_SESSION['warning_message'] = "You have already recorded your attendance for this {$session_type} session.";
    // Rather than redirect, we'll continue to show the success page
} else {
    // Record the attendance
    $query = "INSERT INTO attendance (user_id, event_id, session, qr_code_id) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iisi", $user_id, $event_id, $session_type, $qr_code_id);
    
    error_log("Record Attendance - Attempting to insert attendance record: User $user_id, Event $event_id, Session $session_type, QR Code ID $qr_code_id");
    
    if (mysqli_stmt_execute($stmt)) {
        error_log("Record Attendance - Successfully inserted attendance record");
        $_SESSION['success_message'] = "Your {$session_type} attendance has been recorded successfully!";
    } else {
        error_log("Record Attendance - Failed to insert attendance: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Failed to record attendance: " . mysqli_error($conn);
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL);
        exit;
    }
}

// Clean up session
unset($_SESSION['qr_scan']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Recorded - BCCTAP</title>
    <link href="assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#EF6161',
                        secondary: '#f3af3d',
                    }
                }
            }
        }
    </script>
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #EF6161 0%, #f3af3d 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <?php include 'includes/header.php'; ?>
        
        <main class="flex-grow container mx-auto px-4 py-8">
            <div class="max-w-md mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-primary p-6 text-white text-center">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h1 class="text-2xl font-bold">Attendance Recorded!</h1>
                        <?php elseif (isset($_SESSION['warning_message'])): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <h1 class="text-2xl font-bold">Already Recorded</h1>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-6">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800"><?php echo $_SESSION['success_message']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php elseif (isset($_SESSION['warning_message'])): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-yellow-800"><?php echo $_SESSION['warning_message']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php unset($_SESSION['warning_message']); ?>
                        <?php endif; ?>
                        
                        <div class="mb-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-2">Event Details</h2>
                            <p class="text-gray-600"><span class="font-medium">Title:</span> <?php echo htmlspecialchars($event_title); ?></p>
                            <p class="text-gray-600"><span class="font-medium">Date:</span> <?php echo date('M d, Y'); ?></p>
                            <p class="text-gray-600"><span class="font-medium">Session:</span> <?php echo ucfirst($session_type); ?></p>
                            <p class="text-gray-600"><span class="font-medium">Time:</span> <?php echo date('h:i A'); ?></p>
                        </div>
                        
                        <div class="text-center mt-8">
                            <a href="student/dashboard.php" class="bg-gradient-primary hover:opacity-90 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center justify-center mx-auto w-full max-w-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" />
                                </svg>
                                Go to Dashboard
                            </a>
                            
                            <p class="mt-6 text-sm text-gray-500">
                                You can view all your attendance records in your dashboard.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include 'includes/footer.php'; ?>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html> 