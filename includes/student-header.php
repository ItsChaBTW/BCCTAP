<?php
/**
 * Student Header Navigation Component (modern, robust, glassmorphism slide-down menu)
 */
?>
<header class="lg:hidden text-white shadow-lg sticky top-0 z-20 bg-green-700">
  <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="flex items-center">
                <div class="bg-white rounded-full p-2 mr-3 shadow-md">
        <span class="text-green-500 font-bold text-xl">BCC</span>
                </div>
      <div class="sm:block hidden">
                    <h1 class="text-xl font-bold">BCCTAP</h1>
                    <p class="text-xs text-white text-opacity-70">Bago City College</p>
                </div>
            </a>
    <!-- Menu Button -->
                <button id="mobile-menu-btn" class="p-2 rounded-md hover:bg-black hover:bg-opacity-10 transition-colors text-white focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
  <!-- Slide-down Mobile Menu -->
  <div id="mobile-menu" class="mobile-menu-custom hidden">
    <nav class="pt-4 pb-3 px-2">
                <ul class="space-y-2">
        <li><a href="<?php echo BASE_URL; ?>student/dashboard.php" class="menu-link"><svg xmlns='http://www.w3.org/2000/svg' class='inline-block mr-2' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 12l9-9 9 9M4 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3m-6 0h6'/></svg>Dashboard</a></li>
        <li><a href="<?php echo BASE_URL; ?>student/scan/index.php" class="menu-link"><svg xmlns='http://www.w3.org/2000/svg' class='inline-block mr-2' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='currentColor'><rect x='3' y='3' width='7' height='7' rx='1.5' stroke-width='2'/><rect x='14' y='3' width='7' height='7' rx='1.5' stroke-width='2'/><rect x='14' y='14' width='7' height='7' rx='1.5' stroke-width='2'/><rect x='3' y='14' width='7' height='7' rx='1.5' stroke-width='2'/></svg>Scan QR Code</a></li>
        <li><a href="<?php echo BASE_URL; ?>student/attendance.php" class="menu-link"><svg xmlns='http://www.w3.org/2000/svg' class='inline-block mr-2' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='currentColor'><rect x='3' y='4' width='18' height='18' rx='2' stroke-width='2'/><path stroke-width='2' d='M16 2v4M8 2v4M3 10h18'/><path stroke-width='2' d='M9 16l2 2 4-4'/></svg>My Attendance</a></li>
        <li><a href="<?php echo BASE_URL; ?>student/events.php" class="menu-link"><svg xmlns='http://www.w3.org/2000/svg' class='inline-block mr-2' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='currentColor'><rect x='3' y='4' width='18' height='18' rx='2' stroke-width='2'/><path stroke-width='2' d='M16 2v4M8 2v4M3 10h18'/><path stroke-width='2' d='M12 17l-3.5 2.1 1-4.1-3-2.6 4.2-.3L12 8l1.3 4.1 4.2.3-3 2.6 1 4.1z'/></svg>Events</a></li>
        <li><a href="<?php echo BASE_URL; ?>student/profile.php" class="menu-link"><svg xmlns='http://www.w3.org/2000/svg' class='inline-block mr-2' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='currentColor'><circle cx='12' cy='8' r='4' stroke-width='2'/><path stroke-width='2' d='M4 20v-1a4 4 0 014-4h8a4 4 0 014 4v1'/></svg>My Profile</a></li>
        <li><a href="<?php echo BASE_URL; ?>logout.php" class="menu-link"><svg xmlns='http://www.w3.org/2000/svg' class='inline-block mr-2' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1'/></svg>Logout</a></li>
                </ul>
            </nav>
    </div>
</header>

<style>
.mobile-menu-custom {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  margin: 0 auto;
  width: 95%;
  max-width: 420px;
  background: rgba(34, 197, 94, 0.7); /* green-500 with opacity */
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
  border-radius: 1rem;
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border: 1px solid rgba(255,255,255,0.18);
  z-index: 50;
  transform: scaleY(0);
  transform-origin: top;
  transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
  overflow: hidden;
}
.mobile-menu-custom.open {
  display: block !important;
  transform: scaleY(1);
}
.menu-link {
  display: block;
  width: 100%;
  padding: 0.75rem 1.25rem;
  color: #fff;
  font-weight: 500;
  border-radius: 0.5rem;
  text-decoration: none;
  background: transparent;
  transition: background 0.2s, color 0.2s;
  position: relative;
}
.menu-link:hover, .menu-link:focus {
  background: rgba(20, 83, 45, 0.18);
  color: #d1fae5;
}
.menu-link:not(:last-child)::after {
  content: '';
  display: block;
  height: 1px;
  background: rgba(255,255,255,0.12);
  margin: 0.5rem 0 0 0;
}

/* Hide menu button on mobile, show on tablet, hide on PC */
@media (max-width: 767px) {
  #mobile-menu-btn {
    display: none !important;
  }
}
@media (min-width: 1024px) {
  #mobile-menu-btn {
    display: none !important;
  }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
  // Hide menu by default
  mobileMenu.classList.remove('open', 'block', 'scale-y-100');
  mobileMenu.classList.add('hidden');

  menuBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    if (mobileMenu.classList.contains('open')) {
      mobileMenu.classList.remove('open');
      mobileMenu.classList.add('hidden');
    } else {
      mobileMenu.classList.add('open');
      mobileMenu.classList.remove('hidden');
    }
  });

  // Close menu when clicking a link
  mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', function () {
      mobileMenu.classList.remove('open');
      mobileMenu.classList.add('hidden');
    });
  });

  // Close menu when clicking outside
  document.addEventListener('click', function (e) {
    if (!mobileMenu.contains(e.target) && e.target !== menuBtn) {
      mobileMenu.classList.remove('open');
      mobileMenu.classList.add('hidden');
    }
  });
    });
</script> 
