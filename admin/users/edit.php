<?php
/**
 * User Device Management Page
 * Allows admins to manage user's registered devices
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

// Handle device removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_device'])) {
    $query = "UPDATE users SET device_id = NULL, first_device_date = NULL, device_changed = 0 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        setFlashMessage('success', 'Device has been removed successfully');
    } else {
        setFlashMessage('error', 'Failed to remove device');
    }
    
    redirect(BASE_URL . 'admin/users/edit.php?id=' . $user_id);
}

// Set page title
$page_title = "Manage Devices - " . htmlspecialchars($user['full_name']);

// Add back button to page actions
$page_actions = '
<div class="flex space-x-2">
    <button onclick="history.back()" class="bg-gray-600 hover:bg-gray-700 text-white py-1.5 px-4 rounded-lg flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
        Back
    </button>
</div>
';

// Start output buffering for page content
ob_start();
?>

<!-- User Information Card -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
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
            </div>
        </div>
    </div>
</div>

<!-- Device Management Section -->
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
            Device Management
        </h2>
    </div>

    <?php if ($user['device_id']): ?>
        <div class="bg-gray-50 rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Device ID</h3>
                    <p class="text-gray-900 font-mono bg-white p-3 rounded border border-gray-200 break-all">
                        <?php echo htmlspecialchars($user['device_id']); ?>
                    </p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">First Registered</h3>
                    <p class="text-gray-900">
                        <?php echo date('F j, Y g:i A', strtotime($user['first_device_date'])); ?>
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $user['device_changed'] ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                        <?php echo $user['device_changed'] ? 'Device Changed' : 'Device Stable'; ?>
                    </span>
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to remove this device? This will allow the user to register a new device.');">
                    <button type="submit" name="remove_device" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg flex items-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Remove Device
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Device Registered</h3>
            <p class="text-gray-500">This user has not registered any device yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php
// Get the page content from buffer
$page_content = ob_get_clean();

// Include the admin layout
include '../../includes/admin_layout.php';
?> 