<?php
/**
 * Student Header Navigation Component
 * Shows full header on small/medium screens, icons only on mobile
 */
?>
<header class="lg:hidden text-white shadow-lg sticky top-0 z-20">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="flex items-center">
                <div class="bg-white rounded-full p-2 mr-3 shadow-md">
                    <span class="text-indigo-800 font-bold text-xl">BCC</span>
                </div>
                <div class="hidden sm:block">
                    <h1 class="text-xl font-bold">BCCTAP</h1>
                    <p class="text-xs text-white text-opacity-70">Bago City College</p>
                </div>
            </a>
            
            <!-- Navigation for tablets (hidden on mobile) -->
            <nav class="hidden sm:block md:block lg:hidden">
                <ul class="flex space-x-1">
                    <li>
                        <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="px-4 py-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors flex items-center <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-black bg-opacity-20' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                            </svg>
                            Dashboard
                        </a>
                    </li>
                   
                    <li>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="px-4 py-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm1 2h10v10H4V5zm4.707 5.707a1 1 0 00-1.414-1.414l-2 2a1 1 0 000 1.414l2 2a1 1 0 001.414-1.414L7.414 11H12a1 1 0 100-2H7.414l1.293-1.293z" clip-rule="evenodd" />
                            </svg>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Mobile navigation (icons only) -->
            <nav class="sm:hidden">
                <ul class="flex items-center space-x-1">
                    <li>
                        <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="p-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors inline-flex items-center justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-black bg-opacity-20' : ''; ?>" aria-label="Dashboard">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                            </svg>
                        </a>
                    </li>
                   
                    <li>
                        <a href="<?php echo BASE_URL; ?>student/attendance.php" class="p-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors inline-flex items-center justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'bg-black bg-opacity-20' : ''; ?>" aria-label="My Attendance">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>student/profile.php" class="p-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors inline-flex items-center justify-center <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-black bg-opacity-20' : ''; ?>" aria-label="My Profile">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="p-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors inline-flex items-center justify-center" aria-label="Logout">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm1 2h10v10H4V5zm4.707 5.707a1 1 0 00-1.414-1.414l-2 2a1 1 0 000 1.414l2 2a1 1 0 001.414-1.414L7.414 11H12a1 1 0 100-2H7.414l1.293-1.293z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Mobile menu button (alternative navigation) -->
            <div class="sm:hidden">
                <button id="mobile-menu-btn" class="p-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors text-white focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile expanded menu (hidden by default) -->
        <div id="mobile-menu" class="sm:hidden hidden pt-4 pb-3">
            <nav>
                <ul class="space-y-2">
                    <li><a href="<?php echo BASE_URL; ?>student/dashboard.php" class="flex items-center px-4 py-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                            <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                        </svg>
                        Dashboard
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>student/scan/index.php" class="flex items-center px-4 py-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                        </svg>
                        Scan QR Code
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>student/attendance.php" class="flex items-center px-4 py-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                        </svg>
                        My Attendance
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>student/events.php" class="flex items-center px-4 py-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        Events
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>student/profile.php" class="flex items-center px-4 py-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                        </svg>
                        My Profile
                    </a></li>
                    <li><a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center px-4 py-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm1 2h10v10H4V5zm4.707 5.707a1 1 0 00-1.414-1.414l-2 2a1 1 0 000 1.414l2 2a1 1 0 001.414-1.414L7.414 11H12a1 1 0 100-2H7.414l1.293-1.293z" clip-rule="evenodd" />
                        </svg>
                        Logout
                    </a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    });
</script> 