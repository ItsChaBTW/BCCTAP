<?php
/**
 * Student Events Page
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    redirect(BASE_URL . 'student/login.php');
}

// Get student data
$user_id = $_SESSION['user_id'];
$student_department = isset($_SESSION['program']) ? $_SESSION['program'] : '';

// Get current date
$today = date('Y-m-d');

// Initialize filter variables
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_department = isset($_GET['department']) ? $_GET['department'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT e.*, u.full_name as created_by_name 
          FROM events e 
          LEFT JOIN users u ON e.created_by = u.id
          WHERE e.end_date >= ?"; // Only show ongoing and upcoming events
$params = [$today];
$types = "s";

// Add search condition if search term is provided
if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Add status filter
if ($filter_status === 'ongoing') {
    $query .= " AND ? BETWEEN e.start_date AND e.end_date";
    $params[] = $today;
    $types .= "s";
} elseif ($filter_status === 'upcoming') {
    $query .= " AND e.start_date > ?";
    $params[] = $today;
    $types .= "s";
}

// Add department filter
if ($filter_department !== 'all') {
    if ($filter_department === 'my_department') {
        $query .= " AND (e.department = ? OR e.department IS NULL OR e.department = '')";
        $params[] = $student_department;
        $types .= "s";
    } else {
        $query .= " AND e.department = ?";
        $params[] = $filter_department;
        $types .= "s";
    }
}

// Order by date
$query .= " ORDER BY e.start_date ASC"; // Changed to ASC to show nearest events first

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$events = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get unique departments for filter dropdown
$dept_query = "SELECT DISTINCT department FROM events WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments = mysqli_query($conn, $dept_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - BCCTAP</title>
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
        
        <main class="flex-grow lg:ml-64 px-4 pt-6 pb-20">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Events</h1>
                
                <!-- Filters -->
                <form action="" method="GET" class="bg-white p-4 rounded-xl shadow-md mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                   placeholder="Search events...">
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Events</option>
                                <option value="ongoing" <?php echo $filter_status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="upcoming" <?php echo $filter_status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            </select>
                        </div>
                        
                        <!-- Department Filter -->
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select name="department" id="department" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="all" <?php echo $filter_department === 'all' ? 'selected' : ''; ?>>All Departments</option>
                                <option value="my_department" <?php echo $filter_department === 'my_department' ? 'selected' : ''; ?>>My Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                            <?php echo $filter_department === $dept['department'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filter Button -->
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Events Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($events as $event): 
                        $is_ongoing = ($today >= $event['start_date'] && $today <= $event['end_date']);
                        $is_upcoming = $today < $event['start_date'];
                        
                        // Check if event is for student's department
                        $is_department_compatible = empty($event['department']) || $event['department'] == $student_department;
                    ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="p-6">
                                <!-- Event Status Badge -->
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex flex-wrap gap-2">
                                        <?php if ($is_ongoing): ?>
                                            <span class="student-badge student-badge-success">Ongoing</span>
                                        <?php else: ?>
                                            <span class="student-badge student-badge-info">Upcoming</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($event['department'])): ?>
                                            <span class="student-badge <?php echo $is_department_compatible ? 'student-badge-info' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo htmlspecialchars($event['department']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="student-badge student-badge-info">All Departments</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Event Title -->
                                <h3 class="text-xl font-bold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </h3>
                                
                                <!-- Event Date -->
                                <p class="text-gray-600 mb-4">
                                    <svg class="inline w-5 h-5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <?php 
                                        echo date('M d, Y', strtotime($event['start_date']));
                                        if ($event['start_date'] !== $event['end_date']) {
                                            echo ' - ' . date('M d, Y', strtotime($event['end_date']));
                                        }
                                    ?>
                                </p>
                                
                                <!-- Event Description -->
                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars($event['description']); ?>
                                </p>
                                
                                <!-- View Details Button -->
                                <a href="event-detail.php?id=<?php echo $event['id']; ?>" 
                                   class="student-btn student-btn-primary w-full text-center">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($events)): ?>
                        <div class="col-span-full text-center py-8">
                            <div class="bg-gray-50 rounded-lg p-6 inline-block">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No events found</h3>
                                <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or search terms.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select[name="status"], select[name="department"]').forEach(select => {
            select.addEventListener('change', () => {
                select.closest('form').submit();
            });
        });
    </script>
</body>
</html>