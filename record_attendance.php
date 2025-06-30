<?php
/**
 * Record Attendance
 * This file processes attendance recording after scanning a QR code and logging in
 */
require_once 'config/config.php';
require_once 'utils/GeofenceHelper.php';

// Debug: Log when this file is accessed
error_log("Record Attendance - FILE ACCESSED at " . date('Y-m-d H:i:s'));
error_log("Record Attendance - Session QR scan data: " . print_r($_SESSION['qr_scan'] ?? 'NOT SET', true));

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
    // Clean up QR scan data
    unset($_SESSION['qr_scan']);
    
    // Set department error message for SweetAlert
    $_SESSION['event_error'] = [
        'title' => 'Department Restriction',
        'message' => "This event is only for the {$event['department']} department.",
        'subtitle' => "Your department: " . ($user_department ? $user_department : 'Not Set') . "<br/>" .
                     "Event department: {$event['department']}<br/>" .
                     "Please contact your administrator if you believe this is an error.",
        'event_title' => $event_title,
        'icon' => 'error'
    ];
    
    error_log("Record Attendance - Department mismatch: User Department = " . ($user_department ?? 'not set') . ", Event Department = " . $event['department']);
    redirect(BASE_URL . 'student/dashboard.php');
    exit;
}
 
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
    
    // Clean up QR scan data
    unset($_SESSION['qr_scan']);
    
    // Redirect to a page that will show the SweetAlert
    $_SESSION['event_error'] = [
        'title' => 'Event Not Active',
        'message' => $message,
        'subtitle' => $subtitle,
        'event_title' => $event_title,
        'icon' => 'warning'
    ];
    
    redirect(BASE_URL . 'student/dashboard.php');
    exit;
}

// Determine if it's morning or afternoon based on the current time
$current_time = date('H:i:s');
$current_time_stamp = strtotime($current_time);
$morning_in = strtotime($event['morning_time_in']);
$morning_out = strtotime($event['morning_time_out']);
$afternoon_in = strtotime($event['afternoon_time_in']);
$afternoon_out = strtotime($event['afternoon_time_out']);

error_log("Record Attendance - Current time: $current_time ($current_time_stamp)");
error_log("Record Attendance - Morning time: {$event['morning_time_in']} to {$event['morning_time_out']} ($morning_in to $morning_out)");
error_log("Record Attendance - Afternoon time: {$event['afternoon_time_in']} to {$event['afternoon_time_out']} ($afternoon_in to $afternoon_out)");

// Check existing attendance records for today
$existing_query = "SELECT session, status FROM attendance 
                   WHERE user_id = ? AND event_id = ? AND DATE(time_recorded) = CURRENT_DATE() 
                   ORDER BY time_recorded DESC";
$existing_stmt = mysqli_prepare($conn, $existing_query);
mysqli_stmt_bind_param($existing_stmt, "ii", $user_id, $event_id);
mysqli_stmt_execute($existing_stmt);
$existing_result = mysqli_stmt_get_result($existing_stmt);
$existing_records = mysqli_fetch_all($existing_result, MYSQLI_ASSOC);

error_log("Record Attendance - Existing records: " . print_r($existing_records, true));

// Initialize attendance status variables
$has_morning_in = false;
$has_morning_out = false; 
$has_afternoon_in = false;
$has_afternoon_out = false;

// Check existing attendance records
foreach ($existing_records as $record) {
    if ($record['session'] === 'morning') {
        if ($record['status'] === 'time_in') $has_morning_in = true;
        if ($record['status'] === 'time_out') $has_morning_out = true;
    }
    if ($record['session'] === 'afternoon') {
        if ($record['status'] === 'time_in') $has_afternoon_in = true;
        if ($record['status'] === 'time_out') $has_afternoon_out = true;    
    }
}

// Enhanced logic for determining session and status
// Define specific time-in and time-out windows
$morning_timein_start = $morning_in;
$morning_timein_end = $morning_in + (2 * 3600); // 2 hours for normal time-in window
$morning_late_timein_end = $morning_out; // Allow late time-in until time-out starts
$morning_timeout_start = $morning_out;
$morning_timeout_end = $afternoon_in; // Until afternoon time-in starts

$afternoon_timein_start = $afternoon_in;
$afternoon_timein_end = $afternoon_in + (2 * 3600); // 2 hours for normal time-in window  
$afternoon_late_timein_end = $afternoon_out; // Allow late time-in until time-out starts
$afternoon_timeout_start = $afternoon_out;
$afternoon_timeout_end = $afternoon_out + (2 * 3600); // 2 hours for time-out window

// Initialize variables
$session_type = '';
$attendance_status = 'present';
$is_valid_time = false;
$is_late = false;

error_log("Record Attendance - Morning time-in window: " . date('H:i:s', $morning_timein_start) . " to " . date('H:i:s', $morning_timein_end));
error_log("Record Attendance - Morning late time-in until: " . date('H:i:s', $morning_late_timein_end));
error_log("Record Attendance - Morning time-out window: " . date('H:i:s', $morning_timeout_start) . " to " . date('H:i:s', $morning_timeout_end));
error_log("Record Attendance - Afternoon time-in window: " . date('H:i:s', $afternoon_timein_start) . " to " . date('H:i:s', $afternoon_timein_end));
error_log("Record Attendance - Afternoon late time-in until: " . date('H:i:s', $afternoon_late_timein_end));
error_log("Record Attendance - Afternoon time-out window: " . date('H:i:s', $afternoon_timeout_start) . " to " . date('H:i:s', $afternoon_timeout_end));

// Debug: Log all calculated times
error_log("Record Attendance - DEBUG: Current timestamp: $current_time_stamp (" . date('H:i:s', $current_time_stamp) . ")");
error_log("Record Attendance - DEBUG: Morning timeout start: $morning_timeout_start (" . date('H:i:s', $morning_timeout_start) . ")");
error_log("Record Attendance - DEBUG: Morning timeout end: $morning_timeout_end (" . date('H:i:s', $morning_timeout_end) . ")");

// Check which window the current time falls into
$is_morning_timein_window = ($current_time_stamp >= $morning_timein_start && $current_time_stamp <= $morning_timein_end);
$is_morning_late_timein_window = ($current_time_stamp > $morning_timein_end && $current_time_stamp < $morning_late_timein_end);
$is_morning_timeout_window = ($current_time_stamp >= $morning_timeout_start && $current_time_stamp <= $morning_timeout_end);
$is_afternoon_timein_window = ($current_time_stamp >= $afternoon_timein_start && $current_time_stamp <= $afternoon_timein_end);
$is_afternoon_late_timein_window = ($current_time_stamp > $afternoon_timein_end && $current_time_stamp < $afternoon_late_timein_end);
$is_afternoon_timeout_window = ($current_time_stamp >= $afternoon_timeout_start && $current_time_stamp <= $afternoon_timeout_end);

// Debug: Log window check results
error_log("Record Attendance - DEBUG: Window checks - Morning IN: $is_morning_timein_window, Morning LATE: $is_morning_late_timein_window, Morning OUT: $is_morning_timeout_window");
error_log("Record Attendance - DEBUG: Window checks - Afternoon IN: $is_afternoon_timein_window, Afternoon LATE: $is_afternoon_late_timein_window, Afternoon OUT: $is_afternoon_timeout_window");
error_log("Record Attendance - DEBUG: Has records - Morning IN: $has_morning_in, Morning OUT: $has_morning_out, Afternoon IN: $has_afternoon_in, Afternoon OUT: $has_afternoon_out");

// Initialize variables
$session_type = '';
$attendance_status = 'present';
$is_valid_time = false;
$is_late = false;

error_log("Record Attendance - DEBUG: Starting condition checks...");

// Priority logic: Check time-out conditions first if user has timed in
if ($is_morning_timeout_window && $has_morning_in && !$has_morning_out) {
    // Morning time-out window - user has timed in and hasn't timed out yet
    $session_type = 'morning';
    $attendance_status = 'present';
    $is_valid_time = true;
    error_log("Record Attendance - Morning time-out window: Allowing time-out (priority over time-in)");
    
} elseif ($is_afternoon_timeout_window && $has_afternoon_in && !$has_afternoon_out) {
    // Afternoon time-out window - user has timed in and hasn't timed out yet
    $session_type = 'afternoon';
    $attendance_status = 'present';
    $is_valid_time = true;
    error_log("Record Attendance - Afternoon time-out window: Allowing time-out (priority over time-in)");
    
} elseif ($is_morning_timein_window) {
    // Morning time-in window
    if ($has_morning_in) {
        error_log("Record Attendance - Morning time-in window: User already has morning time-in");
        $_SESSION['warning_message'] = "You have already timed in for the morning session.";
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL . 'student/dashboard.php');
        exit;
    } else {
        $session_type = 'morning';
        $attendance_status = 'present';
        $is_valid_time = true;
        error_log("Record Attendance - Morning time-in window: Allowing time-in");
    }
    
} elseif ($is_afternoon_timein_window) {
    // Afternoon time-in window
    if ($has_afternoon_in) {
        error_log("Record Attendance - Afternoon time-in window: User already has afternoon time-in");
        $_SESSION['warning_message'] = "You have already timed in for the afternoon session.";
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL . 'student/dashboard.php');
        exit;
    } else {
        $session_type = 'afternoon';
        $attendance_status = 'present';
        $is_valid_time = true;
        error_log("Record Attendance - Afternoon time-in window: Allowing time-in");
    }
    
} elseif ($is_morning_late_timein_window) {
    // Morning late time-in window
    if ($has_morning_in) {
        error_log("Record Attendance - Morning late time-in window: User already has morning time-in");
        $_SESSION['warning_message'] = "You have already timed in for the morning session.";
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL . 'student/dashboard.php');
        exit;
    } else {
        $session_type = 'morning';
        $attendance_status = 'late';
        $is_valid_time = true;
        $is_late = true;
        error_log("Record Attendance - Morning late time-in window: Allowing late time-in");
    }
    
} elseif ($is_afternoon_late_timein_window) {
    // Afternoon late time-in window
    if ($has_afternoon_in) {
        error_log("Record Attendance - Afternoon late time-in window: User already has afternoon time-in");
        $_SESSION['warning_message'] = "You have already timed in for the afternoon session.";
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL . 'student/dashboard.php');
        exit;
    } else {
        $session_type = 'afternoon';
        $attendance_status = 'late';
        $is_valid_time = true;
        $is_late = true;
        error_log("Record Attendance - Afternoon late time-in window: Allowing late time-in");
    }
    
} elseif ($is_morning_timeout_window) {
    // Morning time-out window (fallback if no time-in)
    if (!$has_morning_in) {
        error_log("Record Attendance - Morning time-out window: No time-in record found");
        $_SESSION['warning_message'] = "You need to time in first before you can time out for the morning session.";
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL . 'student/dashboard.php');
        exit;
    } elseif ($has_morning_out) {
        error_log("Record Attendance - Morning time-out window: User already has morning time-out");
        $_SESSION['warning_message'] = "You have already timed out for the morning session.";
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL . 'student/dashboard.php');
        exit;
    } else {
        $session_type = 'morning';
        $attendance_status = 'present';
        $is_valid_time = true;
        error_log("Record Attendance - Morning time-out window: Allowing time-out");
    }
    
} elseif ($is_afternoon_timeout_window) {
    // Afternoon time-out window (fallback if no time-in)
    if (!$has_afternoon_in) {
        error_log("Record Attendance - Afternoon time-out window: No time-in record found");
        $_SESSION['warning_message'] = "You need to time in first before you can time out for the afternoon session.";
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL . 'student/dashboard.php');
        exit;
    } elseif ($has_afternoon_out) {
        error_log("Record Attendance - Afternoon time-out window: User already has afternoon time-out");
        $_SESSION['warning_message'] = "You have already timed out for the afternoon session.";
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL . 'student/dashboard.php');
        exit;
    } else {
        $session_type = 'afternoon';
        $attendance_status = 'present';
        $is_valid_time = true;
        error_log("Record Attendance - Afternoon time-out window: Allowing time-out");
    }
}

if (!$is_valid_time) {
    error_log("Record Attendance - Invalid time for attendance");
    unset($_SESSION['qr_scan']);
    redirect(BASE_URL . 'student/dashboard.php');
    exit;
}

error_log("Record Attendance - Session: $session_type, Status: $attendance_status");

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

// Handle geofencing check if event has location coordinates
$geofence_result = null;
$user_latitude = null;
$user_longitude = null;

if (!empty($event['location_latitude']) && !empty($event['location_longitude'])) {
    // Check if location data was provided in POST request or session
    if ((isset($_POST['latitude']) && isset($_POST['longitude'])) || 
        (isset($_SESSION['location_data']))) {
        
        if (isset($_SESSION['location_data'])) {
            $user_latitude = $_SESSION['location_data']['latitude'];
            $user_longitude = $_SESSION['location_data']['longitude'];
            unset($_SESSION['location_data']); // Clean up session
        } else {
            $user_latitude = floatval($_POST['latitude']);
            $user_longitude = floatval($_POST['longitude']);
        }
        
        error_log("Record Attendance - User location: $user_latitude, $user_longitude");
        error_log("Record Attendance - Event location: {$event['location_latitude']}, {$event['location_longitude']}");
        
        // Validate user coordinates
        if (GeofenceHelper::validateCoordinates($user_latitude, $user_longitude)) {
            $geofence_result = GeofenceHelper::isWithinGeofence(
                $user_latitude, 
                $user_longitude, 
                $event['location_latitude'], 
                $event['location_longitude'], 
                $event['geofence_radius']
            );
            
            error_log("Record Attendance - Geofence check result: " . print_r($geofence_result, true));
            
            if (!$geofence_result['within_fence']) {
                error_log("Record Attendance - User outside geofence");
                $_SESSION['error_message'] = "You are not within the event location. " . $geofence_result['message'];
                unset($_SESSION['qr_scan']);
                redirect(BASE_URL . 'student/dashboard.php');
                exit;
            }
        } else {
            error_log("Record Attendance - Invalid user coordinates provided");
            $_SESSION['error_message'] = "Invalid location data provided.";
            unset($_SESSION['qr_scan']);
            redirect(BASE_URL . 'student/dashboard.php');
            exit;
        }
    } else {
        // Location required but not provided - redirect to location check page
        error_log("Record Attendance - Location required but not provided, redirecting to location check");
        $_SESSION['attendance_pending'] = [
            'event_id' => $event_id,
            'event_title' => $event_title,
            'qr_code_id' => $qr_code_id,
            'session_type' => $session_type
        ];
        redirect(BASE_URL . 'check_location.php');
        exit;
    }
} else {
    error_log("Record Attendance - No geofencing required for this event");
}

// Determine the status field (time_in or time_out)
if ($is_late && $attendance_status === 'late') {
    $status = 'time_in';
} elseif ($has_morning_in && !$has_morning_out && $session_type === 'morning') {
    $status = 'time_out';
} elseif ($has_afternoon_in && !$has_afternoon_out && $session_type === 'afternoon') {
    $status = 'time_out';
} else {
    $status = 'time_in';
}

// Record the new attendance
    // Record the attendance (include location data if available)
    if ($user_latitude !== null && $user_longitude !== null) {
        $query = "INSERT INTO attendance (user_id, event_id, session, status, attendance_status, qr_code_id, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iisssidd", $user_id, $event_id, $session_type, $status, $attendance_status, $qr_code_id, $user_latitude, $user_longitude);
    } else {
        $query = "INSERT INTO attendance (user_id, event_id, session, status, attendance_status, qr_code_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iisssi", $user_id, $event_id, $session_type, $status, $attendance_status, $qr_code_id);
    }
    
    error_log("Record Attendance - Attempting to insert attendance record: User $user_id, Event $event_id, Session $session_type, Status $attendance_status, QR Code ID $qr_code_id");
    
    if (mysqli_stmt_execute($stmt)) {
        error_log("Record Attendance - Successfully inserted attendance record");
        $status_text = '';
        if ($is_late && $attendance_status === 'late') {
            $status_text = 'Late Time In';
        } elseif ($status === 'time_in') {
            $status_text = 'Time In';
        } else {
            $status_text = 'Time Out';
        }
        
        $late_message = $is_late ? ' (marked as late)' : '';
        $_SESSION['success_message'] = "Your {$session_type} {$status_text} has been recorded successfully!{$late_message}";
        
        // Trigger real-time notification (optional: create a flag file for real-time system)
        $notification_data = [
            'type' => 'new_attendance',
            'user_id' => $user_id,
            'event_id' => $event_id,
            'session' => $session_type,
            'status' => $attendance_status,
            'timestamp' => time()
        ];
        
        // Write notification to a temporary file that the real-time system can check
        $notification_file = 'logs/realtime_notifications.json';
        $notifications = [];
        if (file_exists($notification_file)) {
            $content = file_get_contents($notification_file);
            $notifications = json_decode($content, true) ?: [];
        }
        $notifications[] = $notification_data;
        
        // Keep only last 50 notifications to prevent file from growing too large
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, -50);
        }
        
        file_put_contents($notification_file, json_encode($notifications));
        error_log("Record Attendance - Real-time notification triggered");
    } else {
        error_log("Record Attendance - Failed to insert attendance: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Failed to record attendance: " . mysqli_error($conn);
        unset($_SESSION['qr_scan']);
        redirect(BASE_URL);
        exit;
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                            <p class="text-gray-600"><span class="font-medium">Status:</span> <?php 
                                if ($is_late && $attendance_status === 'late') {
                                    echo 'Late Time In';
                                } elseif ($status === 'time_in') {
                                    echo 'Time In';
                                } else {
                                    echo 'Time Out';
                                }
                            ?></p>
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
    
    <script>
        // Browser Detection Function
        function detectBrowser() {
            const userAgent = navigator.userAgent;
            const isChrome = /Chrome/.test(userAgent) && /Google Inc/.test(navigator.vendor);
            const isFirefox = /Firefox/.test(userAgent);
            const isSafari = /Safari/.test(userAgent) && !/Chrome/.test(userAgent);
            const isEdge = /Edg/.test(userAgent);
            const isOpera = /Opera|OPR/.test(userAgent);
            
            return {
                isChrome,
                isFirefox,
                isSafari,
                isEdge,
                isOpera,
                name: isChrome ? 'Chrome' : 
                      isFirefox ? 'Firefox' : 
                      isSafari ? 'Safari' : 
                      isEdge ? 'Edge' : 
                      isOpera ? 'Opera' : 'Unknown'
            };
        }
        
        // Chrome Recommendation Function with Auto-redirect
        function showChromeRecommendationWithRedirect() {
            const browser = detectBrowser();
            
            if (!browser.isChrome) {
                Swal.fire({
                    title: 'Redirecting to Chrome',
                    html: `
                        <div class="text-center">
                            <div class="mb-4">
                                <svg class="mx-auto h-12 w-12 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2M21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H11V21H5V3H13V9H21Z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Attempting Chrome Redirect</h3>
                            <p class="text-gray-600 mb-3">You're currently using <strong>${browser.name}</strong>.</p>
                            <p class="text-gray-600 mb-4">For the best QR scanning experience, we're attempting to open this page in Chrome.</p>
                            <div class="bg-blue-50 p-3 rounded-lg mb-4">
                                <p class="text-sm text-blue-700">
                                    <strong>What happens next?</strong><br>
                                    • If Chrome is installed, it will open automatically<br>
                                    • If not, you'll be redirected to download Chrome<br>
                                    • You can continue with ${browser.name} if needed
                                </p>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded-lg">
                                <p class="text-xs text-yellow-700">
                                    <strong>Note:</strong> Some browsers may block automatic redirects. If Chrome doesn't open, you can manually copy this URL to Chrome.
                                </p>
                            </div>
                        </div>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Try Opening in Chrome',
                    cancelButtonText: 'Continue with ' + browser.name,
                    confirmButtonColor: '#10B981',
                    cancelButtonColor: '#3B82F6',
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    timer: 8000,
                    timerProgressBar: true
                }).then((result) => {
                    if (result.isConfirmed || result.isDismissed && result.dismiss === Swal.DismissReason.timer) {
                        // Attempt to open in Chrome
                        attemptChromeRedirect();
                    }
                });
            }
        }
        
        // Function to attempt Chrome redirect
        function attemptChromeRedirect() {
            const currentUrl = window.location.href;
            
            // Try different methods to open in Chrome
            const chromeUrls = [
                `googlechrome://${currentUrl}`,
                `chrome://${currentUrl}`,
                currentUrl
            ];
            
            let success = false;
            
            // Method 1: Try Chrome protocol handlers
            chromeUrls.forEach((url, index) => {
                if (index < 2) { // Only try protocol handlers
                    setTimeout(() => {
                        try {
                            window.location.href = url;
                            success = true;
                        } catch (e) {
                            console.log(`Chrome redirect method ${index + 1} failed:`, e);
                        }
                    }, index * 1000);
                }
            });
            
            // Method 2: Fallback - show instructions if protocol handlers fail
            setTimeout(() => {
                if (!success) {
                    Swal.fire({
                        title: 'Manual Chrome Instructions',
                        html: `
                            <div class="text-left">
                                <p class="mb-3">Automatic redirect didn't work. Here's how to open in Chrome:</p>
                                <ol class="list-decimal list-inside space-y-2 text-sm">
                                    <li>Copy this URL: <code class="bg-gray-100 p-1 rounded text-xs break-all">${currentUrl}</code></li>
                                    <li>Open Google Chrome browser</li>
                                    <li>Paste the URL in Chrome's address bar</li>
                                    <li>Press Enter</li>
                                </ol>
                                <div class="mt-4 p-3 bg-blue-50 rounded">
                                    <p class="text-sm text-blue-700">
                                        <strong>Don't have Chrome?</strong><br>
                                        <a href="https://www.google.com/chrome/" target="_blank" class="underline">Download Chrome here</a>
                                    </p>
                                </div>
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonText: 'Copy URL & Open Chrome',
                        showCancelButton: true,
                        cancelButtonText: 'Continue Here',
                        confirmButtonColor: '#10B981'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Copy URL to clipboard and open Chrome download
                            navigator.clipboard.writeText(currentUrl).then(() => {
                                window.open('https://www.google.com/chrome/', '_blank');
                            }).catch(() => {
                                // Fallback if clipboard doesn't work
                                window.open('https://www.google.com/chrome/', '_blank');
                            });
                        }
                    });
                }
            }, 3000);
        }
        
        // Execute when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we should show browser recommendation
            <?php if (isset($_SESSION['show_browser_recommendation']) && $_SESSION['show_browser_recommendation']): ?>
                // Show browser recommendation with redirect attempt after attendance is recorded
                setTimeout(() => {
                    ChromeDetector.showRedirectRecommendation({
                        title: 'Attendance Recorded - Switch to Chrome?',
                        message: 'Great! Your attendance is recorded. For future QR scans, Chrome provides the best experience.'
                    }).then((result) => {
                        if (result && result.isConfirmed) {
                            console.log('User chose Chrome redirect after attendance recording');
                        }
                    });
                }, 2000);
                <?php unset($_SESSION['show_browser_recommendation']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html> 