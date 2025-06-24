<?php
/**
 * QR Code Scanner Handler
 * This file processes QR code scans and records attendance
 */
require_once 'config/config.php';

// Check if QR code is provided
if (!isset($_GET['code']) || empty($_GET['code'])) {
    $_SESSION['error_message'] = "Invalid QR code.";
    redirect(BASE_URL);
}

$code = sanitize($_GET['code']);

// Find QR code in database
$query = "SELECT qc.*, e.id as event_id, e.title, e.start_date, e.end_date, 
         e.morning_time_in, e.morning_time_out, e.afternoon_time_in, e.afternoon_time_out,
         e.department
         FROM qr_codes qc 
         JOIN events e ON qc.event_id = e.id 
         WHERE qc.code = ?";

// Log the query for debugging
error_log("QR Scan - Searching for QR code: " . $code);

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// If QR code not found
if (mysqli_num_rows($result) === 0) {
    error_log("QR Scan - Invalid QR code: " . $code);
    $_SESSION['error_message'] = "Invalid QR code. This QR code does not exist or has been regenerated.";
    redirect(BASE_URL);
}

$qrCode = mysqli_fetch_assoc($result);
$event_id = $qrCode['event_id'];
$event_title = $qrCode['title'];
$event_department = $qrCode['department'];

// Log found event
error_log("QR Scan - Found event: ID=" . $event_id . ", Title=" . $event_title);

// Get full event details to check dates
$event_query = "SELECT * FROM events WHERE id = ?";
$event_stmt = mysqli_prepare($conn, $event_query);
mysqli_stmt_bind_param($event_stmt, "i", $event_id);
mysqli_stmt_execute($event_stmt);
$event_result = mysqli_stmt_get_result($event_stmt);

if (mysqli_num_rows($event_result) === 0) {
    error_log("QR Scan - Event details not found: " . $event_id);
    $_SESSION['error_message'] = "Event not found.";
    redirect(BASE_URL);
}

$event = mysqli_fetch_assoc($event_result);

// Check if the current date is within the event dates
$current_date = date('Y-m-d');
if ($current_date < $event['start_date'] || $current_date > $event['end_date']) {
    // Calculate days difference for better user feedback
    $today = new DateTime($current_date);
    $event_end = new DateTime($event['end_date']);
    $event_start = new DateTime($event['start_date']);
    
    if ($current_date > $event['end_date']) {
        // Event has finished
        $days_passed = $today->diff($event_end)->days;
        $message = "This event has finished " . $days_passed . " day" . ($days_passed == 1 ? "" : "s") . " ago.";
        $subtitle = "Event ended on " . date('F d, Y', strtotime($event['end_date']));
    } else {
        // Event hasn't started yet
        $days_until = $event_start->diff($today)->days;
        $message = "This event will start in " . $days_until . " day" . ($days_until == 1 ? "" : "s") . ".";
        $subtitle = "Event starts on " . date('F d, Y', strtotime($event['start_date']));
    }
    
    // Set error message for SweetAlert
    $_SESSION['event_error'] = [
        'title' => 'Event Not Active',
        'message' => $message,
        'subtitle' => $subtitle,
        'event_title' => $event_title,
        'icon' => 'warning'
    ];
    
    error_log("QR Scan - Event not active: " . $message);
    redirect(BASE_URL);
    exit;
}

// Check if the current time is within allowed session times
$current_time = date('H:i:s');
$current_time_stamp = strtotime($current_time);
$morning_in = strtotime($event['morning_time_in']);
$morning_out = strtotime($event['morning_time_out']);
$afternoon_in = strtotime($event['afternoon_time_in']);
$afternoon_out = strtotime($event['afternoon_time_out']);

error_log("QR Scan - Current time: $current_time ($current_time_stamp)");
error_log("QR Scan - Morning time: {$event['morning_time_in']} to {$event['morning_time_out']} ($morning_in to $morning_out)");
error_log("QR Scan - Afternoon time: {$event['afternoon_time_in']} to {$event['afternoon_time_out']} ($afternoon_in to $afternoon_out)");

// Check if current time is within any valid session
$is_morning_session = ($current_time_stamp >= $morning_in && $current_time_stamp <= $morning_out);
$is_afternoon_session = ($current_time_stamp >= $afternoon_in && $current_time_stamp <= $afternoon_out);

if (!$is_morning_session && !$is_afternoon_session) {
    // Current time is outside all valid session times
    $morning_in_formatted = date('h:i A', strtotime($event['morning_time_in']));
    $morning_out_formatted = date('h:i A', strtotime($event['morning_time_out']));
    $afternoon_in_formatted = date('h:i A', strtotime($event['afternoon_time_in']));
    $afternoon_out_formatted = date('h:i A', strtotime($event['afternoon_time_out']));
    
    $current_time_formatted = date('h:i A', strtotime($current_time));
    
    // Determine if we're before morning session, between sessions, or after afternoon session
    if ($current_time_stamp < $morning_in) {
        $status = "Event hasn't started yet";
        $next_session = "Morning session starts at $morning_in_formatted";
    } elseif ($current_time_stamp > $morning_out && $current_time_stamp < $afternoon_in) {
        $status = "Break time";
        $next_session = "Afternoon session starts at $afternoon_in_formatted";
    } else {
        $status = "Event has ended for today";
        $next_session = "Check back tomorrow if the event continues";
    }
    
    $_SESSION['event_error'] = [
        'title' => 'Outside Attendance Hours',
        'message' => $status,
        'subtitle' => "Current time: $current_time_formatted<br/>" .
                     "Morning: $morning_in_formatted - $morning_out_formatted<br/>" .
                     "Afternoon: $afternoon_in_formatted - $afternoon_out_formatted<br/>" .
                     $next_session,
        'event_title' => $event_title,
        'icon' => 'warning'
    ];
    
    error_log("QR Scan - Outside valid session times: $status");
    redirect(BASE_URL);
    exit;
}

// Save QR code data in session for after login
$_SESSION['qr_scan'] = [
    'code' => $code,
    'event_id' => $event_id,
    'event_title' => $event_title,
    'qr_code_id' => $qrCode['id']
];

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("QR Scan - User not logged in, redirecting to login page");
    // Save return URL in session using getAbsoluteUrl for consistent path handling
    $_SESSION['redirect_after_login'] = getAbsoluteUrl('record_attendance.php');
    
    // Store browser recommendation flag for login page
    $_SESSION['show_browser_recommendation'] = true;
    
    // Redirect to student login
    redirect(BASE_URL . 'student/login.php');
    exit;
}

// User is logged in, check department compatibility before proceeding
$user_id = $_SESSION['user_id'];

// Get the current user's department
$user_query = "SELECT department FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_data = mysqli_fetch_assoc($user_result);
$user_department = $user_data['department'];

error_log("QR Scan - User logged in (ID: $user_id), Department: " . ($user_department ?? 'not set'));
error_log("QR Scan - Event Department: " . ($event_department ?? 'not set'));

// Check if the event is restricted to a specific department and if the user belongs to it
if (!empty($event_department) && $event_department != $user_department) {
    // Clean up QR scan data
    unset($_SESSION['qr_scan']);
    
    // Set department error message for SweetAlert
    $_SESSION['event_error'] = [
        'title' => 'Department Restriction',
        'message' => "This event is only for the {$event_department} department.",
        'subtitle' => "Your department: " . ($user_department ? $user_department : 'Not Set') . "<br/>" .
                     "Event department: {$event_department}<br/>" .
                     "Please contact your administrator if you believe this is an error.",
        'event_title' => $event_title,
        'icon' => 'error'
    ];
    
    error_log("QR Scan - Department mismatch: User Department = " . ($user_department ?? 'not set') . ", Event Department = " . $event_department);
    redirect(BASE_URL . 'student/dashboard.php');
    exit;
}

// Store browser recommendation flag for record attendance page
$_SESSION['show_browser_recommendation'] = true;

// User is logged in and department is valid, redirect to record attendance
error_log("QR Scan - User logged in (ID: " . $_SESSION['user_id'] . "), redirecting to record_attendance.php");
redirect(BASE_URL . 'record_attendance.php');
?> 