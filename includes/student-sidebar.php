<?php
/**
 * Student Sidebar Navigation Component
 * This will be displayed on wider screens
 */
?>
<div class="student-sidebar hidden lg:flex flex-col w-64 text-white min-h-screen shadow-lg fixed left-0 top-0 z-30">
    <!-- Logo section -->
    <div class="p-4 border-b border-white border-opacity-20 flex items-center space-x-3">
        <div class="bg-white rounded-full p-2 shadow-md">
            <span class="text-green-500 font-bold text-xl">BCC</span>
        </div>
        <div>
            <h1 class="text-xl font-bold text-white">BCCTAP</h1>
            <p class="text-xs text-white text-opacity-70">Bago City College</p>
        </div>
    </div>
    
    <!-- User info section -->
    <div class="p-4 border-b border-white border-opacity-20">
        <div class="flex items-center space-x-3 mb-3">
            <div class="bg-white bg-opacity-20 p-2 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <p class="font-medium text-white"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p class="text-xs text-white text-opacity-70">Student ID: <?php echo htmlspecialchars($_SESSION['student_id']); ?></p>
            </div>
        </div>
        
        <?php if (isset($_SESSION['program'])): ?>
            <div class="bg-slate-700 rounded p-2 text-xs">
                <p class="text-white text-opacity-70">Program</p>
                <p class="font-medium text-white"><?php echo htmlspecialchars($_SESSION['program']); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Navigation -->
    <div class="mt-2 px-2 flex-1">
        <p class="text-xs text-white text-opacity-70 px-3 uppercase tracking-wider mb-2">MENU</p>
        <nav>
            <ul class="space-y-1">
                <li>
                    <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="flex items-center py-2 px-3 rounded-md transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-green-500 text-white' : 'hover:bg-green-600 hover:text-white'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
               
                <li>
                    <a href="<?php echo BASE_URL; ?>student/attendance.php" class="flex items-center py-2 px-3 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'bg-black bg-opacity-20' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                        </svg>
                        <span>My Attendance</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>student/events.php" class="flex items-center py-2 px-3 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'bg-black bg-opacity-20' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        <span>Events</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <p class="text-xs text-white text-opacity-70 px-3 uppercase tracking-wider mb-2 mt-6">ACCOUNT</p>
        <nav>
            <ul class="space-y-1">
                <li>
                    <a href="<?php echo BASE_URL; ?>student/profile.php" class="flex items-center py-2 px-3 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-black bg-opacity-20' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                        </svg>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center py-2 px-3 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm1 2h10v10H4V5zm4.707 5.707a1 1 0 00-1.414-1.414l-2 2a1 1 0 000 1.414l2 2a1 1 0 001.414-1.414L7.414 11H12a1 1 0 100-2H7.414l1.293-1.293z" clip-rule="evenodd" />
                        </svg>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <div class="p-4 border-t border-white border-opacity-20">
        <p class="text-xs text-white text-opacity-70">&copy; <?php echo date('Y'); ?> BCCTAP</p>
        <p class="text-xs text-white text-opacity-70">Bago City College</p>
    </div>
</div> 