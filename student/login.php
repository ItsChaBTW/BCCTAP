<?php
/**
 * Student Login Page - Modern Redesign
 */
require_once '../config/config.php';
require_once '../config/auth.php';

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
        $device_id = getDeviceIdentifier();
        $api_auth_result = authenticateStudentWithTechnoPal($student_id, $password);
        
        if ($api_auth_result) {
            $device_result = verifyDeviceMatch($_SESSION['user_id'], $device_id);
            
            if ($device_result['status']) {
                $_SESSION['show_loading'] = true;
                $_SESSION['redirect_url'] = isset($_SESSION['redirect_after_login']) ? 
                    $_SESSION['redirect_after_login'] : 
                    BASE_URL . 'student/dashboard.php';
                unset($_SESSION['redirect_after_login']);
            } else {
                $error = $device_result['message'];
                session_destroy();
            }
        } else {
            // Fallback to local authentication
            $query = "SELECT * FROM users WHERE student_id = ? AND role = 'student'";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $student_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['student_id'] = $user['student_id'];
                    
                    $device_result = verifyDeviceMatch($_SESSION['user_id'], $device_id);
                    
                    if ($device_result['status']) {
                        $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "i", $user['id']);
                        mysqli_stmt_execute($stmt);
                        
                        $_SESSION['show_loading'] = true;
                        $_SESSION['redirect_url'] = isset($_SESSION['redirect_after_login']) ? 
                            $_SESSION['redirect_after_login'] : 
                            BASE_URL . 'student/dashboard.php';
                        unset($_SESSION['redirect_after_login']);
                    } else {
                        $error = $device_result['message'];
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #16a34a;
            --primary-light: #86efac;
            --primary-dark: #15803d;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.96);
            --error: #dc2626;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #dcfce7 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            line-height: 1.5;
        }

        .login-container {
            width: 100%;
            max-width: 26rem;
            background: var(--card-bg);
            border-radius: 1.25rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .login-header {
            background: var(--primary);
            color: white;
            padding: 1.75rem 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .login-header p {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .login-content {
            padding: 2rem;
        }

        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 auto 1.5rem;
            width: fit-content;
        }

        .qr-code {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 2px solid var(--primary-light);
            width: 5.5rem;
            height: 5.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 0.5rem;
        }

        .qr-code svg {
            width: 3rem;
            height: 3rem;
            color: var(--primary);
        }

        .scan-line {
            position: absolute;
            left: 0.5rem;
            right: 0.5rem;
            top: 0.5rem;
            height: 2px;
            background: linear-gradient(to right, var(--primary-light), var(--primary), var(--primary-light));
            opacity: 0.8;
            animation: scan 1.8s cubic-bezier(.4,0,.2,1) infinite alternate;
        }

        @keyframes scan {
            0% { top: 0.5rem; }
            100% { top: 4.5rem; }
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
            font-size: 0.875rem;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }

        .input-field {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9375rem;
            background-color: #f8fafc;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.2rem;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.1rem;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0;
            font-size: 0.875rem;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
        }

        .checkbox-container input {
            margin-right: 0.5rem;
            width: 1.1rem;
            height: 1.1rem;
            accent-color: var(--primary);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .footer {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .error-message {
            background: #fef2f2;
            border-left: 4px solid var(--error);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
        }

        .error-message p {
            color: var(--error);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .error-message p.text-xs {
            font-size: 0.75rem;
            color: #b91c1c;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .loading-overlay.show {
            opacity: 1;
            pointer-events: all;
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid rgba(22, 163, 74, 0.1);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .loading-subtext {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        @media (max-width: 480px) {
            .login-container {
                border-radius: 1rem;
            }
            
            .login-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Logging you in...</div>
        <div class="loading-subtext">Please wait while we prepare your dashboard</div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <h1>BCCTAP</h1>
            <p>Bago City College Time Attendance Platform</p>
        </div>

        <div class="login-content">
            <!-- QR Code Animation -->
            <!-- <div class="qr-container">
                <div class="qr-code">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="6" height="6" rx="1.5" stroke="currentColor"/>
                        <rect x="15" y="3" width="6" height="6" rx="1.5" stroke="currentColor"/>
                        <rect x="3" y="15" width="6" height="6" rx="1.5" stroke="currentColor"/>
                        <rect x="10" y="10" width="4" height="4" rx="1" stroke="currentColor"/>
                        <rect x="15" y="15" width="2" height="2" rx="0.5" stroke="currentColor"/>
                    </svg>
                    <div class="scan-line"></div>
                </div>
                <p class="text-sm text-green-600">Scan QR to authenticate</p>
            </div> -->

            <h2 class="text-xl font-bold text-center mb-6">Student Login</h2>

            <?php if (isset($error) && !empty($error)): ?>
                <div class="error-message" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                    <?php if (strpos($error, 'Access denied') !== false): ?>
                        <p class="text-xs">
                            For security reasons, you can only access your account from your registered device. 
                            If you need to use a new device, please contact the system administrator.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="loginForm">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="student_id" name="student_id" class="input-field" 
                               placeholder="e.g., 2021116435" required
                               value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="input-field" 
                               placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="options">
                    <div class="checkbox-container">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Remember me</label>
                    </div>
                    <a href="../forgot-password.php" class="text-green-600 hover:text-green-800">Forgot password?</a>
                </div>

                <button type="submit" class="login-btn">Sign In</button>
                <input type="hidden" name="device_fingerprint" id="device_fingerprint" value="">
            </form>

            <div class="footer">
                <div class="flex items-center justify-center mb-3">
                    <img src="../assets/images/technopal-logo.png" alt="TechnoPal" class="h-5 mr-2" onerror="this.style.display='none'">
                    <p class="text-sm text-green-700">
                        Powered by <span class="font-medium">TechnoPal</span> Authentication
                    </p>
                </div>
                <p class="text-xs text-green-600 mb-4">
                    Use the same credentials as your TechnoPal account
                </p>
                <p class="text-sm">
                    Not a student? <a href="../staff_login.php" class="text-green-600 font-medium">Staff Login</a>
                </p>
                <!-- <p class="text-sm mt-2">
                    <a href="../index.php" class="text-green-600"><i class="fas fa-arrow-left mr-1"></i> Back to Home</a>
                </p> -->
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Form submission with device fingerprint
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            
            loginForm.addEventListener('submit', async function(event) {
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