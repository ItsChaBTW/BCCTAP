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
$query = "SELECT e.*, u.full_name as created_by_name,
          CASE 
              WHEN ? BETWEEN e.start_date AND e.end_date THEN 'ongoing'
              WHEN e.start_date > ? THEN 'upcoming'
              ELSE 'past'
          END as event_status
          FROM events e 
          LEFT JOIN users u ON e.created_by = u.id
          WHERE 1=1"; // Changed to allow more flexible filtering

$params = [$today, $today];
$types = "ss";

// Add search condition if search term is provided
if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
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
} else {
    // For 'all' status, only show ongoing and upcoming events
    $query .= " AND e.end_date >= ?";
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

// Order by date and status (ongoing first, then upcoming)
$query .= " ORDER BY 
           CASE 
               WHEN ? BETWEEN start_date AND end_date THEN 1
               WHEN start_date > ? THEN 2
               ELSE 3
           END,
           start_date ASC";
$params[] = $today;
$params[] = $today;
$types .= "ss";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$events = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get unique departments for filter dropdown
$dept_query = "SELECT DISTINCT e.department 
               FROM events e 
               WHERE e.department IS NOT NULL 
               AND e.department != '' 
               AND e.end_date >= ? 
               ORDER BY e.department";
$dept_stmt = mysqli_prepare($conn, $dept_query);
mysqli_stmt_bind_param($dept_stmt, "s", $today);
mysqli_stmt_execute($dept_stmt);
$departments = mysqli_stmt_get_result($dept_stmt)->fetch_all(MYSQLI_ASSOC);
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Events</h1>
                    <div class="text-sm text-gray-600">
                        <?php echo count($events); ?> event<?php echo count($events) !== 1 ? 's' : ''; ?> found
                    </div>
                </div>
                
                <!-- Filters -->
                <form action="" method="GET" class="bg-white p-6 rounded-xl shadow-md mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Events</label>
                            <div class="relative">
                                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       class="w-full rounded-lg border-gray-300 pl-10 pr-4 py-2 focus:border-green-500 focus:ring-green-500"
                                       placeholder="Search by title, description or location...">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <div class="relative">
                                <select name="status" id="status" 
                                        class="w-full rounded-lg border-gray-300 pl-10 pr-4 py-2 focus:border-green-500 focus:ring-green-500 appearance-none">
                                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Events</option>
                                    <option value="ongoing" <?php echo $filter_status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="upcoming" <?php echo $filter_status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                </select>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calendar-alt text-gray-400"></i>
                                </div>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Department Filter -->
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <div class="relative">
                                <select name="department" id="department" 
                                        class="w-full rounded-lg border-gray-300 pl-10 pr-4 py-2 focus:border-green-500 focus:ring-green-500 appearance-none">
                                    <option value="all">All Events</option>
                                    <option value="my_department" <?php echo $filter_department === 'my_department' ? 'selected' : ''; ?>>
                                        My Department (<?php echo htmlspecialchars($student_department); ?>)
                                    </option>
                                    <?php if (!empty($departments)): ?>
                                        <option disabled>──────────</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <?php if ($dept['department'] !== $student_department): ?>
                                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                                        <?php echo $filter_department === $dept['department'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['department']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-building text-gray-400"></i>
                                </div>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                            <?php if (!empty($departments)): ?>
                                <p class="mt-1 text-xs text-gray-500">
                                    Showing <?php echo count($departments); ?> department<?php echo count($departments) !== 1 ? 's' : ''; ?> with active events
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Active Filters -->
                    <?php if (!empty($search) || $filter_status !== 'all' || $filter_department !== 'all'): ?>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <?php if (!empty($search)): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    <span class="mr-1">Search:</span>
                                    "<?php echo htmlspecialchars($search); ?>"
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="ml-2 text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($filter_status !== 'all'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    Status: <?php echo ucfirst($filter_status); ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'all'])); ?>" class="ml-2 text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($filter_department !== 'all'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    Department: <?php echo $filter_department === 'my_department' ? 'My Department' : htmlspecialchars($filter_department); ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['department' => 'all'])); ?>" class="ml-2 text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($search) || $filter_status !== 'all' || $filter_department !== 'all'): ?>
                                <a href="?" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-red-600 hover:text-red-800 hover:bg-red-50">
                                    <i class="fas fa-times mr-1"></i> Clear all filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </form>
                
                <!-- Events Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($events as $event): 
                        $is_ongoing = ($today >= $event['start_date'] && $today <= $event['end_date']);
                        $is_upcoming = $today < $event['start_date'];
                        $is_department_compatible = empty($event['department']) || $event['department'] == $student_department;
                        
                        // Calculate days until event
                        $days_until = (strtotime($event['start_date']) - strtotime($today)) / (60 * 60 * 24);
                        $days_left = (strtotime($event['end_date']) - strtotime($today)) / (60 * 60 * 24);
                    ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 relative overflow-hidden">
                            <?php if ($is_ongoing): ?>
                                <div class="absolute top-0 left-0 w-full h-1 bg-green-500"></div>
                            <?php elseif ($is_upcoming): ?>
                                <div class="absolute top-0 left-0 w-full h-1 bg-blue-500"></div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <!-- Event Status Badge -->
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex flex-wrap gap-2">
                                        <?php if ($is_ongoing): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-circle text-green-400 mr-1"></i>
                                                Ongoing
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-clock text-blue-400 mr-1"></i>
                                                <?php 
                                                    if ($days_until <= 7) {
                                                        echo 'Starting in ' . ($days_until < 1 ? 'less than a day' : round($days_until) . ' days');
                                                    } else {
                                                        echo 'Upcoming';
                                                    }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($event['department'])): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                       <?php echo $is_department_compatible ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <i class="fas fa-building mr-1"></i>
                                                <?php echo htmlspecialchars($event['department']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <i class="fas fa-users mr-1"></i>
                                                All Departments
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Event Title -->
                                <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </h3>
                                
                                <!-- Event Date -->
                                <p class="text-gray-600 mb-2 flex items-center">
                                    <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                                    <?php 
                                        echo date('M d, Y', strtotime($event['start_date']));
                                        if ($event['start_date'] !== $event['end_date']) {
                                            echo ' - ' . date('M d, Y', strtotime($event['end_date']));
                                        }
                                    ?>
                                </p>
                                
                                <!-- Event Time -->
                                <p class="text-gray-600 mb-4 flex items-center">
                                    <i class="fas fa-clock mr-2 text-gray-400"></i>
                                    <?php 
                                        echo date('h:i A', strtotime($event['morning_time_in'])) . ' - ' . 
                                             date('h:i A', strtotime($event['afternoon_time_out']));
                                    ?>
                                </p>
                                
                                <!-- Event Location if available -->
                                <?php if (!empty($event['location'])): ?>
                                    <p class="text-gray-600 mb-4 flex items-center">
                                        <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                        <?php echo htmlspecialchars($event['location']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <!-- Event Description -->
                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars($event['description']); ?>
                                </p>
                                
                                <!-- View Details Button -->
                                <a href="event-detail.php?id=<?php echo $event['id']; ?>" 
                                   class="inline-flex items-center justify-center w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($events)): ?>
                        <div class="col-span-full">
                            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-4">
                                    <i class="fas fa-calendar-times text-gray-400 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-1">No events found</h3>
                                <p class="text-sm text-gray-500 mb-4">Try adjusting your filters or search terms.</p>
                                <a href="?" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                    <i class="fas fa-sync-alt mr-2"></i>
                                    Reset Filters
                                </a>
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
        
        // Add delay to search input to prevent too many requests
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                e.target.closest('form').submit();
            }, 500);
        });
    </script>
</body>
</html>