<?php
/**
 * View Event Page for Admins
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Event ID is required";
    redirect(BASE_URL . 'admin/events/index.php');
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
    redirect(BASE_URL . 'admin/events/index.php');
}

$event = mysqli_fetch_assoc($result);

// Get QR codes for this event
$query = "SELECT * FROM qr_codes WHERE event_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$qr_codes = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get attendance stats
$query = "SELECT 
            COUNT(*) as total_attendance,
            COUNT(CASE WHEN session = 'morning' THEN 1 END) as morning_attendance,
            COUNT(CASE WHEN session = 'afternoon' THEN 1 END) as afternoon_attendance,
            COUNT(DISTINCT user_id) as unique_students
          FROM attendance 
          WHERE event_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_stats = mysqli_fetch_assoc($result);

// Get recent attendance entries
$query = "SELECT a.*, s.full_name as student_name
          FROM attendance a
          LEFT JOIN users s ON a.user_id = s.id
          WHERE a.event_id = ?
          ORDER BY a.time_recorded DESC
          LIMIT 10";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recent_attendance = mysqli_fetch_all($result, MYSQLI_ASSOC);
// Set page title and actions for admin layout
$page_title = "Create New Event";
$page_actions = '<a href="index.php" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:opacity-90 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
    </svg>
    Back to Events
</a>';

// Start output buffering
ob_start();
?>
        
        <main class="flex-grow container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center">
                    <a href="index.php" class="mr-4 text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-3xl font-bold text-indigo-800"><?php echo htmlspecialchars($event['title']); ?></h1>
                </div>
                <div class="flex space-x-3">
                    <a href="edit.php?id=<?php echo $event_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        Edit Event
                    </a>
                    <a href="../qrcodes/view.php?id=<?php echo $event_id; ?>" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                            <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
                        </svg>
                        QR Codes
                    </a>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Event Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-100 mb-8">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($event['title']); ?></h2>
                                <div class="mt-2 flex items-center text-sm text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    Created by <?php echo htmlspecialchars($event['created_by_name']); ?> on <?php echo date('M d, Y', strtotime($event['created_at'])); ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($event['department'])): ?>
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                    <?php echo htmlspecialchars($event['department']); ?>
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    All Departments
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($event['description'])): ?>
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">Description</h3>
                                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                                <h3 class="text-lg font-semibold text-indigo-700 mb-2">Event Dates</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Start Date</p>
                                        <p class="text-gray-900"><?php echo date('M d, Y', strtotime($event['start_date'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">End Date</p>
                                        <p class="text-gray-900"><?php echo date('M d, Y', strtotime($event['end_date'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                                    <h3 class="text-md font-semibold text-green-700 mb-1">Morning Session</h3>
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="text-xs text-gray-500">Time In</p>
                                            <p class="text-gray-900"><?php echo date('h:i A', strtotime($event['morning_time_in'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Time Out</p>
                                            <p class="text-gray-900"><?php echo date('h:i A', strtotime($event['morning_time_out'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-amber-50 p-4 rounded-lg border border-amber-100">
                                    <h3 class="text-md font-semibold text-amber-700 mb-1">Afternoon Session</h3>
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="text-xs text-gray-500">Time In</p>
                                            <p class="text-gray-900"><?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Time Out</p>
                                            <p class="text-gray-900"><?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Attendance -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex justify-between items-center">
                                <h2 class="text-xl font-bold text-gray-900">Recent Attendance</h2>
                                <a href="../attendance.php?id=<?php echo $event_id; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                    View All →
                                </a>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <?php if (count($recent_attendance) > 0): ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recent_attendance as $record): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['student_name']); ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?php echo htmlspecialchars($record['user_id']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php if ($record['session'] === 'morning'): ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Morning
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">
                                                            Afternoon
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('M d, Y g:i A', strtotime($record['time_recorded'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php 
                                                    $status = '';
                                                    $statusClass = '';
                                                    
                                                    if ($record['session'] === 'morning') {
                                                        $time_in = strtotime($event['morning_time_in']);
                                                            $attendance_time = strtotime($record['time_recorded']);
                                                        $diff_minutes = ($attendance_time - $time_in) / 60;
                                                        
                                                        if ($diff_minutes <= 15) {
                                                            $status = 'On Time';
                                                            $statusClass = 'bg-green-100 text-green-800';
                                                        } elseif ($diff_minutes <= 30) {
                                                            $status = 'Late';
                                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                                        } else {
                                                            $status = 'Very Late';
                                                            $statusClass = 'bg-red-100 text-red-800';
                                                        }
                                                    } else {
                                                        $time_in = strtotime($event['afternoon_time_in']);
                                                        $attendance_time = strtotime($record['time_recorded']);
                                                        $diff_minutes = ($attendance_time - $time_in) / 60;
                                                        
                                                        if ($diff_minutes <= 15) {
                                                            $status = 'On Time';
                                                            $statusClass = 'bg-green-100 text-green-800';
                                                        } elseif ($diff_minutes <= 30) {
                                                            $status = 'Late';
                                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                                        } else {
                                                            $status = 'Very Late';
                                                            $statusClass = 'bg-red-100 text-red-800';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                        <?php echo $status; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="py-8 text-center text-gray-500">No attendance records yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Stats & QR Codes -->
                <div>
                    <!-- Attendance Stats -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Attendance Statistics</h2>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-indigo-50 rounded-lg p-4 flex flex-col items-center">
                                <span class="text-3xl font-bold text-indigo-600"><?php echo $attendance_stats['total_attendance']; ?></span>
                                <span class="text-sm text-gray-600 mt-1">Total Check-ins</span>
                            </div>
                            
                            <div class="bg-purple-50 rounded-lg p-4 flex flex-col items-center">
                                <span class="text-3xl font-bold text-purple-600"><?php echo $attendance_stats['unique_students']; ?></span>
                                <span class="text-sm text-gray-600 mt-1">Unique Students</span>
                            </div>
                            
                            <div class="bg-green-50 rounded-lg p-4 flex flex-col items-center">
                                <span class="text-3xl font-bold text-green-600"><?php echo $attendance_stats['morning_attendance']; ?></span>
                                <span class="text-sm text-gray-600 mt-1">Morning Attendance</span>
                            </div>
                            
                            <div class="bg-amber-50 rounded-lg p-4 flex flex-col items-center">
                                <span class="text-3xl font-bold text-amber-600"><?php echo $attendance_stats['afternoon_attendance']; ?></span>
                                <span class="text-sm text-gray-600 mt-1">Afternoon Attendance</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- QR Code Summary -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-900">QR Codes</h2>
                            <a href="../qrcodes/view.php?id=<?php echo $event_id; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                View All →
                            </a>
                        </div>
                        
                        <?php if (count($qr_codes) > 0): ?>
                            <div class="grid grid-cols-1 gap-4 mt-4">
                                <?php foreach ($qr_codes as $index => $qr): ?>
                                    <?php if ($index < 2): // Show only first two QR codes ?>
                                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="font-medium text-gray-900">
                                                        <?php echo ucfirst($qr['session']); ?> Session
                                                    </h3>
                                                    <p class="text-sm text-gray-500 mt-1">Code: <?php echo substr($qr['code'], 0, 12); ?>...</p>
                                                </div>
                                                <span class="bg-<?php echo $qr['session'] === 'morning' ? 'green' : 'amber'; ?>-100 text-<?php echo $qr['session'] === 'morning' ? 'green' : 'amber'; ?>-800 text-xs px-2 py-1 rounded-full font-medium">
                                                    <?php echo $qr['session'] === 'morning' ? 'Morning' : 'Afternoon'; ?>
                                                </span>
                                            </div>
                                            <div class="mt-2 flex justify-center">
                                                <div class="bg-white p-2 rounded-md shadow-sm">
                                                    <?php if (!empty($qr['image_path'])): ?>
                                                    <!-- Display saved QR code image -->
                                                    <img src="<?php echo BASE_URL . $qr['image_path']; ?>" 
                                                         alt="QR Code" class="w-32 h-32">
                                                    <?php else: ?>
                                                    <!-- Placeholder for QR code image -->
                                                    <div class="w-32 h-32 bg-gray-200 flex items-center justify-center">
                                                        <span class="text-gray-500 text-sm">QR Image</span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="py-6 text-center text-gray-500">No QR codes generated yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        <?php
$page_content = ob_get_clean();

// Include admin layout
require_once '../../includes/admin_layout.php';
?> 