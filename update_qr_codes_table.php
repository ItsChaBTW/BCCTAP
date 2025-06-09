<?php
/**
 * Update QR Codes Table - Add Image Path Column
 */
require_once 'config/config.php';
$_SERVER['HTTP_HOST'] = 'localhost'; // To avoid HTTP_HOST warning

echo "<h1>Updating QR Codes Table</h1>";

// Check if image_path column exists
$query = "SHOW COLUMNS FROM qr_codes LIKE 'image_path'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "<p>✅ Image path column already exists.</p>";
} else {
    // Add image_path column
    $query = "ALTER TABLE qr_codes ADD COLUMN image_path VARCHAR(255) NULL AFTER code";
    
    if (mysqli_query($conn, $query)) {
        echo "<p>✅ Successfully added image_path column to qr_codes table.</p>";
    } else {
        echo "<p>❌ Error adding image_path column: " . mysqli_error($conn) . "</p>";
    }
}

echo "<p><a href='admin/events/index.php'>Return to Events</a></p>";
?> 