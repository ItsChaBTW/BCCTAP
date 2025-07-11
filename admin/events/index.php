<?php
/**
 * Events Management Page for Admins
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Handle delete event request
if (isset($_GET['delete']) && !empty($_GET['id'])) {
    $event_id = intval($_GET['id']);
    
    // Get QR code image paths before deleting
    $qr_query = "SELECT image_path FROM qr_codes WHERE event_id = ?";
    $qr_stmt = mysqli_prepare($conn, $qr_query);
    mysqli_stmt_bind_param($qr_stmt, "i", $event_id);
    mysqli_stmt_execute($qr_stmt);
    $qr_result = mysqli_stmt_get_result($qr_stmt);
    
    // Delete QR code images from filesystem
    while ($qr = mysqli_fetch_assoc($qr_result)) {
        if (!empty($qr['image_path']) && file_exists('../../' . $qr['image_path'])) {
            unlink('../../' . $qr['image_path']);
        }
    }

    // Delete event and related records
    mysqli_begin_transaction($conn);
    
    try {
        // Delete QR codes
        $delete_qr = "DELETE FROM qr_codes WHERE event_id = ?";
        $stmt_qr = mysqli_prepare($conn, $delete_qr);
        mysqli_stmt_bind_param($stmt_qr, "i", $event_id);
        mysqli_stmt_execute($stmt_qr);
        
        // Delete attendance records
        $delete_attendance = "DELETE FROM attendance WHERE event_id = ?";
        $stmt_attendance = mysqli_prepare($conn, $delete_attendance);
        mysqli_stmt_bind_param($stmt_attendance, "i", $event_id);
        mysqli_stmt_execute($stmt_attendance);
        
        // Delete event
        $delete_event = "DELETE FROM events WHERE id = ?";
        $stmt_event = mysqli_prepare($conn, $delete_event);
        mysqli_stmt_bind_param($stmt_event, "i", $event_id);
        mysqli_stmt_execute($stmt_event);
        
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Event and all related data deleted successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Failed to delete event: " . $e->getMessage();
    }
    
    redirect(BASE_URL . 'admin/events/index.php');
}

// Get all events
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id) as attendance_count,
          (SELECT COUNT(*) FROM qr_codes q WHERE q.event_id = e.id) as qr_count,
          u.full_name as created_by_name
          FROM events e 
          LEFT JOIN users u ON e.created_by = u.id
          ORDER BY e.start_date DESC";

$result = mysqli_query($conn, $query);
$events = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Set page title and actions for admin layout
$page_title = "Events Management";
$page_actions = '<a href="create.php" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:opacity-90 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
    </svg>
    Create New Event
</a>';

// Start output buffering
ob_start();
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-8">
    <div>
        <p class="text-gray-600">Create, edit and manage attendance events</p>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800"><?php echo $_SESSION['success_message']; ?></p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="close-alert inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800"><?php echo $_SESSION['error_message']; ?></p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="close-alert inline-flex bg-red-50 rounded-md p-1.5 text-red-500 hover:bg-red-100">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<?php
// Calculate analytics
$totalEvents = count($events);
$totalAttendance = array_sum(array_column($events, 'attendance_count'));
$upcomingEvents = 0;
$today = strtotime(date('Y-m-d'));
foreach ($events as $event) {
    if (strtotime($event['start_date']) >= $today) $upcomingEvents++;
}
$departments = array_unique(array_filter(array_map(function($e){return $e['department'];}, $events)));
?>

<!-- Dashboard Analytics -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
  <div class="flex items-center p-6 bg-white rounded-2xl shadow group hover:shadow-lg transition">
    <div class="flex-shrink-0 w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center mr-4">
      <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    </div>
    <div>
      <div class="text-gray-500 text-xs font-semibold uppercase">Total Events</div>
      <div class="text-2xl font-bold text-gray-800"><?php echo $totalEvents; ?></div>
    </div>
  </div>
  <div class="flex items-center p-6 bg-white rounded-2xl shadow group hover:shadow-lg transition">
    <div class="flex-shrink-0 w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mr-4">
      <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2a4 4 0 018 0v2"/><path d="M12 12a4 4 0 100-8 4 4 0 000 8z"/></svg>
    </div>
    <div>
      <div class="text-gray-500 text-xs font-semibold uppercase">Total Attendance</div>
      <div class="text-2xl font-bold text-gray-800"><?php echo $totalAttendance; ?></div>
    </div>
  </div>
  <div class="flex items-center p-6 bg-white rounded-2xl shadow group hover:shadow-lg transition">
    <div class="flex-shrink-0 w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
      <svg class="w-7 h-7 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
    </div>
    <div>
      <div class="text-gray-500 text-xs font-semibold uppercase">Upcoming Events</div>
      <div class="text-2xl font-bold text-gray-800"><?php echo $upcomingEvents; ?></div>
    </div>
  </div>
</div>

<!-- Search & Filter -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
  <div class="flex gap-2 w-full md:w-auto">
    <input id="eventSearch" type="text" placeholder="Search by title or department..." class="w-full md:w-64 px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-200 focus:border-blue-400 transition text-sm" />
    <select id="departmentFilter" class="px-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-200 focus:border-blue-400 transition text-sm">
      <option value="">All Departments</option>
      <?php foreach ($departments as $dept): ?>
        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mt-2 md:mt-0 flex gap-2">
    <?php echo $page_actions; ?>
  </div>
</div>

<!-- Events Table -->
<div class="bg-white rounded-2xl shadow-lg overflow-x-auto border border-gray-100">
  <?php if (count($events) > 0): ?>
    <table id="eventsTable" class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Event</th>
          <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Dates</th>
          <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Department</th>
          <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Stats</th>
          <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Created By</th>
          <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($events as $event): ?>
        <tr class="hover:bg-blue-50 transition">
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-base font-semibold text-gray-900 flex items-center gap-2">
              <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <?php echo htmlspecialchars($event['title']); ?>
            </div>
            <div class="text-xs text-gray-500 mt-1"><?php echo substr(htmlspecialchars($event['description']), 0, 50); ?><?php echo strlen($event['description']) > 50 ? '...' : ''; ?></div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex flex-col">
              <?php 
                $start_timestamp = strtotime($event['start_date']);
                $end_timestamp = strtotime($event['end_date']);
                $current_timestamp = time();
                
                // Determine event status
                $status = '';
                $status_color = '';
                if ($current_timestamp < $start_timestamp) {
                    $status = 'Upcoming';
                    $status_color = 'blue';
                } elseif ($current_timestamp > $end_timestamp) {
                    $status = 'Past';
                    $status_color = 'gray';
                } else {
                    $status = 'Ongoing';
                    $status_color = 'green';
                }
              ?>
              <div class="mb-2">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                  <?php echo $status; ?>
                </span>
              </div>
              <span class="text-sm text-gray-900 font-medium">
                <span class="inline-flex items-center">
                  <svg class="h-4 w-4 mr-1 text-<?php echo $status_color; ?>-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                  </svg>
                  <?php 
                    // If start and end dates are in the same month and year
                    if (date('Y-m', $start_timestamp) === date('Y-m', $end_timestamp)) {
                      echo date('M j', $start_timestamp) . ' - ' . date('j, Y', $end_timestamp);
                    } else {
                      echo date('M j, Y', $start_timestamp) . ' - ' . date('M j, Y', $end_timestamp);
                    }
                  ?>
                </span>
              </span>
              <div class="mt-2 grid grid-cols-1 gap-1 text-xs text-gray-500">
                <span class="inline-flex items-center">
                  <svg class="h-3 w-3 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12zm1-6.41V4a1 1 0 10-2 0v4c0 .28.11.53.29.71l2.8 2.8a1 1 0 001.42-1.42L11 7.59z"/>
                  </svg>
                  Morning: <?php echo date('h:i A', strtotime($event['morning_time_in'])); ?> - <?php echo date('h:i A', strtotime($event['morning_time_out'])); ?>
                </span>
                <span class="inline-flex items-center">
                  <svg class="h-3 w-3 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12zm1-6.41V4a1 1 0 10-2 0v4c0 .28.11.53.29.71l2.8 2.8a1 1 0 001.42-1.42L11 7.59z"/>
                  </svg>
                  Afternoon: <?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?> - <?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?>
                </span>
              </div>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <?php if (!empty($event['department'])): ?>
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                <?php echo htmlspecialchars($event['department']); ?>
              </span>
            <?php else: ?>
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                All Departments
              </span>
            <?php endif; ?>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center space-x-4">
              <div class="flex flex-col items-center">
                <span class="text-sm font-medium text-gray-900"><?php echo $event['attendance_count']; ?></span>
                <span class="text-xs text-gray-500">Attendance</span>
              </div>
              <div class="flex flex-col items-center">
                <span class="text-sm font-medium text-gray-900"><?php echo $event['qr_count']; ?></span>
                <span class="text-xs text-gray-500">QR Codes</span>
              </div>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            <?php echo htmlspecialchars($event['created_by_name']); ?>
            <div class="text-xs"><?php echo date('M d, Y', strtotime($event['created_at'])); ?></div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
            <div class="flex justify-center space-x-2">
              <a href="view.php?id=<?php echo $event['id']; ?>" class="text-blue-500 hover:text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-full p-1.5 transition" title="View Event">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                  <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                  <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                </svg>
              </a>
              <a href="edit.php?id=<?php echo $event['id']; ?>" class="text-green-500 hover:text-green-600 bg-green-50 hover:bg-green-100 rounded-full p-1.5 transition" title="Edit Event">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                  <path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" />
                  <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" />
                </svg>
              </a>
              <a href="../qrcodes/view.php?id=<?php echo $event['id']; ?>" class="text-yellow-500 hover:text-yellow-600 bg-yellow-50 hover:bg-yellow-100 rounded-full p-1.5 transition" title="View QR Codes">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                  <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
                </svg>
              </a>
              <a href="?delete=1&id=<?php echo $event['id']; ?>" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 rounded-full p-1.5 transition" title="Delete Event" onclick="return confirm('Are you sure you want to delete this event? This will also delete all related QR codes and attendance records.')">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <!-- Pagination UI -->
    <div id="pagination" class="flex justify-center items-center gap-2 py-4"></div>
  <?php else: ?>
    <div class="text-center py-16">
      <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No events</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating a new event.</p>
      <div class="mt-6">
        <a href="create.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gradient-to-r from-blue-600 to-blue-800 hover:opacity-90">
          <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
          </svg>
          New Event
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
  <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex items-center">
      <div class="bg-blue-100 p-3 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
      </div>
      <div class="ml-4">
        <h3 class="text-lg font-semibold text-gray-900">View Attendance</h3>
        <p class="text-gray-600 text-sm mt-1">Check attendance records for all events</p>
      </div>
    </div>
    <div class="mt-4">
      <a href="../reports/attendance.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center transition">
        View Records
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </a>
    </div>
  </div>
  <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex items-center">
      <div class="bg-blue-100 p-3 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
        </svg>
      </div>
      <div class="ml-4">
        <h3 class="text-lg font-semibold text-gray-900">QR Codes</h3>
        <p class="text-gray-600 text-sm mt-1">Manage QR codes for your events</p>
      </div>
    </div>
    <div class="mt-4">
      <a href="../qrcodes/index.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center transition">
        Manage QR Codes
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </a>
    </div>
  </div>
  <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <div class="flex items-center">
      <div class="bg-blue-100 p-3 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      </div>
      <div class="ml-4">
        <h3 class="text-lg font-semibold text-gray-900">Students</h3>
        <p class="text-gray-600 text-sm mt-1">Manage student accounts</p>
      </div>
    </div>
    <div class="mt-4">
      <a href="../users/index.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center transition">
        Manage Students
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </a>
    </div>
  </div>
</div>

<script>
// Search & Filter
const searchInput = document.getElementById('eventSearch');
const deptFilter = document.getElementById('departmentFilter');
const table = document.getElementById('eventsTable');
const rows = table ? Array.from(table.tBodies[0].rows) : [];
function filterTable() {
  const search = searchInput.value.toLowerCase();
  const dept = deptFilter.value.toLowerCase();
  let visibleRows = 0;
  rows.forEach(row => {
    const title = row.cells[0].innerText.toLowerCase();
    const department = row.cells[2].innerText.toLowerCase();
    const match = (!search || title.includes(search) || department.includes(search)) && (!dept || department === dept);
    row.style.display = match ? '' : 'none';
    if (match) visibleRows++;
  });
  paginate(1, visibleRows);
}
searchInput && searchInput.addEventListener('input', filterTable);
deptFilter && deptFilter.addEventListener('change', filterTable);

// Pagination
const pagination = document.getElementById('pagination');
const pageSize = 10;
function paginate(page = 1, visibleRows = null) {
  if (!rows.length) return;
  let filteredRows = rows.filter(row => row.style.display !== 'none');
  visibleRows = visibleRows !== null ? visibleRows : filteredRows.length;
  const totalPages = Math.ceil(visibleRows / pageSize);
  filteredRows.forEach((row, i) => {
    row.style.display = (i >= (page-1)*pageSize && i < page*pageSize) ? '' : 'none';
  });
  // Pagination UI
  if (pagination) {
    pagination.innerHTML = '';
    if (totalPages > 1) {
      for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = 'px-3 py-1 rounded-lg mx-1 text-sm font-semibold ' + (i === page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-blue-100');
        btn.onclick = () => paginate(i);
        pagination.appendChild(btn);
      }
    }
  }
}
if (rows.length > pageSize) paginate(1);
</script>

<?php
$page_content = ob_get_clean();

// Include admin layout
require_once '../../includes/admin_layout.php';
?> 