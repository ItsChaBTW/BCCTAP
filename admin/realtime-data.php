<?php
/**
 * Real-time data endpoint using Server-Sent Events
 * Streams live updates to the admin dashboard
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit;
}

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Prevent timeout
set_time_limit(0);
ini_set('memory_limit', '512M');

// Function to send SSE data
function sendSSE($id, $data, $event = null) {
    if ($event) {
        echo "event: $event\n";
    }
    echo "id: $id\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Store last known counts to detect changes
$lastCounts = [
    'students' => 0,
    'teachers' => 0,
    'events' => 0,
    'attendance' => 0,
    'recent_attendance_count' => 0
];

$eventId = 1;

// Main loop
while (true) {
    // Check if client is still connected
    if (connection_aborted()) {
        break;
    }
    
    // Get current statistics
    $currentCounts = [];
    
    // Total students
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
    $result = mysqli_query($conn, $query);
    $currentCounts['students'] = mysqli_fetch_assoc($result)['total'];
    
    // Total teachers  
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'teacher'";
    $result = mysqli_query($conn, $query);
    $currentCounts['teachers'] = mysqli_fetch_assoc($result)['total'];
    
    // Total events
    $query = "SELECT COUNT(*) as total FROM events";
    $result = mysqli_query($conn, $query);
    $currentCounts['events'] = mysqli_fetch_assoc($result)['total'];
    
    // Total attendance records
    $query = "SELECT COUNT(*) as total FROM attendance";
    $result = mysqli_query($conn, $query);
    $currentCounts['attendance'] = mysqli_fetch_assoc($result)['total'];
    
    // Recent attendance count (last 5 minutes)
    $query = "SELECT COUNT(*) as total FROM attendance WHERE time_recorded >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
    $result = mysqli_query($conn, $query);
    $currentCounts['recent_attendance_count'] = mysqli_fetch_assoc($result)['total'];
    
    // Get chart data for realtime updates
    $chartData = [];
    
    // Attendance trends for the last 4 months
    $trendLabels = [];
    $trendData = [];
    for ($i = 3; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M Y', strtotime("-$i months"));
        
        $query = "SELECT COUNT(*) as count FROM attendance WHERE DATE_FORMAT(time_recorded, '%Y-%m') = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $month);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        
        $trendLabels[] = $monthLabel;
        $trendData[] = $count;
    }
    $chartData['trends'] = ['labels' => $trendLabels, 'data' => $trendData];
    
    // Attendance status distribution for current month
    $currentMonth = date('Y-m');
    $query = "SELECT 
                attendance_status,
                COUNT(*) as count 
              FROM attendance 
              WHERE DATE_FORMAT(time_recorded, '%Y-%m') = ? 
              GROUP BY attendance_status";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $currentMonth);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $statusDistribution = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    $statusLabels = [];
    $statusData = [];
    $statusColors = [];
    foreach ($statusDistribution as $status) {
        $statusLabels[] = ucfirst($status['attendance_status']);
        $statusData[] = $status['count'];
        
        // Assign colors based on status
        switch ($status['attendance_status']) {
            case 'present':
                $statusColors[] = '#16a34a'; // Green
                break;
            case 'late':
                $statusColors[] = '#f59e0b'; // Yellow
                break;
            case 'absent':
                $statusColors[] = '#dc2626'; // Red
                break;
            default:
                $statusColors[] = '#6b7280'; // Gray
        }
    }
    
    // If no status data exists, show default
    if (empty($statusData)) {
        $statusLabels = ['No Data'];
        $statusData = [1];
        $statusColors = ['#e5e7eb'];
    }
    
    $chartData['status'] = [
        'labels' => $statusLabels, 
        'data' => $statusData, 
        'colors' => $statusColors
    ];
    
    // Check for changes
    $hasChanges = false;
    foreach ($currentCounts as $key => $value) {
        if ($lastCounts[$key] != $value) {
            $hasChanges = true;
            break;
        }
    }
    
    // Send updates if there are changes
    if ($hasChanges) {
        // Send updated statistics
        sendSSE($eventId++, [
            'type' => 'stats_update',
            'data' => $currentCounts
        ], 'stats');
        
        // If attendance changed, send recent attendance records
        if ($lastCounts['attendance'] != $currentCounts['attendance']) {
            $query = "SELECT a.id, a.user_id, a.event_id, a.session, a.status, a.attendance_status, a.time_recorded,
                             u.full_name as student_name, e.title as event_title 
                      FROM attendance a 
                      INNER JOIN users u ON a.user_id = u.id 
                      INNER JOIN events e ON a.event_id = e.id 
                      ORDER BY a.time_recorded DESC LIMIT 10";
            $result = mysqli_query($conn, $query);
            $recentAttendance = mysqli_fetch_all($result, MYSQLI_ASSOC);
            
            sendSSE($eventId++, [
                'type' => 'attendance_update',
                'data' => $recentAttendance
            ], 'attendance');
            
            // Send updated chart data when attendance changes
            sendSSE($eventId++, [
                'type' => 'charts_update',
                'data' => $chartData
            ], 'charts');
        }
        
        $lastCounts = $currentCounts;
    }
    
    // Send heartbeat every 30 seconds
    sendSSE($eventId++, [
        'type' => 'heartbeat',
        'timestamp' => time()
    ], 'heartbeat');
    
    // Wait 2 seconds before next check
    sleep(2);
}
?> 