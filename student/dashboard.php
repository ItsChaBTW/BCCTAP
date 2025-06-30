<?php
/**
 * Student Dashboard
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    redirect(BASE_URL . 'student/login.php');
}

// Get student data
$student_id = $_SESSION['student_id'];
$user_id = $_SESSION['user_id'];
$student_department = isset($_SESSION['program']) ? $_SESSION['program'] : '';

// Get current date
$today = date('Y-m-d');

// Get ongoing events (events that are happening today)
$query = "SELECT e.* FROM events e 
          WHERE (e.department = ? OR e.department IS NULL OR e.department = '') 
          AND ? BETWEEN e.start_date AND e.end_date
          ORDER BY e.start_date ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $student_department, $today);
mysqli_stmt_execute($stmt);
$ongoing_events = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get upcoming events (events that start in the future)
$query = "SELECT e.* FROM events e 
          WHERE (e.department = ? OR e.department IS NULL OR e.department = '') 
          AND e.start_date > ?
          ORDER BY e.start_date ASC LIMIT 5";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $student_department, $today);
mysqli_stmt_execute($stmt);
$upcoming_events = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get recent attendance records
$query = "SELECT a.*, e.title as event_title, e.start_date, e.end_date, 
          qr.session as session_type 
          FROM attendance a 
          JOIN events e ON a.event_id = e.id 
          JOIN qr_codes qr ON a.qr_code_id = qr.id
          WHERE a.user_id = ?
          ORDER BY a.time_recorded DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$recent_attendance = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get attendance statistics
$query = "SELECT 
            COUNT(*) as total_attendance,
            COUNT(DISTINCT event_id) as events_attended
          FROM attendance 
          WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$attendance_stats = mysqli_stmt_get_result($stmt)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <link href="../assets/css/student-style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Sidebar (on wide screens) -->
        <?php include '../includes/student-sidebar.php'; ?>
        
        <!-- Header (on mobile/tablet) -->
        <?php include '../includes/student-header.php'; ?>
        <?php include '../includes/mobilenavigation.php'; ?>
        <main class="flex-grow lg:ml-64 px-4 pt-6 pb-20">
            <!-- Welcome Banner -->
            <div class="relative overflow-hidden bg-gradient-to-r from-green-400 via-green-500 to-green-600 rounded-2xl shadow-2xl ring-2 ring-green-300 hover:scale-[1.01] transition-transform duration-300 p-8 mb-8 text-white">
                <svg class="absolute right-0 top-0 w-40 h-40 opacity-20 pointer-events-none" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path fill="#bbf7d0" d="M44.8,-67.6C56.7,-60.2,63.7,-44.2,69.2,-28.7C74.7,-13.2,78.7,1.8,75.2,15.2C71.7,28.6,60.7,40.3,48.1,48.7C35.5,57.1,21.3,62.2,6.2,65.1C-8.9,68,-24,68.7,-36.2,62.1C-48.4,55.5,-57.7,41.6,-65.2,26.7C-72.7,11.8,-78.4,-4.1,-74.7,-17.2C-71,-30.3,-57.9,-40.6,-44.7,-48.2C-31.5,-55.8,-15.7,-60.7,0.7,-61.6C17.1,-62.5,34.2,-59.1,44.8,-67.6Z" transform="translate(100 100)" /></svg>
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center relative z-10">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold mb-2 flex items-center gap-2">
                            <svg class="inline w-8 h-8 text-white drop-shadow-lg animate-bounce" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 1.343-3 3 0 1.657 1.343 3 3 3s3-1.343 3-3c0-1.657-1.343-3-3-3zm0 0V4m0 7v9m-7-7h14"/></svg>
                            Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
                        </h1>
                        <p class="text-white text-opacity-80">Student ID: <?php echo htmlspecialchars($_SESSION['student_id']); ?></p>
                        <?php if (isset($_SESSION['program'])): ?>
                            <p class="text-white text-opacity-80"><?php echo htmlspecialchars($_SESSION['program']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Event Error Handling -->
            <?php if (isset($_SESSION['event_error'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: '<?php echo addslashes($_SESSION['event_error']['title']); ?>',
                            html: '<div class="text-center">' +
                                  '<h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo addslashes($_SESSION['event_error']['event_title']); ?></h3>' +
                                  '<p class="text-gray-600 mb-2"><?php echo addslashes($_SESSION['event_error']['message']); ?></p>' +
                                  '<p class="text-sm text-gray-500"><?php echo addslashes($_SESSION['event_error']['subtitle']); ?></p>' +
                                  '</div>',
                            icon: '<?php echo $_SESSION['event_error']['icon']; ?>',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#EF6161',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        });
                    });
                </script>
                <?php 
                    unset($_SESSION['event_error']); 
                ?>
            <?php endif; ?>
            
            <!-- Warning Messages -->
            <?php if (isset($_SESSION['warning_message'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Attendance Notice',
                            text: '<?php echo addslashes($_SESSION['warning_message']); ?>',
                            icon: 'warning',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#F59E0B',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        });
                    });
                </script>
                <?php 
                    unset($_SESSION['warning_message']); 
                ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['device_warning']) && $_SESSION['device_warning'] === true): ?>
            <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-6" role="alert">
                <div class="flex">
                    <div class="py-1">
                        <svg class="fill-current h-6 w-6 text-orange-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                        </svg>
                    </div>
                    <div>
                        <p><?php echo $_SESSION['device_message']; ?></p>
                        <p class="mt-2 text-sm">For security reasons, if this was not you, please contact administration immediately.</p>
                    </div>
                </div>
            </div>
            <?php 
                // Clear the warning flag after displaying it once
                unset($_SESSION['device_warning']);
                unset($_SESSION['device_message']);
            endif; 
            ?>
            
            <!-- Attendance Stats Section -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m4 0h-1v4h-1m-4 0h-1v-4h-1"/></svg>
                    Attendance Overview
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Total Check-ins Card -->
                    <div class="bg-gradient-to-br from-green-100 via-green-50 to-white p-6 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300 stat-card border-l-4 border-green-400 flex items-center gap-4">
                        <div class="flex-shrink-0 bg-green-200 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div class="text-center flex-1">
                            <span class="block text-5xl font-bold text-green-700"><?php echo $attendance_stats['total_attendance']; ?></span>
                            <span class="text-sm text-gray-600 mt-2 block">Total Check-ins</span>
                        </div>
                    </div>
                    <!-- Events Attended Card -->
                    <div class="bg-gradient-to-br from-green-50 via-green-100 to-white p-6 rounded-xl shadow-md hover:shadow-xl hover:scale-105 transition-all duration-300 stat-card border-l-4 border-green-400 flex items-center gap-4">
                        <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 1.343-3 3 0 1.657 1.343 3 3 3s3-1.343 3-3c0-1.657-1.343-3-3-3zm0 0V4m0 7v9m-7-7h14"/></svg>
                        </div>
                        <div class="text-center flex-1">
                            <span class="block text-5xl font-bold text-green-600"><?php echo $attendance_stats['events_attended']; ?></span>
                            <span class="text-sm text-gray-600 mt-2 block">Events Attended</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-center">
                    <a href="attendance.php" class="student-btn student-btn-primary inline-block mt-2 px-6 py-2">
                        View Attendance History
                    </a>
                </div>
            </div>
            
            <!-- Events Sections -->
            <div class="space-y-8">
                <!-- Ongoing Events -->
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Ongoing Events Today</h2>
                    
                    <?php if (count($ongoing_events) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($ongoing_events as $event): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition bg-green-100 border-green-200">
                                    <div class="flex flex-col sm:flex-row justify-between">
                                        <div class="mb-2 sm:mb-0">
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <span class="student-badge student-badge-success">
                                                    Happening Now
                                                </span>
                                                <?php 
                                                $start_date = new DateTime($event['start_date']);
                                                $end_date = new DateTime($event['end_date']);
                                                echo $start_date->format('M d') . ' - ' . $end_date->format('M d, Y'); 
                                                ?>
                                            </p>
                                            <?php if (!empty($event['department'])): ?>
                                            <p class="text-xs text-gray-500 mt-1">Department: <?php echo htmlspecialchars($event['department']); ?></p>
                                            <?php else: ?>
                                            <p class="text-xs text-gray-500 mt-1">Department: All Departments</p>
                                            <?php endif; ?>
                                        </div>
                                        <a href="event-detail.php?id=<?php echo $event['id']; ?>" class="student-btn student-btn-primary self-start sm:self-center whitespace-nowrap">
                                            Attend
                                        </a>
                                    </div>
                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-500">
                                        <div>
                                            <span class="font-medium">Morning:</span> 
                                            <?php echo date('h:i A', strtotime($event['morning_time_in'])); ?> - 
                                            <?php echo date('h:i A', strtotime($event['morning_time_out'])); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium">Afternoon:</span> 
                                            <?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?> - 
                                            <?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2">No ongoing events today</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Events -->
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Upcoming Events</h2>
                    
                    <?php if (count($upcoming_events) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_events as $event): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex flex-col sm:flex-row justify-between">
                                        <div class="mb-2 sm:mb-0">
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <span class="student-badge student-badge-info">
                                                    Upcoming
                                                </span>
                                                <?php 
                                                $start_date = new DateTime($event['start_date']);
                                                $end_date = new DateTime($event['end_date']);
                                                echo $start_date->format('M d') . ' - ' . $end_date->format('M d, Y'); 
                                                ?>
                                            </p>
                                            <?php if (!empty($event['department'])): ?>
                                            <p class="text-xs text-gray-500 mt-1">Department: <?php echo htmlspecialchars($event['department']); ?></p>
                                            <?php else: ?>
                                            <p class="text-xs text-gray-500 mt-1">Department: All Departments</p>
                                            <?php endif; ?>
                                        </div>
                                        <a href="event-detail.php?id=<?php echo $event['id']; ?>" class="student-btn student-btn-secondary self-start sm:self-center whitespace-nowrap">
                                            Details
                                        </a>
                                    </div>
                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-500">
                                        <div>
                                            <span class="font-medium">Morning:</span> 
                                            <?php echo date('h:i A', strtotime($event['morning_time_in'])); ?> - 
                                            <?php echo date('h:i A', strtotime($event['morning_time_out'])); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium">Afternoon:</span> 
                                            <?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?> - 
                                            <?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2">No upcoming events</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
  
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/chrome-detector.js"></script>
    
    <script>
        // Execute when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Show Chrome recommendation every dashboard visit for non-Chrome users
            setTimeout(() => {
                ChromeDetector.showLoginRecommendation({
                    title: 'Optimize Your BCCTAP Experience',
                    message: 'For the best QR code scanning and attendance experience, we recommend using Google Chrome.',
                    showDetails: false // Less detailed on dashboard
                }).then((result) => {
                    if (result && result.isConfirmed) {
                        console.log('User chose to download Chrome from dashboard');
                    }
                });
            }, 4000); // Show after 4 seconds to let dashboard load
        });
    </script>
</body>
</html> 