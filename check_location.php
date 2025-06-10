<?php
/**
 * Location Check Page - Prompts students to share location for geofenced events
 */
require_once 'config/config.php';
require_once 'utils/GeofenceHelper.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . 'student/login.php');
}

if (!isset($_SESSION['attendance_pending'])) {
    $_SESSION['error_message'] = "No pending attendance record found.";
    redirect(BASE_URL . 'student/dashboard.php');
}

$pending = $_SESSION['attendance_pending'];
$event_id = $pending['event_id'];
$event_title = $pending['event_title'];

$query = "SELECT * FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

if (!$event) {
    $_SESSION['error_message'] = "Event not found.";
    unset($_SESSION['attendance_pending']);
    redirect(BASE_URL . 'student/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['latitude']) && isset($_POST['longitude'])) {
    $user_latitude = floatval($_POST['latitude']);
    $user_longitude = floatval($_POST['longitude']);
    
    if (!GeofenceHelper::validateCoordinates($user_latitude, $user_longitude)) {
        $_SESSION['error_message'] = "Invalid location coordinates.";
    } else {
        $geofence_result = GeofenceHelper::isWithinGeofence(
            $user_latitude,
            $user_longitude,
            $event['location_latitude'],
            $event['location_longitude'],
            $event['geofence_radius']
        );
        
        if ($geofence_result['within_fence']) {
            $_SESSION['location_data'] = [
                'latitude' => $user_latitude,
                'longitude' => $user_longitude
            ];
            redirect(BASE_URL . 'record_attendance.php');
        } else {
            $_SESSION['error_message'] = "You are not within the event location. " . $geofence_result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Check - BCCTAP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Location Required</h2>
                <p class="mt-2 text-gray-600">This event requires location verification</p>
            </div>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-4"><?php echo htmlspecialchars($event_title); ?></h3>
                <p class="text-sm text-gray-600 mb-6">Required within <?php echo $event['geofence_radius']; ?> meters</p>
                
                <form method="POST">
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    
                    <button type="button" id="getLocationBtn" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-md mb-4">
                        Get My Location
                    </button>
                    
                    <button type="submit" id="submitBtn" class="w-full py-3 px-4 bg-red-600 text-white rounded-md disabled:bg-gray-300" disabled>
                        Mark Attendance
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('getLocationBtn').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    document.getElementById('submitBtn').disabled = false;
                    document.getElementById('getLocationBtn').textContent = 'Location Found ✓';
                });
            }
        });
    </script>
</body>
</html> 