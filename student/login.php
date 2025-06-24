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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/device-fingerprint.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #bbf7d0 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .login-glass {
            background: rgba(255,255,255,0.7);
            box-shadow: 0 8px 32px 0 rgba(34,197,94,0.15);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 2rem;
            border: 1px solid rgba(34,197,94,0.15);
        }
        .login-floating {
            animation: floaty 4s ease-in-out infinite;
        }
        @keyframes floaty {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        .green-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.25;
            z-index: 0;
        }
    </style>
</head>
<body class="relative min-h-screen flex flex-col justify-center items-center px-4 overflow-x-hidden">
    <!-- Decorative background -->
    <div class="absolute inset-0 -z-10">
        <div class="green-blob bg-green-400 w-[32rem] h-[32rem] top-[-8rem] left-[-8rem] absolute"></div>
        <div class="green-blob bg-green-300 w-[28rem] h-[28rem] bottom-[-6rem] right-[-6rem] absolute"></div>
        <div class="absolute inset-0 pointer-events-none">
            <svg width="100%" height="100%" viewBox="0 0 1440 900" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                <circle cx="1200" cy="200" r="120" fill="#bbf7d0" fill-opacity="0.18" />
                <rect x="-80" y="700" width="400" height="200" rx="100" fill="#22c55e" fill-opacity="0.08" />
                <circle cx="300" cy="150" r="80" fill="#22c55e" fill-opacity="0.10" />
            </svg>
        </div>
        <div class="absolute inset-0 bg-gradient-to-br from-white/80 via-green-50/60 to-green-100/40"></div>
    </div>
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Logging you in...</div>
        <div class="loading-subtext">Please wait while we prepare your dashboard</div>
    </div>
    <div class="min-h-screen flex flex-col justify-center items-center px-4 z-10">
        <div class="w-full max-w-md login-floating">
            <div class="login-glass p-10 md:p-12 shadow-2xl border border-green-100 relative">
                <div class="flex flex-col items-center mb-8">
                    <!-- Animated QR code stacked above avatar -->
                    <div class="flex flex-col items-center gap-2 mb-4">
                        <div class="relative flex items-center justify-center mb-1">
                            <div class="bg-white rounded-lg shadow-lg border-2 border-green-300 relative w-14 h-14 flex items-center justify-center animate-pulse">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="2"/>
                                    <rect x="15" y="3" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="2"/>
                                    <rect x="3" y="15" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="2"/>
                                    <rect x="10" y="10" width="4" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
                                    <rect x="15" y="15" width="2" height="2" rx="0.5" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <div class="absolute left-1 right-1 top-1 h-1 bg-gradient-to-r from-green-400 via-green-500 to-green-400 opacity-70 animate-scan"></div>
                            </div>
                        </div>
                        <!-- <div class="p-2 bg-green-100 rounded-full shadow-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div> -->
                        <style>
                        @keyframes scan {
                            0% { top: 0.25rem; }
                            100% { top: 2.75rem; }
                        }
                        .animate-scan {
                            animation: scan 1.6s cubic-bezier(.4,0,.2,1) infinite alternate;
                        }
                        </style>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-green-700 mb-1 tracking-tight drop-shadow-lg text-center">BCCTAP</h1>
                    <p class="text-green-900 text-opacity-80 mb-1 text-center text-sm">Bago City College Time Attendance Platform</p>
                    <h2 class="text-lg font-bold text-green-800 mb-1 mt-2 text-center">Student Login</h2>
                    <p class="text-green-700 text-opacity-80 mb-2 text-center text-sm">Sign in with your TechnoPal credentials</p>
                </div>
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
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="loginForm" class="space-y-6">
                    <div>
                        <label for="student_id" class="block text-green-800 text-sm font-medium mb-2">Student ID</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-green-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </span>
                            <input type="text" id="student_id" name="student_id" pattern="[0-9]*" inputmode="numeric" class="w-full pl-10 pr-4 py-3 border border-green-200 rounded-xl bg-green-50 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition placeholder-green-400 text-base shadow-sm" value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" required autocomplete="username">
                        </div>
                        <p class="text-xs text-green-600 mt-1">Enter your numeric student ID (e.g., 2021116435)</p>
                    </div>
                    <div>
                        <label for="password" class="block text-green-800 text-sm font-medium mb-2">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-green-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0-1.657 1.343-3 3-3s3 1.343 3 3-1.343 3-3 3-3-1.343-3-3zm0 0V7m0 4v8m-7-7h14"/></svg>
                            </span>
                            <input type="password" id="password" name="password" class="w-full pl-10 pr-4 py-3 border border-green-200 rounded-xl bg-green-50 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition placeholder-green-400 text-base shadow-sm" required autocomplete="current-password">
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-sm text-green-700">
                            <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-green-300 rounded mr-2">
                            Remember me
                        </label>
                        <a href="../forgot-password.php" class="text-sm text-green-600 hover:text-green-800 transition font-medium">Forgot password?</a>
                    </div>
                    <button type="submit" class="w-full py-3 bg-gradient-to-r from-green-500 to-green-700 text-white font-bold rounded-2xl shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 text-lg tracking-wide focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2">Sign In</button>
                    <input type="hidden" name="device_fingerprint" id="device_fingerprint" value="">
                </form>
                <div class="mt-8 pt-5 border-t border-green-100">
                    <div class="flex items-center justify-center">
                        <img src="../assets/images/technopal-logo.png" alt="TechnoPal" class="h-6 mr-2" onerror="this.style.display='none'">
                        <p class="text-sm text-green-700">
                            Powered by <span class="font-medium">TechnoPal</span> Authentication
                        </p>
                    </div>
                    <p class="text-xs text-green-600 text-center mt-2">
                        Use the same credentials as your TechnoPal account
                    </p>
                </div>
                <div class="mt-6 text-center">
                    <p class="text-sm text-green-700">
                        Not a student? 
                        <a href="../staff_login.php" class="text-green-600 hover:text-green-800 font-medium">Staff Login</a>
                    </p>
                    <p class="text-sm text-green-700 mt-4">
                        <a href="../index.php" class="text-green-600 hover:text-green-800">← Back to Home</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Check if we should show browser recommendation
        <?php if (isset($_SESSION['show_browser_recommendation']) && $_SESSION['show_browser_recommendation']): ?>
            // Show browser recommendation every login using ChromeDetector
            setTimeout(() => {
                ChromeDetector.showLoginRecommendation({
                    title: 'Welcome to BCCTAP!',
                    message: 'For the best login and QR scanning experience, we recommend using Google Chrome.'
                }).then((result) => {
                    // Optional: Track user response for analytics
                    if (result && result.isConfirmed) {
                        console.log('User chose to download Chrome after login recommendation');
                    }
                });
            }, 1500);
            <?php unset($_SESSION['show_browser_recommendation']); ?>
        <?php endif; ?>
        
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