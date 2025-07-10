<?php
/**
 * Student Device Management for Admins
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// AJAX endpoint for realtime data
if (isset($_GET['action']) && $_GET['action'] === 'get_devices') {
    header('Content-Type: application/json');
    
    // Get all devices with user information, prioritizing most recent per user
    // Use a subquery approach for better MySQL compatibility
    $query = "SELECT ud.*, u.full_name, u.student_id, u.email
              FROM user_devices ud 
              JOIN users u ON ud.user_id = u.id 
              WHERE u.role = 'student'
              AND ud.id = (
                  SELECT ud2.id FROM user_devices ud2 
                  WHERE ud2.user_id = ud.user_id 
                  ORDER BY ud2.last_seen DESC, ud2.is_verified DESC 
                  LIMIT 1
              )
              ORDER BY ud.is_verified ASC, ud.last_seen DESC";
    $result = mysqli_query($conn, $query);
    $devices = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Group devices by verification status
    $unverified_devices = [];
    $verified_devices = [];

    foreach ($devices as $device) {
        // Add browser and OS info
        $device_info = getBrowserAndOS($device['user_agent'] ?? '');
        $device['browser'] = $device_info['browser'];
        $device['os'] = $device_info['os'];
        
        if ($device['is_verified'] == 1) {
            $verified_devices[] = $device;
        } else {
            $unverified_devices[] = $device;
        }
    }

    echo json_encode([
        'success' => true,
        'unverified' => $unverified_devices,
        'verified' => $verified_devices,
        'unverified_count' => count($unverified_devices),
        'verified_count' => count($verified_devices),
        'timestamp' => time()
    ]);
    exit;
}

// Add this function at the top of the file after the config include
function getBrowserAndOS($user_agent) {
    $browser = "Unknown";
    $os = "Unknown";

    // Detect Browser
    if (strpos($user_agent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($user_agent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($user_agent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($user_agent, 'Edge') !== false) {
        $browser = 'Edge';
    } elseif (strpos($user_agent, 'Opera') !== false) {
        $browser = 'Opera';
    }

    // Detect OS
    if (strpos($user_agent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($user_agent, 'Mac') !== false) {
        $os = 'MacOS';
    } elseif (strpos($user_agent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($user_agent, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($user_agent, 'iOS') !== false) {
        $os = 'iOS';
    }

    return ['browser' => $browser, 'os' => $os];
}

// Handle device verification or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['device_id']) && !empty($_POST['device_id'])) {
        $device_id = intval($_POST['device_id']);
        
        if ($_POST['action'] === 'verify') {
            // Verify device - also verify all devices for this user with the same fingerprint
            $query = "SELECT user_id, fingerprint FROM user_devices WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $device_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $device_info = mysqli_fetch_assoc($result);
            
            if ($device_info) {
                // Verify all devices with the same fingerprint for this user
                $update_query = "UPDATE user_devices SET is_verified = 1, verification_date = NOW() 
                               WHERE user_id = ? AND fingerprint = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "is", $device_info['user_id'], $device_info['fingerprint']);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['success_message'] = "Device verified successfully for this student.";
                } else {
                    $_SESSION['error_message'] = "Failed to verify device: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['error_message'] = "Device not found.";
            }
        } elseif ($_POST['action'] === 'reject') {
            // Delete device and all similar devices for this user
            $query = "SELECT user_id, fingerprint FROM user_devices WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $device_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $device_info = mysqli_fetch_assoc($result);
            
            if ($device_info) {
                // Delete all devices with the same fingerprint for this user
                $delete_query = "DELETE FROM user_devices WHERE user_id = ? AND fingerprint = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_query);
                mysqli_stmt_bind_param($delete_stmt, "is", $device_info['user_id'], $device_info['fingerprint']);
                
                if (mysqli_stmt_execute($delete_stmt)) {
                    $_SESSION['success_message'] = "Device rejected and removed successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to remove device: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['error_message'] = "Device not found.";
            }
        }
    }
    
    // Redirect to avoid form resubmission
    redirect(BASE_URL . 'admin/users/devices.php');
}

// Get all devices with user information, prioritizing most recent per user
// Use a subquery approach for better MySQL compatibility
$query = "SELECT ud.*, u.full_name, u.student_id, u.email
          FROM user_devices ud 
          JOIN users u ON ud.user_id = u.id 
          WHERE u.role = 'student'
          AND ud.id = (
              SELECT ud2.id FROM user_devices ud2 
              WHERE ud2.user_id = ud.user_id 
              ORDER BY ud2.last_seen DESC, ud2.is_verified DESC 
              LIMIT 1
          )
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

// Set page title and actions for admin layout
$page_title = "Device Management";


// Start output buffering
ob_start();
?>
        <main class="flex-grow main-content px-2 sm:px-4 py-8 max-w-6xl mx-auto">
            <!-- Alerts -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-xl shadow flex items-center justify-between transition-all">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        <span class="text-sm text-green-800"><?php echo $_SESSION['success_message']; ?></span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="ml-4 text-green-500 hover:text-green-700 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-xl shadow flex items-center justify-between transition-all">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                        <span class="text-sm text-red-800"><?php echo $_SESSION['error_message']; ?></span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="ml-4 text-red-500 hover:text-red-700 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

           

            <!-- Pending Device Verification Requests -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                    <h2 class="text-xl font-bold text-gray-800">Pending Verification Requests</h2>
                    <span id="pending-count" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 animate-pulse">Pending: <?php echo count($unverified_devices); ?></span>
                </div>
                <!-- Search for Pending -->
                <div class="mb-4 flex justify-end">
                    <input type="text" id="pending-search" placeholder="Search pending devices..." class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-200 focus:border-yellow-400 text-sm" />
                </div>
                <div id="pending-devices-content">
                <?php if (empty($unverified_devices)): ?>
                    <div class="bg-white rounded-2xl shadow p-8 text-center flex flex-col items-center justify-center">
                        <div class="bg-gray-100 inline-block p-4 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-gray-500 text-lg font-medium">No pending device verification requests</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="overflow-x-visible">
                            <table id="pending-table" class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Device Info</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Registration Date</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Last Seen</th>
                                        <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($unverified_devices as $device): ?>
                                        <tr class="hover:bg-yellow-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-800"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10"/></svg>Pending</span>
                                                    <div>
                                                        <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($device['full_name']); ?></div>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($device['student_id']); ?></div>
                                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($device['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="relative group">
                                                    <div class="flex items-center gap-2">
                                                        <div class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($device['device_name']); ?></div>
                                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </div>
                                                    
                                                    <!-- Updated tooltip content -->
                                                    <div class="absolute left-full ml-4 top-0 w-96 p-4 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[9999]">
                                                        <div class="space-y-2">
                                                            <?php
                                                            $device_info = getBrowserAndOS($device['user_agent'] ?? '');
                                                            ?>
                                                            <p><span class="font-semibold">Device Name:</span> <?php echo htmlspecialchars($device['device_name'] ?? 'Not specified'); ?></p>
                                                            <p><span class="font-semibold">Browser:</span> <?php echo htmlspecialchars($device_info['browser']); ?></p>
                                                            <p><span class="font-semibold">OS:</span> <?php echo htmlspecialchars($device_info['os']); ?></p>
                                                            <p class="border-t border-gray-700 pt-2 mt-2"><span class="font-semibold">User Agent:</span> <span class="text-xs break-words"><?php echo htmlspecialchars($device['user_agent'] ?? 'Not available'); ?></span></p>
                                                            <?php if (isset($device['device_id']) && !empty($device['device_id'])): ?>
                                                            <p><span class="font-semibold">Device ID:</span> <?php echo htmlspecialchars($device['device_id']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- Tooltip arrow -->
                                                        <div class="absolute top-4 -left-2 w-4 h-4 bg-gray-900 transform rotate-45"></div>
                                                    </div>
                                                </div>
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
                                                <div class="flex flex-col sm:flex-row justify-center gap-2">
                                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="inline-block">
                                                        <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                                        <input type="hidden" name="action" value="verify">
                                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-1 shadow transition" onclick="return confirm('Are you sure you want to verify this device?')">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                                            Verify
                                                        </button>
                                                    </form>
                                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="inline-block">
                                                        <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-1 shadow transition" onclick="return confirm('Are you sure you want to reject this device?')">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                                            Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div id="pending-pagination" class="flex justify-end items-center gap-2 px-6 py-4"></div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>

            <!-- Verified Devices -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
                    <h2 class="text-xl font-bold text-gray-800">Verified Devices</h2>
                    <span id="verified-count" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verified: <?php echo count($verified_devices); ?></span>
                </div>
                <!-- Search for Verified -->
                <div class="mb-4 flex justify-end">
                    <input type="text" id="verified-search" placeholder="Search verified devices..." class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-400 text-sm" />
                </div>
                <div id="verified-devices-content">
                <?php if (empty($verified_devices)): ?>
                    <div class="bg-white rounded-2xl shadow p-8 text-center flex flex-col items-center justify-center">
                        <div class="bg-gray-100 inline-block p-4 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-gray-500 text-lg font-medium">No verified devices found</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="overflow-x-visible">
                            <table id="verified-table" class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Device Info</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Verification Date</th>
                                        <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Last Seen</th>
                                        <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($verified_devices as $device): ?>
                                        <tr class="hover:bg-green-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10"/></svg>Verified</span>
                                                    <div>
                                                        <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($device['full_name']); ?></div>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($device['student_id']); ?></div>
                                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($device['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="relative group">
                                                    <div class="flex items-center gap-2">
                                                        <div class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($device['device_name']); ?></div>
                                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </div>
                                                    
                                                    <!-- Updated tooltip content -->
                                                    <div class="absolute left-full ml-4 top-0 w-96 p-4 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[9999]">
                                                        <div class="space-y-2">
                                                            <?php
                                                            $device_info = getBrowserAndOS($device['user_agent'] ?? '');
                                                            ?>
                                                            <p><span class="font-semibold">Device Name:</span> <?php echo htmlspecialchars($device['device_name'] ?? 'Not specified'); ?></p>
                                                            <p><span class="font-semibold">Browser:</span> <?php echo htmlspecialchars($device_info['browser']); ?></p>
                                                            <p><span class="font-semibold">OS:</span> <?php echo htmlspecialchars($device_info['os']); ?></p>
                                                            <p class="border-t border-gray-700 pt-2 mt-2"><span class="font-semibold">User Agent:</span> <span class="text-xs break-words"><?php echo htmlspecialchars($device['user_agent'] ?? 'Not available'); ?></span></p>
                                                            <?php if (isset($device['device_id']) && !empty($device['device_id'])): ?>
                                                            <p><span class="font-semibold">Device ID:</span> <?php echo htmlspecialchars($device['device_id']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- Tooltip arrow -->
                                                        <div class="absolute top-4 -left-2 w-4 h-4 bg-gray-900 transform rotate-45"></div>
                                                    </div>
                                                </div>
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
                                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-1 shadow transition" onclick="return confirm('Are you sure you want to remove this device? The student will need to verify again from this device.')">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                                        Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div id="verified-pagination" class="flex justify-end items-center gap-2 px-6 py-4"></div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>

            <!-- Help Section -->
            <div class="mt-12 bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M13 16h-1v-4h-1m1-4h.01"/></svg>
                    <h2 class="text-lg font-bold text-gray-800">About Device Verification</h2>
                </div>
                <div class="space-y-4 text-gray-600">
                    <p><strong>Automatic Verification:</strong> Devices are automatically verified when students successfully scan QR codes from inside the event venue (geofenced area).</p>
                    <p><strong>Pending Verification:</strong> Devices appear in pending when students try to scan from outside the venue or login from unauthorized locations.</p>
                    <p><strong>Security:</strong> Only verified devices are allowed to access student accounts, ensuring attendance integrity and preventing unauthorized access.</p>
                    <p><strong>Deduplication:</strong> Only the most recent device activity per student is shown to avoid confusion from multiple browser sessions.</p>
                </div>
                <div class="mt-6 bg-green-50 p-4 rounded-lg border border-green-100">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 mt-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        <div>
                            <h3 class="text-sm font-semibold text-green-800">New Automated System</h3>
                            <p class="mt-2 text-sm text-green-700">
                                ✅ <strong>Inside venue + successful scan</strong> → Device automatically verified<br/>
                                ⏳ <strong>Outside venue or failed scan</strong> → Device requires manual verification<br/>
                                🚫 <strong>Suspicious activity</strong> → Device blocked until admin review
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mt-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>
                        <div>
                            <h3 class="text-sm font-semibold text-blue-800">Manual Verification Guidelines</h3>
                            <p class="mt-2 text-sm text-blue-700">
                                Only verify devices manually if you can confirm the student's identity and their legitimate need to access from that device. Contact the student directly if there are multiple pending requests.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <script>
        // Realtime Device Management System
        document.addEventListener('DOMContentLoaded', function() {
            // Global state
            let lastDataTimestamp = 0;
            let updateInterval;
            let isUpdating = false;
            let lastUnverifiedCount = <?php echo count($unverified_devices); ?>;
            let lastVerifiedCount = <?php echo count($verified_devices); ?>;
            
            // Elements
            const realtimeStatus = document.getElementById('realtime-status');
            const lastUpdateElement = document.getElementById('last-update');
            const refreshButton = document.getElementById('refresh-now');
            const autoUpdateToggle = document.getElementById('auto-update');
            const pendingCountElement = document.getElementById('pending-count');
            const verifiedCountElement = document.getElementById('verified-count');

            // Utility function to format relative time
            function getRelativeTime(timestamp) {
                const now = Date.now();
                const diff = now - timestamp;
                const seconds = Math.floor(diff / 1000);
                const minutes = Math.floor(seconds / 60);
                const hours = Math.floor(minutes / 60);
                
                if (seconds < 60) return 'Just updated';
                if (minutes < 60) return `${minutes}m ago`;
                if (hours < 24) return `${hours}h ago`;
                return 'More than a day ago';
            }

            // Show notification
            function showNotification(message, type = 'info') {
                // Remove existing notifications
                const existingNotifications = document.querySelectorAll('.realtime-notification');
                existingNotifications.forEach(notification => notification.remove());

                const notification = document.createElement('div');
                notification.className = 'realtime-notification fixed top-4 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg transition-all transform translate-x-full';
                
                const bgColor = type === 'success' ? 'bg-green-500' : 
                               type === 'error' ? 'bg-red-500' : 
                               type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
                
                notification.className += ` ${bgColor} text-white`;
                notification.innerHTML = `
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            ${type === 'success' ? '<path d="M5 13l4 4L19 7"/>' :
                              type === 'error' ? '<path d="M6 18L18 6M6 6l12 12"/>' :
                              type === 'warning' ? '<path d="M12 8v4m0 4h.01"/>' :
                              '<circle cx="12" cy="12" r="10"/><path d="M13 16h-1v-4h-1m1-4h.01"/>'}
                        </svg>
                        <span class="font-medium">${message}</span>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:bg-white hover:bg-opacity-20 rounded p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.classList.remove('translate-x-full');
                }, 100);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => notification.remove(), 300);
                }, 5000);
            }

            // Update status indicator
            function updateStatus(status) {
                if (status === 'updating') {
                    realtimeStatus.className = 'w-3 h-3 bg-yellow-500 rounded-full animate-spin';
                    lastUpdateElement.textContent = 'Updating...';
                } else if (status === 'success') {
                    realtimeStatus.className = 'w-3 h-3 bg-green-500 rounded-full animate-pulse';
                    lastUpdateElement.textContent = 'Just updated';
                } else if (status === 'error') {
                    realtimeStatus.className = 'w-3 h-3 bg-red-500 rounded-full';
                    lastUpdateElement.textContent = 'Update failed';
                }
            }

            // Generate device row HTML
            function generateDeviceRow(device, isPending = true) {
                const statusClass = isPending ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                const statusText = isPending ? 'Pending' : 'Verified';
                const dateField = isPending ? device.registration_date : device.verification_date;
                const hoverClass = isPending ? 'hover:bg-yellow-50' : 'hover:bg-green-50';
                
                const actionButtons = isPending ? `
                    <form method="post" action="" class="inline-block">
                        <input type="hidden" name="device_id" value="${device.id}">
                        <input type="hidden" name="action" value="verify">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-1 shadow transition" onclick="return confirm('Are you sure you want to verify this device?')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                            Verify
                        </button>
                    </form>
                    <form method="post" action="" class="inline-block">
                        <input type="hidden" name="device_id" value="${device.id}">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-1 shadow transition" onclick="return confirm('Are you sure you want to reject this device?')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            Reject
                        </button>
                    </form>
                ` : `
                    <form method="post" action="" class="inline-block">
                        <input type="hidden" name="device_id" value="${device.id}">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-1 shadow transition" onclick="return confirm('Are you sure you want to remove this device? The student will need to verify again from this device.')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            Remove
                        </button>
                    </form>
                `;

                const date = new Date(dateField);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const formattedTime = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

                const lastSeenDate = new Date(device.last_seen);
                const lastSeenFormatted = lastSeenDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const lastSeenTime = lastSeenDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

                return `
                    <tr class="${hoverClass} transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${statusClass}">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10"/></svg>${statusText}
                                </span>
                                <div>
                                    <div class="text-sm font-bold text-gray-900">${device.full_name}</div>
                                    <div class="text-xs text-gray-500">${device.student_id}</div>
                                    <div class="text-xs text-gray-400">${device.email}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="relative group">
                                <div class="flex items-center gap-2">
                                    <div class="text-sm text-gray-900 font-medium">${device.device_name}</div>
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="absolute left-full ml-4 top-0 w-96 p-4 bg-gray-900 text-white text-sm rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[9999]">
                                    <div class="space-y-2">
                                        <p><span class="font-semibold">Device Name:</span> ${device.device_name || 'Not specified'}</p>
                                        <p><span class="font-semibold">Browser:</span> ${device.browser}</p>
                                        <p><span class="font-semibold">OS:</span> ${device.os}</p>
                                        <p class="border-t border-gray-700 pt-2 mt-2"><span class="font-semibold">User Agent:</span> <span class="text-xs break-words">${device.user_agent || 'Not available'}</span></p>
                                        ${device.device_id ? `<p><span class="font-semibold">Device ID:</span> ${device.device_id}</p>` : ''}
                                    </div>
                                    <div class="absolute top-4 -left-2 w-4 h-4 bg-gray-900 transform rotate-45"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${formattedDate}</div>
                            <div class="text-xs text-gray-500">${formattedTime}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${lastSeenFormatted}</div>
                            <div class="text-xs text-gray-500">${lastSeenTime}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex flex-col sm:flex-row justify-center gap-2">
                                ${actionButtons}
                            </div>
                        </td>
                    </tr>
                `;
            }

            // Update table content
            function updateTable(devices, tableId, isUnverified = true) {
                const table = document.getElementById(tableId);
                if (!table) return;

                const tbody = table.querySelector('tbody');
                if (!tbody) return;

                if (devices.length === 0) {
                    const parentDiv = table.closest('.bg-white.rounded-2xl');
                    if (parentDiv) {
                        const message = isUnverified ? 'No pending device verification requests' : 'No verified devices found';
                        const icon = isUnverified ? 
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' :
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />';
                        
                        parentDiv.innerHTML = `
                            <div class="p-8 text-center flex flex-col items-center justify-center">
                                <div class="bg-gray-100 inline-block p-4 rounded-full mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        ${icon}
                                    </svg>
                                </div>
                                <p class="text-gray-500 text-lg font-medium">${message}</p>
                            </div>
                        `;
                    }
                } else {
                    const parentDiv = table.closest('.bg-white.rounded-2xl');
                    if (parentDiv && !parentDiv.querySelector('table')) {
                        // Restore table structure if it was replaced with empty message
                        location.reload();
                        return;
                    }
                    
                    tbody.innerHTML = devices.map(device => generateDeviceRow(device, isUnverified)).join('');
                }
                
                // Reapply search and pagination
                initializeTableFeatures();
            }

            // Fetch updated data
            async function fetchDeviceData() {
                if (isUpdating) return;
                
                isUpdating = true;
                updateStatus('updating');
                
                try {
                    const response = await fetch(window.location.pathname + '?action=get_devices');
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error('Server returned error response');
                    }
                    
                    // Check for new devices
                    if (data.unverified_count > lastUnverifiedCount) {
                        const newDevicesCount = data.unverified_count - lastUnverifiedCount;
                        showNotification(`${newDevicesCount} new device${newDevicesCount > 1 ? 's' : ''} require${newDevicesCount > 1 ? '' : 's'} verification!`, 'warning');
                    }
                    
                    // Update counts
                    lastUnverifiedCount = data.unverified_count;
                    lastVerifiedCount = data.verified_count;
                    lastDataTimestamp = data.timestamp * 1000;
                    
                    // Update count elements
                    if (pendingCountElement) {
                        pendingCountElement.textContent = `Pending: ${data.unverified_count}`;
                        pendingCountElement.style.display = data.unverified_count > 0 ? 'inline-flex' : 'none';
                    }
                    
                    if (verifiedCountElement) {
                        verifiedCountElement.textContent = `Verified: ${data.verified_count}`;
                    }
                    
                    // Update tables
                    updateTable(data.unverified, 'pending-table', true);
                    updateTable(data.verified, 'verified-table', false);
                    
                    updateStatus('success');
                    
                } catch (error) {
                    console.error('Error fetching device data:', error);
                    updateStatus('error');
                    showNotification('Failed to update device data', 'error');
                } finally {
                    isUpdating = false;
                }
            }

            // Initialize table features (search and pagination)
            function initializeTableFeatures() {
                setTimeout(() => {
                    filterAndPaginateTable('pending-search', 'pending-table', 'pending-pagination');
                    filterAndPaginateTable('verified-search', 'verified-table', 'verified-pagination');
                }, 100);
            }

            // Search and pagination functionality (preserved from original)
            function filterAndPaginateTable(inputId, tableId, paginationId) {
                const input = document.getElementById(inputId);
                const table = document.getElementById(tableId);
                const pagination = document.getElementById(paginationId);
                if (!input || !table || !pagination) return;
                
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                let filteredRows = rows;
                let currentPage = 1;
                const rowsPerPage = 10;

                function renderTable() {
                    rows.forEach(row => row.style.display = 'none');
                    const start = (currentPage - 1) * rowsPerPage;
                    const end = start + rowsPerPage;
                    filteredRows.slice(start, end).forEach(row => row.style.display = '');
                    renderPagination();
                }

                function renderPagination() {
                    pagination.innerHTML = '';
                    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
                    if (totalPages <= 1) return;

                    // Prev button
                    const prev = document.createElement('button');
                    prev.textContent = 'Prev';
                    prev.className = 'px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm';
                    prev.disabled = currentPage === 1;
                    prev.onclick = () => { currentPage--; renderTable(); };
                    pagination.appendChild(prev);

                    // Page numbers
                    let pageButtons = [];
                    for (let i = 1; i <= totalPages; i++) {
                        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                            pageButtons.push(i);
                        } else if ((i === currentPage - 3 && currentPage - 3 > 1) || (i === currentPage + 3 && currentPage + 3 < totalPages)) {
                            pageButtons.push('...');
                        }
                    }
                    
                    let lastWasEllipsis = false;
                    pageButtons.forEach(i => {
                        if (i === '...') {
                            if (!lastWasEllipsis) {
                                const ellipsis = document.createElement('span');
                                ellipsis.textContent = '...';
                                ellipsis.className = 'px-2 text-gray-400 select-none';
                                pagination.appendChild(ellipsis);
                                lastWasEllipsis = true;
                            }
                        } else {
                            const btn = document.createElement('button');
                            btn.textContent = i;
                            btn.className = 'px-3 py-1 rounded ' + (i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700') + ' text-sm';
                            btn.onclick = () => { currentPage = i; renderTable(); };
                            pagination.appendChild(btn);
                            lastWasEllipsis = false;
                        }
                    });

                    // Next button
                    const next = document.createElement('button');
                    next.textContent = 'Next';
                    next.className = 'px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm';
                    next.disabled = currentPage === totalPages;
                    next.onclick = () => { currentPage++; renderTable(); };
                    pagination.appendChild(next);
                }

                input.addEventListener('input', function() {
                    const filter = input.value.toLowerCase();
                    filteredRows = rows.filter(row => row.innerText.toLowerCase().includes(filter));
                    currentPage = 1;
                    renderTable();
                });

                filteredRows = rows;
                currentPage = 1;
                renderTable();
            }

            // Start auto-refresh
            function startAutoRefresh() {
                if (updateInterval) clearInterval(updateInterval);
                updateInterval = setInterval(fetchDeviceData, 10000); // Update every 10 seconds
                
                // Update relative time every 30 seconds
                setInterval(() => {
                    if (lastDataTimestamp && lastUpdateElement) {
                        lastUpdateElement.textContent = getRelativeTime(lastDataTimestamp);
                    }
                }, 30000);
            }

            // Stop auto-refresh
            function stopAutoRefresh() {
                if (updateInterval) {
                    clearInterval(updateInterval);
                    updateInterval = null;
                }
            }

            // Event listeners
            if (refreshButton) {
                refreshButton.addEventListener('click', fetchDeviceData);
            }

            if (autoUpdateToggle) {
                autoUpdateToggle.addEventListener('change', function() {
                    if (this.checked) {
                        startAutoRefresh();
                        showNotification('Auto-refresh enabled', 'success');
                    } else {
                        stopAutoRefresh();
                        showNotification('Auto-refresh disabled', 'info');
                    }
                });
            }

            // Initialize
            initializeTableFeatures();
            startAutoRefresh();
            
            // Initial data fetch
            setTimeout(fetchDeviceData, 1000);
        });
        </script>
<?php
$page_content = ob_get_clean();

// Include admin layout
require_once '../../includes/admin_layout.php';
?> 