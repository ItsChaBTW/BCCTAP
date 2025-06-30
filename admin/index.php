<?php
/**
 * Admin Dashboard
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Get system statistics
// Total students
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$result = mysqli_query($conn, $query);
$totalStudents = mysqli_fetch_assoc($result)['total'];

// Total teachers
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'teacher'";
$result = mysqli_query($conn, $query);
$totalTeachers = mysqli_fetch_assoc($result)['total'];

// Total events
$query = "SELECT COUNT(*) as total FROM events";
$result = mysqli_query($conn, $query);
$totalEvents = mysqli_fetch_assoc($result)['total'];

// Total attendance records
$query = "SELECT COUNT(*) as total FROM attendance";
$result = mysqli_query($conn, $query);
$totalAttendance = mysqli_fetch_assoc($result)['total'];

// Get recent events
$query = "SELECT e.*, u.full_name as created_by_name 
          FROM events e 
          INNER JOIN users u ON e.created_by = u.id 
          ORDER BY e.created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
$recentEvents = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get recent attendance records
$query = "SELECT a.id, a.user_id, a.event_id, a.session, a.status, a.attendance_status, a.time_recorded, 
                 u.full_name as student_name, e.title as event_title 
          FROM attendance a 
          INNER JOIN users u ON a.user_id = u.id 
          INNER JOIN events e ON a.event_id = e.id 
          ORDER BY a.time_recorded DESC LIMIT 10";
$result = mysqli_query($conn, $query);
$recentAttendance = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Set the page title
$page_title = "Dashboard";

// Page content
ob_start();
?>
<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="stat-card blue">
        <h3>Total Students</h3>
        <div class="value"><?php echo $totalStudents; ?></div>
    </div>
    
    <div class="stat-card green">
        <h3>Total Teachers</h3>
        <div class="value"><?php echo $totalTeachers; ?></div>
    </div>
    
    <div class="stat-card yellow">
        <h3>Total Events</h3>
        <div class="value"><?php echo $totalEvents; ?></div>
    </div>
    
    <div class="stat-card purple">
        <h3>Attendance Records</h3>
        <div class="value"><?php echo $totalAttendance; ?></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Recent Events -->
    <div class="content-panel">
        <div class="panel-header">
            <h2>Recent Events</h2>
            <a href="events/index.php">View All</a>
        </div>
        <div class="panel-body">
            <?php if (count($recentEvents) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($recentEvents as $event): ?>
                        <div class="border border-gray-200 p-4 rounded-md hover:shadow-md transition-shadow">
                            <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="text-sm text-gray-600 mt-1">
                                <?php echo date('M d, Y', strtotime($event['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($event['end_date'])); ?>
                            </p>
                            <div class="mt-2 text-sm">
                                <p>Morning: <?php echo date('h:i A', strtotime($event['morning_time_in'])); ?> - 
                                   <?php echo date('h:i A', strtotime($event['morning_time_out'])); ?></p>
                                <p>Afternoon: <?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?> - 
                                   <?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?></p>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                Created by: <?php echo htmlspecialchars($event['created_by_name']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No events created yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Attendance -->
    <div class="content-panel">
        <div class="panel-header">
            <h2>Recent Attendance</h2>
            <a href="reports/attendance.php">View All</a>
        </div>
        <div class="panel-body">
            <?php if (count($recentAttendance) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <th class="px-4 py-2 border-b">Student</th>
                                <th class="px-4 py-2 border-b">Event</th>
                                <th class="px-4 py-2 border-b">Session</th>
                                <th class="px-4 py-2 border-b">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($recentAttendance as $attendance): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 border-b border-gray-100"><?php echo htmlspecialchars($attendance['student_name']); ?></td>
                                    <td class="px-4 py-3 border-b border-gray-100"><?php echo htmlspecialchars($attendance['event_title']); ?></td>
                                    <td class="px-4 py-3 border-b border-gray-100"><?php echo ucfirst($attendance['session']); ?></td>
                                    <td class="px-4 py-3 border-b border-gray-100">
                                        <?php 
                                        $attendance_status = $attendance['attendance_status'] ?? 'present';
                                        $statusColor = '';
                                        $statusText = '';
                                        
                                        switch ($attendance_status) {
                                            case 'present':
                                                $statusColor = 'bg-green-100 text-green-800';
                                                $statusText = 'Present';
                                                break;
                                            case 'late':
                                                if ($record['status'] === 'time_in') {
                                                    $statusColor = 'bg-orange-100 text-orange-800';
                                                    $statusText = 'Late Time In';
                                                } else {
                                                    $statusColor = 'bg-yellow-100 text-yellow-800';
                                                    $statusText = 'Late';
                                                }
                                                break;
                                            case 'absent':
                                                $statusColor = 'bg-red-100 text-red-800';
                                                $statusText = 'Absent';
                                                break;
                                            default:
                                                $statusColor = 'bg-gray-100 text-gray-800';
                                                $statusText = ucfirst(str_replace('_', ' ', $attendance_status));
                                                break;
                                        }
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No attendance records found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="content-panel">
    <div class="panel-header">
        <h2>Quick Actions</h2>
    </div>
    <div class="panel-body">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="events/create.php" class="action-button primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                <span>Create New Event</span>
            </a>
            <a href="users/create.php" class="action-button success">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                </svg>
                <span>Add New User</span>
            </a>
            <a href="qrcodes/index.php" class="action-button secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                </svg>
                <span>Generate QR Codes</span>
            </a>
            <a href="users/devices.php" class="action-button warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <span>Manage Student Devices</span>
            </a>
        </div>
    </div>
</div>

<!-- Real-time Status Indicator -->
<div id="realtime-status" class="fixed bottom-4 right-4 bg-white rounded-lg shadow-lg p-3 border-l-4 border-green-500 hidden">
    <div class="flex items-center">
        <div id="status-indicator" class="w-3 h-3 rounded-full bg-green-500 mr-2 animate-pulse"></div>
        <span id="status-text" class="text-sm font-medium text-gray-700">Live Updates Active</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time updates using Server-Sent Events
    let eventSource;
    let reconnectInterval = 3000; // 3 seconds
    let maxReconnectAttempts = 5;
    let reconnectAttempts = 0;
    
    function connectEventSource() {
        // Close existing connection if any
        if (eventSource) {
            eventSource.close();
        }
        
        eventSource = new EventSource('realtime-data.php');
        
        eventSource.onopen = function() {
            console.log('Real-time connection established');
            reconnectAttempts = 0;
            updateConnectionStatus('connected', 'Live Updates Active');
        };
        
        eventSource.addEventListener('stats', function(event) {
            const data = JSON.parse(event.data);
            updateStatistics(data.data);
        });
        
        eventSource.addEventListener('attendance', function(event) {
            const data = JSON.parse(event.data);
            updateAttendanceTable(data.data);
            showNotification('New attendance record added!', 'success');
        });
        
        eventSource.addEventListener('heartbeat', function(event) {
            // Update last heartbeat time
            console.log('Heartbeat received');
        });
        
        eventSource.onerror = function(event) {
            console.error('Real-time connection error:', event);
            updateConnectionStatus('error', 'Connection Lost');
            
            if (reconnectAttempts < maxReconnectAttempts) {
                setTimeout(() => {
                    reconnectAttempts++;
                    updateConnectionStatus('reconnecting', 'Reconnecting...');
                    connectEventSource();
                }, reconnectInterval);
            } else {
                updateConnectionStatus('failed', 'Real-time Updates Disabled');
            }
        };
    }
    
    function updateStatistics(stats) {
        // Update stat cards with smooth animation
        animateValueChange('.stat-card.blue .value', stats.students);
        animateValueChange('.stat-card.green .value', stats.teachers);
        animateValueChange('.stat-card.yellow .value', stats.events);
        animateValueChange('.stat-card.purple .value', stats.attendance);
    }
    
    function animateValueChange(selector, newValue) {
        const element = document.querySelector(selector);
        if (element && element.textContent != newValue) {
            element.style.transform = 'scale(1.1)';
            element.style.color = '#10B981'; // Green color
            element.textContent = newValue;
            
            setTimeout(() => {
                element.style.transform = 'scale(1)';
                element.style.color = '';
            }, 300);
        }
    }
    
    function updateAttendanceTable(attendanceData) {
        const tbody = document.querySelector('.content-panel:last-of-type tbody');
        if (!tbody) return;
        
        // Clear existing rows
        tbody.innerHTML = '';
        
        // Add new rows
        attendanceData.forEach(record => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 animate-fade-in';
            
            const attendanceStatus = record.attendance_status || 'present';
            let statusColor = 'bg-gray-100 text-gray-800';
            let statusText = '';
            
            switch (attendanceStatus) {
                case 'present':
                    statusColor = 'bg-green-100 text-green-800';
                    statusText = 'Present';
                    break;
                case 'late':
                    if (record.status === 'time_in') {
                        statusColor = 'bg-orange-100 text-orange-800';
                        statusText = 'Late Time In';
                    } else {
                        statusColor = 'bg-yellow-100 text-yellow-800';
                        statusText = 'Late';
                    }
                    break;
                case 'absent':
                    statusColor = 'bg-red-100 text-red-800';
                    statusText = 'Absent';
                    break;
                default:
                    statusColor = 'bg-gray-100 text-gray-800';
                    statusText = attendanceStatus.charAt(0).toUpperCase() + attendanceStatus.slice(1);
                    break;
            }
            
            row.innerHTML = `
                <td class="px-4 py-3 border-b border-gray-100">${record.student_name}</td>
                <td class="px-4 py-3 border-b border-gray-100">${record.event_title}</td>
                <td class="px-4 py-3 border-b border-gray-100">${record.session.charAt(0).toUpperCase() + record.session.slice(1)}</td>
                <td class="px-4 py-3 border-b border-gray-100">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColor}">
                        ${statusText}
                    </span>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    function updateConnectionStatus(status, message) {
        const statusElement = document.getElementById('realtime-status');
        const indicatorElement = document.getElementById('status-indicator');
        const textElement = document.getElementById('status-text');
        
        statusElement.classList.remove('hidden');
        textElement.textContent = message;
        
        // Reset classes
        indicatorElement.className = 'w-3 h-3 rounded-full mr-2';
        
        switch(status) {
            case 'connected':
                indicatorElement.classList.add('bg-green-500', 'animate-pulse');
                statusElement.className = statusElement.className.replace('border-red-500', '').replace('border-yellow-500', '') + ' border-green-500';
                break;
            case 'reconnecting':
                indicatorElement.classList.add('bg-yellow-500', 'animate-ping');
                statusElement.className = statusElement.className.replace('border-green-500', '').replace('border-red-500', '') + ' border-yellow-500';
                break;
            case 'error':
            case 'failed':
                indicatorElement.classList.add('bg-red-500');
                statusElement.className = statusElement.className.replace('border-green-500', '').replace('border-yellow-500', '') + ' border-red-500';
                break;
        }
    }
    
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 animate-slide-down`;
        
        let bgColor = 'bg-blue-500';
        if (type === 'success') bgColor = 'bg-green-500';
        if (type === 'error') bgColor = 'bg-red-500';
        if (type === 'warning') bgColor = 'bg-yellow-500';
        
        notification.innerHTML = `
            <div class="${bgColor} text-white px-4 py-2 rounded-lg flex items-center">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">×</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    
    // Start real-time connection
    connectEventSource();
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (eventSource) {
            eventSource.close();
        }
    });
});
</script>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slide-down {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

.animate-slide-down {
    animation: slide-down 0.3s ease-out;
}

.stat-card .value {
    transition: transform 0.3s ease, color 0.3s ease;
}
</style>
<?php
$page_content = ob_get_clean();

// Include the admin layout
include '../includes/admin_layout.php';
?> 