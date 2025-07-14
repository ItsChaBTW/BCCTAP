<?php
/**
 * Create User - Admin Module
 * Enhanced UI: Create Department Head Account
 */
require_once '../../config/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

$full_name = $email = $username = $department = '';
$role = 'teacher';
$password = $confirm_password = '';
$errors = [];

$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $role = 'teacher';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) $errors[] = "Username is already in use";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) $errors[] = "Email already in use";
    }
    if (empty($department)) $errors[] = "Department is required";
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (full_name, username, email, password, role, department, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssss", $full_name, $username, $email, $hashed_password, $role, $department);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Department Head account created successfully.";
            redirect(BASE_URL . 'admin/users/index.php');
        } else {
            $errors[] = "Failed to create user: " . mysqli_error($conn);
        }
    }
}

$page_title = "Create Department Head Account";
$page_actions = '<a href="' . BASE_URL . 'admin/users/index.php" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:opacity-90 text-white py-2 px-4 rounded flex items-center">
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
<path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
</svg>
Back to Users
</a>';

ob_start();
?>

<div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-2xl font-bold text-primary mb-6 flex items-center gap-2">
        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 .38-.214.725-.553.895l-.895.447A1 1 0 0110 12.5V13m0 0v.5a1 1 0 001 1h2a1 1 0 001-1v-.5m-4 0h4"></path><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v.01"></path></svg>
        Create Department Head Account
    </h2>

    <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username *</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="department" class="block text-sm font-medium text-gray-700">Department *</label>
                <select id="department" name="department" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department === $dept['department'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="role" value="teacher">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password *</label>
                <input type="password" id="password" name="password"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">Minimum of 8 characters</p>
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
            </div>
        </div>

        <div class="flex justify-end pt-6">
            <a href="<?php echo BASE_URL; ?>admin/users/index.php" class="mr-3 px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition duration-200">Create Account</button>
        </div>
    </form>
</div>

<?php $page_content = ob_get_clean(); include '../../includes/admin_layout.php'; ?>
