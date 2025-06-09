<?php
/**
 * Staff Login Page (For Admins and Teachers)
 */
require_once 'config/config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Check if username exists (for either admin or teacher)
        $query = "SELECT id, username, password, full_name, role FROM users WHERE username = ? AND (role = 'admin' OR role = 'teacher')";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $row['password'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];
                
                // Set a flag to show the loading animation
                $_SESSION['show_loading'] = true;
                
                // Redirect based on role but store URL in session instead of immediate redirect
                if ($row['role'] === 'admin') {
                    $_SESSION['redirect_url'] = BASE_URL . 'admin/index.php';
                } else {
                    $_SESSION['redirect_url'] = BASE_URL . 'teacher/index.php';
                }
                
                // Don't redirect here, we'll handle it with JavaScript
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Invalid username";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - BCCTAP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/colors.css" rel="stylesheet">
    <link href="assets/css/loading-animation.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .login-container {
            min-height: calc(100vh - 120px);
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .login-header {
            background: linear-gradient(135deg, #EF6161 0%, #f3af3d 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
        }
        
        .login-form {
            padding: 2.5rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #EF6161;
            box-shadow: 0 0 0 3px rgba(239, 97, 97, 0.2);
            background: white;
        }
        
        .input-group label {
            position: absolute;
            top: 0.75rem;
            left: 1rem;
            color: #718096;
            pointer-events: none;
            transition: all 0.3s;
        }
        
        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label {
            top: -0.7rem;
            left: 0.75rem;
            font-size: 0.75rem;
            background: white;
            padding: 0 0.25rem;
            color: #EF6161;
        }
        
        .login-btn {
            background: linear-gradient(135deg, #EF6161 0%, #f3af3d 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            border: none;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 97, 97, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .alert-danger {
            background-color: #FEECEC;
            color: #EF6161;
            border-left: 4px solid #EF6161;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .alert-icon {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Logging you in...</div>
        <div class="loading-subtext">Please wait while we prepare your dashboard</div>
    </div>
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex login-container items-center justify-center px-4 py-10">
        <div class="w-full max-w-lg">
            <div class="login-card">
                <div class="login-header">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 005 10a1 1 0 111.998 0C7.001 12.762 9.016 15 11.5 15s4.498-2.238 4.5-5A1 1 0 0117 10c0 .34-.035.675-.102 1a5 5 0 00-5-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold mb-1">Staff Login</h1>
                    <p class="text-white text-opacity-90">Access portal for teachers and administrators</p>
                </div>
                
                <div class="login-form">
                    <?php if (isset($error)): ?>
                        <div class="alert-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 alert-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="staff_login.php">
                        <div class="input-group">
                            <input type="text" id="username" name="username" placeholder=" " value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            <label for="username">Username</label>
                        </div>
                        
                        <div class="input-group">
                            <input type="password" id="password" name="password" placeholder=" " required>
                            <label for="password">Password</label>
                        </div>
                        
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" class="h-4 w-4 text-primary-color focus:ring-primary-color border-gray-300 rounded">
                                <label for="remember" class="ml-2 block text-sm text-gray-600">Remember me</label>
                            </div>
                            
                            <div>
                                <a href="#" class="text-sm text-primary-color hover:text-accent-color">Forgot password?</a>
                            </div>
                        </div>
                        
                        <button type="submit" class="login-btn">
                            Sign In
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <a href="index.php" class="text-gray-600 hover:text-primary-color text-sm flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-600">
                    Are you a student? <a href="student/login.php" class="text-primary-color hover:text-accent-color font-medium">Student Login</a>
                </p>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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