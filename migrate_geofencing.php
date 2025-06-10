<?php
/**
 * Migration Script for Geofencing Features
 * Adds location coordinates and geofence radius to events table
 */
require_once 'config/config.php';

echo "<h1>Migrating Database for Geofencing Features</h1><br>";

try {
    // Check if uuid column exists
    $query = "SHOW COLUMNS FROM events LIKE 'uuid'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        echo "Adding uuid column...<br>";
        $query = "ALTER TABLE events ADD COLUMN uuid VARCHAR(36) DEFAULT NULL AFTER id";
        if (mysqli_query($conn, $query)) {
            echo "✅ Successfully added uuid column<br>";
        } else {
            echo "❌ Error adding uuid column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "✅ UUID column already exists<br>";
    }

    // Check if location_latitude column exists
    $query = "SHOW COLUMNS FROM events LIKE 'location_latitude'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        echo "Adding location_latitude column...<br>";
        $query = "ALTER TABLE events ADD COLUMN location_latitude DECIMAL(10, 8) DEFAULT NULL AFTER location";
        if (mysqli_query($conn, $query)) {
            echo "✅ Successfully added location_latitude column<br>";
        } else {
            echo "❌ Error adding location_latitude column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "✅ Location latitude column already exists<br>";
    }

    // Check if location_longitude column exists
    $query = "SHOW COLUMNS FROM events LIKE 'location_longitude'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        echo "Adding location_longitude column...<br>";
        $query = "ALTER TABLE events ADD COLUMN location_longitude DECIMAL(11, 8) DEFAULT NULL AFTER location_latitude";
        if (mysqli_query($conn, $query)) {
            echo "✅ Successfully added location_longitude column<br>";
        } else {
            echo "❌ Error adding location_longitude column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "✅ Location longitude column already exists<br>";
    }

    // Check if geofence_radius column exists
    $query = "SHOW COLUMNS FROM events LIKE 'geofence_radius'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        echo "Adding geofence_radius column...<br>";
        $query = "ALTER TABLE events ADD COLUMN geofence_radius INT DEFAULT 100 COMMENT 'Radius in meters' AFTER location_longitude";
        if (mysqli_query($conn, $query)) {
            echo "✅ Successfully added geofence_radius column<br>";
        } else {
            echo "❌ Error adding geofence_radius column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "✅ Geofence radius column already exists<br>";
    }

    // Update existing events with UUID if they don't have one
    echo "<br>Updating existing events with UUIDs...<br>";
    $query = "SELECT id FROM events WHERE uuid IS NULL";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $event_id = $row['id'];
            $uuid = generate_uuid();
            
            $update_query = "UPDATE events SET uuid = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $uuid, $event_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "✅ Updated event ID {$event_id} with UUID: {$uuid}<br>";
            } else {
                echo "❌ Failed to update event ID {$event_id}<br>";
            }
        }
    } else {
        echo "✅ All events already have UUIDs<br>";
    }

    echo "<br><h2>Migration Complete!</h2>";
    echo "<p>✅ Geofencing features are now ready to use.</p>";
    echo "<p><a href='admin/events/index.php'>Return to Events Management</a></p>";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "<br>";
}

// Function to generate a UUID v4
function generate_uuid() {
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $data = openssl_random_pseudo_bytes(16);
    } else {
        $data = '';
        for ($i = 0; $i < 16; $i++) {
            $data .= chr(mt_rand(0, 255));
        }
    }
    
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
?>
