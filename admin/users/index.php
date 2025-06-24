<?php
/**
 * User Management for Admins
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Initialize filter variables
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$department_filter = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get departments for filter dropdown
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);

// Handle user actions (deactivate, activate, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $action = sanitize($_POST['action']);
        $current_user_id = $_SESSION['user_id'];
        
        // Don't allow actions on the current user
        if ($user_id === $current_user_id) {
            $_SESSION['error_message'] = "You cannot perform actions on your own account.";
        } else {
            switch ($action) {
                case 'deactivate':
                    $query = "UPDATE users SET active = 0 WHERE id = ?";
                    $action_message = "User deactivated successfully";
                    break;
                case 'activate':
                    $query = "UPDATE users SET active = 1 WHERE id = ?";
                    $action_message = "User activated successfully";
                    break;
                case 'delete':
                    $query = "DELETE FROM users WHERE id = ?";
                    $action_message = "User deleted successfully";
                    break;
                default:
                    $_SESSION['error_message'] = "Invalid action specified";
                    redirect(BASE_URL . 'admin/users/index.php');
                    exit;
            }
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = $action_message;
            } else {
                $_SESSION['error_message'] = "Failed to perform action: " . mysqli_error($conn);
            }
        }
        
        // Redirect to avoid form resubmission
        redirect(BASE_URL . 'admin/users/index.php');
    }
}

// Build the query with filters
$query_count = "SELECT COUNT(*) AS total FROM users WHERE 1=1";
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $query_count .= " AND (full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $query_count .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($department_filter)) {
    $query .= " AND department = ?";
    $query_count .= " AND department = ?";
    $params[] = $department_filter;
    $types .= "s";
}

// Get total count for pagination
$stmt_count = mysqli_prepare($conn, $query_count);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
}
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$total_users = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_users / $per_page);

// Add sorting and pagination
$query .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";

// Get user data
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Count users by role
$query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$result = mysqli_query($conn, $query);
$role_counts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $role_counts[$row['role']] = $row['count'];
}

// Define page title and content
$page_title = "User Management";
$page_actions = '
<a href="create.php" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:opacity-90 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
    </svg>
    Add New User
</a>';

// Start output buffering for page content
ob_start();
?>

<!-- User Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card blue">
        <h3>Total Users</h3>
        <div class="value"><?php echo number_format($total_users); ?></div>
    </div>
    
    <div class="stat-card green">
        <h3>Students</h3>
        <div class="value"><?php echo number_format($role_counts['student'] ?? 0); ?></div>
    </div>
    
    <div class="stat-card amber">
        <h3>Teacher/s</h3>
        <div class="value"><?php echo number_format($role_counts['teacher'] ?? 0); ?></div>
    </div>
    
    <div class="stat-card purple">
        <h3>Admin/s</h3>
        <div class="value"><?php echo number_format($role_counts['admin'] ?? 0); ?></div>
    </div>
</div>

<!-- Filter Section -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Filter Users</h2>
    <form action="" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Name, email, or ID" 
                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
        </div>
        
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
            <select id="role" name="role" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                <option value="">All Roles</option>
                <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>Department Heads</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
            </select>
        </div>
        
        <div>
            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select id="department" name="department" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                            <?php echo $department_filter === $dept['department'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['department']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="md:col-span-3 flex justify-end space-x-2">
            <a href="<?php echo BASE_URL; ?>admin/users/index.php" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded">Clear Filters</a>
            <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded">Apply Filters</button>
        </div>
    </form>
</div>

<!-- Users List -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Users</h2>
        <p class="text-sm text-gray-500">
            Showing <?php echo min(($page - 1) * $per_page + 1, $total_users); ?> to 
            <?php echo min($page * $per_page, $total_users); ?> of 
            <?php echo number_format($total_users); ?> users
        </p>
    </div>
    
    <?php if (empty($users)): ?>
        <div class="p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <p class="text-gray-600 font-medium">No users found</p>
            <p class="text-gray-500 text-sm mt-1">Try adjusting your filters or add a new user</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name/Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID/Department</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 font-medium">
                                        <?php echo substr($user['full_name'], 0, 1); ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($user['student_id'])): ?>
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['student_id']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($user['department'])): ?>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['department']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $roleClass = '';
                                switch ($user['role']) {
                                    case 'admin':
                                        $roleClass = 'bg-purple-100 text-purple-800';
                                        break;
                                    case 'teacher':
                                        $roleClass = 'bg-amber-100 text-amber-800';
                                        break;
                                    case 'student':
                                        $roleClass = 'bg-green-100 text-green-800';
                                        break;
                                    default:
                                        $roleClass = 'bg-gray-100 text-gray-800';
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $roleClass; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (isset($user['active']) && $user['active'] == 1): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <div class="flex justify-center space-x-2">
                                    <a href="view.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-800" title="View Details">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    
                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit User">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <?php if (isset($user['active']) && $user['active'] == 1): ?>
                                            <form method="post" action="" class="inline-block">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Deactivate User"
                                                        onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="" class="inline-block">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="text-green-600 hover:text-green-900" title="Activate User"
                                                        onclick="return confirm('Are you sure you want to activate this user?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="post" action="" class="inline-block">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Delete User"
                                                    onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-700">
                            Page <span class="font-medium"><?php echo $page; ?></span> of <span class="font-medium"><?php echo $total_pages; ?></span>
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($department_filter) ? '&department=' . urlencode($department_filter) : ''; ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?><?php echo !empty($department_filter) ? '&department=' . urlencode($department_filter) : ''; ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg shadow-sm p-6 flex items-center border-l-4 border-blue-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        <div>
            <h3 class="font-medium text-gray-900">Add New User</h3>
            <p class="text-sm text-gray-500 mt-1">Create a new user account</p>
            <a href="create.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">Get Started →</a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 flex items-center border-l-4 border-green-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        <div>
            <h3 class="font-medium text-gray-900">Bulk Import</h3>
            <p class="text-sm text-gray-500 mt-1">Import users from CSV</p>
            <a href="bulk_import.php" class="text-green-600 hover:text-green-800 text-sm font-medium mt-2 inline-block">Upload File →</a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 flex items-center border-l-4 border-amber-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-amber-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
        </svg>
        <div>
            <h3 class="font-medium text-gray-900">Export Users</h3>
            <p class="text-sm text-gray-500 mt-1">Download user data</p>
            <a href="#" class="text-amber-600 hover:text-amber-800 text-sm font-medium mt-2 inline-block">Export CSV →</a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 flex items-center border-l-4 border-purple-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-purple-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        <div>
            <h3 class="font-medium text-gray-900">User Reports</h3>
            <p class="text-sm text-gray-500 mt-1">View detailed reports</p>
            <a href="../reports/users.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium mt-2 inline-block">View Reports →</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Close alert messages
    const closeButtons = document.querySelectorAll('.close-alert');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.parentElement.parentElement.parentElement.remove();
        });
    });
});
</script>

<?php
// Get the page content from buffer
$page_content = ob_get_clean();

// Include the admin layout
include '../../includes/admin_layout.php';
?> 