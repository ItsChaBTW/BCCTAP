<?php
/**
 * Attendance Records for Department Heads
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
$section_filter = isset($_GET['section']) ? sanitize($_GET['section']) : '';
$year_level_filter = isset($_GET['year_level']) ? intval($_GET['year_level']) : 0;

// Build the query based on filters
$query = "SELECT a.*, u.full_name as student_name, u.student_id, u.department as student_department, u.year_level, u.section,
                 e.title as event_title, e.department as event_department
          FROM attendance a 
          INNER JOIN users u ON a.user_id = u.id 
          INNER JOIN events e ON a.event_id = e.id 
          WHERE u.department = ?";

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

if (!empty($section_filter)) {
    $query .= " AND u.section = ?";
    $params[] = $section_filter;
    $types .= "s";
}

if ($year_level_filter > 0) {
    $query .= " AND u.year_level = ?";
    $params[] = $year_level_filter;
    $types .= "i";
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
    <title>Department Attendance Management - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if ($print_mode): ?>
        <style>
            @media print {
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 12px;
                    line-height: 1.4;
                    margin: 0;
                    padding: 15px;
                }
                .no-print { display: none !important; }
                .print-only { display: block !important; }
                
                .print-header {
                    border-bottom: 2px solid #333;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                }
                
                .print-header h1 {
                    font-size: 20px;
                    margin: 0 0 8px 0;
                    text-align: center;
                }
                
                .print-header h2 {
                    font-size: 16px;
                    margin: 0 0 10px 0;
                    text-align: center;
                    font-weight: bold;
                }
                
                .report-params {
                    margin-bottom: 15px;
                    border: 1px solid #ddd;
                    padding: 10px;
                }
                
                .report-params h3 {
                    font-size: 14px;
                    margin: 0 0 8px 0;
                    font-weight: bold;
                }
                
                .params-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 8px;
                    font-size: 11px;
                }
                
                .summary-stats {
                    margin-bottom: 15px;
                    border: 1px solid #ddd;
                    padding: 10px;
                }
                
                .summary-stats h3 {
                    font-size: 14px;
                    margin: 0 0 8px 0;
                    font-weight: bold;
                }
                
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 8px;
                    font-size: 11px;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 10px;
                }
                
                table th,
                table td {
                    border: 1px solid #ddd;
                    padding: 4px 6px;
                    text-align: left;
                    vertical-align: top;
                }
                
                table th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                    font-size: 9px;
                    text-transform: uppercase;
                }
                
                .status-badge {
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 9px;
                    font-weight: bold;
                }
                
                .status-present { background-color: #d4edda; color: #155724; }
                .status-late { background-color: #fff3cd; color: #856404; }
                .status-excused { background-color: #cce5ff; color: #004085; }
                
                .print-footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 10px;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                
                /* Page break handling */
                @page {
                    size: A4 portrait;
                    margin: 1cm;
                }
                
                /* Avoid breaking table rows across pages */
                tbody tr {
                    page-break-inside: avoid;
                }
                
                /* Keep table headers at top of new pages */
                thead {
                    display: table-header-group;
                }
                
                /* Ensure proper spacing between sections */
                .print-header, .report-params, .summary-stats {
                    page-break-inside: avoid;
                }
                
                /* Remove overflow scroll and fix table layout for print */
                .overflow-x-auto {
                    overflow-x: visible !important;
                    overflow-y: visible !important;
                }
                
                /* Fix table layout for print */
                .min-w-full {
                    width: 100% !important;
                    table-layout: fixed !important;
                }
                
                /* Adjust column widths for print */
                table th:nth-child(1), table td:nth-child(1) { width: 16%; } /* Student */
                table th:nth-child(2), table td:nth-child(2) { width: 12%; } /* Year/Section */
                table th:nth-child(3), table td:nth-child(3) { width: 20%; } /* Event */
                table th:nth-child(4), table td:nth-child(4) { width: 12%; } /* Date */
                table th:nth-child(5), table td:nth-child(5) { width: 10%; } /* Session */
                table th:nth-child(6), table td:nth-child(6) { width: 12%; } /* Status */
                table th:nth-child(7), table td:nth-child(7) { width: 18%; } /* Time */
                
                /* Ensure text wraps properly */
                table td, table th {
                    word-wrap: break-word !important;
                    overflow-wrap: break-word !important;
                    white-space: normal !important;
                }
                
                /* Remove all scrollbars and ensure content fits */
                body {
                    overflow: visible !important;
                }
                
                .container {
                    max-width: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }
                
                /* Remove any potential horizontal scrolling */
                * {
                    overflow-x: visible !important;
                }
                
                /* Ensure the table container doesn't scroll */
                .bg-white.rounded-lg.shadow-md.overflow-hidden {
                    overflow: visible !important;
                }
                
                /* Remove all scrollbars and ensure content fits */
                body {
                    overflow: visible !important;
                }
                
                .container {
                    max-width: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }
                
                /* Remove any potential horizontal scrolling */
                * {
                    overflow-x: visible !important;
                }
                
                /* Ensure the table container doesn't scroll */
                .bg-white.rounded-lg.shadow-md.overflow-hidden {
                    overflow: visible !important;
                }
            }
            
            .print-only { display: none; }
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
                    <h1 class="text-2xl font-bold text-gray-800">Department Attendance Records</h1>
                    <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
                
                <!-- Department Header -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($department); ?> Department</h2>
                    <p class="text-gray-600">View and filter attendance records for students in your department</p>
                </div>
                
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Records</h2>
                    
                    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
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
                            <label for="year_level" class="form-label">Year Level</label>
                            <select id="year_level" name="year_level" class="form-control">
                                <option value="0">All Year Levels</option>
                                <?php foreach ($year_levels as $level): ?>
                                    <option value="<?php echo $level['year_level']; ?>" <?php echo $year_level_filter == $level['year_level'] ? 'selected' : ''; ?>>
                                        Year <?php echo $level['year_level']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="section" class="form-label">Section</label>
                            <select id="section" name="section" class="form-control">
                                <option value="">All Sections</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?php echo htmlspecialchars($sec['section']); ?>" <?php echo $section_filter === $sec['section'] ? 'selected' : ''; ?>>
                                        Section <?php echo htmlspecialchars($sec['section']); ?>
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
                                <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>Present</option>
                                <option value="late" <?php echo $status === 'late' ? 'selected' : ''; ?>>Late</option>
                                <option value="excused" <?php echo $status === 'excused' ? 'selected' : ''; ?>>Excused</option>
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
                <div class="print-only print-header">
                    <h1>Bago City College Time Attendance Platform</h1>
                    <h2><?php echo htmlspecialchars($department); ?> Department Attendance Report</h2>
                    <p style="text-align: center; margin: 5px 0; font-size: 11px;">Generated on: <?php echo date('F d, Y g:i A'); ?></p>
                </div>
                
                <!-- Filter Information -->
                <div class="print-only report-params">
                    <h3>Report Parameters:</h3>
                    <div class="params-grid">
                        <div>
                            <strong>Date:</strong> <?php echo !empty($date) ? date('F d, Y', strtotime($date)) : 'All Dates'; ?>
                        </div>
                        <?php if ($event_id > 0): ?>
                            <div>
                                <strong>Event:</strong> 
                                <?php 
                                foreach ($events as $event) {
                                    if ($event['id'] == $event_id) {
                                        echo htmlspecialchars($event['title']);
                                        break;
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($year_level_filter > 0): ?>
                            <div><strong>Year Level:</strong> Year <?php echo $year_level_filter; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($section_filter)): ?>
                            <div><strong>Section:</strong> Section <?php echo htmlspecialchars($section_filter); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($session)): ?>
                            <div><strong>Session:</strong> <?php echo ucfirst($session); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($status)): ?>
                            <div><strong>Status Filter:</strong> <?php echo str_replace('_', ' ', ucfirst($status)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Summary Statistics for Print -->
                <?php if (count($attendance_records) > 0): ?>
                    <div class="print-only summary-stats">
                        <h3>Summary Statistics:</h3>
                        <div class="stats-grid">
                            <div><strong>Total Records:</strong> <?php echo count($attendance_records); ?></div>
                            <div><strong>Present:</strong> <?php echo count(array_filter($attendance_records, function($r) { return $r['status'] === 'present'; })); ?></div>
                            <div><strong>Late:</strong> <?php echo count(array_filter($attendance_records, function($r) { return $r['status'] === 'late'; })); ?></div>
                            <div><strong>Unique Students:</strong> <?php 
                                $unique_students = array_unique(array_map(function($record) {
                                    return $record['student_id'];
                                }, $attendance_records));
                                echo count($unique_students); 
                            ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Summary Stats -->
            <?php if (!$print_mode && count($attendance_records) > 0): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600"><?php echo count($attendance_records); ?></p>
                            <p class="text-sm text-gray-600">Total Records</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">
                                <?php echo count(array_filter($attendance_records, function($r) { return $r['status'] === 'present'; })); ?>
                            </p>
                            <p class="text-sm text-gray-600">Present</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-600">
                                <?php echo count(array_filter($attendance_records, function($r) { return $r['status'] === 'late'; })); ?>
                            </p>
                            <p class="text-sm text-gray-600">Late</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-600">
                                <?php 
                                // Get unique student IDs from attendance records
                                $unique_students = array_unique(array_map(function($record) {
                                    return $record['student_id']; // This is from users table via join
                                }, $attendance_records));
                                echo count($unique_students); 
                                ?>
                            </p>
                            <p class="text-sm text-gray-600">Unique Students</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Attendance Records -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if (!$print_mode): ?>
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-700">
                            Attendance Records
                            <?php if (!empty($section_filter) || $year_level_filter > 0): ?>
                                <span class="text-sm font-normal text-gray-500">
                                    (<?php 
                                    $filter_labels = [];
                                    if ($year_level_filter > 0) $filter_labels[] = "Year " . $year_level_filter;
                                    if (!empty($section_filter)) $filter_labels[] = "Section " . $section_filter;
                                    echo implode(", ", $filter_labels);
                                    ?>)
                                </span>
                            <?php endif; ?>
                        </h2>
                        
                        <div class="flex space-x-2">
                            <?php
                            // Prepare URLs with all current filters
                            $filter_params = [];
                            if ($event_id > 0) $filter_params[] = 'event_id=' . $event_id;
                            if (!empty($session)) $filter_params[] = 'session=' . urlencode($session);
                            if (!empty($date)) $filter_params[] = 'date=' . $date;
                            if (!empty($status)) $filter_params[] = 'status=' . urlencode($status);
                            if (!empty($section_filter)) $filter_params[] = 'section=' . urlencode($section_filter);
                            if ($year_level_filter > 0) $filter_params[] = 'year_level=' . $year_level_filter;
                            
                            $query_string = !empty($filter_params) ? '&' . implode('&', $filter_params) : '';
                            $print_url = 'attendance.php?print=1' . $query_string;
                            $excel_url = 'export_excel.php?' . ltrim($query_string, '&');
                            ?>
                            
                            <a href="<?php echo $print_url; ?>" class="btn btn-secondary" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                </svg>
                                Print
                            </a>
                            
                            <a href="<?php echo $excel_url; ?>" class="btn btn-success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                Download Excel
                            </a>
                            
                            <?php if (!empty($section_filter) || $year_level_filter > 0 || !empty($session) || !empty($status) || $event_id > 0): ?>
                                <span class="text-sm text-gray-500 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                                    </svg>
                                    Filtered
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto">
                    <?php if (count($attendance_records) > 0): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year/Section</th>
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
                                                ID: <?php echo htmlspecialchars($record['student_id']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                Year <?php echo $record['year_level'] ?: 'N/A'; ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Section <?php echo $record['section'] ?: 'N/A'; ?>
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
                                            <?php if ($print_mode): ?>
                                                <span class="status-badge status-<?php echo $record['status']; ?>">
                                                    <?php echo str_replace('_', ' ', ucfirst($record['status'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    switch($record['status']) {
                                                        case 'present': echo 'bg-green-100 text-green-800'; break;
                                                        case 'late': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'excused': echo 'bg-blue-100 text-blue-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo str_replace('_', ' ', ucfirst($record['status'])); ?>
                                                </span>
                                            <?php endif; ?>
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
                            <?php if (!empty($section_filter) || $year_level_filter > 0): ?>
                                <p class="mt-2 text-sm">Try adjusting your year level or section filters, or <a href="attendance.php" class="text-blue-600 hover:underline">view all records</a>.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($print_mode): ?>
                <div class="print-footer">
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