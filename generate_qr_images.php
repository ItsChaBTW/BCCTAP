<?php
/**
 * Generate QR Code Images for Existing QR Codes
 */
require_once 'config/config.php';
require_once 'utils/QrCodeGenerator.php';

$_SERVER['HTTP_HOST'] = 'localhost'; // To avoid the HTTP_HOST warning

echo "<h1>Generating QR Code Images</h1>";

// Check if the uploads/qrcodes directory exists, create it if not
$directory = 'uploads/qrcodes';
if (!is_dir($directory)) {
    if (mkdir($directory, 0777, true)) {
        echo "<p>Created directory: $directory</p>";
    } else {
        echo "<p class='error'>Failed to create directory: $directory</p>";
        exit;
    }
}

// Get all QR codes that don't have an image path
$query = "SELECT qc.*, e.title as event_title 
          FROM qr_codes qc
          JOIN events e ON qc.event_id = e.id
          WHERE qc.image_path IS NULL OR qc.image_path = ''";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "<p class='error'>Error: " . mysqli_error($conn) . "</p>";
    exit;
}

$total = mysqli_num_rows($result);
echo "<p>Found $total QR codes without images.</p>";

$generated = 0;
$errors = 0;

while ($qr_code = mysqli_fetch_assoc($result)) {
    echo "<h3>Processing QR Code ID: " . $qr_code['id'] . " for Event: " . htmlspecialchars($qr_code['event_title']) . "</h3>";
    
    // Create the scan URL
    $scan_url = BASE_URL . 'scan.php?code=' . urlencode($qr_code['code']);
    
    // Generate filename from event ID and QR code ID
    $filename = "event_{$qr_code['event_id']}_qr_{$qr_code['id']}.png";
    
    try {
        // Generate the QR code image
        $qr_image_path = QrCodeGenerator::generate(
            $scan_url,
            $filename,
            $directory,
            300,
            "Event QR Code"
        );
        
        // Update the QR code record with the image path
        $update_query = "UPDATE qr_codes SET image_path = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        $relative_path = 'uploads/qrcodes/' . basename($qr_image_path);
        mysqli_stmt_bind_param($stmt, "si", $relative_path, $qr_code['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color:green;'>✅ Generated and saved QR code image: $relative_path</p>";
            $generated++;
        } else {
            echo "<p style='color:red;'>❌ Failed to update QR code record: " . mysqli_error($conn) . "</p>";
            $errors++;
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Error generating QR code: " . $e->getMessage() . "</p>";
        $errors++;
    }
}

echo "<h2>Summary</h2>";
echo "<p>Total QR codes processed: $total</p>";
echo "<p>Successfully generated: $generated</p>";
echo "<p>Errors: $errors</p>";

echo "<p><a href='admin/index.php'>Return to Dashboard</a></p>";
?> 