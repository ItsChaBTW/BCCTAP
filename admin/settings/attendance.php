<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../utils/AttendanceStatus.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$attendanceStatus = new AttendanceStatus();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['late_interval'])) {
        $minutes = (int)$_POST['late_interval'];
        if ($minutes >= 0) {
            if ($attendanceStatus->setLateInterval($minutes)) {
                $message = "Late interval updated successfully to {$minutes} minutes.";
            } else {
                $error = "Failed to update late interval.";
            }
        } else {
            $error = "Late interval cannot be negative.";
        }
    }
}

$currentInterval = $attendanceStatus->getLateInterval();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Settings - BCCTAP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <?php include '../includes/navbar.php'; ?>

        <div class="container mx-auto px-4 py-8">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h1 class="text-2xl font-bold mb-6">Attendance Settings</h1>

                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="late_interval" class="block text-sm font-medium text-gray-700">
                                Late Interval (minutes)
                            </label>
                            <div class="mt-1">
                                <input type="number" 
                                       name="late_interval" 
                                       id="late_interval" 
                                       value="<?php echo htmlspecialchars($currentInterval); ?>"
                                       min="0"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                       required>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Number of minutes after scheduled time to consider attendance as late.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
</body>
</html> 