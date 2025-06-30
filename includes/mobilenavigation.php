<!-- Mobile Navigation Include -->
    <style>
        /* Mobile Navigation System - Scoped to prevent conflicts */
        .mobile-nav-system {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            z-index: 100;
            display: none; /* Hidden by default, shown on mobile */
            font-family: inherit;
            pointer-events: none;
        }
        
        .mobile-nav-system * {
            box-sizing: border-box;
            pointer-events: auto;
        }
        
        .mobile-nav-container {
            position: relative;
            width: 100%;
            height: 100%;
        }
        
        .mobile-nav-system .mobile-nav-bar {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 60px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 30px;
            overflow: hidden;
            height: 60px;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-nav-system .mobile-nav-bar.expanded {
            height: 320px;
            width: 60px;
            bottom: 0;
            justify-content: space-between;
            padding: 10px 0;
        }
        
        /* Menu Items - Scoped */
        .mobile-nav-system .menu-item {
            width: 48px;
            height: 48px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #e2e8f0;
            text-decoration: none;
            opacity: 0;
            transform: scale(0.3) translateY(20px);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            font-size: 20px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            margin: 2px 0;
        }
        
        .mobile-nav-system .mobile-nav-bar.expanded .menu-item {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        
        .mobile-nav-system .mobile-nav-bar.expanded .menu-item:nth-child(1) { transition-delay: 0.1s; }
        .mobile-nav-system .mobile-nav-bar.expanded .menu-item:nth-child(2) { transition-delay: 0.15s; }
        .mobile-nav-system .mobile-nav-bar.expanded .menu-item:nth-child(3) { transition-delay: 0.2s; }
        .mobile-nav-system .mobile-nav-bar.expanded .menu-item:nth-child(4) { transition-delay: 0.25s; }
        .mobile-nav-system .mobile-nav-bar.expanded .menu-item:nth-child(5) { transition-delay: 0.3s; }
        
        .mobile-nav-system .menu-item:hover {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            transform: scale(1.1) translateY(0);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .mobile-nav-system .menu-item:active {
            transform: scale(0.95) translateY(0);
        }
        
        /* Trigger Button - Scoped */
        .mobile-nav-system .mobile-menu-trigger {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border-radius: 50%;
            border: none;
            outline: none;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
            z-index: 102;
            font-family: inherit;
        }
        
        .mobile-nav-system .mobile-menu-trigger:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.5);
        }
        
        .mobile-nav-system .mobile-menu-trigger:active {
            transform: scale(0.95);
        }
        
        .mobile-nav-system .mobile-menu-trigger ion-icon {
            color: white;
            font-size: 24px;
            transition: transform 0.3s ease;
        }
        
        /* Tooltip - Scoped */
        .mobile-nav-system .menu-item::before {
            content: attr(data-tooltip);
            position: absolute;
            right: 65px;
            background: rgba(15, 23, 42, 0.95);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            opacity: 0;
            pointer-events: none;
            white-space: nowrap;
            transform: translateX(10px);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 103;
        }
        
        .mobile-nav-system .menu-item:hover::before {
            opacity: 1;
            transform: translateX(0);
        }
        
        /* Backdrop overlay - Less intrusive */
        .mobile-nav-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 99;
            backdrop-filter: blur(1px);
            pointer-events: none;
        }
        
        .mobile-nav-backdrop.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        
        /* Responsive Design - More specific */
        @media (max-width: 768px) {
            .mobile-nav-system {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            .mobile-nav-system {
                bottom: 15px;
                right: 15px;
            }
        }
        
        /* Remove bounce animation that might cause issues */
        .mobile-nav-system .mobile-menu-trigger:focus {
            outline: 2px solid rgba(76, 175, 80, 0.5);
            outline-offset: 2px;
        }
    </style>
        
<!-- Mobile Navigation HTML -->
    <div class="mobile-nav-backdrop"></div>
    <div class="mobile-nav-system">
        <div class="mobile-nav-container">
        <div class="mobile-nav-bar">
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>student/dashboard.php" 
                   class="menu-item" 
                   data-tooltip="Dashboard">
                <ion-icon name="home-outline"></ion-icon>
            </a>
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>student/attendance.php" 
                   class="menu-item" 
                   data-tooltip="Attendance">
                <ion-icon name="calendar-outline"></ion-icon>
            </a>
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>student/events.php" 
                   class="menu-item" 
                   data-tooltip="Events">
                    <ion-icon name="calendar-number-outline"></ion-icon>
                </a>
                <a href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>logout.php" 
                   class="menu-item" 
                   data-tooltip="Logout">
                    <ion-icon name="log-out-outline"></ion-icon>
                </a>
                <a href=""
                   class="menu-item" 
                   data-tooltip="Logout">
                <ion-icon name="log-out-outline"></ion-icon>
            </a>
        </div>
            <button class="mobile-menu-trigger" aria-label="Toggle mobile menu">
                <ion-icon name="menu-outline"></ion-icon>
        </button>
        </div>
    </div>
    
    <!-- Load Ionicons if not already loaded -->
    <script>
        if (typeof window.mobileNavLoaded === 'undefined') {
            window.mobileNavLoaded = true;
            
            if (!window.customElements || !window.customElements.get('ion-icon')) {
                const ioniconsModule = document.createElement('script');
                ioniconsModule.type = 'module';
                ioniconsModule.src = 'https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js';
                document.head.appendChild(ioniconsModule);
                
                const ioniconsNoModule = document.createElement('script');
                ioniconsNoModule.setAttribute('nomodule', '');
                ioniconsNoModule.src = 'https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js';
                document.head.appendChild(ioniconsNoModule);
            }
        }
    </script>
    
    <script>
        // Scoped mobile navigation functions to prevent conflicts
        (function() {
            'use strict';
            
            // Only run if not already initialized
            if (window.mobileNavInitialized) return;
            window.mobileNavInitialized = true;
            
            // Function to open Google Lens Camera - mobile optimized
            window.mobileNavOpenGoogleLens = function(event) {
                event.preventDefault();
                event.stopPropagation();
                
                // Visual feedback
                const button = event.target.closest('.menu-item');
                if (button) {
                    button.style.background = 'rgba(76, 175, 80, 0.4)';
                    button.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        button.style.background = '';
                        button.style.transform = '';
                    }, 300);
                }
                
                // Detect device
                const isAndroid = /android/i.test(navigator.userAgent);
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
                const isMobile = isAndroid || isIOS;
                
                if (isAndroid) {
                    // Android - try multiple methods
                    // Method 1: Try Google Lens app
                    window.location.href = 'intent://scan/#Intent;scheme=https;package=com.google.ar.lens;end';
                    
                    // Method 2: Fallback to Google app after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'intent://lens#Intent;scheme=https;package=com.google.android.googlequicksearchbox;end';
                    }, 2000);
                    
                    // Method 3: Final fallback to web after 4 seconds
                    setTimeout(() => {
                        window.location.href = 'https://lens.google.com/';
                    }, 4000);
                    
                } else if (isIOS) {
                    // iOS - direct approach
                    window.location.href = 'https://lens.google.com/';
                    
                } else if (isMobile) {
                    // Other mobile devices
                    window.location.href = 'https://lens.google.com/';
                    
                } else {
                    // Desktop
                    window.open('https://lens.google.com/', '_blank');
                }
            };

            document.addEventListener('DOMContentLoaded', function() {
                const navSystem = document.querySelector('.mobile-nav-system');
                if (!navSystem) return;
                
                const navBar = navSystem.querySelector('.mobile-nav-bar');
                const trigger = navSystem.querySelector('.mobile-menu-trigger');
                const backdrop = document.querySelector('.mobile-nav-backdrop');
                
                if (!navBar || !trigger || !backdrop) return;
                
                let originalBodyOverflow = '';
                
                // Open menu function
                function openMenu() {
                    navBar.classList.add('expanded');
                    backdrop.classList.add('active');
                    trigger.setAttribute('aria-expanded', 'true');
                    
                    // Store original overflow and prevent scrolling
                    originalBodyOverflow = document.body.style.overflow;
                    document.body.style.overflow = 'hidden';
                    
                    const icon = trigger.querySelector('ion-icon');
                    if (icon) {
                        icon.setAttribute('name', 'close-outline');
                    }
                }
                
                // Close menu function
                function closeMenu() {
                    navBar.classList.remove('expanded');
                    backdrop.classList.remove('active');
                    trigger.setAttribute('aria-expanded', 'false');
                    
                    // Restore original overflow
                    document.body.style.overflow = originalBodyOverflow;
                    
                    const icon = trigger.querySelector('ion-icon');
                    if (icon) {
                        icon.setAttribute('name', 'menu-outline');
                    }
                }
                
                // Toggle menu
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (navBar.classList.contains('expanded')) {
                        closeMenu();
                    } else {
                        openMenu();
                    }
                });
                
                // Close when clicking backdrop
                backdrop.addEventListener('click', closeMenu);
                
                // Close when clicking outside (only for mobile nav system)
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.mobile-nav-system') && navBar.classList.contains('expanded')) {
                        closeMenu();
                    }
                });
                
                // Close after selecting a menu item (only mobile nav items)
                navSystem.querySelectorAll('.menu-item').forEach(item => {
                    item.addEventListener('click', function(e) {
                        // Don't close for Google Lens since it handles its own feedback
                        if (this.getAttribute('onclick')) return;
                        
                        this.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                            this.style.transform = '';
                            closeMenu();
                        }, 150);
                    });
                });
                
                // Handle escape key (only when mobile nav is expanded)
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && navBar.classList.contains('expanded')) {
                        closeMenu();
                    }
                });
            });
        })();
    </script>