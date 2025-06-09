<?php
/**
 * Student Device Management for Admins
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Handle device verification or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['device_id']) && !empty($_POST['device_id'])) {
        $device_id = intval($_POST['device_id']);
        
        if ($_POST['action'] === 'verify') {
            // Verify device
            $query = "UPDATE user_devices SET is_verified = 1, verification_date = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $device_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Device verified successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to verify device: " . mysqli_error($conn);
            }
        } elseif ($_POST['action'] === 'reject') {
            // Delete device
            $query = "DELETE FROM user_devices WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $device_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Device rejected and removed successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to remove device: " . mysqli_error($conn);
            }
        }
    }
    
    // Redirect to avoid form resubmission
    redirect(BASE_URL . 'admin/users/devices.php');
}

// Get all devices with user information
$query = "SELECT ud.*, u.full_name, u.student_id, u.email 
          FROM user_devices ud 
          JOIN users u ON ud.user_id = u.id 
          ORDER BY ud.is_verified ASC, ud.last_seen DESC";
$result = mysqli_query($conn, $query);
$devices = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Group devices by verification status
$unverified_devices = [];
$verified_devices = [];

foreach ($devices as $device) {
    if ($device['is_verified'] == 1) {
        $verified_devices[] = $device;
    } else {
        $unverified_devices[] = $device;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Devices - BCCTAP</title>
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#EF6161',
                        secondary: '#f3af3d',
                    }
                }
            }
        }
    </script>
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #EF6161 0%, #f3af3d 100%);
        }
        .main-content {
            margin-left: 16rem; /* 256px - width of the sidebar */
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <?php include '../../includes/admin_sidebar.php'; ?>
        
        <main class="flex-grow main-content px-4 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-primary">Student Device Management</h1>
                <a href="../index.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Back to Dashboard
                </a>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800"><?php echo $_SESSION['success_message']; ?></p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button type="button" class="close-alert inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800"><?php echo $_SESSION['error_message']; ?></p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button type="button" class="close-alert inline-flex bg-red-50 rounded-md p-1.5 text-red-500 hover:bg-red-100">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Pending Device Verification Requests -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Pending Verification Requests</h2>
                
                <?php if (empty($unverified_devices)): ?>
                    <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                        <div class="bg-gray-100 inline-block p-4 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-gray-600">No pending device verification requests</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device Info</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($unverified_devices as $device): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($device['full_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($device['student_id']); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($device['email']); ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($device['device_name']); ?></div>
                                                <div class="text-xs text-gray-500 truncate max-w-xs"><?php echo substr(htmlspecialchars($device['user_agent'] ?? 'N/A'), 0, 60); ?>...</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($device['registration_date'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($device['registration_date'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($device['last_seen'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($device['last_seen'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div class="flex justify-center space-x-2">
                                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="inline-block">
                                                        <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                                        <input type="hidden" name="action" value="verify">
                                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md" onclick="return confirm('Are you sure you want to verify this device?')">
                                                            Verify
                                                        </button>
                                                    </form>
                                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="inline-block">
                                                        <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md" onclick="return confirm('Are you sure you want to reject this device?')">
                                                            Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Verified Devices -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Verified Devices</h2>
                
                <?php if (empty($verified_devices)): ?>
                    <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                        <p class="text-gray-600">No verified devices found</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device Info</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verification Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($verified_devices as $device): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($device['full_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($device['student_id']); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($device['email']); ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($device['device_name']); ?></div>
                                                <div class="text-xs text-gray-500 truncate max-w-xs"><?php echo substr(htmlspecialchars($device['user_agent'] ?? 'N/A'), 0, 60); ?>...</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($device['verification_date'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($device['verification_date'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($device['last_seen'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($device['last_seen'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="inline-block">
                                                    <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md" onclick="return confirm('Are you sure you want to remove this device? The student will need to verify again from this device.')">
                                                        Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Help Section -->
            <div class="mt-8 bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">About Device Verification</h2>
                
                <div class="space-y-4 text-gray-600">
                    <p><strong>Cross-Browser Support:</strong> With the new system, students can use any browser on their verified device to access the platform.</p>
                    
                    <p><strong>Device Recognition:</strong> The system uses advanced fingerprinting to identify the same physical device across different browsers.</p>
                    
                    <p><strong>Security:</strong> Only verified devices are allowed to access student accounts, protecting against unauthorized access.</p>
                    
                    <p><strong>Process:</strong> When a student attempts to log in from a new device, the login is blocked until you verify the device here.</p>
                </div>
                
                <div class="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Important Note</h3>
                            <p class="mt-2 text-sm text-blue-700">
                                When verifying a device, make sure to confirm the student's identity first, especially if there are multiple verification requests from different devices for the same student in a short period.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include '../../includes/footer.php'; ?>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        // Close alert buttons
        document.querySelectorAll('.close-alert').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.bg-green-50, .bg-red-50').remove();
            });
        });
    </script>
</body>
</html> 