<?php
/**
 * Admin Reports Dashboard
 * Allows administrators to generate and view various attendance reports
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Set default filter values
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');        // Current day
$department = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$event = isset($_GET['event']) ? intval($_GET['event']) : 0;
$attendance_status = isset($_GET['attendance_status']) ? sanitize($_GET['attendance_status']) : '';

// Get all departments for filter dropdown
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);

// Get events for filter dropdown
$query = "SELECT id, title FROM events ORDER BY created_at DESC LIMIT 100";
$events_result = mysqli_query($conn, $query);
$events = mysqli_fetch_all($events_result, MYSQLI_ASSOC);

// Build the attendance query with filters
$query = "SELECT a.id, a.time_recorded, a.session, a.attendance_status,
                 u.id as user_id, u.full_name as student_name, u.student_id as student_id, u.department, 
                 e.id as event_id, e.title as event_title
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

$query .= " ORDER BY a.time_recorded DESC LIMIT 1000";

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
$absent_count = 0;

foreach ($attendance_records as $record) {
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
}

// Set the page title
$page_title = "Attendance Reports";

// Add Export Buttons in Page Actions
$page_actions = '
<div class="flex space-x-2">
    <a href="#" onclick="exportToCSV()" class="bg-green-600 hover:bg-green-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
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
    const DEFAULT_START_DATE = ' . date("Y-m-01") . ';
    const DEFAULT_END_DATE = ' . date("Y-m-d") . ';
    
    function exportToCSV() {
        const table = document.getElementById("attendance-table");
        if (!table) return;
        
        let csv = "Student ID,Student Name,Department,Event,Date,Time,Session,Status\n";
        
        for (let i = 1; i < table.rows.length; i++) {
            const row = table.rows[i];
            const rowData = [];
            
            rowData.push(row.getAttribute("data-student-id") || "");
            
            for (let j = 0; j < row.cells.length - 1; j++) {
                const cellText = row.cells[j].innerText.replace(/,/g, " ").replace(/\n/g, " ");
                rowData.push(cellText);
            }
            
            csv += rowData.join(",") + "\n";
        }
        
        const filename = "attendance_report_" + new Date().toISOString().slice(0,10) + ".csv";
        const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
        
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
    
    document.addEventListener("DOMContentLoaded", function() {
        const startDateInput = document.getElementById("start_date");
        const endDateInput = document.getElementById("end_date");
        
        if (!startDateInput.value) {
            startDateInput.value = DEFAULT_START_DATE;
        }
        if (!endDateInput.value) {
            endDateInput.value = DEFAULT_END_DATE;
        }
        // Filter form submission handling
        const filterForm = document.getElementById("filter-form");
        if (filterForm) {
            filterForm.addEventListener("submit", function(e) {
                e.preventDefault();
                applyFilters();
            });
        }
        
        // Reset filters
        const resetButton = document.getElementById("reset-filters");
        if (resetButton) {
            resetButton.addEventListener("click", function(e) {
                e.preventDefault();
                resetFilters();
            });
        }
    });
    
    function resetFilters() {
        document.getElementById("start_date").value = DEFAULT_START_DATE;
        document.getElementById("end_date").value = DEFAULT_END_DATE;
        document.getElementById("department").value = "";
        document.getElementById("event").value = "";
        document.getElementById("attendance_status").value = "";
        applyFilters();
    }
    
    function applyFilters() {
        const start_date = document.getElementById("start_date").value;
        const end_date = document.getElementById("end_date").value;
        const department = document.getElementById("department").value;
        const event = document.getElementById("event").value;
        const attendance_status = document.getElementById("attendance_status").value;
        
        // Validate dates
        if (start_date && end_date && new Date(start_date) > new Date(end_date)) {
            alert("End date cannot be earlier than start date");
            return;
        }
        
        // Build URL with filters
        const params = new URLSearchParams();
        params.append("start_date", start_date);
        params.append("end_date", end_date);
        
        if (department) {
            params.append("department", department);
        }
        if (event) {
            params.append("event", event);
        }
        if (attendance_status) {
            params.append("attendance_status", attendance_status);
        }
        
        // Redirect with filters
        window.location.href = window.location.pathname + "?" + params.toString();
    }
</script>
';

// Start output buffering for page content
ob_start();
?>
<div class="max-w-7xl mx-auto px-2 sm:px-4 py-6">
<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="flex items-center p-5 bg-gradient-to-r from-blue-500 to-blue-700 rounded-2xl shadow group hover:shadow-lg transition">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
            <div class="text-white text-xs font-semibold uppercase">Total Records</div>
            <div class="text-2xl font-bold text-white"><?php echo number_format($total_records); ?></div>
        </div>
    </div>
    <div class="flex items-center p-5 bg-gradient-to-r from-green-400 to-green-600 rounded-2xl shadow group hover:shadow-lg transition">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9 16l2-2 4 4"/></svg>
        </div>
        <div>
            <div class="text-white text-xs font-semibold uppercase">Present</div>
            <div class="text-2xl font-bold text-white"><?php echo number_format($present_count); ?></div>
            <div class="text-white text-xs"><?php echo $total_records > 0 ? round(($present_count / $total_records) * 100) . '%' : '0%'; ?></div>
        </div>
    </div>
    <div class="flex items-center p-5 bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-2xl shadow group hover:shadow-lg transition">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        </div>
        <div>
            <div class="text-white text-xs font-semibold uppercase">Late</div>
            <div class="text-2xl font-bold text-white"><?php echo number_format($late_count); ?></div>
            <div class="text-white text-xs"><?php echo $total_records > 0 ? round(($late_count / $total_records) * 100) . '%' : '0%'; ?></div>
        </div>
    </div>
    <div class="flex items-center p-5 bg-gradient-to-r from-red-400 to-red-600 rounded-2xl shadow group hover:shadow-lg transition">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
        </div>
        <div>
            <div class="text-white text-xs font-semibold uppercase">Absent</div>
            <div class="text-2xl font-bold text-white"><?php echo number_format($absent_count); ?></div>
            <div class="text-white text-xs"><?php echo $total_records > 0 ? round(($absent_count / $total_records) * 100) . '%' : '0%'; ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow p-6 mb-8">
    <h2 class="text-lg font-semibold mb-4 text-blue-700">Filter Records</h2>
    <form id="filter-form" action="" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label for="start_date" class="block text-xs font-semibold text-gray-600 mb-1">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-sm" />
        </div>
        <div>
            <label for="end_date" class="block text-xs font-semibold text-gray-600 mb-1">End Date</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-sm" />
        </div>
        <div>
            <label for="department" class="block text-xs font-semibold text-gray-600 mb-1">Department</label>
            <select id="department" name="department" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-sm">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department === $dept['department'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="event" class="block text-xs font-semibold text-gray-600 mb-1">Event</label>
            <select id="event" name="event" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-sm">
                <option value="">All Events</option>
                <?php foreach ($events as $evt): ?>
                    <option value="<?php echo $evt['id']; ?>" <?php echo $event === intval($evt['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($evt['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="attendance_status" class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
            <select id="attendance_status" name="attendance_status" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-sm">
                <option value="">All Status</option>
                <option value="present" <?php echo $attendance_status === 'present' ? 'selected' : ''; ?>>Present</option>
                <option value="late" <?php echo $attendance_status === 'late' ? 'selected' : ''; ?>>Late</option>
                <option value="absent" <?php echo $attendance_status === 'absent' ? 'selected' : ''; ?>>Absent</option>
            </select>
        </div>
        <div class="md:col-span-2 lg:col-span-5 flex items-center justify-end space-x-3 mt-2">
            <button type="button" id="reset-filters" class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-800 transition">Reset Filters</button>
            <button type="submit" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">Apply Filters</button>
        </div>
    </form>
</div>

<!-- Attendance Records -->
<div class="bg-white rounded-2xl shadow p-6">
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-center gap-2">
        <h2 class="text-lg font-semibold text-gray-800">Attendance Records</h2>
        <span class="text-sm text-gray-500">Showing <?php echo count($attendance_records); ?> records</span>
    </div>
    <div class="overflow-x-auto">
        <table id="attendance-table" class="min-w-full text-sm">
            <thead class="sticky top-0 z-10 bg-gray-50 shadow-sm">
                <tr class="text-gray-700">
                    <th class="py-3 px-4 text-left font-semibold border-b">Student Name</th>
                    <th class="py-3 px-4 text-left font-semibold border-b">Department</th>
                    <th class="py-3 px-4 text-left font-semibold border-b">Event</th>
                    <th class="py-3 px-4 text-left font-semibold border-b">Date</th>
                    <th class="py-3 px-4 text-left font-semibold border-b">Time</th>
                    <th class="py-3 px-4 text-left font-semibold border-b">Session</th>
                    <th class="py-3 px-4 text-left font-semibold border-b">Status</th>
                    <th class="py-3 px-4 text-left font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($attendance_records) > 0): ?>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr data-student-id="<?php echo htmlspecialchars($record['student_id'] ?? ''); ?>" class="hover:bg-blue-50 even:bg-gray-50 border-b border-gray-100 transition">
                            <td class="py-3 px-4">
                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($record['student_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($record['student_id'] ?? 'No ID'); ?></div>
                            </td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($record['department'] ?? 'Not assigned'); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($record['event_title']); ?></td>
                            <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($record['time_recorded'])); ?></td>
                            <td class="py-3 px-4"><?php echo date('h:i A', strtotime($record['time_recorded'])); ?></td>
                            <td class="py-3 px-4">
                                <span class="capitalize inline-flex items-center gap-1">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3"/></svg>
                                    <?php echo $record['session']; ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <?php 
                                $status = $record['attendance_status'] ?? 'present';
                                $statusColor = '';
                                $statusIcon = '';
                                switch ($status) {
                                    case 'present':
                                        $statusColor = 'bg-green-100 text-green-800';
                                        $statusIcon = '<svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9 16l2-2 4 4"/></svg>';
                                        break;
                                    case 'late':
                                        $statusColor = 'bg-yellow-100 text-yellow-800';
                                        $statusIcon = '<svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>';
                                        break;
                                    case 'absent':
                                        $statusColor = 'bg-red-100 text-red-800';
                                        $statusIcon = '<svg class="w-4 h-4 mr-1 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
                                        break;
                                }
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full inline-flex items-center <?php echo $statusColor; ?>">
                                    <?php echo $statusIcon; ?>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <a href="../users/view.php?id=<?php echo $record['user_id']; ?>" class="text-blue-600 hover:text-blue-800 font-semibold transition">View Student</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-blue-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-lg font-bold">No attendance records found</span>
                                <p class="text-sm mt-2">Try adjusting your filters or adding data to the system</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Report Options -->
<div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-gradient-to-r from-blue-500 to-blue-700 rounded-2xl shadow-md p-6 text-white transform transition hover:scale-105 hover:shadow-xl">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div>
                <h3 class="text-lg font-semibold">Daily Summary Report</h3>
                <p class="text-sm text-blue-100">Attendance summary for each day</p>
            </div>
        </div>
        <div class="mt-4">
            <a href="#" class="inline-block px-4 py-2 bg-white text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50 transition">Generate Report</a>
        </div>
    </div>
    <div class="bg-gradient-to-r from-green-500 to-green-700 rounded-2xl shadow-md p-6 text-white transform transition hover:scale-105 hover:shadow-xl">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <div>
                <h3 class="text-lg font-semibold">Student Reports</h3>
                <p class="text-sm text-green-100">Individual student attendance records</p>
            </div>
        </div>
        <div class="mt-4">
            <a href="#" class="inline-block px-4 py-2 bg-white text-green-600 rounded-lg text-sm font-medium hover:bg-green-50 transition">Generate Report</a>
        </div>
    </div>
    <div class="bg-gradient-to-r from-purple-500 to-purple-700 rounded-2xl shadow-md p-6 text-white transform transition hover:scale-105 hover:shadow-xl">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <div>
                <h3 class="text-lg font-semibold">Event Reports</h3>
                <p class="text-sm text-purple-100">Attendance statistics by event</p>
            </div>
        </div>
        <div class="mt-4">
            <a href="#" class="inline-block px-4 py-2 bg-white text-purple-600 rounded-lg text-sm font-medium hover:bg-purple-50 transition">Generate Report</a>
        </div>
    </div>
</div>
</div>

<?php
// Get the page content from buffer
$page_content = ob_get_clean();

// Include the admin layout
include '../../includes/admin_layout.php';
?> 