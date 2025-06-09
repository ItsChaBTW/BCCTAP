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

// Get all departments for filter dropdown
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);

// Get events for filter dropdown
$query = "SELECT id, title FROM events ORDER BY created_at DESC LIMIT 100";
$events_result = mysqli_query($conn, $query);
$events = mysqli_fetch_all($events_result, MYSQLI_ASSOC);

// Build the attendance query with filters
$query = "SELECT a.id, a.time_recorded, a.session, a.status,
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
    if (stripos($record['status'], 'present') !== false) {
        $present_count++;
    } elseif (stripos($record['status'], 'late') !== false) {
        $late_count++;
    } elseif (stripos($record['status'], 'absent') !== false) {
        $absent_count++;
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
    function exportToCSV() {
        // Get the table
        const table = document.getElementById("attendance-table");
        if (!table) return;
        
        // CSV Header
        let csv = "Student ID,Student Name,Department,Event,Date,Time,Session,Status\n";
        
        // Loop through all rows except the header
        for (let i = 1; i < table.rows.length; i++) {
            let row = table.rows[i];
            let rowData = [];
            
            // Student ID (from data attribute)
            rowData.push(row.getAttribute("data-student-id") || "");
            
            // Get cell values
            for (let j = 0; j < row.cells.length - 1; j++) { // Skip the last column (Actions)
                let cellText = row.cells[j].innerText.replace(/,/g, " ").replace(/\\n/g, " ");
                rowData.push(cellText);
            }
            
            // Add row to CSV
            csv += rowData.join(",") + "\\n";
        }
        
        // Create a download link
        const filename = "attendance_report_" + new Date().toISOString().slice(0,10) + ".csv";
        const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
        
        // Create download link
        const link = document.createElement("a");
        if (navigator.msSaveBlob) { // For IE
            navigator.msSaveBlob(blob, filename);
        } else {
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
    
    // Add datepicker functionality if needed
    document.addEventListener("DOMContentLoaded", function() {
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
                document.getElementById("start_date").value = "' . date('Y-m-01') . '";
                document.getElementById("end_date").value = "' . date('Y-m-d') . '";
                document.getElementById("department").value = "";
                document.getElementById("event").value = "";
                applyFilters();
            });
        }
    });
    
    function applyFilters() {
        const start_date = document.getElementById("start_date").value;
        const end_date = document.getElementById("end_date").value;
        const department = document.getElementById("department").value;
        const event = document.getElementById("event").value;
        
        let url = window.location.pathname + "?start_date=" + start_date + "&end_date=" + end_date;
        
        if (department) {
            url += "&department=" + encodeURIComponent(department);
        }
        
        if (event) {
            url += "&event=" + event;
        }
        
        window.location.href = url;
    }
</script>
';

// Start output buffering for page content
ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Filter Records</h2>
    <form id="filter-form" action="" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
        
        <div class="md:col-span-2 lg:col-span-4 flex items-center justify-end space-x-3">
            <button type="button" id="reset-filters" class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-800">
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
        <h2 class="text-lg font-semibold">Attendance Records</h2>
        <span class="text-sm text-gray-500">Showing <?php echo count($attendance_records); ?> records</span>
    </div>
    
    <div class="overflow-x-auto">
        <table id="attendance-table" class="min-w-full">
            <thead>
                <tr class="bg-gray-50 text-gray-700">
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Student Name</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Department</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Event</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Date</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Time</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Session</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Status</th>
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
                                <?php 
                                $status = $record['status'] ?? 'present';
                                $statusColor = '';
                                
                                if (stripos($status, 'present') !== false) {
                                    $statusColor = 'bg-green-100 text-green-800';
                                } elseif (stripos($status, 'late') !== false) {
                                    $statusColor = 'bg-yellow-100 text-yellow-800';
                                } elseif (stripos($status, 'absent') !== false) {
                                    $statusColor = 'bg-red-100 text-red-800';
                                }
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColor; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                </span>
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
                        <td colspan="8" class="py-6 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

<!-- Report Options -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md p-5 text-white">
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
            <a href="#" class="inline-block px-4 py-2 bg-white text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50">Generate Report</a>
        </div>
    </div>
    
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md p-5 text-white">
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
            <a href="#" class="inline-block px-4 py-2 bg-white text-green-600 rounded-lg text-sm font-medium hover:bg-green-50">Generate Report</a>
        </div>
    </div>
    
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-md p-5 text-white">
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
            <a href="#" class="inline-block px-4 py-2 bg-white text-purple-600 rounded-lg text-sm font-medium hover:bg-purple-50">Generate Report</a>
        </div>
    </div>
</div>

<?php
// Get the page content from buffer
$page_content = ob_get_clean();

// Include the admin layout
include '../../includes/admin_layout.php';
?> 