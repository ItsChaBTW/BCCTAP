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
            --card-bg: rgba(255, 255, 255, 0.75); /* glassy */
            --card-blur: blur(16px);
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
            border-radius: 1.5rem;
            box-shadow: 0 12px 32px -8px rgba(22,163,74,0.18), 0 1.5px 8px 0 rgba(0,0,0,0.04);
            overflow: hidden;
            border: 1.5px solid rgba(22,163,74,0.10);
            backdrop-filter: var(--card-blur);
            -webkit-backdrop-filter: var(--card-blur);
            transition: box-shadow 0.2s;
        }

        .login-header {
            background: var(--primary);
            color: white;
            padding: 2rem 2rem 1.5rem 2rem;
            text-align: center;
            border-bottom: 1.5px solid rgba(255,255,255,0.12);
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            letter-spacing: 0.01em;
        }

        .login-header p {
            font-size: 1rem;
            opacity: 0.92;
            font-weight: 500;
        }

        .login-content {
            padding: 2.25rem 2rem 2rem 2rem;
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
            margin-bottom: 1.5rem;
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
            margin-bottom: 0.2rem;
        }

        .input-field {
            width: 100%;
            padding: 1rem 1.1rem 1rem 3.1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 1rem;
            font-size: 1rem;
            background-color: rgba(248,250,252,0.85);
            transition: border 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px 0 rgba(22,163,74,0.04);
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.13);
            background: #fff;
        }

        .input-icon {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.25rem;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.15rem;
            transition: color 0.18s;
        }

        .password-toggle:hover {
            color: var(--primary-dark);
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.7rem 0 1.2rem 0;
            font-size: 0.95rem;
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
            padding: 1.1rem;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 1rem;
            font-weight: 700;
            font-size: 1.08rem;
            cursor: pointer;
            box-shadow: 0 4px 16px 0 rgba(22,163,74,0.10);
            transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
        }

        .login-btn:hover {
            background: linear-gradient(90deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 24px 0 rgba(22,163,74,0.16);
        }

        .footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
            font-size: 0.95rem;
            color: var(--text-light);
        }

        .footer a {
            color: var(--primary);
            text-decoration: underline;
            transition: color 0.18s;
        }

        .footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline wavy;
        }

        .error-message {
            background: #fef2f2;
            border-left: 4px solid var(--error);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.7rem;
            box-shadow: 0 2px 8px 0 rgba(220,38,38,0.06);
        }

        .error-message p {
            color: var(--error);
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .error-message p.text-xs {
            font-size: 0.8rem;
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
                padding: 1.2rem;
            }
            
            .input-field {
                font-size: 0.97rem;
                padding: 0.9rem 0.9rem 0.9rem 2.7rem;
            }
            
            .input-icon {
                font-size: 1.1rem;
                left: 0.9rem;
            }
            
            .password-toggle {
                font-size: 1rem;
                right: 0.9rem;
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