<?php
/**
 * Authentication functions for BCCTAP
 */

/**
 * Verify student credentials against TechnoPal API
 * 
 * @param string $student_id Student ID (numeric)
 * @param string $password Student password
 * @return array Authentication result with status and user data if successful
 */
function verifyTechnoPalCredentials($student_id, $password) {
    // For debugging - create a log file
    $log_file = dirname(__DIR__) . '/logs/api_debug.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Attempting to authenticate: $student_id\n", FILE_APPEND);
    
    // Build the direct URL with query parameters (instead of POST)
    $api_url = "https://bagocitycollege.com/BCCWeb/TPLoginAPI?txtUserName=$student_id&txtPassword=$password";
    
    // Initialize cURL session with GET request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only use in development
    
    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Log the raw response for debugging
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - HTTP Status: $http_code\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - API Response: $response\n", FILE_APPEND);
    
    // Close cURL session
    curl_close($ch);
    
    // Process response
    if ($http_code == 200) {
        $result = json_decode($response, true);
        
        // Log the decoded result for debugging
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Decoded: " . print_r($result, true) . "\n", FILE_APPEND);
        
        // Check if login was successful based on the known API response format
        if (isset($result['login']) && $result['login'] === true && 
            isset($result['is_valid']) && $result['is_valid'] === true) {
            
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Authentication successful\n", FILE_APPEND);
            return [
                'status' => true,
                'user_data' => $result
            ];
        }
    }
    
    // Log failure reason
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Authentication failed\n", FILE_APPEND);
    
    // Return failure if API call failed or returned unsuccessful login
    return ['status' => false];
}

/**
 * Authenticate student using TechnoPal API and create/update local account if needed
 * 
 * @param string $student_id Student ID (numeric)
 * @param string $password Student password
 * @return boolean Authentication success or failure
 */
function authenticateStudentWithTechnoPal($student_id, $password) {
    global $conn;
    
    // Verify credentials with TechnoPal API
    $verification = verifyTechnoPalCredentials($student_id, $password);
    
    if ($verification['status'] === true) {
        // API authentication successful
        $user_data = $verification['user_data'];
        
        // Extract user_code as the student ID from TechnoPal
        $technopal_user_code = $user_data['user_code'];
        
        // Format user data from API response
        $first_name = $user_data['first_name'] ?? '';
        $middle_name = $user_data['middle_name'] ?? '';
        $last_name = $user_data['last_name'] ?? '';
        $full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
        $email = $user_data['email_address'] ?? '';
        $program = $user_data['program_description'] ?? '';
        $year_level = $user_data['year_level'] ?? '';
        $section = $user_data['section'] ?? '';
        $address = $user_data['address'] ?? '';
        $gender = $user_data['gender'] ?? '';
        $contact_number = $user_data['cp_number'] ?? '';
        $rfid = $user_data['rfid'] ?? '';
        
        // Check if user already exists in our system using the TechnoPal user_code
        $query = "SELECT * FROM users WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $technopal_user_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // User exists, update information
            $user = mysqli_fetch_assoc($result);
            
            // Update user information with latest data from API
            $query = "UPDATE users SET 
                      full_name = ?, 
                      email = ?, 
                      department = ?,
                      year_level = ?,
                      section = ?,
                      address = ?,
                      gender = ?,
                      contact_number = ?,
                      rfid = ?,
                      last_login = NOW() 
                      WHERE id = ?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssisssssi", 
                $full_name, 
                $email, 
                $program,
                $year_level,
                $section,
                $address,
                $gender,
                $contact_number,
                $rfid,
                $user['id']
            );
            mysqli_stmt_execute($stmt);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $full_name;
            $_SESSION['student_id'] = $technopal_user_code;
            $_SESSION['program'] = $program;
            $_SESSION['year_level'] = $year_level;
            $_SESSION['section'] = $section;
            
            return true;
        } else {
            // User doesn't exist in our system, create new account
            $username = 'student_' . $technopal_user_code;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Get current device ID for first login
            $device_id = getDeviceIdentifier();
            
            $query = "INSERT INTO users (
                        username, 
                        email, 
                        password, 
                        full_name, 
                        student_id, 
                        department, 
                        year_level,
                        section,
                        address,
                        gender,
                        contact_number,
                        rfid,
                        role,
                        device_id,
                        first_device_date,
                        created_at
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', ?, NOW(), NOW())";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssssssssss", 
                $username, 
                $email, 
                $hashed_password, 
                $full_name, 
                $technopal_user_code, 
                $program,
                $year_level,
                $section,
                $address,
                $gender,
                $contact_number,
                $rfid,
                $device_id
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn);
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'student';
                $_SESSION['full_name'] = $full_name;
                $_SESSION['student_id'] = $technopal_user_code;
                $_SESSION['program'] = $program;
                $_SESSION['year_level'] = $year_level;
                $_SESSION['section'] = $section;
                
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Get a unique device identifier from client browser
 * 
 * @return string Device identifier (hashed)
 */
function getDeviceIdentifier() {
    // Use the device fingerprint from the client-side if available
    if (isset($_POST['device_fingerprint']) && !empty($_POST['device_fingerprint'])) {
        return $_POST['device_fingerprint'];
    }
    
    // Fallback: Get browser and device information
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Get additional headers that might help identify the device
    $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    
    // Combine factors to create a basic fingerprint
    $device_data = $user_agent . $accept_language;
    
    // Create a hash of the combined data
    $device_id = hash('sha256', $device_data);
    
    return $device_id;
}

/**
 * Verify if the current device matches any of the user's verified devices
 * 
 * FIXED: Duplicate entry error prevention
 * - Uses INSERT ... ON DUPLICATE KEY UPDATE instead of INSERT + error handling
 * - Eliminates race conditions between check and insert operations
 * - Always checks for existing device fingerprint first before any INSERT attempts
 * - Shows browser recommendations without attempting to save duplicates
 * 
 * @param int $user_id User ID
 * @param string $current_device_id Current device fingerprint
 * @return array Status and message
 */
function verifyDeviceMatch($user_id, $current_device_id) {
    global $conn;
    
    // Count verified devices for this user
    $query = "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_verified = 1";
    $stmt = mysqli_prepare($conn, $query);
    
    // Check if statement preparation failed - might indicate table doesn't exist
    if (!$stmt) {
        // Create user_devices table
        $create_device_table = "CREATE TABLE IF NOT EXISTS `user_devices` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `fingerprint` varchar(255) NOT NULL,
            `device_name` varchar(255) DEFAULT NULL,
            `is_verified` tinyint(1) DEFAULT 0,
            `verification_date` datetime DEFAULT NULL,
            `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
            `last_seen` datetime DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `fingerprint` (`fingerprint`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if (mysqli_query($conn, $create_device_table)) {
            // Try preparing the statement again now that we created the table
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                // Still failed, return error
                return ['status' => false, 'message' => 'Error checking device: ' . mysqli_error($conn)];
            }
        } else {
            // Failed to create table
            return ['status' => false, 'message' => 'Error setting up device verification: ' . mysqli_error($conn)];
        }
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    // FIRST: Always check if this exact fingerprint already exists for this user
    $query = "SELECT * FROM user_devices WHERE user_id = ? AND fingerprint = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $current_device_id);
    mysqli_stmt_execute($stmt);
    $existing_device = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($existing_device) > 0) {
        // Fingerprint already exists - handle based on verification status
        $device = mysqli_fetch_assoc($existing_device);
        
        // Update last_seen timestamp
        $query = "UPDATE user_devices SET last_seen = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $device['id']);
        mysqli_stmt_execute($stmt);
        
        if ($device['is_verified'] == 1) {
            // Device exists and is verified - allow access
            return ['status' => true, 'message' => 'Device verified'];
        } else {
            // Device exists but is not verified - deny access but show browser recommendation
            $_SESSION['show_browser_recommendation'] = true;
            return ['status' => false, 'message' => 'Access denied: Please use a verified device or contact the administrator to verify this device.'];
        }
    }
    
    // If user has no verified devices, create and verify the first one
    if ($row['count'] == 0) {
        // This is the first device - create and verify it using INSERT ... ON DUPLICATE KEY UPDATE
        $device_name = getDeviceNameFromUserAgent();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "INSERT INTO user_devices (user_id, fingerprint, device_name, is_verified, verification_date, last_seen, user_agent) 
                  VALUES (?, ?, ?, 1, NOW(), NOW(), ?)
                  ON DUPLICATE KEY UPDATE is_verified = 1, verification_date = NOW(), last_seen = NOW(), user_agent = VALUES(user_agent)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $current_device_id, $device_name, $user_agent);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['status' => true, 'message' => 'First device registered and verified'];
        } else {
            return ['status' => false, 'message' => 'Error registering device: ' . mysqli_error($conn)];
        }
    }
    
    // Check for similar devices (fuzzy matching for cross-browser)
    $similar_devices = findSimilarDevices($conn, $user_id, $current_device_id);
    
    if (!empty($similar_devices)) {
        // Found a similar device, consider it verified
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle race conditions gracefully
        $device_name = getDeviceNameFromUserAgent();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "INSERT INTO user_devices (user_id, fingerprint, device_name, is_verified, verification_date, last_seen, user_agent) 
                  VALUES (?, ?, ?, 1, NOW(), NOW(), ?)
                  ON DUPLICATE KEY UPDATE is_verified = 1, verification_date = NOW(), last_seen = NOW(), user_agent = VALUES(user_agent)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $current_device_id, $device_name, $user_agent);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['status' => true, 'message' => 'Similar device found and verified'];
        } else {
            return ['status' => false, 'message' => 'Error verifying similar device: ' . mysqli_error($conn)];
        }
    }
    
    // If no match found, this is a new unverified device
    // Instead of trying to INSERT and handling duplicates, use INSERT IGNORE or ON DUPLICATE KEY UPDATE
    $device_name = getDeviceNameFromUserAgent();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Use INSERT ... ON DUPLICATE KEY UPDATE to handle race conditions gracefully
    $query = "INSERT INTO user_devices (user_id, fingerprint, device_name, is_verified, last_seen, user_agent) 
              VALUES (?, ?, ?, 0, NOW(), ?)
              ON DUPLICATE KEY UPDATE last_seen = NOW(), user_agent = VALUES(user_agent)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $current_device_id, $device_name, $user_agent);
    
    if (mysqli_stmt_execute($stmt)) {
        // Successfully handled device record (created or updated)
        // Set flag to show browser recommendation
        $_SESSION['show_browser_recommendation'] = true;
        
        return ['status' => false, 'message' => 'Access denied: Please use a verified device or contact the administrator to verify this device.'];
    } else {
        // Even with ON DUPLICATE KEY UPDATE, something went wrong
        return ['status' => false, 'message' => 'Error handling device information: ' . mysqli_error($conn)];
    }
}

/**
 * Find similar devices based on partial fingerprint matches
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $fingerprint Current device fingerprint
 * @return array Array of similar devices
 */
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

/**
 * Calculate similarity between two fingerprints
 * 
 * @param string $fingerprint1 First fingerprint
 * @param string $fingerprint2 Second fingerprint
 * @return float Similarity score between 0 and 1
 */
function calculateFingerprintSimilarity($fingerprint1, $fingerprint2) {
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

/**
 * Get a device name from the user agent
 * 
 * @return string Friendly device name
 */
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