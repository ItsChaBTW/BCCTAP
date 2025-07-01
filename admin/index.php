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
      <div class="text-2xl font-bold text-gray-800"><?php echo $totalStudents; ?></div>
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
      <div class="text-2xl font-bold text-gray-800"><?php echo $totalTeachers; ?></div>
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
      <div class="text-2xl font-bold text-gray-800"><?php echo $totalEvents; ?></div>
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
      <div class="text-2xl font-bold text-gray-800"><?php echo $totalAttendance; ?></div>
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
      <h3 class="text-lg font-semibold text-gray-800">Attendance Trends</h3>
    </div>
    <canvas id="attendanceTrendsChart" height="120"></canvas>
  </div>
  <!-- Student vs Teacher Ratio Pie Chart -->
  <div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Student vs Teacher Ratio</h3>
    </div>
    <canvas id="ratioChart" height="120"></canvas>
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
        <tbody>
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
// Example Chart.js data (replace with PHP data as needed)
const attendanceTrendsCtx = document.getElementById('attendanceTrendsChart').getContext('2d');
const attendanceTrendsChart = new Chart(attendanceTrendsCtx, {
  type: 'line',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], // Example months
    datasets: [{
      label: 'Attendance',
      data: [120, 150, 170, 140, 180, 200], // Replace with PHP data if needed
      borderColor: '#16a34a',
      backgroundColor: 'rgba(22,163,74,0.1)',
      tension: 0.4,
      fill: true,
      pointRadius: 4,
      pointBackgroundColor: '#16a34a'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } }
  }
});
const ratioCtx = document.getElementById('ratioChart').getContext('2d');
const ratioChart = new Chart(ratioCtx, {
  type: 'doughnut',
  data: {
    labels: ['Students', 'Teachers'],
    datasets: [{
      data: [<?php echo $totalStudents; ?>, <?php echo $totalTeachers; ?>],
      backgroundColor: ['#16a34a', '#3b82f6'],
      borderWidth: 2
    }]
  },
  options: {
    cutout: '70%',
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>
<?php
$page_content = ob_get_clean();

// Include the admin layout
include '../includes/admin_layout.php';
?> 