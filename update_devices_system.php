<?php
/**
 * Deploy Device Management System Update
 * This script:
 * 1. Creates the necessary user_devices table
 * 2. Adds a setting for device restriction enforcement
 * 3. Migrates existing device data to the new system
 */
require_once 'config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    echo "Access denied. You must be logged in as an administrator.";
    exit;
}

// Create the user_devices table
$sql_file = 'sql/create_user_devices_table.sql';
if (!file_exists($sql_file)) {
    echo "Error: SQL file not found!";
    exit;
}

$sql = file_get_contents($sql_file);
if (mysqli_multi_query($conn, $sql)) {
    echo "<p>✅ User devices table created successfully.</p>";
    
    // Clear result sets to continue execution
    while (mysqli_next_result($conn)) {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
} else {
    echo "<p>❌ Error creating user devices table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Add setting for device restriction enforcement
$query = "INSERT INTO settings (name, value, description, type, options) 
          VALUES ('enforce_device_restriction', '1', 'Enforce device restriction for student logins', 'boolean', 'true,false')
          ON DUPLICATE KEY UPDATE value = '1'";

if (mysqli_query($conn, $query)) {
    echo "<p>✅ Device restriction setting added successfully.</p>";
} else {
    echo "<p>❌ Error adding device restriction setting: " . mysqli_error($conn) . "</p>";
}

// Migrate existing device data from users table
$query = "SELECT id, device_id, first_device_date FROM users WHERE role = 'student' AND device_id IS NOT NULL";
$result = mysqli_query($conn, $query);

$migrated = 0;
$errors = 0;

if ($result) {
    while ($user = mysqli_fetch_assoc($result)) {
        $user_id = $user['id'];
        $device_id = $user['device_id'];
        $first_device_date = $user['first_device_date'] ?? date('Y-m-d H:i:s');
        
        // Create device entry in the new table
        $insert_query = "INSERT INTO user_devices (user_id, fingerprint, device_name, is_verified, verification_date, last_seen) 
                        VALUES (?, ?, 'Migrated Device', 1, ?, ?)
                        ON DUPLICATE KEY UPDATE last_seen = ?";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $device_id, $first_device_date, $first_device_date, $first_device_date);
        
        if (mysqli_stmt_execute($stmt)) {
            $migrated++;
        } else {
            $errors++;
            echo "<p>❌ Error migrating device for user ID $user_id: " . mysqli_error($conn) . "</p>";
        }
    }
    
    echo "<p>✅ Device data migration completed. Migrated $migrated devices with $errors errors.</p>";
} else {
    echo "<p>❌ Error retrieving existing device data: " . mysqli_error($conn) . "</p>";
}

echo "<p>✅ Cross-browser device verification system is now installed!</p>";
echo "<p>➡️ <a href='admin/users/devices.php'>Go to Device Management</a></p>";
?> 