<?php
/**
 * Admin Sidebar Navigation
 * Used across all admin pages
 */

// Get the current page URL to highlight active menu item
$current_page = $_SERVER['PHP_SELF'];
?>

<div class="bg-sidebar-gradient text-white h-screen w-64 fixed left-0 top-0 overflow-y-auto shadow-lg flex flex-col">
    <!-- Logo Section -->
    <div class="p-4 border-b border-gray-700 bg-gradient-to-r from-[#EF6161] to-[#f3af3d]">
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="flex items-center space-x-2">
            <div class="text-white font-bold text-2xl">BCCTAP</div>
        </a>
        <div class="text-sm text-white mt-1">Admin Portal</div>
    </div>
    
    <!-- Navigation Items -->
    <nav class="flex-grow py-4 bg-gray-900">
        <ul class="space-y-1">
            <li>
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="flex items-center px-4 py-3 <?php echo strpos($current_page, 'admin/index.php') !== false ? 'bg-gray-800 border-l-4 border-[#EF6161]' : 'hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/events/index.php" class="flex items-center px-4 py-3 <?php echo strpos($current_page, 'admin/events/') !== false ? 'bg-gray-800 border-l-4 border-[#EF6161]' : 'hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    <span>Events</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/users/index.php" class="flex items-center px-4 py-3 <?php echo strpos($current_page, 'admin/users/index.php') !== false ? 'bg-gray-800 border-l-4 border-[#EF6161]' : 'hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                    </svg>
                    <span>Users</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/users/devices.php" class="flex items-center px-4 py-3 <?php echo strpos($current_page, 'admin/users/devices.php') !== false ? 'bg-gray-800 border-l-4 border-[#EF6161]' : 'hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <span>Student Devices</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/qrcodes/index.php" class="flex items-center px-4 py-3 <?php echo strpos($current_page, 'admin/qrcodes/') !== false ? 'bg-gray-800 border-l-4 border-[#EF6161]' : 'hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                    </svg>
                    <span>QR Codes</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/reports/index.php" class="flex items-center px-4 py-3 <?php echo strpos($current_page, 'admin/reports/') !== false ? 'bg-gray-800 border-l-4 border-[#EF6161]' : 'hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd" />
                    </svg>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/settings/index.php" class="flex items-center px-4 py-3 <?php echo strpos($current_page, 'admin/settings/') !== false ? 'bg-gray-800 border-l-4 border-[#EF6161]' : 'hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                    <span>Settings</span>
                </a>
            </li>
            
        </ul>
    </nav>
    
    <!-- User Account Section -->
    <div class="p-4 border-t border-gray-700 bg-gray-900">
        <?php if (isset($_SESSION['full_name']) && isset($_SESSION['role'])): ?>
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#EF6161] to-[#f3af3d] flex items-center justify-center">
                    <span class="text-white font-bold"><?php echo substr($_SESSION['full_name'], 0, 1); ?></span>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <div class="text-xs text-gray-400"><?php echo ucfirst($_SESSION['role']); ?></div>
                </div>
            </div>
            <div class="mt-3">
                <a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center text-sm text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm7 4a1 1 0 10-2 0v4a1 1 0 102 0V7z" clip-rule="evenodd" />
                    </svg>
                    Logout
                </a>
            </div>
        <?php endif; ?>
    </div>
</div> 