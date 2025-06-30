<?php
/**
 * Student Attendance History
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    redirect(BASE_URL . 'student/login.php');
}

// Get student data
$student_id = $_SESSION['student_id'];
$user_id = $_SESSION['user_id'];

// Handle filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$session = isset($_GET['session']) ? $_GET['session'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Base query - Fixed to use attendance session instead of QR code session
$query = "SELECT a.*, e.title as event_title, e.start_date, e.end_date, 
          e.morning_time_in, e.morning_time_out, e.afternoon_time_in, e.afternoon_time_out,
          a.session as session_type 
          FROM attendance a 
          JOIN events e ON a.event_id = e.id 
          WHERE a.user_id = ?";
$params = [$user_id];
$types = "i";

// Apply filters
if (!empty($start_date)) {
    $query .= " AND DATE(a.time_recorded) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $query .= " AND DATE(a.time_recorded) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if (!empty($event_id)) {
    $query .= " AND a.event_id = ?";
    $params[] = $event_id;
    $types .= "i";
}

if (!empty($session)) {
    $query .= " AND a.session = ?";
    $params[] = $session;
    $types .= "s";
}

if (!empty($status)) {
    $query .= " AND a.attendance_status = ?";
    $params[] = $status;
    $types .= "s";
}

// Finalize query
$query .= " ORDER BY a.time_recorded DESC";

// Execute query
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get events for filter dropdown
$query = "SELECT DISTINCT e.id, e.title 
          FROM events e 
          JOIN attendance a ON e.id = a.event_id 
          WHERE a.user_id = ?
          ORDER BY e.start_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$events = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get attendance statistics
$query = "SELECT 
            COUNT(*) as total_records,
            COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN a.attendance_status = 'late' THEN 1 END) as late_count,
            COUNT(CASE WHEN a.attendance_status = 'absent' THEN 1 END) as absent_count,
            COUNT(DISTINCT a.event_id) as total_events
          FROM attendance a
          WHERE a.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <link href="../assets/css/student-style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Sidebar (on wide screens) -->
        <?php include '../includes/student-sidebar.php'; ?>
        
        <!-- Header (on mobile/tablet) -->
        <?php include '../includes/student-header.php'; ?>
        <?php include '../includes/mobilenavigation.php'; ?>
        <main class="flex-grow lg:ml-64 px-4 pt-6 pb-20">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Attendance History</h1>
                <p class="text-gray-600">View and filter your attendance records</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-green-100 via-green-50 to-white p-5 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300 stat-card border-l-4 border-green-400 flex items-center gap-4">
                    <div class="flex-shrink-0 bg-green-200 rounded-full p-3">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="text-center flex-1">
                        <span class="block text-3xl font-bold text-green-700"><?php echo $stats['total_records']; ?></span>
                        <span class="text-sm text-gray-600 mt-2 block">Total Records</span>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-green-50 via-green-100 to-white p-5 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300 stat-card border-l-4 border-green-400 flex items-center gap-4">
                    <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                        <svg class="w-7 h-7 text-green-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 1.343-3 3 0 1.657 1.343 3 3 3s3-1.343 3-3c0-1.657-1.343-3-3-3zm0 0V4m0 7v9m-7-7h14"/></svg>
                    </div>
                    <div class="text-center flex-1">
                        <span class="block text-3xl font-bold text-green-600"><?php echo $stats['present_count']; ?></span>
                        <span class="text-sm text-gray-600 mt-2 block">Present</span>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-yellow-100 via-yellow-50 to-white p-5 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300 stat-card border-l-4 border-yellow-400 flex items-center gap-4">
                    <div class="flex-shrink-0 bg-yellow-200 rounded-full p-3">
                        <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg>
                    </div>
                    <div class="text-center flex-1">
                        <span class="block text-3xl font-bold text-yellow-600"><?php echo $stats['late_count']; ?></span>
                        <span class="text-sm text-gray-600 mt-2 block">Late</span>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-blue-100 via-blue-50 to-white p-5 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300 stat-card border-l-4 border-blue-400 flex items-center gap-4">
                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                        <svg class="w-7 h-7 text-blue-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h2"/></svg>
                    </div>
                    <div class="text-center flex-1">
                        <span class="block text-3xl font-bold text-blue-900"><?php echo $stats['total_events']; ?></span>
                        <span class="text-sm text-gray-600 mt-2 block">Events Attended</span>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-white p-6 rounded-xl shadow-md mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Records</h2>
                
                <form method="get" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="event_id" class="block text-sm font-medium text-gray-700">Event</label>
                        <select id="event_id" name="event_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Events</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?php echo $event['id']; ?>" <?php echo $event_id == $event['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="session" class="block text-sm font-medium text-gray-700">Session</label>
                        <select id="session" name="session" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Sessions</option>
                            <option value="morning" <?php echo $session === 'morning' ? 'selected' : ''; ?>>Morning</option>
                            <option value="afternoon" <?php echo $session === 'afternoon' ? 'selected' : ''; ?>>Afternoon</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Status</option>
                            <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>Present</option>
                            <option value="late" <?php echo $status === 'late' ? 'selected' : ''; ?>>Late</option>
                            <option value="absent" <?php echo $status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-5 flex flex-wrap justify-end gap-2">
                        <button type="submit" class="student-btn student-btn-primary">
                            Apply Filters
                        </button>
                        
                        <a href="attendance.php" class="student-btn student-btn-secondary">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Attendance Records -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Attendance Records</h2>
                </div>
                
                <?php if (count($attendance_records) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['event_title']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?php 
                                                $start = date('M d', strtotime($record['start_date']));
                                                $end = date('M d, Y', strtotime($record['end_date']));
                                                echo $start . ' - ' . $end; 
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($record['time_recorded'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('h:i A', strtotime($record['time_recorded'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($record['session_type'] === 'morning'): ?>
                                                <span class="student-badge student-badge-success">
                                                    Morning
                                                </span>
                                            <?php else: ?>
                                                <span class="student-badge student-badge-warning">
                                                    Afternoon
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($record['status'] === 'time_in'): ?>
                                                <span class="student-badge student-badge-info">
                                                    Time In
                                                </span>
                                            <?php else: ?>
                                                <span class="student-badge student-badge-secondary">
                                                    Time Out
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                            // Calculate attendance status based on timing like admin side
                                            $attendance_status = $record['attendance_status'] ?? 'present';
                                            $status_display = '';
                                            $status_class = '';
                                            
                                            // Enhanced status calculation based on timing
                                            if ($record['session_type'] === 'morning' && $record['status'] === 'time_in') {
                                                $scheduled_time = strtotime($record['morning_time_in']);
                                                $actual_time = strtotime($record['time_recorded']);
                                                $diff_minutes = ($actual_time - $scheduled_time) / 60;
                                                
                                                if ($diff_minutes <= 0) {
                                                    $status_display = 'On Time';
                                                    $status_class = 'student-badge-success';
                                                } elseif ($diff_minutes <= 15) {
                                                    $status_display = 'Late';
                                                    $status_class = 'student-badge-warning';
                                                } else {
                                                    $status_display = 'Very Late';
                                                    $status_class = 'student-badge-error';
                                                }
                                            } elseif ($record['session_type'] === 'afternoon' && $record['status'] === 'time_in') {
                                                $scheduled_time = strtotime($record['afternoon_time_in']);
                                                $actual_time = strtotime($record['time_recorded']);
                                                $diff_minutes = ($actual_time - $scheduled_time) / 60;
                                                
                                                if ($diff_minutes <= 0) {
                                                    $status_display = 'On Time';
                                                    $status_class = 'student-badge-success';
                                                } elseif ($diff_minutes <= 15) {
                                                    $status_display = 'Late';
                                                    $status_class = 'student-badge-warning';
                                                } else {
                                                    $status_display = 'Very Late';
                                                    $status_class = 'student-badge-error';
                                                }
                                            } else {
                                                // For time_out or fallback to database status
                                                switch($attendance_status) {
                                                    case 'present':
                                                        $status_display = 'Present';
                                                        $status_class = 'student-badge-success';
                                                        break;
                                                    case 'late':
                                                        $status_display = 'Late';
                                                        $status_class = 'student-badge-warning';
                                                        break;
                                                    case 'absent':
                                                        $status_display = 'Absent';
                                                        $status_class = 'student-badge-error';
                                                        break;
                                                    default:
                                                        $status_display = ucfirst($attendance_status);
                                                        $status_class = 'student-badge';
                                                }
                                            }
                                            ?>
                                            <span class="student-badge <?php echo $status_class; ?>">
                                                <?php echo $status_display; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 px-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No attendance records</h3>
                        <p class="mt-1 text-sm text-gray-500">No records were found matching your filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
       
    </div>


    <script src="../assets/js/main.js"></script>
</body>
</html> 