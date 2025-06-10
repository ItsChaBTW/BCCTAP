<?php
/**
 * Timezone Test Script - Verify Philippines timezone configuration
 */
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timezone Test - BCCTAP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">🕒 Timezone Configuration Test</h1>
            
            <!-- PHP Timezone Info -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h2 class="text-lg font-semibold text-blue-800 mb-3">PHP Timezone Information</h2>
                <div class="space-y-2">
                    <p><strong>Current Timezone:</strong> <span class="text-blue-600"><?php echo date_default_timezone_get(); ?></span></p>
                    <p><strong>Current PHP Date/Time:</strong> <span class="text-blue-600"><?php echo date('Y-m-d H:i:s T'); ?></span></p>
                    <p><strong>Formatted Time:</strong> <span class="text-blue-600"><?php echo date('F d, Y - h:i:s A'); ?></span></p>
                    <p><strong>UTC Offset:</strong> <span class="text-blue-600"><?php echo date('P'); ?></span></p>
                </div>
            </div>

            <!-- Database Timezone Info -->
            <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                <h2 class="text-lg font-semibold text-green-800 mb-3">Database Timezone Information</h2>
                <?php
                $query = "SELECT NOW() as current_time, @@session.time_zone as session_timezone, @@global.time_zone as global_timezone";
                $result = mysqli_query($conn, $query);
                $db_info = mysqli_fetch_assoc($result);
                ?>
                <div class="space-y-2">
                    <p><strong>Database Current Time:</strong> <span class="text-green-600"><?php echo $db_info['current_time']; ?></span></p>
                    <p><strong>Session Timezone:</strong> <span class="text-green-600"><?php echo $db_info['session_timezone']; ?></span></p>
                    <p><strong>Global Timezone:</strong> <span class="text-green-600"><?php echo $db_info['global_timezone']; ?></span></p>
                </div>
            </div>

            <!-- Time Verification -->
            <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                <h2 class="text-lg font-semibold text-purple-800 mb-3">📍 Philippines Time Verification</h2>
                <div class="space-y-2">
                    <p><strong>Expected Philippines Time (UTC+8):</strong> <span class="text-purple-600" id="philippinesTime">Loading...</span></p>
                    <p><strong>Is Timezone Correct?:</strong> 
                        <span class="text-purple-600 font-semibold">
                            <?php 
                            $expected_offset = '+08:00';
                            $current_offset = date('P');
                            echo ($current_offset === $expected_offset) ? '✅ YES - Correctly set to Asia/Manila' : '❌ NO - Expected +08:00, got ' . $current_offset;
                            ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Attendance Time Format Test -->
            <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                <h2 class="text-lg font-semibold text-indigo-800 mb-3">📝 Attendance Time Format Test</h2>
                <div class="space-y-2">
                    <p><strong>Date Format (as shown in attendance):</strong> <span class="text-indigo-600"><?php echo date('M d, Y'); ?></span></p>
                    <p><strong>Time Format (as shown in attendance):</strong> <span class="text-indigo-600"><?php echo date('h:i A'); ?></span></p>
                    <p><strong>Full DateTime Format:</strong> <span class="text-indigo-600"><?php echo date('F d, Y @ h:i:s A T'); ?></span></p>
                </div>
            </div>

            <div class="text-center">
                <a href="admin/events/index.php" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                    Back to Admin
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const philippinesTime = new Date().toLocaleString('en-US', {
                timeZone: 'Asia/Manila',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            document.getElementById('philippinesTime').textContent = philippinesTime;
        });
    </script>
</body>
</html> 