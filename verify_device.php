<?php
/**
 * Device Verification Handler
 * This file processes device verification requests from the client
 */
require_once 'config/config.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Check if this is a verification request
if (isset($_POST['action']) && $_POST['action'] === 'verify_device') {
    // Get the fingerprint from the request
    if (!isset($_POST['fingerprint']) || empty($_POST['fingerprint'])) {
        echo json_encode(['status' => 'error', 'message' => 'No fingerprint provided']);
        exit;
    }
    
    $fingerprint = sanitize($_POST['fingerprint']);
    
    // Check if this fingerprint already exists for this user
    $query = "SELECT * FROM user_devices WHERE user_id = ? AND fingerprint = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $fingerprint);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Device is already verified
        $device = mysqli_fetch_assoc($result);
        
        // Update the last_seen timestamp
        $update_query = "UPDATE user_devices SET last_seen = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $device['id']);
        mysqli_stmt_execute($update_stmt);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Device already verified',
            'device_id' => $device['id'],
            'is_verified' => true
        ]);
        exit;
    }
    
    // Check for similar fingerprints (fuzzy matching)
    // This helps identify the same device across different browsers
    $similar_devices = findSimilarDevices($conn, $user_id, $fingerprint);
    
    if (!empty($similar_devices)) {
        // We found a similar device, consider it verified
        echo json_encode([
            'status' => 'success',
            'message' => 'Similar device found',
            'device_id' => $similar_devices[0]['id'],
            'is_verified' => true
        ]);
        
        // Also store this fingerprint for future verification
        $insert_query = "INSERT INTO user_devices (user_id, fingerprint, device_name, is_verified, verification_date, last_seen)
                        VALUES (?, ?, 'Browser on ' || ?, 1, NOW(), NOW())";
        $device_name = getDeviceNameFromUserAgent();
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "iss", $user_id, $fingerprint, $device_name);
        mysqli_stmt_execute($insert_stmt);
        
        exit;
    }
    
    // This is a new device fingerprint
    // If the user has no verified devices yet, automatically verify this one
    $query = "SELECT COUNT(*) as device_count FROM user_devices WHERE user_id = ? AND is_verified = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $auto_verify = ($row['device_count'] == 0);
    $device_name = getDeviceNameFromUserAgent();
    
    // Store the new device
    $insert_query = "INSERT INTO user_devices (user_id, fingerprint, device_name, is_verified, verification_date, last_seen)
                   VALUES (?, ?, ?, ?, " . ($auto_verify ? "NOW()" : "NULL") . ", NOW())";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "issi", $user_id, $fingerprint, $device_name, $auto_verify);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $device_id = mysqli_insert_id($conn);
        
        echo json_encode([
            'status' => 'success',
            'message' => $auto_verify ? 'First device auto-verified' : 'New device registered',
            'device_id' => $device_id,
            'is_verified' => $auto_verify,
            'requires_verification' => !$auto_verify
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to register device']);
        exit;
    }
}

// Handle device verification confirmation
if (isset($_POST['action']) && $_POST['action'] === 'confirm_device') {
    if (!isset($_POST['device_id']) || empty($_POST['device_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'No device ID provided']);
        exit;
    }
    
    $device_id = intval($_POST['device_id']);
    
    // Verify that this device belongs to the user
    $query = "SELECT * FROM user_devices WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $device_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Device not found or does not belong to you']);
        exit;
    }
    
    // Update the device to mark it as verified
    $update_query = "UPDATE user_devices SET is_verified = 1, verification_date = NOW() WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $device_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Device verified successfully']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to verify device']);
        exit;
    }
}

// Function to find similar devices based on partial fingerprint matches
function findSimilarDevices($conn, $user_id, $fingerprint) {
    $devices = [];
    
    // Get all the user's verified devices
    $query = "SELECT * FROM user_devices WHERE user_id = ? AND is_verified = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($device = mysqli_fetch_assoc($result)) {
        // Skip exact matches as they are handled elsewhere
        if ($device['fingerprint'] === $fingerprint) {
            continue;
        }
        
        // Compare hardware aspects of the fingerprint
        $similarity = calculateFingerprintSimilarity($fingerprint, $device['fingerprint']);
        
        // If similarity is above threshold, consider it the same device
        if ($similarity >= 0.7) {
            $devices[] = $device;
        }
    }
    
    return $devices;
}

// Function to calculate similarity between two fingerprints
// This is a simplified version - in a real implementation you'd decode the fingerprints
// and compare the hardware components directly
function calculateFingerprintSimilarity($fingerprint1, $fingerprint2) {
    // In a real implementation, you'd compare specific hardware attributes
    // For this example, we use a simple string similarity function
    
    // Simple comparison - same length prefix match
    $length = min(strlen($fingerprint1), strlen($fingerprint2));
    $matchingChars = 0;
    
    for ($i = 0; $i < $length; $i++) {
        if ($fingerprint1[$i] === $fingerprint2[$i]) {
            $matchingChars++;
        }
    }
    
    return $matchingChars / $length;
}

// Function to get a device name from the user agent
function getDeviceNameFromUserAgent() {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    
    // Detect OS
    $os = 'Unknown OS';
    if (strpos($ua, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($ua, 'Mac') !== false) {
        $os = 'macOS';
    } elseif (strpos($ua, 'iOS') !== false || strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) {
        $os = 'iOS';
    } elseif (strpos($ua, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($ua, 'Linux') !== false) {
        $os = 'Linux';
    }
    
    // Detect browser
    $browser = 'Unknown Browser';
    if (strpos($ua, 'Edge') !== false || strpos($ua, 'Edg') !== false) {
        $browser = 'Edge';
    } elseif (strpos($ua, 'Chrome') !== false && strpos($ua, 'Chromium') === false) {
        $browser = 'Chrome';
    } elseif (strpos($ua, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($ua, 'Safari') !== false && strpos($ua, 'Chrome') === false) {
        $browser = 'Safari';
    } elseif (strpos($ua, 'Opera') !== false || strpos($ua, 'OPR') !== false) {
        $browser = 'Opera';
    }
    
    return "$browser on $os";
}
?> 