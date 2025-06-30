<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Circular Navigation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            height: 100vh;
            background: #f8fafc;
            color: #1e293b;
        }
        
        
        
     
       
        
        /* Modern Navigation System */
        .mobile-nav-system {
            position: fixed;
            top: 50%;
            right: 24px;
            transform: translateY(-50%);
            width: 64px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1000;
        }
        
        .mobile-nav-bar {
            display: flex;
            flex-direction: column;
            width: 60px;
            background: #0f172a;
            border-radius: 32px;
            overflow: hidden;
            height: 60px;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
            align-items: center;
            position: relative;
            z-index: 100;
        }
        
        .mobile-nav-bar.expanded {
            height: 420px;
            justify-content: space-around;
        }
        
        /* Spacing between specific items */
        .mobile-nav-bar.expanded .menu-item:nth-child(3) {
            margin-bottom: 16px;
        }
        
        .mobile-nav-bar.expanded .menu-item:nth-child(4) {
            margin-top: 16px;
        }
        
        /* Modern Trigger Button */
        .mobile-menu-trigger {
            width: 60px;
            height: 60px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 101;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: none;
            outline: none;
        }
        
        .mobile-menu-trigger:hover {
            background: #4CAF50;
            transform: translate(-50%, -50%) scale(1.05);
        }
        
        .mobile-menu-trigger span {
            color: white;
            font-size: 28px;
            transition: transform 0.3s ease;
        }
        
        .mobile-nav-bar.expanded ~ .mobile-menu-trigger span {
            transform: rotate(90deg);
        }
        
        /* Modern Menu Items */
        .menu-item {
            width: 56px;
            height: 56px;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-decoration: none;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            font-size: 24px;
            border-radius: 50%;
            position: relative;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .mobile-nav-bar.expanded .menu-item {
            opacity: 1;
            transform: scale(1);
        }
        
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.1) !important;
        }
        
        /* Modern Tooltip */
        .menu-item::after {
            content: attr(title);
            position: absolute;
            right: 72px;
            background: #0f172a;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .menu-item:hover::after {
            opacity: 1;
            right: 68px;
        }
        
        /* Icons styling */
        .menu-item i {
            transition: transform 0.3s ease;
        }
        
        .menu-item:hover i {
            transform: scale(1.1);
        }

        /* Hide mobile nav on desktop */
        @media (min-width: 768px) {
            .mobile-nav-system {
                display: none !important;
            }
        }
    </style>
</head>
<body>
        
    
    <!-- Modern Navigation -->
    <div class="mobile-nav-system">
        <div class="mobile-nav-bar">
            <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="menu-item" title="Dashboard">
                <ion-icon name="home-outline"></ion-icon>
            </a>
            <a href="<?php echo BASE_URL; ?>student/scan/index.php" class="menu-item" title="Scan QR">
                <ion-icon name="qr-code-outline"></ion-icon>
            </a>
            <a href="<?php echo BASE_URL; ?>student/attendance.php" class="menu-item" title="Attendance">
                <ion-icon name="calendar-outline"></ion-icon>
            </a>
            <a href="<?php echo BASE_URL; ?>student/events.php" class="menu-item" title="Events">
                <ion-icon name="ticket-outline"></ion-icon>
            </a>
            <a href="<?php echo BASE_URL; ?>student/profile.php" class="menu-item" title="Profile">
                <ion-icon name="person-outline"></ion-icon>
            </a>
            <a href="<?php echo BASE_URL; ?>logout.php" class="menu-item" title="Logout">
                <ion-icon name="log-out-outline"></ion-icon>
            </a>
        </div>
        <button class="mobile-menu-trigger">
            <span><ion-icon name="menu-outline"></ion-icon></span>
        </button>
    </div>
    
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navBar = document.querySelector('.mobile-nav-bar');
            const trigger = document.querySelector('.mobile-menu-trigger');
            
            // Toggle menu expansion
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                navBar.classList.toggle('expanded');
            });
            
            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.mobile-nav-system') && 
                    navBar.classList.contains('expanded')) {
                    navBar.classList.remove('expanded');
                }
            });
            
            // Close after selecting a menu item
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    setTimeout(() => {
                        navBar.classList.remove('expanded');
                    }, 300);
                });
            });
        });
    </script>
</body>
</html>