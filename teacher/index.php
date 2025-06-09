<?php
/**
 * Teacher Dashboard
 */
require_once '../config/config.php';

// Check if user is logged in and is a teacher
if (!isLoggedIn() || !isTeacher()) {
    redirect(BASE_URL . 'teacher/login.php');
}

// Get the teacher's department
$query = "SELECT department FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$teacher = mysqli_fetch_assoc($result);
$department = $teacher['department'];

// Get recent events for this teacher's department or all departments
$query = "SELECT * FROM events 
          WHERE (department = ? OR department IS NULL OR department = '') 
          AND end_date >= CURDATE()
          ORDER BY start_date ASC LIMIT 5";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $department);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$events = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get recent attendance records for events in this teacher's department
$query = "SELECT a.*, u.full_name as student_name, u.student_id, e.title as event_title 
          FROM attendance a 
          INNER JOIN users u ON a.user_id = u.id 
          INNER JOIN events e ON a.event_id = e.id 
          WHERE (e.department = ? OR e.department IS NULL OR e.department = '')
          ORDER BY a.time_recorded DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $department);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recentAttendance = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get count of students with attendance records for this department
$query = "SELECT COUNT(DISTINCT u.id) as total 
          FROM users u 
          INNER JOIN attendance a ON u.id = a.user_id
          INNER JOIN events e ON a.event_id = e.id
          WHERE u.role = 'student' 
          AND (u.department = ? OR e.department = ? OR e.department IS NULL OR e.department = '')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $department, $department);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalStudentsWithAttendance = mysqli_fetch_assoc($result)['total'];

// Get count of events for this department
$query = "SELECT COUNT(*) as total 
          FROM events 
          WHERE department = ? OR department IS NULL OR department = ''";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $department);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalEvents = mysqli_fetch_assoc($result)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <?php include '../includes/header.php'; ?>
        
        <main class="flex-grow container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Teacher Dashboard</h1>
                <a href="attendance.php" class="btn btn-primary">View Attendance Records</a>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <h3 class="text-lg font-semibold text-gray-700">Department</h3>
                    <p class="text-xl font-bold text-green-600 mt-2"><?php echo htmlspecialchars($department); ?></p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <h3 class="text-lg font-semibold text-gray-700">Students with Records</h3>
                    <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $totalStudentsWithAttendance; ?></p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                    <h3 class="text-lg font-semibold text-gray-700">Total Events</h3>
                    <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $totalEvents; ?></p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <h3 class="text-lg font-semibold text-gray-700">Current Date</h3>
                    <p class="text-xl font-bold text-purple-600 mt-2"><?php echo date('F d, Y'); ?></p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Upcoming Events -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Upcoming Events</h2>
                    </div>
                    <div class="p-6">
                        <?php if (count($events) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($events as $event): ?>
                                    <div class="border border-gray-200 p-4 rounded-md">
                                        <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('M d, Y', strtotime($event['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($event['end_date'])); ?>
                                        </p>
                                        <div class="mt-2 text-sm">
                                            <p>Morning: <?php echo date('h:i A', strtotime($event['morning_time_in'])); ?> - 
                                               <?php echo date('h:i A', strtotime($event['morning_time_out'])); ?></p>
                                            <p>Afternoon: <?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?> - 
                                               <?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?></p>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">
                                            Department: <?php echo !empty($event['department']) ? htmlspecialchars($event['department']) : 'All Departments'; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-600">No upcoming events found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Attendance -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Attendance</h2>
                    </div>
                    <div class="p-6">
                        <?php if (count($recentAttendance) > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            <th class="px-4 py-2">Student</th>
                                            <th class="px-4 py-2">Event</th>
                                            <th class="px-4 py-2">Session</th>
                                            <th class="px-4 py-2">Status</th>
                                            <th class="px-4 py-2">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                        <?php foreach ($recentAttendance as $attendance): ?>
                                            <tr class="border-t">
                                                <td class="px-4 py-2">
                                                    <?php echo htmlspecialchars($attendance['student_name']); ?>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($attendance['student_id']); ?></div>
                                                </td>
                                                <td class="px-4 py-2"><?php echo htmlspecialchars($attendance['event_title']); ?></td>
                                                <td class="px-4 py-2"><?php echo ucfirst($attendance['session']); ?></td>
                                                <td class="px-4 py-2"><?php echo str_replace('_', ' ', ucfirst($attendance['status'])); ?></td>
                                                <td class="px-4 py-2"><?php echo date('M d, h:i A', strtotime($attendance['time_recorded'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-right">
                                <a href="attendance.php" class="text-blue-600 hover:underline text-sm">View All Records</a>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-600">No attendance records found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="attendance.php" class="btn btn-primary w-full flex items-center justify-center py-3">
                        <span>View Attendance Records</span>
                    </a>
                    <a href="attendance_report.php" class="btn btn-success w-full flex items-center justify-center py-3">
                        <span>Generate Attendance Report</span>
                    </a>
                </div>
            </div>
        </main>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html> 