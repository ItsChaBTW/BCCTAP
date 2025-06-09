<?php
/**
 * Attendance Records for Teachers
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

// Get all events for this department
$query = "SELECT * FROM events 
          WHERE department = ? OR department IS NULL OR department = '' 
          ORDER BY start_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $department);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$events = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Set default filter values
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$session = isset($_GET['session']) ? sanitize($_GET['session']) : '';
$date = isset($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d');
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Build the query based on filters
$query = "SELECT a.*, u.full_name as student_name, u.student_id, u.department as student_department, 
                 e.title as event_title, e.department as event_department
          FROM attendance a 
          INNER JOIN users u ON a.user_id = u.id 
          INNER JOIN events e ON a.event_id = e.id 
          WHERE (e.department = ? OR e.department IS NULL OR e.department = '')";

$params = [$department];
$types = "s";

if ($event_id > 0) {
    $query .= " AND a.event_id = ?";
    $params[] = $event_id;
    $types .= "i";
}

if (!empty($session)) {
    $query .= " AND a.session = ?";
    $params[] = $session;
    $types .= "s";
}

if (!empty($date)) {
    $query .= " AND DATE(a.time_recorded) = ?";
    $params[] = $date;
    $types .= "s";
}

if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY a.time_recorded DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Handle print request
$print_mode = isset($_GET['print']) && $_GET['print'] == 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if ($print_mode): ?>
        <style>
            @media print {
                body {
                    font-family: Arial, sans-serif;
                }
                .no-print {
                    display: none !important;
                }
                .print-only {
                    display: block !important;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
            }
        </style>
    <?php endif; ?>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <?php if (!$print_mode): ?>
            <?php include '../includes/header.php'; ?>
        <?php endif; ?>
        
        <main class="flex-grow container mx-auto px-4 py-8">
            <?php if (!$print_mode): ?>
                <div class="flex justify-between items-center mb-6 no-print">
                    <h1 class="text-2xl font-bold text-gray-800">Attendance Records</h1>
                    <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
                
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Records</h2>
                    
                    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="form-group">
                            <label for="event_id" class="form-label">Event</label>
                            <select id="event_id" name="event_id" class="form-control">
                                <option value="0">All Events</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?php echo $event['id']; ?>" <?php echo $event_id == $event['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="session" class="form-label">Session</label>
                            <select id="session" name="session" class="form-control">
                                <option value="">All Sessions</option>
                                <option value="morning" <?php echo $session === 'morning' ? 'selected' : ''; ?>>Morning</option>
                                <option value="afternoon" <?php echo $session === 'afternoon' ? 'selected' : ''; ?>>Afternoon</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" id="date" name="date" class="form-control" value="<?php echo $date; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="time_in" <?php echo $status === 'time_in' ? 'selected' : ''; ?>>Time In</option>
                                <option value="time_out" <?php echo $status === 'time_out' ? 'selected' : ''; ?>>Time Out</option>
                            </select>
                        </div>
                        
                        <div class="form-group flex items-end">
                            <button type="submit" class="btn btn-primary mr-2">Apply Filters</button>
                            <a href="attendance.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Print Header -->
                <div class="print-only mb-6 text-center">
                    <h1 class="text-2xl font-bold">Bago City College Time Attendance Platform</h1>
                    <h2 class="text-xl"><?php echo htmlspecialchars($department); ?> Department Attendance Report</h2>
                    <p class="mt-2">
                        <?php 
                        echo 'Date: ' . date('F d, Y', strtotime($date));
                        if ($event_id > 0) {
                            foreach ($events as $event) {
                                if ($event['id'] == $event_id) {
                                    echo ' | Event: ' . htmlspecialchars($event['title']);
                                    break;
                                }
                            }
                        }
                        if (!empty($session)) {
                            echo ' | Session: ' . ucfirst($session);
                        }
                        if (!empty($status)) {
                            echo ' | Status: ' . str_replace('_', ' ', ucfirst($status));
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Attendance Records -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if (!$print_mode): ?>
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-700">Attendance Records</h2>
                        
                        <div>
                            <?php
                            // Prepare print URL with all current filters
                            $print_url = 'attendance.php?print=1';
                            if ($event_id > 0) $print_url .= '&event_id=' . $event_id;
                            if (!empty($session)) $print_url .= '&session=' . $session;
                            if (!empty($date)) $print_url .= '&date=' . $date;
                            if (!empty($status)) $print_url .= '&status=' . $status;
                            ?>
                            <a href="<?php echo $print_url; ?>" class="btn btn-secondary" target="_blank">Print View</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto">
                    <?php if (count($attendance_records) > 0): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <?php if (!$print_mode): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['student_name']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                ID: <?php echo htmlspecialchars($record['student_id']); ?> | 
                                                Dept: <?php echo htmlspecialchars($record['student_department']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['event_title']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo !empty($record['event_department']) ? htmlspecialchars($record['event_department']) : 'All Departments'; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($record['time_recorded'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo ucfirst($record['session']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $record['status'] === 'time_in' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                <?php echo str_replace('_', ' ', ucfirst($record['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('h:i:s A', strtotime($record['time_recorded'])); ?>
                                        </td>
                                        <?php if (!$print_mode): ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php if (!empty($record['latitude']) && !empty($record['longitude'])): ?>
                                                    <a href="https://www.google.com/maps?q=<?php echo $record['latitude']; ?>,<?php echo $record['longitude']; ?>" target="_blank" class="text-blue-600 hover:underline">
                                                        View Map
                                                    </a>
                                                <?php else: ?>
                                                    No location data
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="p-6 text-center text-gray-500">
                            <p>No attendance records found matching the selected filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($print_mode): ?>
                <div class="mt-8 text-center">
                    <p>Generated on <?php echo date('F d, Y h:i A'); ?></p>
                    <p>Bago City College Time Attendance Platform</p>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                    };
                </script>
            <?php endif; ?>
        </main>
        
        <?php if (!$print_mode): ?>
            <?php include '../includes/footer.php'; ?>
        <?php endif; ?>
    </div>
    
    <?php if (!$print_mode): ?>
        <script src="../assets/js/main.js"></script>
    <?php endif; ?>
</body>
</html> 