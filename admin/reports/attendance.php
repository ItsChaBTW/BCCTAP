<?php
/**
 * Admin Attendance Reports
 * Comprehensive attendance reporting with detailed analytics
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Set default filter values
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');
$department = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$event = isset($_GET['event']) ? intval($_GET['event']) : 0;
$attendance_status = isset($_GET['attendance_status']) ? sanitize($_GET['attendance_status']) : '';
$session = isset($_GET['session']) ? sanitize($_GET['session']) : '';
$status_type = isset($_GET['status_type']) ? sanitize($_GET['status_type']) : '';

// Get all departments for filter dropdown
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);

// Get events for filter dropdown
$query = "SELECT id, title FROM events ORDER BY created_at DESC LIMIT 100";
$events_result = mysqli_query($conn, $query);
$events = mysqli_fetch_all($events_result, MYSQLI_ASSOC);

// Build the attendance query with filters - Enhanced with more details
$query = "SELECT a.id, a.time_recorded, a.session, a.status, a.attendance_status,
                 u.id as user_id, u.full_name as student_name, u.student_id, u.department, 
                 e.id as event_id, e.title as event_title, e.morning_time_in, e.morning_time_out,
                 e.afternoon_time_in, e.afternoon_time_out
          FROM attendance a
          INNER JOIN users u ON a.user_id = u.id
          INNER JOIN events e ON a.event_id = e.id
          WHERE a.time_recorded BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
$params = [$start_date, $end_date];
$types = "ss";

if (!empty($department)) {
    $query .= " AND u.department = ?";
    $params[] = $department;
    $types .= "s";
}

if ($event > 0) {
    $query .= " AND e.id = ?";
    $params[] = $event;
    $types .= "i";
}

if (!empty($attendance_status)) {
    $query .= " AND a.attendance_status = ?";
    $params[] = $attendance_status;
    $types .= "s";
}

if (!empty($session)) {
    $query .= " AND a.session = ?";
    $params[] = $session;
    $types .= "s";
}

if (!empty($status_type)) {
    $query .= " AND a.status = ?";
    $params[] = $status_type;
    $types .= "s";
}

$query .= " ORDER BY a.time_recorded DESC LIMIT 2000";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Count statistics
$total_records = count($attendance_records);
$present_count = 0;
$late_count = 0;
$late_time_in_count = 0;
$absent_count = 0;
$time_in_count = 0;
$time_out_count = 0;
$on_time_count = 0;
$very_late_count = 0;

foreach ($attendance_records as $record) {
    // Count attendance status
    switch ($record['attendance_status']) {
        case 'present':
            $present_count++;
            break;
        case 'late':
            // Check if this is a late time-in specifically
            if ($record['status'] === 'time_in') {
                $late_time_in_count++;
            }
            $late_count++;
            break;
        case 'absent':
            $absent_count++;
            break;
    }
    
    // Count status types
    if ($record['status'] === 'time_in') {
        $time_in_count++;
    } else {
        $time_out_count++;
    }
    
    // Calculate timing for detailed stats
    if ($record['status'] === 'time_in') {
        $scheduled_time = $record['session'] === 'morning' ? 
            strtotime($record['morning_time_in']) : 
            strtotime($record['afternoon_time_in']);
        $actual_time = strtotime($record['time_recorded']);
        $diff_minutes = ($actual_time - $scheduled_time) / 60;
        
        if ($diff_minutes <= 0) {
            $on_time_count++;
        } elseif ($diff_minutes > 30) {
            $very_late_count++;
        }
    }
}

// Get department statistics including late time-in data
$dept_stats_query = "SELECT u.department,
    COUNT(*) as total,
    COUNT(CASE WHEN a.attendance_status = 'present' THEN 1 END) as present,
    COUNT(CASE WHEN a.attendance_status = 'late' THEN 1 END) as late,
    COUNT(CASE WHEN a.attendance_status = 'late' AND a.status = 'time_in' THEN 1 END) as late_time_in,
    COUNT(CASE WHEN a.attendance_status = 'absent' THEN 1 END) as absent
    FROM attendance a
    INNER JOIN users u ON a.user_id = u.id
    INNER JOIN events e ON a.event_id = e.id
    WHERE a.time_recorded BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";

$dept_stmt = mysqli_prepare($conn, $dept_stats_query);
mysqli_stmt_bind_param($dept_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($dept_stmt);
$dept_result = mysqli_stmt_get_result($dept_stmt);
$department_stats = mysqli_fetch_all($dept_result, MYSQLI_ASSOC);

// Set the page title
$page_title = "Attendance Reports";

// Add Export Buttons in Page Actions
$page_actions = '
<div class="flex space-x-2">
    <a href="#" onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
        Export Excel
    </a>
    <a href="#" onclick="exportToCSV()" class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
        Export CSV
    </a>
    <a href="#" onclick="printReport()" class="bg-gray-600 hover:bg-gray-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
        </svg>
        Print
    </a>
</div>
';

// Add extra JavaScript for export and print functionality
$extra_js = '
<script>
    function exportToCSV() {
        const table = document.getElementById("attendance-table");
        if (!table) return;
        
        let csv = "Student ID,Student Name,Department,Event,Date,Time,Session,Type,Status,Timing Status\n";
        
        for (let i = 1; i < table.rows.length; i++) {
            const row = table.rows[i];
            if (row.cells.length === 1) continue; // Skip "no data" row
            
            const rowData = [];
            rowData.push(row.getAttribute("data-student-id") || "");
            
            for (let j = 0; j < row.cells.length - 1; j++) {
                const cellText = row.cells[j].innerText.replace(/,/g, " ").replace(/\n/g, " ");
                rowData.push(`"${cellText}"`);
            }
            
            csv += rowData.join(",") + "\n";
        }
        
        const filename = "attendance_report_" + new Date().toISOString().slice(0,10) + ".csv";
        downloadFile(csv, filename, "text/csv");
    }
    
    function exportToExcel() {
        // Generate Excel-compatible content
        let content = "Student ID\\tStudent Name\\tDepartment\\tEvent\\tDate\\tTime\\tSession\\tType\\tStatus\\tTiming Status\\n";
        
        const table = document.getElementById("attendance-table");
        if (!table) return;
        
        for (let i = 1; i < table.rows.length; i++) {
            const row = table.rows[i];
            if (row.cells.length === 1) continue;
            
            const rowData = [];
            rowData.push(row.getAttribute("data-student-id") || "");
            
            for (let j = 0; j < row.cells.length - 1; j++) {
                const cellText = row.cells[j].innerText.replace(/\\t/g, " ").replace(/\\n/g, " ");
                rowData.push(cellText);
            }
            
            content += rowData.join("\\t") + "\\n";
        }
        
        const filename = "attendance_report_" + new Date().toISOString().slice(0,10) + ".xls";
        downloadFile(content, filename, "application/vnd.ms-excel");
    }
    
    function downloadFile(content, filename, contentType) {
        const blob = new Blob([content], { type: contentType + ";charset=utf-8;" });
        
        if (navigator.msSaveBlob) {
            navigator.msSaveBlob(blob, filename);
        } else {
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
    
    function printReport() {
        window.print();
    }
    
    function resetFilters() {
        document.getElementById("start_date").value = "' . date('Y-m-01') . '";
        document.getElementById("end_date").value = "' . date('Y-m-d') . '";
        document.getElementById("department").value = "";
        document.getElementById("event").value = "";
        document.getElementById("attendance_status").value = "";
        document.getElementById("session").value = "";
        document.getElementById("status_type").value = "";
        applyFilters();
    }
    
    function applyFilters() {
        const form = document.getElementById("filter-form");
        form.submit();
    }
</script>
';

// Start output buffering for page content
ob_start();
?>

<!-- Statistics Overview -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
    <div class="stat-card primary">
        <h3>Total Records</h3>
        <div class="value"><?php echo number_format($total_records); ?></div>
    </div>
    
    <div class="stat-card success">
        <h3>Present</h3>
        <div class="value"><?php echo number_format($present_count); ?></div>
        <div class="percentage"><?php echo $total_records > 0 ? round(($present_count / $total_records) * 100) . '%' : '0%'; ?></div>
    </div>
    
    <div class="stat-card warning">
        <h3>Late</h3>
        <div class="value"><?php echo number_format($late_count); ?></div>
        <div class="percentage"><?php echo $total_records > 0 ? round(($late_count / $total_records) * 100) . '%' : '0%'; ?></div>
    </div>
    
    <div class="stat-card bg-orange-50 border-orange-200">
        <div class="stat-icon bg-orange-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="stat-content">
            <div class="label">Late Time In</div>
            <div class="value"><?php echo number_format($late_time_in_count); ?></div>
            <div class="percentage"><?php echo $total_records > 0 ? round(($late_time_in_count / $total_records) * 100) . '%' : '0%'; ?></div>
        </div>
    </div>
    
    <div class="stat-card danger">
        <h3>Absent</h3>
        <div class="value"><?php echo number_format($absent_count); ?></div>
        <div class="percentage"><?php echo $total_records > 0 ? round(($absent_count / $total_records) * 100) . '%' : '0%'; ?></div>
    </div>
    
    <div class="stat-card info">
        <h3>Time In</h3>
        <div class="value"><?php echo number_format($time_in_count); ?></div>
        <div class="percentage"><?php echo $total_records > 0 ? round(($time_in_count / $total_records) * 100) . '%' : '0%'; ?></div>
    </div>
    
    <div class="stat-card secondary">
        <h3>Time Out</h3>
        <div class="value"><?php echo number_format($time_out_count); ?></div>
        <div class="percentage"><?php echo $total_records > 0 ? round(($time_out_count / $total_records) * 100) . '%' : '0%'; ?></div>
    </div>
</div>

<!-- Department Statistics -->
<?php if (count($department_stats) > 0): ?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Department Statistics</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($department_stats as $dept): ?>
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-medium text-gray-800 mb-2"><?php echo htmlspecialchars($dept['department'] ?: 'Unassigned'); ?></h3>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>Total: <span class="font-semibold"><?php echo $dept['total']; ?></span></div>
                    <div>Present: <span class="text-green-600 font-semibold"><?php echo $dept['present']; ?></span></div>
                    <div>Late: <span class="text-yellow-600 font-semibold"><?php echo $dept['late']; ?></span></div>
                    <div>Late Time In: <span class="text-orange-600 font-semibold"><?php echo $dept['late_time_in'] ?? 0; ?></span></div>
                    <div>Absent: <span class="text-red-600 font-semibold"><?php echo $dept['absent']; ?></span></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Advanced Filters -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Filter Records</h2>
    <form id="filter-form" action="" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" 
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
        </div>
        
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" 
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
        </div>
        
        <div>
            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select id="department" name="department" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                            <?php echo $department === $dept['department'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['department']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="event" class="block text-sm font-medium text-gray-700 mb-1">Event</label>
            <select id="event" name="event" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                <option value="">All Events</option>
                <?php foreach ($events as $evt): ?>
                    <option value="<?php echo $evt['id']; ?>" 
                            <?php echo $event === intval($evt['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($evt['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="session" class="block text-sm font-medium text-gray-700 mb-1">Session</label>
            <select id="session" name="session" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                <option value="">All Sessions</option>
                <option value="morning" <?php echo $session === 'morning' ? 'selected' : ''; ?>>Morning</option>
                <option value="afternoon" <?php echo $session === 'afternoon' ? 'selected' : ''; ?>>Afternoon</option>
            </select>
        </div>

        <div>
            <label for="status_type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select id="status_type" name="status_type" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                <option value="">All Types</option>
                <option value="time_in" <?php echo $status_type === 'time_in' ? 'selected' : ''; ?>>Time In</option>
                <option value="time_out" <?php echo $status_type === 'time_out' ? 'selected' : ''; ?>>Time Out</option>
            </select>
        </div>

        <div>
            <label for="attendance_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select id="attendance_status" name="attendance_status" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                <option value="">All Status</option>
                <option value="present" <?php echo $attendance_status === 'present' ? 'selected' : ''; ?>>Present</option>
                <option value="late" <?php echo $attendance_status === 'late' ? 'selected' : ''; ?>>Late</option>
                <option value="absent" <?php echo $attendance_status === 'absent' ? 'selected' : ''; ?>>Absent</option>
            </select>
        </div>
        
        <div class="xl:col-span-7 flex items-center justify-end space-x-3">
            <button type="button" onclick="resetFilters()" class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-800">
                Reset Filters
            </button>
            <button type="submit" class="px-4 py-2 text-sm bg-primary hover:bg-primary-dark text-white rounded-lg">
                Apply Filters
            </button>
        </div>
    </form>
</div>

<!-- Attendance Records -->
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="mb-4 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Detailed Attendance Records</h2>
        <span class="text-sm text-gray-500">Showing <?php echo count($attendance_records); ?> records</span>
    </div>
    
    <div class="overflow-x-auto">
        <table id="attendance-table" class="min-w-full">
            <thead>
                <tr class="bg-gray-50 text-gray-700">
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Student</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Department</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Event</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Date</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Time</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Session</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Type</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Status</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Timing</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($attendance_records) > 0): ?>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr data-student-id="<?php echo htmlspecialchars($record['student_id'] ?? ''); ?>" 
                            class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="py-3 px-4 text-sm">
                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($record['student_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($record['student_id'] ?? 'No ID'); ?></div>
                            </td>
                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($record['department'] ?? 'Not assigned'); ?></td>
                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($record['event_title']); ?></td>
                            <td class="py-3 px-4 text-sm"><?php echo date('M d, Y', strtotime($record['time_recorded'])); ?></td>
                            <td class="py-3 px-4 text-sm"><?php echo date('h:i A', strtotime($record['time_recorded'])); ?></td>
                            <td class="py-3 px-4 text-sm">
                                <span class="capitalize"><?php echo $record['session']; ?></span>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <?php if ($record['status'] === 'time_in'): ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Time In</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Time Out</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <?php
                                $badge_class = '';
                                $status_text = '';
                                switch ($record['attendance_status']) {
                                    case 'present':
                                        $badge_class = 'bg-green-100 text-green-800';
                                        $status_text = 'Present';
                                        break;
                                    case 'late':
                                        if ($record['status'] === 'time_in') {
                                            $badge_class = 'bg-orange-100 text-orange-800';
                                            $status_text = 'Late Time In';
                                        } else {
                                            $badge_class = 'bg-yellow-100 text-yellow-800';
                                            $status_text = 'Late';
                                        }
                                        break;
                                    case 'absent':
                                        $badge_class = 'bg-red-100 text-red-800';
                                        $status_text = 'Absent';
                                        break;
                                    default:
                                        $badge_class = 'bg-gray-100 text-gray-800';
                                        $status_text = ucfirst($record['attendance_status']);
                                }
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $badge_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <?php 
                                // Calculate timing status for time_in records
                                if ($record['status'] === 'time_in') {
                                    $scheduled_time = $record['session'] === 'morning' ? 
                                        strtotime($record['morning_time_in']) : 
                                        strtotime($record['afternoon_time_in']);
                                    $actual_time = strtotime($record['time_recorded']);
                                    $diff_minutes = ($actual_time - $scheduled_time) / 60;
                                    
                                    if ($diff_minutes <= 0) {
                                        echo '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">On Time</span>';
                                    } elseif ($diff_minutes <= 15) {
                                        echo '<span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Late (' . round($diff_minutes) . 'm)</span>';
                                    } else {
                                        echo '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Very Late (' . round($diff_minutes) . 'm)</span>';
                                    }
                                } else {
                                    echo '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Time Out</span>';
                                }
                                ?>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <a href="../users/view.php?id=<?php echo $record['user_id']; ?>" class="text-blue-600 hover:text-blue-800 mr-3">
                                    View Student
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-lg font-medium">No attendance records found</span>
                                <p class="text-sm mt-2">Try adjusting your filters or adding data to the system</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$page_content = ob_get_clean();
include '../../includes/admin_layout.php';
?> 