<?php
/**
 * Admin Layout Template
 * Used as a base template for all admin pages
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - BCCTAP Admin' : 'BCCTAP Admin'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/styles.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/colors.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/admin-style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar" id="sidebar">
        <div class="sidebar-logo">
            <h1>BCCTAP</h1>
            <p>Admin Portal</p>
        </div>
        <nav class="nav-menu">
            <a href="<?php echo BASE_URL; ?>admin/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/index.php') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>admin/events/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/events/') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                </svg>
                Events
            </a>
            <a href="<?php echo BASE_URL; ?>admin/users/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/users/index.php') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                </svg>
                Users
            </a>
            <a href="<?php echo BASE_URL; ?>admin/users/devices.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/users/devices.php') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                Student Devices
            </a>
            <a href="<?php echo BASE_URL; ?>admin/qrcodes/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/qrcodes/') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                </svg>
                QR Codes
            </a>
            <a href="<?php echo BASE_URL; ?>admin/reports/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/reports/') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd" />
                </svg>
                Reports
            </a>
            <a href="<?php echo BASE_URL; ?>admin/settings/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/settings/') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
                Settings
            </a>
        </nav>
        
        <!-- User Account Section -->
        <div class="user-profile">
            <?php if (isset($_SESSION['full_name']) && isset($_SESSION['role'])): ?>
                <div class="flex items-center">
                    <div class="profile-avatar">
                        <span><?php echo substr($_SESSION['full_name'], 0, 1); ?></span>
                    </div>
                    <div class="ml-3">
                        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div class="text-xs text-gray-400"><?php echo ucfirst($_SESSION['role']); ?></div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>logout.php" class="text-sm text-gray-400 hover:text-white flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm7 4a1 1 0 10-2 0v4a1 1 0 102 0V7z" clip-rule="evenodd" />
                        </svg>
                        Logout
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Top Header Bar -->
        <header class="admin-header">
            <div class="flex items-center">
                <button id="sidebar-toggle" class="md:hidden mr-4 text-gray-600 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-xl font-medium text-gray-800"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
            </div>
            <div>
                <?php if (isset($page_actions)): echo $page_actions; endif; ?>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="p-6">
            <?php if (isset($page_content)): echo $page_content; endif; ?>
        </main>
        
        <!-- Footer -->
        <footer class="admin-footer">
            &copy; <?php echo date('Y'); ?> BCCTAP - Bago City College Time Attendance Platform
        </footer>
    </div>
    
    <!-- Scripts -->
    <script>
        // Sidebar toggle functionality for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 768 && 
                    sidebar && 
                    sidebar.classList.contains('show') && 
                    !sidebar.contains(e.target) && 
                    !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
        });
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <?php if (isset($extra_js)): echo $extra_js; endif; ?>
</body>
</html> 