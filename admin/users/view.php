<?php
/**
 * User Details View Page
 * Shows comprehensive information about a specific user/student
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    setFlashMessage('error', 'Invalid user ID');
    redirect(BASE_URL . 'admin/users/');
}

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    setFlashMessage('error', 'User not found');
    redirect(BASE_URL . 'admin/users/');
}

// Get user's attendance history
$query = "SELECT a.*, e.title as event_title 
          FROM attendance a 
          INNER JOIN events e ON a.event_id = e.id 
          WHERE a.user_id = ? 
          ORDER BY a.time_recorded DESC 
          LIMIT 50";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$attendance_result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($attendance_result, MYSQLI_ASSOC);

// Calculate attendance statistics
$total_attendance = count($attendance_records);
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

// Set page title
$page_title = "Student Details - " . htmlspecialchars($user['full_name']);

// Add back button to page actions
$page_actions = '
<div class="flex space-x-2">
    <button onclick="history.back()" class="bg-gray-600 hover:bg-gray-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
        Back
    </button>
    <a href="edit.php?id=' . $user_id . '" class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
        </svg>
        Edit Student
    </a>
</div>
';

// Start output buffering for page content
ob_start();
?>

<!-- Student Information Card -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white/20 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="text-blue-100"><?php echo htmlspecialchars($user['student_id'] ?? 'No Student ID'); ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo ($user['role'] ?? 'student') === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                    <?php echo ucfirst($user['role'] ?? 'student'); ?>
                </span>
                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $user['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Information Grid -->
    <div class="p-6">
        <!-- Basic Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
                Basic Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Department</h4>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($user['department'] ?? 'Not assigned'); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Year Level</h4>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($user['year_level'] ?? 'Not assigned'); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Section</h4>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($user['section'] ?? 'Not assigned'); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Gender</h4>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($user['gender'] ?? 'Not specified'); ?></p>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
                Contact Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Email</h4>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Contact Number</h4>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($user['contact_number'] ?? 'Not provided'); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg md:col-span-2">
                    <h4 class="text-sm font-medium text-gray-500">Address</h4>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></p>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
                System Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Last Login</h4>
                    <p class="mt-1 text-gray-900"><?php echo $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never logged in'; ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Registered On</h4>
                    <p class="mt-1 text-gray-900"><?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500">Last Updated</h4>
                    <p class="mt-1 text-gray-900"><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Device Information -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                Device Information
            </h3>
            <div class="mb-2">
                <?php if ($user['device_id']): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg md:col-span-2">
                            <h4 class="text-sm font-medium text-gray-500">Device ID</h4>
                            <p class="mt-1 text-gray-900 truncate" title="<?php echo htmlspecialchars($user['device_id']); ?>"><?php echo htmlspecialchars($user['device_id']); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-500">First Device Date</h4>
                            <p class="mt-1 text-gray-900"><?php echo date('F j, Y g:i A', strtotime($user['first_device_date'])); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-500">Device Changed</h4>
                            <p class="mt-1">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $user['device_changed'] ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $user['device_changed'] ? 'Yes' : 'No'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No device registered</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 mt-6">
    <div class="stat-card primary">
        <h3>Total Attendance</h3>
        <div class="value"><?php echo number_format($total_attendance); ?></div>
    </div>
    
    <div class="stat-card success">
        <h3>Present</h3>
        <div class="value"><?php echo number_format($present_count); ?></div>
        <div class="percentage"><?php echo $total_attendance > 0 ? round(($present_count / $total_attendance) * 100) . '%' : '0%'; ?></div>
    </div>
    
    <div class="stat-card warning">
        <h3>Late</h3>
        <div class="value"><?php echo number_format($late_count); ?></div>
        <div class="percentage"><?php echo $total_attendance > 0 ? round(($late_count / $total_attendance) * 100) . '%' : '0%'; ?></div>
    </div>
    
    <div class="stat-card danger">
        <h3>Absent</h3>
        <div class="value"><?php echo number_format($absent_count); ?></div>
        <div class="percentage"><?php echo $total_attendance > 0 ? round(($absent_count / $total_attendance) * 100) . '%' : '0%'; ?></div>
    </div>
</div>

<!-- Attendance History -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-lg font-semibold mb-4">Attendance History</h2>
    
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50 text-gray-700">
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Event</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Date</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Time</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Session</th>
                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($attendance_records) > 0): ?>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($record['event_title']); ?></td>
                            <td class="py-3 px-4 text-sm"><?php echo date('M d, Y', strtotime($record['time_recorded'])); ?></td>
                            <td class="py-3 px-4 text-sm"><?php echo date('h:i A', strtotime($record['time_recorded'])); ?></td>
                            <td class="py-3 px-4 text-sm">
                                <span class="capitalize"><?php echo $record['session']; ?></span>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <?php 
                                $status = $record['attendance_status'];
                                $statusColor = '';
                                
                                switch ($status) {
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
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="py-6 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-lg font-medium">No attendance records found</span>
                                <p class="text-sm mt-2">This student has not attended any events yet</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Get the page content from buffer
$page_content = ob_get_clean();

// Include the admin layout
include '../../includes/admin_layout.php';
?> 