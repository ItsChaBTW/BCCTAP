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
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
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
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
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
            color: #22c55e;
        }
        
        .login-btn {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
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
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .alert-danger {
            background-color: #ECFDF5;
            color: #22c55e;
            border-left: 4px solid #22c55e;
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
<body class="    min-h-screen flex flex-col">
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Logging you in...</div>
        <div class="loading-subtext">Please wait while we prepare your dashboard</div>
    </div>
    
    <?php include 'includes/header.php'; ?>
    
    <div class="flex login-container items-center justify-center px-4 py-10 flex-1">
        <div class="w-full max-w-3xl">
            <div class="backdrop-blur-lg bg-white/70 border border-slate-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
                <!-- Left: Header -->
                <div class="md:w-1/2 flex flex-col justify-center items-center bg-gradient-to-r from-green-500 to-green-700 p-8 text-center text-white">
                    <div class="mb-2 flex justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-200 drop-shadow-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <h1 class="text-xl font-bold mb-1">Staff Login</h1>
                    <p class="text-green-100 text-opacity-90">Access portal for teachers and administrators</p>
                </div>
                <!-- Right: Form -->
                <div class="md:w-1/2 p-8 flex flex-col justify-center">
                    <?php if (isset($error)): ?>
                        <div class="bg-green-100 text-green-800 border-l-4 border-green-400 rounded p-3 mb-6 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12A9 9 0 113 12a9 9 0 0118 0z" /></svg>
                            <span><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="staff_login.php" class="space-y-6">
                        <div class="relative">
                            <input type="text" id="username" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required class="w-full px-4 py-3 border border-green-300 rounded-lg bg-green-50 focus:ring-2 focus:ring-green-400 focus:border-green-400 outline-none transition placeholder-green-400 text-base">
                        </div>
                        <div class="relative">
                            <input type="password" id="password" name="password" placeholder="Password" required class="w-full px-4 py-3 border border-green-300 rounded-lg bg-green-50 focus:ring-2 focus:ring-green-400 focus:border-green-400 outline-none transition placeholder-green-400 text-base">
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="flex items-center text-sm text-green-600">
                                <input type="checkbox" id="remember" class="h-4 w-4 text-green-600 focus:ring-green-500 border-green-300 rounded mr-2">
                                Remember me
                            </label>
                            <a href="#" class="text-sm text-green-600 hover:text-green-800 transition">Forgot password?</a>
                        </div>
                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-green-500 to-green-700 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300 text-lg tracking-wide">Sign In</button>
                    </form>
                    <div class="mt-6 text-center">
                        <a href="index.php" class="text-green-500 hover:text-green-700 text-sm flex items-center justify-center transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <p class="text-sm text-green-400">
                    Are you a student? <a href="student/login.php" class="text-green-600 hover:text-green-800 font-medium transition">Student Login</a>
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