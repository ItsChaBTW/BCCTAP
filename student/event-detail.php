<?php
/**
 * Event Detail Page
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    redirect(BASE_URL . 'student/login.php');
}

// Get student data
$user_id = $_SESSION['user_id'];

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Event ID is required";
    redirect(BASE_URL . 'student/dashboard.php');
}

$event_id = intval($_GET['id']);

// Get event details
$query = "SELECT e.*, u.full_name as created_by_name
          FROM events e 
          LEFT JOIN users u ON e.created_by = u.id
          WHERE e.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "Event not found";
    redirect(BASE_URL . 'student/dashboard.php');
}

$event = mysqli_fetch_assoc($result);

// Get student attendance for this event
$query = "SELECT a.*, qr.session as session_type 
          FROM attendance a 
          JOIN qr_codes qr ON a.qr_code_id = qr.id
          WHERE a.user_id = ? AND a.event_id = ?
          ORDER BY a.time_recorded ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Group attendance by session
$morning_attendance = null;
$afternoon_attendance = null;

foreach ($attendance_records as $record) {
    if ($record['session_type'] === 'morning') {
        $morning_attendance = $record;
    } else {
        $afternoon_attendance = $record;
    }
}

// Check if this is an ongoing event
$today = date('Y-m-d');
$is_ongoing = ($today >= $event['start_date'] && $today <= $event['end_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - BCCTAP</title>
    <link href="../assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <?php include '../includes/header.php'; ?>
        
        <main class="flex-grow container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center">
                    <a href="dashboard.php" class="mr-4 text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-indigo-800"><?php echo htmlspecialchars($event['title']); ?></h1>
                </div>
                
              
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Event Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-indigo-600 text-white">
                            <h2 class="text-xl font-semibold">Event Details</h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($event['description'])): ?>
                                <div class="mb-6">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Description</h3>
                                    <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h3 class="text-md font-semibold text-gray-700 mb-2">Event Dates</h3>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Start Date</p>
                                            <p class="text-gray-900"><?php echo date('M d, Y', strtotime($event['start_date'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">End Date</p>
                                            <p class="text-gray-900"><?php echo date('M d, Y', strtotime($event['end_date'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <?php if (!empty($event['department'])): ?>
                                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-4">
                                            <h3 class="text-md font-semibold text-blue-700 mb-1">Department</h3>
                                            <p class="text-gray-800"><?php echo htmlspecialchars($event['department']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($event['location'])): ?>
                                        <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                                            <h3 class="text-md font-semibold text-purple-700 mb-1">Location</h3>
                                            <p class="text-gray-800"><?php echo htmlspecialchars($event['location']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                                    <h3 class="text-md font-semibold text-green-700 mb-1">Morning Session</h3>
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="text-xs text-gray-500">Time In</p>
                                            <p class="text-gray-900"><?php echo date('h:i A', strtotime($event['morning_time_in'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Time Out</p>
                                            <p class="text-gray-900"><?php echo date('h:i A', strtotime($event['morning_time_out'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-amber-50 p-4 rounded-lg border border-amber-100">
                                    <h3 class="text-md font-semibold text-amber-700 mb-1">Afternoon Session</h3>
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="text-xs text-gray-500">Time In</p>
                                            <p class="text-gray-900"><?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Time Out</p>
                                            <p class="text-gray-900"><?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Status -->
                <div>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 bg-indigo-600 text-white">
                            <h2 class="text-xl font-semibold">Your Attendance</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <!-- Morning Session -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-medium text-gray-800 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                            </svg>
                                            Morning Session
                                        </h3>
                                        
                                        <?php if ($morning_attendance): ?>
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch ($morning_attendance['status']) {
                                                case 'present':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    $status_text = 'Present';
                                                    break;
                                                case 'late':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    $status_text = 'Late';
                                                    break;
                                                case 'excused':
                                                    $status_class = 'bg-blue-100 text-blue-800';
                                                    $status_text = 'Excused';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = 'Unknown';
                                            }
                                            ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Not Recorded
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($morning_attendance): ?>
                                        <div class="mt-3 text-sm text-gray-600">
                                            <p>Checked in at: <?php echo date('h:i A', strtotime($morning_attendance['time_recorded'])); ?></p>
                                            <p class="mt-1">Date: <?php echo date('M d, Y', strtotime($morning_attendance['time_recorded'])); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-3 text-sm text-gray-600">
                                            <p>You have not recorded your attendance for the morning session yet.</p>
                                           
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Afternoon Session -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-medium text-gray-800 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                            </svg>
                                            Afternoon Session
                                        </h3>
                                        
                                        <?php if ($afternoon_attendance): ?>
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch ($afternoon_attendance['status']) {
                                                case 'present':
                                                    $status_class = 'bg-green-100 text-green-800';
                                                    $status_text = 'Present';
                                                    break;
                                                case 'late':
                                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                                    $status_text = 'Late';
                                                    break;
                                                case 'excused':
                                                    $status_class = 'bg-blue-100 text-blue-800';
                                                    $status_text = 'Excused';
                                                    break;
                                                default:
                                                    $status_class = 'bg-gray-100 text-gray-800';
                                                    $status_text = 'Unknown';
                                            }
                                            ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Not Recorded
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($afternoon_attendance): ?>
                                        <div class="mt-3 text-sm text-gray-600">
                                            <p>Checked in at: <?php echo date('h:i A', strtotime($afternoon_attendance['time_recorded'])); ?></p>
                                            <p class="mt-1">Date: <?php echo date('M d, Y', strtotime($afternoon_attendance['time_recorded'])); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-3 text-sm text-gray-600">
                                            <p>You have not recorded your attendance for the afternoon session yet.</p>
                                           
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($is_ongoing): ?>
                                <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700">
                                                This event is currently ongoing. Don't forget to scan the QR code for each session.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html> 