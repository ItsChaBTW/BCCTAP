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

// Get attendance trends for the last 4 months (reduced from 6)
$attendanceTrends = [];
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

// Get attendance status distribution for current month
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

// Prepare status data for chart
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

// Get recent events
$query = "SELECT e.*, u.full_name as created_by_name 
          FROM events e 
          INNER JOIN users u ON e.created_by = u.id 
          ORDER BY e.created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
$recentEvents = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get recent attendance records
$query = "SELECT a.*, u.full_name as student_name, e.title as event_title 
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
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Statistic Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <div class="flex items-center p-6 bg-white rounded-2xl shadow group hover:shadow-lg transition">
    <div class="flex-shrink-0 w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mr-4">
      <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/>
        <path d="M4 20v-1c0-2.21 3.58-4 8-4s8 1.79 8 4v1"/>
      </svg>
    </div>
    <div>
      <div class="text-gray-500 text-xs font-semibold uppercase">Total Students</div>
      <div id="total-students" class="text-2xl font-bold text-gray-800"><?php echo $totalStudents; ?></div>
    </div>
  </div>
  <div class="flex items-center p-6 bg-white rounded-2xl shadow group hover:shadow-lg transition">
    <div class="flex-shrink-0 w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center mr-4">
      <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/>
        <path d="M4 20v-1c0-2.21 3.58-4 8-4s8 1.79 8 4v1"/>
      </svg>
    </div>
    <div>
      <div class="text-gray-500 text-xs font-semibold uppercase">Total Teachers</div>
      <div id="total-teachers" class="text-2xl font-bold text-gray-800"><?php echo $totalTeachers; ?></div>
    </div>
  </div>
  <div class="flex items-center p-6 bg-white rounded-2xl shadow group hover:shadow-lg transition">
    <div class="flex-shrink-0 w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
      <svg class="w-7 h-7 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
    </div>
    <div>
      <div class="text-gray-500 text-xs font-semibold uppercase">Total Events</div>
      <div id="total-events" class="text-2xl font-bold text-gray-800"><?php echo $totalEvents; ?></div>
    </div>
  </div>
  <div class="flex items-center p-6 bg-white rounded-2xl shadow group hover:shadow-lg transition">
    <div class="flex-shrink-0 w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center mr-4">
      <svg class="w-7 h-7 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M9 17v-2a4 4 0 018 0v2"/>
        <path d="M12 12a4 4 0 100-8 4 4 0 000 8z"/>
      </svg>
    </div>
    <div>
      <div class="text-gray-500 text-xs font-semibold uppercase">Attendance Records</div>
      <div id="total-attendance" class="text-2xl font-bold text-gray-800"><?php echo $totalAttendance; ?></div>
    </div>
  </div>
</div>
<div class="bg-white rounded-2xl shadow p-6 mb-8">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-semibold text-gray-800">Quick Actions</h3>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
    <a href="events/create.php" class="flex items-center justify-center gap-2 p-4 rounded-xl bg-green-600 text-white font-semibold shadow hover:bg-green-700 transition">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 4v16m8-8H4"/>
      </svg>
      Create New Event
    </a>
    <a href="users/create.php" class="flex items-center justify-center gap-2 p-4 rounded-xl bg-blue-600 text-white font-semibold shadow hover:bg-blue-700 transition">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M16 21v-2a4 4 0 00-8 0v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Add New User
    </a>
    <a href="qrcodes/index.php" class="flex items-center justify-center gap-2 p-4 rounded-xl bg-yellow-500 text-white font-semibold shadow hover:bg-yellow-600 transition">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <rect x="3" y="3" width="7" height="7" rx="1.5"/>
        <rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="14" y="14" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/>
      </svg>
      Generate QR Codes
    </a>
    <a href="users/devices.php" class="flex items-center justify-center gap-2 p-4 rounded-xl bg-purple-600 text-white font-semibold shadow hover:bg-purple-700 transition">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <rect x="2" y="7" width="20" height="14" rx="2"/>
        <path d="M16 3v4M8 3v4"/>
      </svg>
      Manage Student Devices
    </a>
  </div>
</div>
<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
  <!-- Attendance Trends Chart -->
  <div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <h3 class="text-lg font-semibold text-gray-800">Attendance Trends (Last 4 Months)</h3>
        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse" title="Live Updates"></div>
      </div>
      <div class="text-sm text-gray-500">
        Total: <span id="trends-total"><?php echo array_sum($trendData); ?></span> records
      </div>
    </div>
    <div class="h-64 relative">
      <canvas id="attendanceTrendsChart"></canvas>
    </div>
  </div>
  <!-- Attendance Status Distribution Chart -->
  <div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <h3 class="text-lg font-semibold text-gray-800">Attendance Status (This Month)</h3>
        <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse" title="Live Updates"></div>
      </div>
      <div class="text-sm text-gray-500">
        <?php echo date('F Y'); ?>
      </div>
    </div>
    <div class="h-64 relative">
      <canvas id="statusChart"></canvas>
    </div>
  </div>
</div>

<!-- Recent Events & Attendance -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
  <!-- Recent Events -->
  <div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Recent Events</h3>
      <a href="events/index.php" class="text-green-600 hover:text-green-800 text-sm font-medium">View All</a>
    </div>
    <div class="space-y-4">
      <?php foreach ($recentEvents as $event): ?>
        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50 hover:shadow transition">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h4>
              <div class="text-xs text-gray-500 mt-1">
                <?php echo date('M d, Y', strtotime($event['start_date'])); ?> - 
                <?php echo date('M d, Y', strtotime($event['end_date'])); ?>
              </div>
            </div>
            <div class="flex flex-col items-end text-xs text-gray-400">
              <span>By: <?php echo htmlspecialchars($event['created_by_name']); ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (count($recentEvents) === 0): ?>
        <div class="text-center text-gray-400 py-8">No events created yet.</div>
      <?php endif; ?>
    </div>
  </div>
  <!-- Recent Attendance -->
  <div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Recent Attendance</h3>
      <a href="reports/attendance.php" class="text-green-600 hover:text-green-800 text-sm font-medium">View All</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-gray-500 uppercase text-xs">
            <th class="px-4 py-2">Student</th>
            <th class="px-4 py-2">Event</th>
            <th class="px-4 py-2">Session</th>
            <th class="px-4 py-2">Status</th>
          </tr>
        </thead>
        <tbody id="recent-attendance-tbody">
          <?php foreach ($recentAttendance as $attendance): ?>
            <tr class="hover:bg-green-50 transition">
              <td class="px-4 py-3"><?php echo htmlspecialchars($attendance['student_name']); ?></td>
              <td class="px-4 py-3"><?php echo htmlspecialchars($attendance['event_title']); ?></td>
              <td class="px-4 py-3"><?php echo ucfirst($attendance['session']); ?></td>
              <td class="px-4 py-3">
                <?php 
                  $status = str_replace('_', ' ', ucfirst($attendance['status']));
                  $statusColor = '';
                  if (stripos($status, 'present') !== false) $statusColor = 'bg-green-100 text-green-800';
                  elseif (stripos($status, 'late') !== false) $statusColor = 'bg-yellow-100 text-yellow-800';
                  elseif (stripos($status, 'absent') !== false) $statusColor = 'bg-red-100 text-red-800';
                ?>
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColor; ?>">
                  <?php echo $status; ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($recentAttendance) === 0): ?>
            <tr><td colspan="4" class="text-center text-gray-400 py-8">No attendance records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Quick Actions -->


<script>
// Attendance Trends Chart with live data
const attendanceTrendsCtx = document.getElementById('attendanceTrendsChart').getContext('2d');
const attendanceTrendsChart = new Chart(attendanceTrendsCtx, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($trendLabels); ?>,
    datasets: [{
      label: 'Attendance Records',
      data: <?php echo json_encode($trendData); ?>,
      borderColor: '#16a34a',
      backgroundColor: 'rgba(22,163,74,0.1)',
      tension: 0.4,
      fill: true,
      pointRadius: 6,
      pointBackgroundColor: '#16a34a',
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointHoverRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { 
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        titleColor: '#ffffff',
        bodyColor: '#ffffff',
        borderColor: '#16a34a',
        borderWidth: 1
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(0,0,0,0.1)'
        }
      },
      x: {
        grid: {
          display: false
        }
      }
    }
  }
});

// Attendance Status Distribution Chart with live data
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($statusLabels); ?>,
    datasets: [{
      data: <?php echo json_encode($statusData); ?>,
      backgroundColor: <?php echo json_encode($statusColors); ?>,
      borderWidth: 3,
      borderColor: '#ffffff',
      hoverBorderWidth: 4
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: { 
      legend: { 
        position: 'bottom',
        labels: {
          padding: 20,
          usePointStyle: true,
          font: {
            size: 12
          }
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0,0,0,0.8)',
        titleColor: '#ffffff',
        bodyColor: '#ffffff',
        borderWidth: 1,
        callbacks: {
          label: function(context) {
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = ((context.parsed / total) * 100).toFixed(1);
            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
          }
        }
      }
    }
  }
});
</script>

<!-- Real-time Dashboard Updates -->
<script>
// Real-time updates using Server-Sent Events
let eventSource;
let connectionStatus = document.createElement('div');
connectionStatus.id = 'connection-status';
connectionStatus.className = 'fixed top-4 right-4 px-3 py-2 rounded-lg text-xs font-semibold z-50 hidden';
document.body.appendChild(connectionStatus);

function showConnectionStatus(message, type = 'info') {
    connectionStatus.textContent = message;
    connectionStatus.className = `fixed top-4 right-4 px-3 py-2 rounded-lg text-xs font-semibold z-50 ${
        type === 'success' ? 'bg-green-100 text-green-800' :
        type === 'error' ? 'bg-red-100 text-red-800' :
        type === 'warning' ? 'bg-yellow-100 text-yellow-800' :
        'bg-blue-100 text-blue-800'
    }`;
    connectionStatus.classList.remove('hidden');
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        connectionStatus.classList.add('hidden');
    }, 3000);
}

function animateNumber(element, newValue) {
    const currentValue = parseInt(element.textContent) || 0;
    if (currentValue === newValue) return;
    
    element.style.color = '#16a34a'; // Green color for updates
    element.textContent = newValue;
    
    // Add pulse effect
    element.style.transform = 'scale(1.1)';
    setTimeout(() => {
        element.style.transform = 'scale(1)';
        element.style.color = ''; // Reset to original color
    }, 300);
}

function formatAttendanceStatus(attendance) {
    let status = attendance.status;
    let statusColor = '';
    
    // Handle "Late Time In" case
    if (attendance.attendance_status === 'late' && attendance.status === 'time_in') {
        status = 'Late Time In';
        statusColor = 'bg-orange-100 text-orange-800';
    } else {
        status = status.replace('_', ' ').split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
        
        if (status.toLowerCase().includes('present')) statusColor = 'bg-green-100 text-green-800';
        else if (status.toLowerCase().includes('late')) statusColor = 'bg-yellow-100 text-yellow-800';
        else if (status.toLowerCase().includes('absent')) statusColor = 'bg-red-100 text-red-800';
        else statusColor = 'bg-gray-100 text-gray-800';
    }
    
    return { status, statusColor };
}

function updateRecentAttendance(attendanceData) {
    const tbody = document.getElementById('recent-attendance-tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (attendanceData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-gray-400 py-8">No attendance records found.</td></tr>';
        return;
    }
    
    attendanceData.forEach(attendance => {
        const { status, statusColor } = formatAttendanceStatus(attendance);
        
        const row = document.createElement('tr');
        row.className = 'hover:bg-green-50 transition';
        row.innerHTML = `
            <td class="px-4 py-3">${attendance.student_name}</td>
            <td class="px-4 py-3">${attendance.event_title}</td>
            <td class="px-4 py-3">${attendance.session.charAt(0).toUpperCase() + attendance.session.slice(1)}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusColor}">
                    ${status}
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    // Add flash effect to indicate update
    tbody.style.backgroundColor = '#f0f9ff';
    setTimeout(() => {
        tbody.style.backgroundColor = '';
    }, 500);
}

function updateCharts(chartData) {
    // Update Attendance Trends Chart
    if (chartData.trends && attendanceTrendsChart) {
        // Add smooth transition effect
        attendanceTrendsChart.data.labels = chartData.trends.labels;
        attendanceTrendsChart.data.datasets[0].data = chartData.trends.data;
        
        // Update the total count display
        const totalCount = chartData.trends.data.reduce((sum, value) => sum + value, 0);
        const totalElement = document.getElementById('trends-total');
        if (totalElement) {
            animateNumber(totalElement, totalCount);
        }
        
        // Animate the chart update
        attendanceTrendsChart.update('active');
        
        // Add visual feedback
        const trendsContainer = document.getElementById('attendanceTrendsChart').parentElement;
        trendsContainer.style.boxShadow = '0 0 20px rgba(22, 163, 74, 0.3)';
        setTimeout(() => {
            trendsContainer.style.boxShadow = '';
        }, 1000);
    }
    
    // Update Attendance Status Distribution Chart
    if (chartData.status && statusChart) {
        // Add smooth transition effect
        statusChart.data.labels = chartData.status.labels;
        statusChart.data.datasets[0].data = chartData.status.data;
        statusChart.data.datasets[0].backgroundColor = chartData.status.colors;
        
        // Animate the chart update
        statusChart.update('active');
        
        // Add visual feedback
        const statusContainer = document.getElementById('statusChart').parentElement;
        statusContainer.style.boxShadow = '0 0 20px rgba(245, 158, 11, 0.3)';
        setTimeout(() => {
            statusContainer.style.boxShadow = '';
        }, 1000);
    }
    
    console.log('✅ Charts updated successfully');
}

function initRealtimeConnection() {
    if (eventSource) {
        eventSource.close();
    }
    
    eventSource = new EventSource('realtime-data.php');
    
    eventSource.onopen = function(e) {
        console.log('✅ Real-time connection established');
        showConnectionStatus('Connected to real-time updates', 'success');
    };
    
    eventSource.addEventListener('stats', function(e) {
        const data = JSON.parse(e.data);
        console.log('📊 Statistics update received:', data.data);
        
        // Update statistics with animation
        animateNumber(document.getElementById('total-students'), data.data.students);
        animateNumber(document.getElementById('total-teachers'), data.data.teachers);
        animateNumber(document.getElementById('total-events'), data.data.events);
        animateNumber(document.getElementById('total-attendance'), data.data.attendance);
        
        showConnectionStatus('Statistics updated', 'info');
    });
    
    eventSource.addEventListener('attendance', function(e) {
        const data = JSON.parse(e.data);
        console.log('👥 Attendance update received:', data.data.length, 'records');
        
        updateRecentAttendance(data.data);
        showConnectionStatus('New attendance record!', 'success');
    });
    
    eventSource.addEventListener('charts', function(e) {
        const data = JSON.parse(e.data);
        console.log('📊 Charts update received:', data.data);
        
        updateCharts(data.data);
        showConnectionStatus('Charts updated!', 'info');
    });
    
    eventSource.addEventListener('heartbeat', function(e) {
        const data = JSON.parse(e.data);
        console.log('💓 Heartbeat received:', new Date(data.timestamp * 1000).toLocaleTimeString());
    });
    
    eventSource.onerror = function(e) {
        console.error('❌ Real-time connection error:', e);
        showConnectionStatus('Connection lost. Reconnecting...', 'error');
        
        // Reconnect after 5 seconds
        setTimeout(() => {
            initRealtimeConnection();
        }, 5000);
    };
}

// Initialize real-time connection when page loads
document.addEventListener('DOMContentLoaded', function() {
    initRealtimeConnection();
    console.log('🚀 Real-time dashboard initialized');
});

// Clean up connection when page unloads
window.addEventListener('beforeunload', function() {
    if (eventSource) {
        eventSource.close();
    }
});
</script>
<?php
$page_content = ob_get_clean();

// Include the admin layout
include '../includes/admin_layout.php';
?> 