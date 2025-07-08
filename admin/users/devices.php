<?php
/**
 * Student Device Management for Admins
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
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
                    <?php if (!empty($unverified_devices)): ?>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 animate-pulse">Pending: <?php echo count($unverified_devices); ?></span>
                    <?php endif; ?>
                </div>
                <!-- Search for Pending -->
                <div class="mb-4 flex justify-end">
                    <input type="text" id="pending-search" placeholder="Search pending devices..." class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-200 focus:border-yellow-400 text-sm" />
                </div>
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

            <!-- Verified Devices -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
                    <h2 class="text-xl font-bold text-gray-800">Verified Devices</h2>
                    <?php if (!empty($verified_devices)): ?>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verified: <?php echo count($verified_devices); ?></span>
                    <?php endif; ?>
                </div>
                <!-- Search for Verified -->
                <div class="mb-4 flex justify-end">
                    <input type="text" id="verified-search" placeholder="Search verified devices..." class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-400 text-sm" />
                </div>
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

            <!-- Help Section -->
            <div class="mt-12 bg-white rounded-2xl shadow-lg p-8">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M13 16h-1v-4h-1m1-4h.01"/></svg>
                    <h2 class="text-lg font-bold text-gray-800">About Device Verification</h2>
                </div>
                <div class="space-y-4 text-gray-600">
                    <p><strong>Cross-Browser Support:</strong> With the new system, students can use any browser on their verified device to access the platform.</p>
                    <p><strong>Device Recognition:</strong> The system uses advanced fingerprinting to identify the same physical device across different browsers.</p>
                    <p><strong>Security:</strong> Only verified devices are allowed to access student accounts, protecting against unauthorized access.</p>
                    <p><strong>Process:</strong> When a student attempts to log in from a new device, the login is blocked until you verify the device here.</p>
                </div>
                <div class="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mt-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>
                        <div>
                            <h3 class="text-sm font-semibold text-blue-800">Important Note</h3>
                            <p class="mt-2 text-sm text-blue-700">
                                When verifying a device, make sure to confirm the student's identity first, especially if there are multiple verification requests from different devices for the same student in a short period.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <script>
        // Search and Pagination for Pending/Verified Devices
        document.addEventListener('DOMContentLoaded', function() {
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
                    // Hide all rows
                    rows.forEach(row => row.style.display = 'none');
                    // Show only filtered rows for current page
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
                    for (let i = 1; i <= totalPages; i++) {
                        const btn = document.createElement('button');
                        btn.textContent = i;
                        btn.className = 'px-3 py-1 rounded ' + (i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700') + ' text-sm';
                        btn.onclick = () => { currentPage = i; renderTable(); };
                        pagination.appendChild(btn);
                    }
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

                // Initial render
                filteredRows = rows;
                currentPage = 1;
                renderTable();
            }
            filterAndPaginateTable('pending-search', 'pending-table', 'pending-pagination');
            filterAndPaginateTable('verified-search', 'verified-table', 'verified-pagination');
        });
        </script>
<?php
$page_content = ob_get_clean();

// Include admin layout
require_once '../../includes/admin_layout.php';
?> 