<?php
/**
 * Department Head Dashboard
 */
require_once '../config/config.php';

// Check if user is logged in and is a teacher (department head)
if (!isLoggedIn() || !isTeacher()) {
    redirect(BASE_URL . 'teacher/login.php');
}

// Get the department head's department
$query = "SELECT department FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$teacher = mysqli_fetch_assoc($result);
$department = $teacher['department'];

// Get filter values
$section_filter = isset($_GET['section']) ? sanitize($_GET['section']) : '';
$year_level_filter = isset($_GET['year_level']) ? intval($_GET['year_level']) : 0;

// Get distinct sections and year levels for this department
$query = "SELECT DISTINCT section FROM users WHERE role = 'student' AND department = ? AND section IS NOT NULL AND section != '' ORDER BY section";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $department);
mysqli_stmt_execute($stmt);
$sections_result = mysqli_stmt_get_result($stmt);
$sections = mysqli_fetch_all($sections_result, MYSQLI_ASSOC);

$query = "SELECT DISTINCT year_level FROM users WHERE role = 'student' AND department = ? AND year_level IS NOT NULL ORDER BY year_level";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $department);
mysqli_stmt_execute($stmt);
$year_levels_result = mysqli_stmt_get_result($stmt);
$year_levels = mysqli_fetch_all($year_levels_result, MYSQLI_ASSOC);

// Get recent events for this department
$query = "SELECT * FROM events 
          WHERE (department = ? OR department IS NULL OR department = '') 
          AND end_date >= CURDATE()
          ORDER BY start_date ASC LIMIT 5";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $department);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$events = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Build query for students with filters
$student_query = "SELECT u.id, u.full_name, u.student_id, u.year_level, u.section
                  FROM users u 
                  WHERE u.role = 'student' AND u.department = ?";
$student_params = [$department];
$student_types = "s";

if (!empty($section_filter)) {
    $student_query .= " AND u.section = ?";
    $student_params[] = $section_filter;
    $student_types .= "s";
}

if ($year_level_filter > 0) {
    $student_query .= " AND u.year_level = ?";
    $student_params[] = $year_level_filter;
    $student_types .= "i";
}

$student_query .= " ORDER BY u.year_level, u.section, u.full_name LIMIT 20";

$stmt = mysqli_prepare($conn, $student_query);
mysqli_stmt_bind_param($stmt, $student_types, ...$student_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$filtered_students = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get recent attendance records for students in this department with filters
$attendance_query = "SELECT a.*, u.full_name as student_name, u.student_id, u.year_level, u.section, e.title as event_title 
                     FROM attendance a 
                     INNER JOIN users u ON a.user_id = u.id 
                     INNER JOIN events e ON a.event_id = e.id 
                     WHERE u.department = ?";
$attendance_params = [$department];
$attendance_types = "s";

if (!empty($section_filter)) {
    $attendance_query .= " AND u.section = ?";
    $attendance_params[] = $section_filter;
    $attendance_types .= "s";
}

if ($year_level_filter > 0) {
    $attendance_query .= " AND u.year_level = ?";
    $attendance_params[] = $year_level_filter;
    $attendance_types .= "i";
}

$attendance_query .= " ORDER BY a.time_recorded DESC LIMIT 10";

$stmt = mysqli_prepare($conn, $attendance_query);
mysqli_stmt_bind_param($stmt, $attendance_types, ...$attendance_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recentAttendance = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get count of students with attendance records for this department with filters
$count_query = "SELECT COUNT(DISTINCT u.id) as total 
                FROM users u 
                INNER JOIN attendance a ON u.id = a.user_id
                WHERE u.role = 'student' AND u.department = ?";
$count_params = [$department];
$count_types = "s";

if (!empty($section_filter)) {
    $count_query .= " AND u.section = ?";
    $count_params[] = $section_filter;
    $count_types .= "s";
}

if ($year_level_filter > 0) {
    $count_query .= " AND u.year_level = ?";
    $count_params[] = $year_level_filter;
    $count_types .= "i";
}

$stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($stmt, $count_types, ...$count_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalStudentsWithAttendance = mysqli_fetch_assoc($result)['total'];

// Get total students count for this department with filters
$total_students_query = "SELECT COUNT(*) as total FROM users WHERE role = 'student' AND department = ?";
$total_students_params = [$department];
$total_students_types = "s";

if (!empty($section_filter)) {
    $total_students_query .= " AND section = ?";
    $total_students_params[] = $section_filter;
    $total_students_types .= "s";
}

if ($year_level_filter > 0) {
    $total_students_query .= " AND year_level = ?";
    $total_students_params[] = $year_level_filter;
    $total_students_types .= "i";
}

$stmt = mysqli_prepare($conn, $total_students_query);
mysqli_stmt_bind_param($stmt, $total_students_types, ...$total_students_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$totalStudents = mysqli_fetch_assoc($result)['total'];

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
    <title>Department Head Dashboard - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <?php include '../includes/header.php'; ?>
        
        <main class="flex-grow container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Department Head Dashboard</h1>
                <a href="attendance.php" class="btn btn-primary">View All Attendance Records</a>
            </div>
            
            <!-- Department Info and Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($department); ?> Department</h2>
                </div>
                
                <!-- Filters -->
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="year_level" class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                        <select id="year_level" name="year_level" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                            <option value="0">All Year Levels</option>
                            <?php foreach ($year_levels as $level): ?>
                                <option value="<?php echo $level['year_level']; ?>" <?php echo $year_level_filter == $level['year_level'] ? 'selected' : ''; ?>>
                                    Year <?php echo $level['year_level']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="section" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                        <select id="section" name="section" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?php echo htmlspecialchars($sec['section']); ?>" <?php echo $section_filter === $sec['section'] ? 'selected' : ''; ?>>
                                    Section <?php echo htmlspecialchars($sec['section']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-primary mr-2">Apply Filters</button>
                        <a href="index.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <h3 class="text-lg font-semibold text-gray-700">Total Students</h3>
                    <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $totalStudents; ?></p>
                    <?php if (!empty($section_filter) || $year_level_filter > 0): ?>
                        <p class="text-sm text-gray-500 mt-1">
                            <?php 
                            $filter_text = [];
                            if ($year_level_filter > 0) $filter_text[] = "Year " . $year_level_filter;
                            if (!empty($section_filter)) $filter_text[] = "Section " . $section_filter;
                            echo "(" . implode(", ", $filter_text) . ")";
                            ?>
                        </p>
                    <?php endif; ?>
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
                <!-- Recent Events -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Department Events</h2>
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
                            <p class="text-gray-600">No events found for this department.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Attendance -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Attendance</h2>
                        <?php if (!empty($section_filter) || $year_level_filter > 0): ?>
                            <p class="text-sm text-gray-500">
                                Filtered: 
                                <?php 
                                $filter_text = [];
                                if ($year_level_filter > 0) $filter_text[] = "Year " . $year_level_filter;
                                if (!empty($section_filter)) $filter_text[] = "Section " . $section_filter;
                                echo implode(", ", $filter_text);
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <?php if (count($recentAttendance) > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            <th class="px-4 py-2">Student</th>
                                            <th class="px-4 py-2">Year/Section</th>
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
                                                <td class="px-4 py-2">
                                                    <div class="text-xs">
                                                        Year <?php echo $attendance['year_level'] ?: 'N/A'; ?><br>
                                                        Section <?php echo $attendance['section'] ?: 'N/A'; ?>
                                                    </div>
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
                                <a href="attendance.php<?php echo (!empty($_GET['year_level']) || !empty($_GET['section'])) ? '?' . http_build_query($_GET) : ''; ?>" class="text-blue-600 hover:underline text-sm">View All Records</a>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-600">No attendance records found for the selected filters.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Students List (if filters applied) -->
            <?php if (!empty($section_filter) || $year_level_filter > 0): ?>
                <div class="mt-8 bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Students in Selected Filters</h2>
                    </div>
                    <div class="p-6">
                        <?php if (count($filtered_students) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($filtered_students as $student): ?>
                                    <div class="border border-gray-200 p-4 rounded-md">
                                        <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                                        <p class="text-sm text-gray-600">ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                                        <p class="text-sm text-gray-500">
                                            Year <?php echo $student['year_level'] ?: 'N/A'; ?> | 
                                            Section <?php echo $student['section'] ?: 'N/A'; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($filtered_students) == 20): ?>
                                <p class="mt-4 text-sm text-gray-500 text-center">Showing first 20 students. Use attendance page for complete list.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-gray-600">No students found for the selected filters.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="attendance.php<?php echo (!empty($_GET['year_level']) || !empty($_GET['section'])) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-primary w-full flex items-center justify-center py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                        </svg>
                        <span>View Detailed Records</span>
                    </a>
                    
                    <a href="export_excel.php<?php echo (!empty($_GET['year_level']) || !empty($_GET['section'])) ? '?' . http_build_query($_GET) : ''; ?>" class="btn btn-success w-full flex items-center justify-center py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span>Download Excel Report</span>
                    </a>
                    
                    <a href="../admin/reports/index.php?department=<?php echo urlencode($department); ?>" class="btn btn-secondary w-full flex items-center justify-center py-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                            <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                        </svg>
                        <span>Advanced Reports</span>
                    </a>
                </div>
            </div>
        </main>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html> 