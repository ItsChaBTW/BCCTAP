<?php
/**
 * Admin Settings Page
 * Provides interface for system configurations
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Handle form submissions
$success_message = '';
$error_message = '';

// Define settings categories and default values
$settings = [
    'general' => [
        'site_title' => 'BCCTAP - Bago City College Time Attendance Platform',
        'site_description' => 'Time Attendance Management System for Bago City College',
        'timezone' => 'Asia/Manila',
        'date_format' => 'Y-m-d',
        'time_format' => 'h:i A',
    ],
    'attendance' => [
        'allow_late_scans' => '1',
        'max_late_minutes' => '15',
        'auto_mark_absent' => '1',
        'require_device_registration' => '0',
        'late_interval' => '15',
    ],
    'notifications' => [
        'email_notifications' => '0',
        'email_admin_on_attendance' => '0',
        'email_student_on_attendance' => '0',
        'admin_email' => 'admin@bcctap.bccbsis.com',
    ]
];

// Check if settings table exists, create it if it doesn't
$check_table_query = "SHOW TABLES LIKE 'settings'";
$table_exists = mysqli_query($conn, $check_table_query);

if (mysqli_num_rows($table_exists) == 0) {
    // Create settings table
    $create_table_query = "CREATE TABLE settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL,
        value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY setting_key_unique (setting_key)
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        $error_message = "Failed to create settings table: " . mysqli_error($conn);
    } else {
        // Insert default settings
        foreach ($settings as $category => $category_settings) {
            foreach ($category_settings as $key => $value) {
                $full_key = $category . '.' . $key;
                $insert_query = "INSERT INTO settings (setting_key, value) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "ss", $full_key, $value);
                mysqli_stmt_execute($stmt);
            }
        }
        $success_message = "Settings table created with default values";
    }
}

// Load settings from database if they exist
$query = "SELECT * FROM settings";
if ($result = mysqli_query($conn, $query)) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Add checks to make sure the keys exist
        if (!isset($row['setting_key']) || !isset($row['value'])) {
            continue; // Skip this row if required keys aren't present
        }
        
        $key_parts = explode('.', $row['setting_key']);
        if (count($key_parts) !== 2) {
            continue; // Skip if key format is invalid
        }
        
        $category = $key_parts[0];
        $setting_key = $key_parts[1];
        $value = $row['value'];
        
        if (isset($settings[$category][$setting_key])) {
            $settings[$category][$setting_key] = $value;
        }
    }
}

// Process settings update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $category = sanitize($_POST['category']);
    
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            foreach ($_POST['settings'] as $key => $value) {
                $key = sanitize($key);
                $value = sanitize($value);
                $full_key = $category . '.' . $key;
                
                // Check if setting exists
                $query = "SELECT id FROM settings WHERE setting_key = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "s", $full_key);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    // Update existing setting
                    $query = "UPDATE settings SET value = ? WHERE setting_key = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "ss", $value, $full_key);
                } else {
                    // Insert new setting
                    $query = "INSERT INTO settings (setting_key, value) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "ss", $full_key, $value);
                }
                
                mysqli_stmt_execute($stmt);
                
                // Update in-memory settings
                if (isset($settings[$category][$key])) {
                    $settings[$category][$key] = $value;
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            $success_message = "Settings updated successfully!";
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            $error_message = "Failed to update settings: " . $e->getMessage();
        }
    }
}

// Handle adding new department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $department_name = sanitize($_POST['department_name']);
    $department_code = isset($_POST['department_code']) ? sanitize($_POST['department_code']) : '';
    
    // Check if department name is empty
    if (empty($department_name)) {
        $error_message = "Department name cannot be empty";
    } else {
        // Check if department already exists
        $check_query = "SELECT COUNT(*) as count FROM departments WHERE name = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $department_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            $error_message = "Department with this name already exists";
        } else {
            // Add the new department
            $insert_query = "INSERT INTO departments (name, code) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "ss", $department_name, $department_code);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Department added successfully!";
                
                // Refresh department list
                $query = "SELECT * FROM departments ORDER BY name";
                $departments_result = mysqli_query($conn, $query);
                $departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);
            } else {
                $error_message = "Failed to add department: " . mysqli_error($conn);
            }
        }
    }
}

// Get departments for department management
$departments = [];
$query = "SELECT * FROM departments ORDER BY name";

// Check if departments table exists
$check_dept_table = mysqli_query($conn, "SHOW TABLES LIKE 'departments'");
if (mysqli_num_rows($check_dept_table) > 0) {
    $departments_result = mysqli_query($conn, $query);
    if ($departments_result) {
        $departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);
    }
} else {
    // Create departments table
    $create_dept_query = "CREATE TABLE departments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY name_unique (name)
    )";
    if (mysqli_query($conn, $create_dept_query)) {
        $success_message = "Departments table created successfully. Add your first department below.";
    } else {
        $error_message = "Failed to create departments table: " . mysqli_error($conn);
    }
}

// Set the page title and active tab
$page_title = "System Settings";
$active_tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'general';

// Start output buffering for page content
ob_start();
?>

<div class="max-w-5xl mx-auto px-2 sm:px-4 py-6">
    <!-- Tab Bar -->
    <nav class="sticky top-0 z-10 bg-white border-b border-gray-200 flex overflow-x-auto no-scrollbar shadow-sm rounded-t-2xl">
        <a href="?tab=general" class="flex items-center gap-2 px-5 py-4 font-medium text-sm transition whitespace-nowrap <?php echo $active_tab == 'general' ? 'bg-blue-600 text-white font-bold shadow' : 'text-gray-600 hover:bg-gray-100'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
            General
        </a>
        <a href="?tab=attendance" class="flex items-center gap-2 px-5 py-4 font-medium text-sm transition whitespace-nowrap <?php echo $active_tab == 'attendance' ? 'bg-blue-600 text-white font-bold shadow' : 'text-gray-600 hover:bg-gray-100'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2a4 4 0 018 0v2"/><circle cx="12" cy="7" r="4"/></svg>
            Attendance
        </a>
        <a href="?tab=notifications" class="flex items-center gap-2 px-5 py-4 font-medium text-sm transition whitespace-nowrap <?php echo $active_tab == 'notifications' ? 'bg-blue-600 text-white font-bold shadow' : 'text-gray-600 hover:bg-gray-100'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 7.165 6 9.388 6 12v2.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Notifications
        </a>
        <a href="?tab=departments" class="flex items-center gap-2 px-5 py-4 font-medium text-sm transition whitespace-nowrap <?php echo $active_tab == 'departments' ? 'bg-blue-600 text-white font-bold shadow' : 'text-gray-600 hover:bg-gray-100'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Departments
        </a>
        <a href="?tab=database" class="flex items-center gap-2 px-5 py-4 font-medium text-sm transition whitespace-nowrap <?php echo $active_tab == 'database' ? 'bg-blue-600 text-white font-bold shadow' : 'text-gray-600 hover:bg-gray-100'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9V7a5 5 0 0110 0v2"/></svg>
            Database
        </a>
    </nav>

    <!-- Feedback Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mt-6 mb-4 rounded-xl shadow flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="text-sm text-green-800"><?php echo $success_message; ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-4 text-green-500 hover:text-green-700 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mt-6 mb-4 rounded-xl shadow flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                <span class="text-sm text-red-800"><?php echo $error_message; ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-4 text-red-500 hover:text-red-700 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    <?php endif; ?>

    <div class="py-6">
        <?php if ($active_tab == 'general'): ?>
            <!-- General Settings -->
            <form method="post" action="" class="bg-white rounded-2xl shadow p-6 transition-all">
                <input type="hidden" name="category" value="general">
                <div class="max-w-3xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="site_title" class="block text-sm font-semibold text-gray-700 mb-1">Site Title</label>
                        <input type="text" id="site_title" name="settings[site_title]" value="<?php echo htmlspecialchars($settings['general']['site_title']); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition" />
                        <p class="mt-1 text-xs text-gray-500">The title of your site that appears in the browser tab</p>
                    </div>
                    <div>
                        <label for="site_description" class="block text-sm font-semibold text-gray-700 mb-1">Site Description</label>
                        <textarea id="site_description" name="settings[site_description]" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition"><?php echo htmlspecialchars($settings['general']['site_description']); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">A short description of your site</p>
                    </div>
                    <div>
                        <label for="timezone" class="block text-sm font-semibold text-gray-700 mb-1">Timezone</label>
                        <select id="timezone" name="settings[timezone]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition">
                            <option value="Asia/Manila" <?php echo $settings['general']['timezone'] == 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila</option>
                            <option value="Asia/Singapore" <?php echo $settings['general']['timezone'] == 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore</option>
                            <option value="UTC" <?php echo $settings['general']['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">The timezone used for date and time calculations</p>
                    </div>
                    <div>
                        <label for="date_format" class="block text-sm font-semibold text-gray-700 mb-1">Date Format</label>
                        <select id="date_format" name="settings[date_format]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition">
                            <option value="Y-m-d" <?php echo $settings['general']['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD (e.g., 2025-06-09)</option>
                            <option value="m/d/Y" <?php echo $settings['general']['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY (e.g., 06/09/2025)</option>
                            <option value="d-m-Y" <?php echo $settings['general']['date_format'] == 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY (e.g., 09-06-2025)</option>
                            <option value="F j, Y" <?php echo $settings['general']['date_format'] == 'F j, Y' ? 'selected' : ''; ?>>Month Day, Year (e.g., June 9, 2025)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Format for displaying dates throughout the application</p>
                    </div>
                    <div>
                        <label for="time_format" class="block text-sm font-semibold text-gray-700 mb-1">Time Format</label>
                        <select id="time_format" name="settings[time_format]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition">
                            <option value="h:i A" <?php echo $settings['general']['time_format'] == 'h:i A' ? 'selected' : ''; ?>>12-hour (e.g., 3:45 PM)</option>
                            <option value="H:i" <?php echo $settings['general']['time_format'] == 'H:i' ? 'selected' : ''; ?>>24-hour (e.g., 15:45)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Format for displaying times throughout the application</p>
                    </div>
                </div>
                <div class="mt-8 border-t border-gray-200 pt-6 flex justify-end">
                    <button type="submit" name="save_settings" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        Save Settings
                    </button>
                </div>
            </form>
        <?php elseif ($active_tab == 'attendance'): ?>
            <!-- Attendance Settings -->
            <form method="post" action="" class="bg-white rounded-2xl shadow p-6 transition-all">
                <input type="hidden" name="category" value="attendance">
                <div class="max-w-3xl mx-auto grid grid-cols-1 gap-6">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="settings[allow_late_scans]" value="0">
                        <input type="checkbox" id="allow_late_scans" name="settings[allow_late_scans]" value="1" <?php echo $settings['attendance']['allow_late_scans'] == '1' ? 'checked' : ''; ?> onchange="toggleLateInterval(this.checked)" class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition" />
                        <label for="allow_late_scans" class="ml-2 block text-base text-gray-700 font-medium">Allow Late Attendance Scans</label>
                        <span class="ml-4 text-xs text-gray-500">If enabled, students can scan attendance after the scheduled time but will be marked as late</span>
                    </div>
                    <div>
                        <label for="late_interval" class="block text-sm font-semibold text-gray-700 mb-1">Late Interval (minutes)</label>
                        <input type="number" id="late_interval" name="settings[late_interval]" value="<?php echo htmlspecialchars($settings['attendance']['late_interval']); ?>" <?php echo $settings['attendance']['allow_late_scans'] != '1' ? 'disabled' : ''; ?> class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed" min="1" required />
                        <p class="mt-1 text-xs text-gray-500">Number of minutes after scheduled time to consider attendance as late</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="settings[auto_mark_absent]" value="0">
                        <input type="checkbox" id="auto_mark_absent" name="settings[auto_mark_absent]" value="1" <?php echo $settings['attendance']['auto_mark_absent'] == '1' ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition" />
                        <label for="auto_mark_absent" class="ml-2 block text-base text-gray-700 font-medium">Auto-mark Absent Students</label>
                        <span class="ml-4 text-xs text-gray-500">Automatically mark students as absent if they haven't scanned by the end of the session</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="settings[require_device_registration]" value="0">
                        <input type="checkbox" id="require_device_registration" name="settings[require_device_registration]" value="1" <?php echo $settings['attendance']['require_device_registration'] == '1' ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition" />
                        <label for="require_device_registration" class="ml-2 block text-base text-gray-700 font-medium">Require Device Registration</label>
                        <span class="ml-4 text-xs text-gray-500">If enabled, students must register their device before being able to scan attendance</span>
                    </div>
                </div>
                <div class="mt-8 border-t border-gray-200 pt-6 flex justify-end">
                    <button type="submit" name="save_settings" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        Save Settings
                    </button>
                </div>
            </form>
        <?php elseif ($active_tab == 'notifications'): ?>
            <!-- Notification Settings -->
            <form method="post" action="" class="bg-white rounded-2xl shadow p-6 transition-all">
                <input type="hidden" name="category" value="notifications">
                <div class="max-w-3xl mx-auto grid grid-cols-1 gap-6">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="settings[email_notifications]" value="0">
                        <input type="checkbox" id="email_notifications" name="settings[email_notifications]" value="1" <?php echo $settings['notifications']['email_notifications'] == '1' ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition" />
                        <label for="email_notifications" class="ml-2 block text-base text-gray-700 font-medium">Enable Email Notifications</label>
                        <span class="ml-4 text-xs text-gray-500">Master switch for all email notifications</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="settings[email_admin_on_attendance]" value="0">
                        <input type="checkbox" id="email_admin_on_attendance" name="settings[email_admin_on_attendance]" value="1" <?php echo $settings['notifications']['email_admin_on_attendance'] == '1' ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition" />
                        <label for="email_admin_on_attendance" class="ml-2 block text-base text-gray-700 font-medium">Notify Admin on Attendance</label>
                        <span class="ml-4 text-xs text-gray-500">Send email notifications to admin when attendance is recorded</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="settings[email_student_on_attendance]" value="0">
                        <input type="checkbox" id="email_student_on_attendance" name="settings[email_student_on_attendance]" value="1" <?php echo $settings['notifications']['email_student_on_attendance'] == '1' ? 'checked' : ''; ?> class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition" />
                        <label for="email_student_on_attendance" class="ml-2 block text-base text-gray-700 font-medium">Notify Students on Attendance</label>
                        <span class="ml-4 text-xs text-gray-500">Send email confirmation to students when they record attendance</span>
                    </div>
                    <div>
                        <label for="admin_email" class="block text-sm font-semibold text-gray-700 mb-1">Admin Email</label>
                        <input type="email" id="admin_email" name="settings[admin_email]" value="<?php echo htmlspecialchars($settings['notifications']['admin_email']); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition" />
                        <p class="mt-1 text-xs text-gray-500">Email address for admin notifications</p>
                    </div>
                </div>
                <div class="mt-8 border-t border-gray-200 pt-6 flex justify-end">
                    <button type="submit" name="save_settings" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        Save Settings
                    </button>
                </div>
            </form>
        <?php elseif ($active_tab == 'departments'): ?>
            <!-- Department Management -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Department List -->
                <div class="bg-white rounded-2xl shadow p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>Current Departments</h3>
                    <div class="border border-gray-200 rounded-lg overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($departments) > 0): ?>
                                    <?php foreach ($departments as $department): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?php echo htmlspecialchars($department['name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap flex gap-2">
                                                <a href="#" class="text-blue-600 hover:text-blue-900 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536M9 11l6 6M3 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>Edit</a>
                                                <a href="#" class="text-red-600 hover:text-red-900 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="px-6 py-4 text-gray-500 text-center">No departments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Add Department Form (collapsible on mobile) -->
                <div class="bg-white rounded-2xl shadow p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-3 flex items-center gap-2"><svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>Add New Department</h3>
                    <form method="post" action="" class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="department_name" class="block text-sm font-semibold text-gray-700 mb-1">Department Name</label>
                            <input type="text" id="department_name" name="department_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition" />
                        </div>
                        <div>
                            <label for="department_code" class="block text-sm font-semibold text-gray-700 mb-1">Department Code (Optional)</label>
                            <input type="text" id="department_code" name="department_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400 text-base shadow-sm transition" />
                            <p class="mt-1 text-xs text-gray-500">A short code for the department (e.g., "CS" for Computer Science)</p>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" name="add_department" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                                Add Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($active_tab == 'database'): ?>
            <!-- Database Tools -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Backup Database -->
                <div class="bg-gradient-to-r from-green-100 to-green-50 border border-green-200 rounded-2xl p-6 flex flex-col gap-2 shadow hover:shadow-lg transition">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        <h3 class="text-lg font-medium text-green-800">Database Backup</h3>
                    </div>
                    <p class="text-sm text-green-700 mb-4">Create a backup of your database that you can download and store safely.</p>
                    <form method="post" action="">
                        <button type="submit" name="backup_database" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition duration-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            Create Backup
                        </button>
                    </form>
                </div>
                <!-- Database Optimization -->
                <div class="bg-gradient-to-r from-blue-100 to-blue-50 border border-blue-200 rounded-2xl p-6 flex flex-col gap-2 shadow hover:shadow-lg transition">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                        <h3 class="text-lg font-medium text-blue-800">Optimize Database</h3>
                    </div>
                    <p class="text-sm text-blue-700 mb-4">Optimize database tables to improve performance.</p>
                    <form method="post" action="">
                        <button type="submit" name="optimize_database" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition duration-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                            Optimize Now
                        </button>
                    </form>
                </div>
                <!-- Clear Old Data -->
                <div class="bg-gradient-to-r from-amber-100 to-amber-50 border border-amber-200 rounded-2xl p-6 flex flex-col gap-2 shadow hover:shadow-lg transition">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="h-6 w-6 text-amber-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        <h3 class="text-lg font-medium text-amber-800">Clear Old Data</h3>
                    </div>
                    <p class="text-sm text-amber-700 mb-4">Remove attendance records older than the selected date to free up space.</p>
                    <form method="post" action="" class="flex flex-col sm:flex-row items-end gap-3">
                        <div class="flex-grow">
                            <label for="clear_before_date" class="block text-sm font-medium text-amber-700 mb-1">Clear Data Before</label>
                            <input type="date" id="clear_before_date" name="clear_before_date" required class="w-full border border-amber-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white" />
                        </div>
                        <button type="submit" name="clear_old_data" class="bg-amber-600 hover:bg-amber-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2 transition duration-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear Data
                        </button>
                    </form>
                </div>
                <!-- System Information -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 flex flex-col gap-2 shadow">
                    <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center gap-2"><svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01"/></svg>System Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-200"><span class="font-medium text-gray-600 flex items-center gap-1"><svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01"/></svg>PHP Version</span><span class="text-gray-800"><?php echo phpversion(); ?></span></div>
                        <div class="flex justify-between py-2 border-b border-gray-200"><span class="font-medium text-gray-600 flex items-center gap-1"><svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01"/></svg>MySQL Version</span><span class="text-gray-800"><?php echo mysqli_get_server_info($conn); ?></span></div>
                        <div class="flex justify-between py-2 border-b border-gray-200"><span class="font-medium text-gray-600 flex items-center gap-1"><svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01"/></svg>Total Users</span><span class="text-gray-800"><?php $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users"); $user_count = mysqli_fetch_assoc($result)['total']; echo $user_count; ?></span></div>
                        <div class="flex justify-between py-2"><span class="font-medium text-gray-600 flex items-center gap-1"><svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01"/></svg>Database Size</span><span class="text-gray-800"><?php $result = mysqli_query($conn, "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = DATABASE() GROUP BY table_schema"); $db_size = mysqli_fetch_assoc($result)['Size (MB)'] ?? '0'; echo $db_size; ?> MB</span></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const allowLateScans = document.getElementById('allow_late_scans');
    if (allowLateScans) {
        toggleLateInterval(allowLateScans.checked);
    }
});

function toggleLateInterval(isChecked) {
    const lateIntervalInput = document.getElementById('late_interval');
    if (!lateIntervalInput) return;

    lateIntervalInput.disabled = !isChecked;
    
    if (!isChecked) {
        // Store the current value before disabling
        lateIntervalInput.dataset.previousValue = lateIntervalInput.value;
        lateIntervalInput.value = '15'; // Default value when disabled
    } else {
        // Restore the previous value if it exists
        if (lateIntervalInput.dataset.previousValue) {
            lateIntervalInput.value = lateIntervalInput.dataset.previousValue;
        }
    }

    // Update input styling
    if (!isChecked) {
        lateIntervalInput.classList.add('bg-gray-100', 'text-gray-500', 'cursor-not-allowed');
    } else {
        lateIntervalInput.classList.remove('bg-gray-100', 'text-gray-500', 'cursor-not-allowed');
    }
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const allowLateScans = document.getElementById('allow_late_scans');
    const lateIntervalInput = document.getElementById('late_interval');
    
    if (allowLateScans && allowLateScans.checked) {
        const value = parseInt(lateIntervalInput.value);
        if (isNaN(value) || value < 1) {
            e.preventDefault();
            alert('Please enter a valid late interval (minimum 1 minute)');
            lateIntervalInput.focus();
        }
    }
});
</script>

<?php
// Get the page content from buffer
$page_content = ob_get_clean();

// Include the admin layout
include '../../includes/admin_layout.php';
?> 