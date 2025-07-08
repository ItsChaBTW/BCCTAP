<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    .animated-gradient {
        background: linear-gradient(-45deg, #16a34a, #22c55e, #059669, #10b981);
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    .logo-glow {
        box-shadow: 0 0 30px rgba(34, 197, 94, 0.3);
        transition: all 0.3s ease;
    }
    
    .logo-glow:hover {
        box-shadow: 0 0 40px rgba(34, 197, 94, 0.5);
        transform: scale(1.05);
    }
    
    .nav-glass {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .nav-glass:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .text-shimmer {
        background: linear-gradient(45deg, #ffffff, #f0f9ff, #ffffff);
        background-size: 200% 200%;
        background-clip: text;
        -webkit-background-clip: text;
        animation: shimmer 3s ease-in-out infinite;
    }
    
    @keyframes shimmer {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .mobile-menu-slide {
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }
    
    .mobile-menu-slide.active {
        transform: translateX(0);
    }
    
    .floating-particles {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }
    
    .particle {
        position: absolute;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
        50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
    }
    
    .nav-indicator {
        position: relative;
        overflow: hidden;
    }
    
    .nav-indicator::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #fbbf24, #f59e0b);
        transition: width 0.3s ease;
    }
    
    .nav-indicator:hover::before {
        width: 100%;
    }
</style>

<header class="animated-gradient text-white shadow-2xl relative overflow-hidden" style="font-family: 'Inter', sans-serif;">
    <!-- Floating Particles Background -->
    <div class="floating-particles">
        <div class="particle" style="left: 10%; top: 20%; width: 4px; height: 4px; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; top: 60%; width: 6px; height: 6px; animation-delay: 1s;"></div>
        <div class="particle" style="left: 35%; top: 30%; width: 3px; height: 3px; animation-delay: 2s;"></div>
        <div class="particle" style="left: 50%; top: 70%; width: 5px; height: 5px; animation-delay: 0.5s;"></div>
        <div class="particle" style="left: 65%; top: 40%; width: 4px; height: 4px; animation-delay: 1.5s;"></div>
        <div class="particle" style="left: 80%; top: 20%; width: 6px; height: 6px; animation-delay: 2.5s;"></div>
        <div class="particle" style="left: 90%; top: 80%; width: 3px; height: 3px; animation-delay: 3s;"></div>
    </div>
    
    <div class="container mx-auto px-6 py-4 relative z-10">
        <div class="flex justify-between items-center">
            <!-- Enhanced Logo -->
            <a href="<?php echo (isLoggedIn() && isStudent()) ? BASE_URL . 'student/dashboard.php' : BASE_URL; ?>" class="flex items-center group">
                <div class="logo-glow bg-gradient-to-br from-white to-green-50 rounded-2xl p-3 mr-4 shadow-lg">
                    <div class="relative">
                        <span class="text-green-700 font-bold text-2xl bg-gradient-to-r from-green-600 to-green-800 bg-clip-text text-transparent">BCC</span>
                        <div class="absolute -top-1 -right-1 w-2 h-2 bg-yellow-400 rounded-full animate-pulse"></div>
                    </div>
                </div>
                <div class="group-hover:scale-105 transition-transform duration-300">
                    <h1 class="text-2xl font-bold text-shimmer bg-gradient-to-r from-white via-green-100 to-white bg-clip-text">BCCTAP</h1>
                    <p class="text-sm text-green-100 font-medium tracking-wide">Bago City College Time Attendance Platform</p>
                </div>
            </a>
            
            <!-- Enhanced Desktop Navigation -->
            <nav class="hidden lg:block">
                <ul class="flex space-x-2">
                    <li>
                        <a href="<?php echo (isLoggedIn() && isStudent()) ? BASE_URL . 'student/dashboard.php' : BASE_URL; ?>" 
                           class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                            <span>Home</span>
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>admin/index.php" 
                                   class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                                </svg>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>admin/events/index.php" 
                                   class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                    <span>Events</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>admin/users/index.php" 
                                   class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                                    <span>Users</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>admin/qrcodes/index.php" 
                                   class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                                    <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
                                </svg>
                                    <span>QR Codes</span>
                                </a>
                            </li>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>teacher/index.php" 
                                   class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                                </svg>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>teacher/attendance.php" 
                                   class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                    <span>Attendance</span>
                                </a>
                            </li>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                            <li>
                                <a href="<?php echo BASE_URL; ?>student/dashboard.php" 
                                   class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                                </svg>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo BASE_URL; ?>student/scan/index.php" 
                                   class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                                    <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
                                </svg>
                                    <span>Scan QR</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>logout.php" 
                               class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-red-200 transition-all duration-300 bg-red-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm1 2h10v10H4V5zm4.707 5.707a1 1 0 00-1.414-1.414l-2 2a1 1 0 000 1.414l2 2a1 1 0 001.414-1.414L7.414 11H12a1 1 0 100-2H7.414l1.293-1.293z" clip-rule="evenodd" />
                            </svg>
                                <span>Logout</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>student/login.php" 
                               class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                                <span>Student Login</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>staff_login.php" 
                               class="nav-glass nav-indicator px-5 py-3 rounded-xl font-medium flex items-center space-x-2 hover:text-yellow-200 transition-all duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                                <span>Staff Login</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <!-- Enhanced Mobile Menu Button -->
            <div class="lg:hidden">
                <button id="mobile-menu-btn" class="nav-glass p-3 rounded-xl focus:outline-none hover:scale-105 transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="menu-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="close-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Enhanced Mobile Menu -->
        <div id="mobile-menu" class="lg:hidden fixed left-0 top-0 w-80 h-full bg-gradient-to-b from-green-600/95 to-green-800/95 backdrop-blur-lg mobile-menu-slide z-50">
            <div class="p-6">
                <!-- Mobile Menu Header -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center">
                        <div class="logo-glow bg-white rounded-xl p-2 mr-3">
                            <span class="text-green-700 font-bold text-lg">BCC</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">BCCTAP</h2>
                            <p class="text-xs text-green-100">Mobile Menu</p>
                        </div>
                    </div>
                    <button id="mobile-close-btn" class="nav-glass p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Mobile Navigation -->
            <nav>
                    <ul class="space-y-3">
                        <li>
                            <a href="<?php echo (isLoggedIn() && isStudent()) ? BASE_URL . 'student/dashboard.php' : BASE_URL; ?>" 
                               class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                </svg>
                                <span class="font-medium">Home</span>
                            </a>
                        </li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li><a href="<?php echo BASE_URL; ?>admin/index.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    </svg>
                                    <span class="font-medium">Dashboard</span>
                                </a></li>
                                <li><a href="<?php echo BASE_URL; ?>admin/events/index.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-medium">Events</span>
                                </a></li>
                                <li><a href="<?php echo BASE_URL; ?>admin/users/index.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                                    </svg>
                                    <span class="font-medium">Users</span>
                                </a></li>
                                <li><a href="<?php echo BASE_URL; ?>admin/qrcodes/index.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-medium">QR Codes</span>
                                </a></li>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                                <li><a href="<?php echo BASE_URL; ?>teacher/index.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    </svg>
                                    <span class="font-medium">Dashboard</span>
                                </a></li>
                                <li><a href="<?php echo BASE_URL; ?>teacher/attendance.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    </svg>
                                    <span class="font-medium">Attendance</span>
                                </a></li>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                                <li><a href="<?php echo BASE_URL; ?>student/dashboard.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                    </svg>
                                    <span class="font-medium">Dashboard</span>
                                </a></li>
                                <li><a href="<?php echo BASE_URL; ?>student/scan/index.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-medium">Scan QR</span>
                                </a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo BASE_URL; ?>logout.php" class="flex items-center space-x-3 p-4 rounded-xl bg-red-500/20 hover:bg-red-500/30 transition-all duration-300 group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">Logout</span>
                            </a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>student/login.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">Student Login</span>
                            </a></li>
                            <li><a href="<?php echo BASE_URL; ?>staff_login.php" class="flex items-center space-x-3 p-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all duration-300 group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="font-medium">Staff Login</span>
                            </a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            </div>
        </div>
        
        <!-- Mobile Menu Overlay -->
        <div id="mobile-overlay" class="lg:hidden fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-40"></div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('mobile-close-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const overlay = document.getElementById('mobile-overlay');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');
        
        function openMenu() {
            mobileMenu.classList.add('active');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            menuIcon.classList.add('hidden');
            closeIcon.classList.remove('hidden');
        }
        
        function closeMenu() {
            mobileMenu.classList.remove('active');
            overlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
            menuIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
        }
        
        if (menuBtn) {
            menuBtn.addEventListener('click', function() {
                if (mobileMenu.classList.contains('active')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeMenu);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }
        
        // Close menu when clicking on links
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', closeMenu);
        });
    });
</script> 