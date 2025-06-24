<?php
/**
 * Event Detail Page
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    redirect(BASE_URL . 'student/login.php');
}

// Get student data
$user_id = $_SESSION['user_id'];

// Get the current user's department
$user_query = "SELECT department FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_data = mysqli_fetch_assoc($user_result);
$user_department = $user_data['department'];

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Event ID is required";
    redirect(BASE_URL . 'student/dashboard.php');
}

$event_id = intval($_GET['id']);

// Get event details
$query = "SELECT e.*, u.full_name as created_by_name
          FROM events e 
          LEFT JOIN users u ON e.created_by = u.id
          WHERE e.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "Event not found";
    redirect(BASE_URL . 'student/dashboard.php');
}

$event = mysqli_fetch_assoc($result);

// Check if the event is restricted to a specific department and if the user belongs to it
$is_department_compatible = true;
$department_message = '';
if (!empty($event['department']) && $event['department'] != $user_department) {
    $is_department_compatible = false;
    $department_message = "This event is only for the {$event['department']} department. Your department: " . ($user_department ? $user_department : 'Not Set');
}

// Get student attendance for this event - Fixed query to use attendance session field
$query = "SELECT a.*, a.session as session_type 
          FROM attendance a 
          WHERE a.user_id = ? AND a.event_id = ?
          ORDER BY a.time_recorded ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Group attendance by session - now using the correct session field
$morning_attendance = null;
$afternoon_attendance = null;

foreach ($attendance_records as $record) {
    if ($record['session_type'] === 'morning') {
        $morning_attendance = $record;
    } elseif ($record['session_type'] === 'afternoon') {
        $afternoon_attendance = $record;
    }
}

// Check if this is an ongoing event
$today = date('Y-m-d');
$is_ongoing = ($today >= $event['start_date'] && $today <= $event['end_date']);

// Check if current time is within valid session hours
$current_time = date('H:i:s');
$current_time_stamp = strtotime($current_time);
$morning_in = strtotime($event['morning_time_in']);
$morning_out = strtotime($event['morning_time_out']);
$afternoon_in = strtotime($event['afternoon_time_in']);
$afternoon_out = strtotime($event['afternoon_time_out']);

$is_morning_session = ($current_time_stamp >= $morning_in && $current_time_stamp <= $morning_out);
$is_afternoon_session = ($current_time_stamp >= $afternoon_in && $current_time_stamp <= $afternoon_out);
$is_within_session_time = ($is_morning_session || $is_afternoon_session);

// Determine current session status for display
$session_status = '';
$next_session_info = '';
if ($is_ongoing) {
    if ($is_morning_session) {
        $session_status = 'Morning session is active';
        $next_session_info = '';
    } elseif ($is_afternoon_session) {
        $session_status = 'Afternoon session is active';
        $next_session_info = '';
    } elseif ($current_time_stamp < $morning_in) {
        $session_status = 'Event hasn\'t started yet';
        $next_session_info = 'Morning session starts at ' . date('h:i A', strtotime($event['morning_time_in']));
    } elseif ($current_time_stamp > $morning_out && $current_time_stamp < $afternoon_in) {
        $session_status = 'Break time';
        $next_session_info = 'Afternoon session starts at ' . date('h:i A', strtotime($event['afternoon_time_in']));
    } else {
        $session_status = 'Event has ended for today';
        $next_session_info = 'Check back tomorrow if the event continues';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <link href="../assets/css/student-style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Sidebar (on wide screens) -->
        <?php include '../includes/student-sidebar.php'; ?>
        
        <!-- Header (on mobile/tablet) -->
        <?php include '../includes/student-header.php'; ?>
        
        <main class="flex-grow lg:ml-64 px-4 pt-6 pb-20">
            <!-- Page Header -->
            <div class="mb-6">
                <div class="flex items-center mb-4">
                    <a href="dashboard.php" class="mr-4 text-gray-500 hover:text-gray-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h1>
                </div>
                
                <!-- Event Status Badge -->
                <div class="flex flex-wrap gap-2">
                    <?php if ($is_ongoing): ?>
                        <span class="student-badge student-badge-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Ongoing Event
                        </span>
                    <?php else: ?>
                        <span class="student-badge student-badge-info">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            Event Details
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['department'])): ?>
                        <?php if ($is_department_compatible): ?>
                            <span class="student-badge student-badge-info">
                                <?php echo htmlspecialchars($event['department']); ?>
                            </span>
                        <?php else: ?>
                            <span class="student-badge bg-red-100 text-red-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <?php echo htmlspecialchars($event['department']); ?> Only
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="student-badge student-badge-info">
                            All Departments
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Event Details and Attendance Grid -->
            <div class="space-y-6">
                <!-- Event Information Card -->
                <div class="bg-white p-6 rounded-xl shadow-md hover-card">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Event Information</h2>
                    
                    <!-- Event Description -->
                            <?php if (!empty($event['description'])): ?>
                                <div class="mb-6">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Description</h3>
                            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                    <!-- Event Details Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        <!-- Dates -->
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-md font-semibold text-gray-700 mb-2 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                Event Duration
                            </h3>
                            <div class="space-y-1 text-sm">
                                        <div>
                                    <span class="font-medium text-gray-500">Start:</span>
                                    <span class="text-gray-900"><?php echo date('M d, Y', strtotime($event['start_date'])); ?></span>
                                        </div>
                                        <div>
                                    <span class="font-medium text-gray-500">End:</span>
                                    <span class="text-gray-900"><?php echo date('M d, Y', strtotime($event['end_date'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                        <!-- Location -->
                        <?php if (!empty($event['location'])): ?>
                        <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                            <h3 class="text-md font-semibold text-purple-700 mb-2 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                Location
                            </h3>
                            <p class="text-gray-800 text-sm"><?php echo htmlspecialchars($event['location']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                        <!-- Created By -->
                        <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                            <h3 class="text-md font-semibold text-indigo-700 mb-2 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                                </svg>
                                Organizer
                            </h3>
                            <p class="text-gray-800 text-sm"><?php echo htmlspecialchars($event['created_by_name'] ?: 'Admin'); ?></p>
                                </div>
                            </div>
                            
                    <!-- Session Times -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                            <h3 class="text-md font-semibold text-green-700 mb-2 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                Morning Session
                            </h3>
                            <div class="flex justify-between text-sm">
                                        <div>
                                            <p class="text-xs text-gray-500">Time In</p>
                                    <p class="text-gray-900 font-medium"><?php echo date('h:i A', strtotime($event['morning_time_in'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Time Out</p>
                                    <p class="text-gray-900 font-medium"><?php echo date('h:i A', strtotime($event['morning_time_out'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-amber-50 p-4 rounded-lg border border-amber-100">
                            <h3 class="text-md font-semibold text-amber-700 mb-2 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                Afternoon Session
                            </h3>
                            <div class="flex justify-between text-sm">
                                        <div>
                                            <p class="text-xs text-gray-500">Time In</p>
                                    <p class="text-gray-900 font-medium"><?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Time Out</p>
                                    <p class="text-gray-900 font-medium"><?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Your Attendance Status -->
                <div class="bg-white p-6 rounded-xl shadow-md hover-card">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Your Attendance Status</h2>
                    
                            <div class="space-y-4">
                        <!-- Morning Session Attendance -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between mb-3">
                                        <h3 class="font-medium text-gray-800 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                            </svg>
                                            Morning Session
                                        </h3>
                                        
                                        <?php if ($morning_attendance): ?>
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch ($morning_attendance['status']) {
                                                case 'present':
                                            $status_class = 'student-badge-success';
                                                    $status_text = 'Present';
                                                    break;
                                                case 'late':
                                            $status_class = 'student-badge-warning';
                                                    $status_text = 'Late';
                                                    break;
                                                case 'excused':
                                            $status_class = 'student-badge-info';
                                                    $status_text = 'Excused';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = 'Unknown';
                                            }
                                            ?>
                                    <span class="student-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        <?php else: ?>
                                    <span class="student-badge bg-gray-100 text-gray-800">
                                                Not Recorded
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($morning_attendance): ?>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <p><span class="font-medium">Checked in at:</span> <?php echo date('h:i A', strtotime($morning_attendance['time_recorded'])); ?></p>
                                    <p><span class="font-medium">Date:</span> <?php echo date('M d, Y', strtotime($morning_attendance['time_recorded'])); ?></p>
                                        </div>
                                    <?php else: ?>
                                <div class="text-sm text-gray-600">
                                            <p>You have not recorded your attendance for the morning session yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                        <!-- Afternoon Session Attendance -->
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between mb-3">
                                        <h3 class="font-medium text-gray-800 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                            </svg>
                                            Afternoon Session
                                        </h3>
                                        
                                        <?php if ($afternoon_attendance): ?>
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch ($afternoon_attendance['status']) {
                                                case 'present':
                                            $status_class = 'student-badge-success';
                                                    $status_text = 'Present';
                                                    break;
                                                case 'late':
                                            $status_class = 'student-badge-warning';
                                                    $status_text = 'Late';
                                                    break;
                                                case 'excused':
                                            $status_class = 'student-badge-info';
                                                    $status_text = 'Excused';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = 'Unknown';
                                            }
                                            ?>
                                    <span class="student-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        <?php else: ?>
                                    <span class="student-badge bg-gray-100 text-gray-800">
                                                Not Recorded
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($afternoon_attendance): ?>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <p><span class="font-medium">Checked in at:</span> <?php echo date('h:i A', strtotime($afternoon_attendance['time_recorded'])); ?></p>
                                    <p><span class="font-medium">Date:</span> <?php echo date('M d, Y', strtotime($afternoon_attendance['time_recorded'])); ?></p>
                                        </div>
                                    <?php else: ?>
                                <div class="text-sm text-gray-600">
                                            <p>You have not recorded your attendance for the afternoon session yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                    <!-- Action Button -->
                    <?php if ($is_ongoing): ?>
                        <!-- Department Restriction Check -->
                        <?php if (!$is_department_compatible): ?>
                            <div class="mt-6 mb-4">
                                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-red-700 font-medium">Department Restriction</p>
                                            <p class="text-xs text-red-600 mt-1"><?php echo htmlspecialchars($department_message); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Session Status Display -->
                        <div class="mt-6 mb-4">
                            <?php if ($is_within_session_time): ?>
                                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-green-700 font-medium"><?php echo $session_status; ?></p>
                                            <p class="text-xs text-green-600 mt-1">Current time: <?php echo date('h:i A'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700 font-medium"><?php echo $session_status; ?></p>
                                            <p class="text-xs text-yellow-600 mt-1">Current time: <?php echo date('h:i A'); ?></p>
                                            <?php if ($next_session_info): ?>
                                                <p class="text-xs text-yellow-600 mt-1"><?php echo $next_session_info; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-6 text-center">
                            <?php if (!$is_department_compatible): ?>
                                <button onclick="showDepartmentAlert()" class="student-btn bg-red-400 text-white cursor-not-allowed opacity-75">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 inline" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    Department Restricted
                                </button>
                            <?php elseif ($is_within_session_time): ?>
                               
                            <?php else: ?>
                                <button onclick="showTimeAlert()" class="student-btn bg-gray-400 text-white cursor-not-allowed opacity-75">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 inline" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                    Outside Session Hours
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <?php if (!$is_department_compatible): ?>
                                            This event is restricted to students from the <?php echo htmlspecialchars($event['department']); ?> department only.
                                        <?php else: ?>
                                            This event is currently ongoing. Attendance can only be recorded during the scheduled session times<?php echo !empty($event['department']) ? ' by ' . htmlspecialchars($event['department']) . ' department students' : ''; ?>.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-6 text-center">
                            <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg">
                                <p class="text-gray-600 text-sm">This event is not currently active for attendance recording.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white p-6 rounded-xl shadow-md hover-card">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <a href="dashboard.php" class="student-btn student-btn-secondary flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                            </svg>
                            Back to Dashboard
                        </a>
                        <a href="attendance.php" class="student-btn student-btn-secondary flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                            </svg>
                            View All Attendance
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function showTimeAlert() {
            const currentTime = '<?php echo date('h:i A'); ?>';
            const morningStart = '<?php echo date('h:i A', strtotime($event['morning_time_in'])); ?>';
            const morningEnd = '<?php echo date('h:i A', strtotime($event['morning_time_out'])); ?>';
            const afternoonStart = '<?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?>';
            const afternoonEnd = '<?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?>';
            const sessionStatus = '<?php echo addslashes($session_status); ?>';
            const nextSessionInfo = '<?php echo addslashes($next_session_info); ?>';
            
            Swal.fire({
                title: 'Outside Attendance Hours',
                html: '<div class="text-center">' +
                      '<h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo addslashes($event['title']); ?></h3>' +
                      '<p class="text-gray-600 mb-3">' + sessionStatus + '</p>' +
                      '<div class="text-sm text-gray-500 space-y-1">' +
                      '<p><strong>Current time:</strong> ' + currentTime + '</p>' +
                      '<p><strong>Morning session:</strong> ' + morningStart + ' - ' + morningEnd + '</p>' +
                      '<p><strong>Afternoon session:</strong> ' + afternoonStart + ' - ' + afternoonEnd + '</p>' +
                      (nextSessionInfo ? '<p class="text-blue-600 mt-2">' + nextSessionInfo + '</p>' : '') +
                      '</div>' +
                      '</div>',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#EF6161',
                allowOutsideClick: true,
                allowEscapeKey: true
            });
        }
        
        function showDepartmentAlert() {
            const userDepartment = '<?php echo addslashes($user_department ? $user_department : 'Not Set'); ?>';
            const eventDepartment = '<?php echo addslashes($event['department']); ?>';
            
            Swal.fire({
                title: 'Department Restriction',
                html: '<div class="text-center">' +
                      '<h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo addslashes($event['title']); ?></h3>' +
                      '<p class="text-gray-600 mb-3">This event is only for the ' + eventDepartment + ' department.</p>' +
                      '<div class="text-sm text-gray-500 space-y-1">' +
                      '<p><strong>Your department:</strong> ' + userDepartment + '</p>' +
                      '<p><strong>Event department:</strong> ' + eventDepartment + '</p>' +
                      '<p class="text-blue-600 mt-3">Please contact your administrator if you believe this is an error.</p>' +
                      '</div>' +
                      '</div>',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#EF6161',
                allowOutsideClick: true,
                allowEscapeKey: true
            });
        }
    </script>
</body>
</html> 