<?php
/**
 * Admin Event Attendance Management
 * Shows detailed attendance for a specific event with management tools
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Event ID is required');
    redirect(BASE_URL . 'admin/events/index.php');
}

$event_id = intval($_GET['id']);

// Get event details
$event_query = "SELECT e.*, u.full_name as created_by_name
                FROM events e 
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.id = ?";
$stmt = mysqli_prepare($conn, $event_query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    setFlashMessage('error', 'Event not found');
    redirect(BASE_URL . 'admin/events/index.php');
}

$event = mysqli_fetch_assoc($result);

// Set filter parameters
$session = isset($_GET['session']) ? sanitize($_GET['session']) : '';
$status_type = isset($_GET['status_type']) ? sanitize($_GET['status_type']) : '';
$attendance_status = isset($_GET['attendance_status']) ? sanitize($_GET['attendance_status']) : '';
$department = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$date = isset($_GET['date']) ? sanitize($_GET['date']) : '';

// Get all departments for filtering
$dept_query = "SELECT DISTINCT u.department 
               FROM attendance a 
               INNER JOIN users u ON a.user_id = u.id 
               WHERE a.event_id = ? AND u.department IS NOT NULL AND u.department != ''
               ORDER BY u.department";
$dept_stmt = mysqli_prepare($conn, $dept_query);
mysqli_stmt_bind_param($dept_stmt, "i", $event_id);
mysqli_stmt_execute($dept_stmt);
$dept_result = mysqli_stmt_get_result($dept_stmt);
$departments = mysqli_fetch_all($dept_result, MYSQLI_ASSOC);

// Build attendance query with filters
$query = "SELECT a.*, u.full_name as student_name, u.student_id, u.department,
                 e.morning_time_in, e.morning_time_out, e.afternoon_time_in, e.afternoon_time_out
          FROM attendance a
          INNER JOIN users u ON a.user_id = u.id
          INNER JOIN events e ON a.event_id = e.id
          WHERE a.event_id = ?";
$params = [$event_id];
$types = "i";

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

if (!empty($attendance_status)) {
    $query .= " AND a.attendance_status = ?";
    $params[] = $attendance_status;
    $types .= "s";
}

if (!empty($department)) {
    $query .= " AND u.department = ?";
    $params[] = $department;
    $types .= "s";
}

if (!empty($date)) {
    $query .= " AND DATE(a.time_recorded) = ?";
    $params[] = $date;
    $types .= "s";
}

$query .= " ORDER BY a.time_recorded DESC";

// Execute query
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate statistics
$total_records = count($attendance_records);
$present_count = 0;
$late_count = 0;
$absent_count = 0;
$time_in_count = 0;
$time_out_count = 0;
$morning_count = 0;
$afternoon_count = 0;
$on_time_count = 0;
$very_late_count = 0;

foreach ($attendance_records as $record) {
    // Count attendance status
    switch ($record['attendance_status']) {
        case 'present':
            $present_count++;
            break;
        case 'late':
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
    
    // Count sessions
    if ($record['session'] === 'morning') {
        $morning_count++;
    } else {
        $afternoon_count++;
    }
    
    // Calculate timing for time_in records
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

// Get unique students count
$unique_query = "SELECT COUNT(DISTINCT user_id) as unique_students FROM attendance WHERE event_id = ?";
$unique_stmt = mysqli_prepare($conn, $unique_query);
mysqli_stmt_bind_param($unique_stmt, "i", $event_id);
mysqli_stmt_execute($unique_stmt);
$unique_result = mysqli_stmt_get_result($unique_stmt);
$unique_stats = mysqli_fetch_assoc($unique_result);

// Set page title
$page_title = "Event Attendance - " . htmlspecialchars($event['title']);

// Page actions
$page_actions = '
<div class="flex space-x-2">
    <a href="#" onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
        Export Excel
    </a>
    <a href="#" onclick="printReport()" class="bg-gray-600 hover:bg-gray-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
        </svg>
        Print Report
    </a>
    <a href="events/view.php?id=' . $event_id . '" class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
        </svg>
        View Event
    </a>
</div>
';

// Extra JavaScript
$extra_js = '
<script>
    function exportToExcel() {
        // Generate Excel-compatible content
        let content = "Student ID\\tStudent Name\\tDepartment\\tDate\\tTime\\tSession\\tType\\tStatus\\tTiming Status\\n";
        
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
        
        const filename = "event_' . $event_id . '_attendance_" + new Date().toISOString().slice(0,10) + ".xls";
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
        const params = new URLSearchParams();
        params.append("id", "' . $event_id . '");
        window.location.href = window.location.pathname + "?" + params.toString();
    }
</script>
';

// Start output buffering
ob_start();
?>

<!-- Event Info Header -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h1>
            <p class="text-gray-600 mt-1">
                <?php echo date('M d, Y', strtotime($event['start_date'])); ?> - 
                <?php echo date('M d, Y', strtotime($event['end_date'])); ?>
            </p>
            <div class="text-sm text-gray-500 mt-1">
                Created by <?php echo htmlspecialchars($event['created_by_name']); ?>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if (!empty($event['department'])): ?>
                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-sm font-medium rounded-full">
                    <?php echo htmlspecialchars($event['department']); ?>
                </span>
            <?php endif; ?>
            <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">
                <?php echo $unique_stats['unique_students']; ?> Students
            </span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-6">
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
    
    <div class="stat-card danger">
        <h3>Absent</h3>
        <div class="value"><?php echo number_format($absent_count); ?></div>
        <div class="percentage"><?php echo $total_records > 0 ? round(($absent_count / $total_records) * 100) . '%' : '0%'; ?></div>
    </div>
    
    <div class="stat-card info">
        <h3>Time In</h3>
        <div class="value"><?php echo number_format($time_in_count); ?></div>
    </div>
    
    <div class="stat-card secondary">
        <h3>Time Out</h3>
        <div class="value"><?php echo number_format($time_out_count); ?></div>
    </div>
    
    <div class="stat-card success">
        <h3>Morning</h3>
        <div class="value"><?php echo number_format($morning_count); ?></div>
    </div>
    
    <div class="stat-card warning">
        <h3>Afternoon</h3>
        <div class="value"><?php echo number_format($afternoon_count); ?></div>
    </div>
</div>

<!-- Event Schedule Info -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-green-50 border border-green-100 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-green-700 mb-2">Morning Session</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Time In</p>
                <p class="font-medium"><?php echo date('h:i A', strtotime($event['morning_time_in'])); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time Out</p>
                <p class="font-medium"><?php echo date('h:i A', strtotime($event['morning_time_out'])); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-amber-50 border border-amber-100 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-amber-700 mb-2">Afternoon Session</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Time In</p>
                <p class="font-medium"><?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time Out</p>
                <p class="font-medium"><?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Filter Attendance Records</h2>
    <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
        <input type="hidden" name="id" value="<?php echo $event_id; ?>">
        
        <div>
            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" id="date" name="date" value="<?php echo $date; ?>" 
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
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
        
        <div class="flex items-end space-x-2">
            <button type="submit" class="px-4 py-2 text-sm bg-primary hover:bg-primary-dark text-white rounded-lg">
                Apply Filters
            </button>
            <button type="button" onclick="resetFilters()" class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-800">
                Reset
            </button>
        </div>
    </form>
</div>

<!-- Attendance Records -->
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="mb-4 flex justify-between items-center">
        <h2 class="text-lg font-semibold">Attendance Records</h2>
        <span class="text-sm text-gray-500">Showing <?php echo count($attendance_records); ?> records</span>
    </div>
    
    <div class="overflow-x-auto">
        <table id="attendance-table" class="min-w-full">
            <thead>
                <tr class="bg-gray-50 text-gray-700">
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Student</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Department</th>
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
                                $attendance_status = $record['attendance_status'] ?? 'present';
                                $statusColor = '';
                                
                                switch ($attendance_status) {
                                    case 'present':
                                        $statusColor = 'bg-green-100 text-green-800';
                                        break;
                                    case 'late':
                                        $statusColor = 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'absent':
                                        $statusColor = 'bg-red-100 text-red-800';
                                        break;
                                }
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColor; ?>">
                                    <?php echo ucfirst($attendance_status); ?>
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
                                <a href="users/view.php?id=<?php echo $record['user_id']; ?>" class="text-blue-600 hover:text-blue-800 mr-3">
                                    View Student
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-lg font-medium">No attendance records found</span>
                                <p class="text-sm mt-2">Try adjusting your filters or check if students have scanned their attendance</p>
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
include '../includes/admin_layout.php';
?> 