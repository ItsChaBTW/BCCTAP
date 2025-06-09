<header class="bg-indigo-800 text-white shadow-lg">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <a href="<?php echo (isLoggedIn() && isStudent()) ? BASE_URL . 'student/dashboard.php' : BASE_URL; ?>" class="flex items-center">
                <div class="bg-white rounded-full p-2 mr-3 shadow-md">
                    <span class="text-indigo-800 font-bold text-xl">BCC</span>
                </div>
                <div>
                    <h1 class="text-xl font-bold">BCCTAP</h1>
                    <p class="text-xs text-indigo-200">Bago City College Time Attendance Platform</p>
                </div>
            </a>
            
            <nav class="hidden md:block">
                <ul class="flex space-x-1">
                    <li><a href="<?php echo (isLoggedIn() && isStudent()) ? BASE_URL . 'student/dashboard.php' : BASE_URL; ?>" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        Home
                    </a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/index.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                                </svg>
                                Dashboard
                            </a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/events/index.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                Events
                            </a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/users/index.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                                Users
                            </a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/qrcodes/index.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                                    <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
                                </svg>
                                QR Codes
                            </a></li>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                            <li><a href="<?php echo BASE_URL; ?>teacher/index.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                                </svg>
                                Dashboard
                            </a></li>
                            <li><a href="<?php echo BASE_URL; ?>teacher/attendance.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                Attendance
                            </a></li>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                            <li><a href="<?php echo BASE_URL; ?>student/dashboard.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                                </svg>
                                Dashboard
                            </a></li>
                            <li><a href="<?php echo BASE_URL; ?>student/scan/index.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                                    <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
                                </svg>
                                Scan QR
                            </a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>logout.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm1 2h10v10H4V5zm4.707 5.707a1 1 0 00-1.414-1.414l-2 2a1 1 0 000 1.414l2 2a1 1 0 001.414-1.414L7.414 11H12a1 1 0 100-2H7.414l1.293-1.293z" clip-rule="evenodd" />
                            </svg>
                            Logout
                        </a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>student/login.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                            Student Login
                        </a></li>
                        <li><a href="<?php echo BASE_URL; ?>staff_login.php" class="px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                            Staff Login
                        </a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobile-menu-btn" class="text-white focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden pt-4 pb-3">
            <nav>
                <ul class="space-y-2">
                    <li><a href="<?php echo (isLoggedIn() && isStudent()) ? BASE_URL . 'student/dashboard.php' : BASE_URL; ?>" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Home</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/index.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/events/index.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Events</a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/users/index.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Users</a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/qrcodes/index.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">QR Codes</a></li>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                            <li><a href="<?php echo BASE_URL; ?>teacher/index.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>teacher/attendance.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Attendance</a></li>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                            <li><a href="<?php echo BASE_URL; ?>student/index.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>student/scan/index.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Scan QR</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>logout.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>student/login.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Student Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>staff_login.php" class="block px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">Staff Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</header> 