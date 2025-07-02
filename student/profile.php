<?php
/**
 * Student Profile Page
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    redirect(BASE_URL . 'student/login.php');
}

// Get user data
$user_id = $_SESSION['user_id'];

// Get user details with attendance statistics
$query = "SELECT u.*,
          COUNT(DISTINCT a.event_id) as total_events_attended,
          COUNT(a.id) as total_attendances,
          (SELECT COUNT(*) FROM attendance WHERE user_id = u.id AND status = 'time_in') as total_present,
          (SELECT COUNT(*) FROM attendance WHERE user_id = u.id AND status = 'time_out') as total_late
          FROM users u
          LEFT JOIN attendance a ON u.id = a.user_id
          WHERE u.id = ?
          GROUP BY u.id";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Calculate attendance rate
$total_attendance = $user['total_present'] + $user['total_late'];
$attendance_rate = $total_attendance > 0 ? 
    round(($user['total_present'] / $total_attendance) * 100) : 0;

// Get recent attendance history
$history_query = "SELECT a.*, e.title as event_title, e.start_date 
                 FROM attendance a
                 JOIN events e ON a.event_id = e.id
                 WHERE a.user_id = ?
                 ORDER BY a.time_recorded DESC
                 LIMIT 5";
$history_stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($history_stmt, "i", $user_id);
mysqli_stmt_execute($history_stmt);
$recent_attendance = mysqli_stmt_get_result($history_stmt)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <link href="../assets/css/colors.css" rel="stylesheet">
    <link href="../assets/css/student-style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Sidebar (on wide screens) -->
        <?php include '../includes/student-sidebar.php'; ?>
        
        <!-- Header (on mobile/tablet) -->
        <?php include '../includes/student-header.php'; ?>
        
        <main class="flex-grow lg:ml-64 p-4 md:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Profile Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-2xl shadow-xl mb-6 overflow-hidden">
                    <div class="p-6 sm:p-8 md:p-10">
                        <div class="flex flex-col md:flex-row items-center gap-6">
                            <!-- Profile Avatar -->
                            <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center shadow-lg">
                                <span class="text-5xl font-bold text-green-600">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </span>
                            </div>
                            
                            <!-- Profile Info -->
                            <div class="text-center md:text-left">
                                <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </h1>
                                <p class="text-green-100 text-lg mb-2">
                                    <?php echo htmlspecialchars($user['student_id']); ?>
                                </p>
                                <p class="text-green-100">
                                    <?php echo htmlspecialchars($user['department']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Events Attended -->
                    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-600 font-medium">Events Attended</h3>
                            <span class="bg-green-100 text-green-600 p-2 rounded-lg">
                                <i class="fas fa-calendar-check"></i>
                            </span>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">
                            <?php echo $user['total_events_attended']; ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-2">Total events participated</p>
                    </div>
                    
                    <!-- Attendance Rate -->
                    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-600 font-medium">Attendance Rate</h3>
                            <span class="bg-blue-100 text-blue-600 p-2 rounded-lg">
                                <i class="fas fa-chart-line"></i>
                            </span>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">
                            <?php echo $attendance_rate; ?>%
                        </p>
                        <p class="text-sm text-gray-500 mt-2">On-time attendance rate</p>
                    </div>
                    
                    <!-- Present Count -->
                    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-600 font-medium">Present</h3>
                            <span class="bg-green-100 text-green-600 p-2 rounded-lg">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">
                            <?php echo $user['total_present']; ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-2">Times present on time</p>
                    </div>
                    
                    <!-- Late Count -->
                    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-600 font-medium">Late</h3>
                            <span class="bg-yellow-100 text-yellow-600 p-2 rounded-lg">
                                <i class="fas fa-clock"></i>
                            </span>
                        </div>
                        <p class="text-3xl font-bold text-gray-800">
                            <?php echo $user['total_late']; ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-2">Times marked as late</p>
                    </div>
                </div>
                
                <!-- Profile Details and Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Profile Details -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-user text-green-600"></i>
                                Profile Information
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm text-gray-500">Student ID</label>
                                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['student_id']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm text-gray-500">Full Name</label>
                                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm text-gray-500">Department</label>
                                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['department']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm text-gray-500">Year & Section</label>
                                        <p class="text-gray-800 font-medium">
                                            <?php 
                                                echo $user['year_level'] . ' Year - Section ' . 
                                                     htmlspecialchars($user['section']); 
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm text-gray-500">Email</label>
                                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm text-gray-500">Contact Number</label>
                                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['contact_number']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm text-gray-500">Gender</label>
                                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['gender']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm text-gray-500">Address</label>
                                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['address']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-history text-green-600"></i>
                                Recent Activity
                            </h2>
                            
                            <div class="space-y-4">
                                <?php foreach ($recent_attendance as $attendance): ?>
                                    <div class="flex items-start gap-4 p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                                        <div class="flex-shrink-0">
                                            <?php if ($attendance['attendance_status'] === 'present'): ?>
                                                <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                            <?php else: ?>
                                                <span class="text-yellow-500"><i class="fas fa-clock"></i></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="text-gray-800 font-medium">
                                                <?php echo htmlspecialchars($attendance['event_title']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?php 
                                                    echo date('M d, Y h:i A', strtotime($attendance['time_recorded']));
                                                    echo ' - ' . ucfirst($attendance['session']) . ' Session';
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (empty($recent_attendance)): ?>
                                    <div class="text-center text-gray-500 py-4">
                                        <i class="fas fa-calendar-times text-2xl mb-2"></i>
                                        <p>No recent activity</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>