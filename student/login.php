<?php
/**
 * Student Login Page
 */
require_once '../config/config.php';
require_once '../config/auth.php'; // Direct include of auth.php

// Check if already logged in
if (isLoggedIn() && $_SESSION['role'] === 'student') {
    redirect(BASE_URL . 'student/dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $error = '';
    
    if (empty($student_id) || empty($password)) {
        $error = "Student ID and password are required";
    } else {
        // Get device identifier
        $device_id = getDeviceIdentifier();
        
        // ALWAYS try TechnoPal API authentication first
        // This is the primary authentication method for students
        $api_auth_result = authenticateStudentWithTechnoPal($student_id, $password);
        
        if ($api_auth_result) {
            // API authentication successful
            
            // Verify device match
            $device_result = verifyDeviceMatch($_SESSION['user_id'], $device_id);
            
            // Check if device verification was successful
            if ($device_result['status']) {
                // Set a flag to show the loading animation
                $_SESSION['show_loading'] = true;
                
                // Check if redirect URL is set in session
                if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                    $redirect_url = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    $_SESSION['redirect_url'] = $redirect_url;
                } else {
                    $_SESSION['redirect_url'] = BASE_URL . 'student/dashboard.php';
                }
                
                // Don't redirect here, we'll handle it with JavaScript
            } else {
                // Device verification failed - show error and prevent login
                $error = $device_result['message'];
                
                // Destroy the session since we're blocking the login
                session_destroy();
            }
        } else {
            // API authentication failed, fall back to local database
            // Only as a backup if API is unavailable
            $query = "SELECT * FROM users WHERE student_id = ? AND role = 'student'";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $student_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);
                
                if (password_verify($password, $user['password'])) {
                    // Local authentication successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['student_id'] = $user['student_id'];
                    
                    // Verify device match
                    $device_result = verifyDeviceMatch($_SESSION['user_id'], $device_id);
                    
                    // Check if device verification was successful
                    if ($device_result['status']) {
                        // Update last login time
                        $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "i", $user['id']);
                        mysqli_stmt_execute($stmt);
                        
                        // Set a flag to show the loading animation
                        $_SESSION['show_loading'] = true;
                        
                        // Check if redirect URL is set in session
                        if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                            $redirect_url = $_SESSION['redirect_after_login'];
                            unset($_SESSION['redirect_after_login']);
                            $_SESSION['redirect_url'] = $redirect_url;
                        } else {
                            $_SESSION['redirect_url'] = BASE_URL . 'student/dashboard.php';
                        }
                        
                        // Don't redirect here, we'll handle it with JavaScript
                    } else {
                        // Device verification failed - show error and prevent login
                        $error = $device_result['message'];
                        
                        // Destroy the session since we're blocking the login
                        session_destroy();
                    }
                } else {
                    $error = "Invalid student ID or password";
                }
            } else {
                $error = "Invalid student ID or password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <link href="../assets/css/loading-animation.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/device-fingerprint.js"></script>
    <script>
        // Set to true to automatically verify the device on page load
        const verifyOnLoad = true;
    </script>
</head>
<body class="bg-gray-50">
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Logging you in...</div>
        <div class="loading-subtext">Please wait while we prepare your dashboard</div>
    </div>

    <div class="min-h-screen flex flex-col justify-center items-center px-4">
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-indigo-700 mb-2">BCCTAP</h1>
            <p class="text-gray-600">Bago City College Time Attendance Platform</p>
        </div>
        
        <div class="w-full max-w-md">
            <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">
                <div class="flex justify-center mb-6">
                    <div class="p-2 bg-indigo-100 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
                
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-4">Student Login</h2>
                <p class="text-center text-gray-600 mb-6">Sign in with your TechnoPal credentials</p>
                
                <?php if (isset($error) && !empty($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6" role="alert">
                        <p class="text-sm text-red-700"><?php echo $error; ?></p>
                        <?php if (strpos($error, 'Access denied') !== false): ?>
                            <p class="text-xs text-red-700 mt-2">
                                For security reasons, you can only access your account from your registered device. 
                                If you need to use a new device, please contact the system administrator.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="loginForm">
                    <div class="mb-4">
                        <label for="student_id" class="block text-gray-700 text-sm font-medium mb-2">Student ID</label>
                        <input type="text" id="student_id" name="student_id" pattern="[0-9]*" inputmode="numeric" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" required>
                        <p class="text-xs text-gray-500 mt-1">Enter your numeric student ID (e.g., 2021116435)</p>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                        <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                    
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700">Remember me</label>
                        </div>
                        
                        <div class="text-sm">
                            <a href="../forgot-password.php" class="text-indigo-600 hover:text-indigo-500">Forgot password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Sign In
                    </button>
                    
                    <!-- Hidden input to store device fingerprint -->
                    <input type="hidden" name="device_fingerprint" id="device_fingerprint" value="">
                </form>
                
                <div class="mt-6 pt-5 border-t border-gray-200">
                    <div class="flex items-center justify-center">
                        <img src="../assets/images/technopal-logo.png" alt="TechnoPal" class="h-6 mr-2" onerror="this.style.display='none'">
                        <p class="text-sm text-gray-600">
                            Powered by <span class="font-medium">TechnoPal</span> Authentication
                        </p>
                    </div>
                    <p class="text-xs text-gray-500 text-center mt-2">
                        Use the same credentials as your TechnoPal account
                    </p>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Not a student? 
                    <a href="../staff_login.php" class="text-indigo-600 hover:text-indigo-500 font-medium">Staff Login</a>
                </p>
                <p class="text-sm text-gray-600 mt-4">
                    <a href="../index.php" class="text-indigo-600 hover:text-indigo-500">← Back to Home</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Execute when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Get and generate device fingerprint using the new library
            const loginForm = document.getElementById('loginForm');
            
            // Set up form submission handler
            loginForm.addEventListener('submit', async function(event) {
                // Only prevent default if fingerprinting hasn't been done yet
                if (!document.getElementById('device_fingerprint').value) {
                    event.preventDefault();
                    
                    try {
                        // Generate the device fingerprint
                        const fingerprint = await DeviceFingerprint.generate();
                        
                        // Set the fingerprint in the form
                        document.getElementById('device_fingerprint').value = fingerprint;
                        
                        // Now submit the form
                        loginForm.submit();
                    } catch (error) {
                        console.error('Error generating device fingerprint:', error);
                        // Submit form anyway so login can proceed
                        loginForm.submit();
                    }
                }
            });
            
            // Generate fingerprint in background as soon as possible
            DeviceFingerprint.generate().then(fingerprint => {
                document.getElementById('device_fingerprint').value = fingerprint;
            }).catch(error => {
                console.error('Error pre-generating device fingerprint:', error);
            });
            
            // Check if we should show the loading animation and redirect
            <?php if (isset($_SESSION['show_loading']) && $_SESSION['show_loading']): ?>
                // Show loading animation
                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.classList.add('show');
                
                // Wait for 5 seconds and then redirect
                setTimeout(function() {
                    window.location.href = '<?php echo $_SESSION['redirect_url']; ?>';
                }, 5000);
                
                // Clear the session flags since we've handled them
                <?php 
                    unset($_SESSION['show_loading']);
                    unset($_SESSION['redirect_url']);
                ?>
            <?php endif; ?>
        });
    </script>
</body>
</html> 