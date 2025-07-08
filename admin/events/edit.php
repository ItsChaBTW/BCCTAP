<?php
/**
 * Edit Event Page for Admins
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No event specified.";
    redirect(BASE_URL . 'admin/events/index.php');
}

$event_id = intval($_GET['id']);

// Get event details
$query = "SELECT * FROM events WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "Event not found.";
    redirect(BASE_URL . 'admin/events/index.php');
}

$event = mysqli_fetch_assoc($result);

// Get departments for dropdown - only those that have registered users
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    if (empty($_POST['title'])) {
        $errors[] = "Event title is required";
    }
    if (empty($_POST['start_date'])) {
        $errors[] = "Start date is required";
    }
    if (empty($_POST['end_date'])) {
        $errors[] = "End date is required";
    }
    if (empty($_POST['morning_time_in'])) {
        $errors[] = "Morning time in is required";
    }
    if (empty($_POST['morning_time_out'])) {
        $errors[] = "Morning time out is required";
    }
    if (empty($_POST['afternoon_time_in'])) {
        $errors[] = "Afternoon time in is required";
    }
    if (empty($_POST['afternoon_time_out'])) {
        $errors[] = "Afternoon time out is required";
    }
    
    // Validate dates
    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $start_date = strtotime($_POST['start_date']);
        $end_date = strtotime($_POST['end_date']);
        
        if ($end_date < $start_date) {
            $errors[] = "End date cannot be earlier than start date";
        }
    }
    
    // If no errors, proceed with event update
    if (empty($errors)) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $department = mysqli_real_escape_string($conn, $_POST['department']);
        $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
        $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
        $morning_time_in = mysqli_real_escape_string($conn, $_POST['morning_time_in']);
        $morning_time_out = mysqli_real_escape_string($conn, $_POST['morning_time_out']);
        $afternoon_time_in = mysqli_real_escape_string($conn, $_POST['afternoon_time_in']);
        $afternoon_time_out = mysqli_real_escape_string($conn, $_POST['afternoon_time_out']);
        $location_latitude = !empty($_POST['location_latitude']) ? mysqli_real_escape_string($conn, $_POST['location_latitude']) : null;
        $location_longitude = !empty($_POST['location_longitude']) ? mysqli_real_escape_string($conn, $_POST['location_longitude']) : null;
        $geofence_radius = !empty($_POST['geofence_radius']) ? intval($_POST['geofence_radius']) : null;
        
        // Update event in database
        $query = "UPDATE events SET 
                  title = ?, 
                  description = ?, 
                  department = ?, 
                  start_date = ?, 
                  end_date = ?, 
                  morning_time_in = ?, 
                  morning_time_out = ?, 
                  afternoon_time_in = ?, 
                  afternoon_time_out = ?, 
                  location_latitude = ?, 
                  location_longitude = ?, 
                  geofence_radius = ? 
                  WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssssssddii", 
            $title, $description, $department, $start_date, $end_date,
            $morning_time_in, $morning_time_out, $afternoon_time_in, $afternoon_time_out,
            $location_latitude, $location_longitude, $geofence_radius, $event_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Event updated successfully!";
            redirect(BASE_URL . 'admin/events/view.php?id=' . $event_id);
        } else {
            $errors[] = "Error updating event: " . mysqli_error($conn);
        }
    }
}

// Set page title and actions for admin layout
$page_title = "Edit Event";
$page_actions = '<a href="index.php" class="bg-gradient-to-r from-blue-600 to-blue-800 hover:opacity-90 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
    </svg>
    Back to Events
</a>';

// Start output buffering
ob_start();
?>
 <main class="flex-grow main-content px-4 py-8">
            
            <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-100">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-700">Please correct the following errors:</h3>
                                <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
    <form method="POST" action="" class="space-y-6">
        <div class="bg-white rounded-xl">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-700">Please correct the following errors:</h3>
                            <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div>
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Event Title <span class="text-primary font-bold text-red-500">*</span></label>
                        <input type="text" id="title" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" 
                            value="<?php echo htmlspecialchars($event['title']); ?>" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>
                    
                    <!-- Interactive Map Location Section -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Event Location & Geofence <span class="text-primary font-bold text-red-500">*</span></label>
                        <div class="map-container">
                            <!-- Map Container -->
                            <div id="locationMap"></div>
                            
                            <!-- Map Controls -->
                            <div class="map-controls">
                                <button type="button" id="getCurrentLocationMap" class="map-control-btn bg-blue-600 hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                    My Location
                                </button>
                                <button type="button" id="clearLocationMap" class="map-control-btn bg-red-600 hover:bg-red-700">
                                    Clear
                                </button>
                            </div>
                            
                            <!-- Map Status -->
                            <div id="mapStatus" class="map-status">
                                Click on the map to set event location
                            </div>
                        </div>
                        
                        <!-- Map Controls Panel -->
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg mt-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Coordinates Display -->
                                <div class="md:col-span-2">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label for="location_latitude" class="block text-xs font-medium text-gray-600 mb-1">Latitude</label>
                                            <input type="number" id="location_latitude" name="location_latitude" step="0.000001" readonly class="w-full px-3 py-2 text-xs bg-gray-100 border border-gray-300 rounded-md" value="<?php echo htmlspecialchars($event['location_latitude'] ?? ''); ?>" placeholder="Click map to set">
                                        </div>
                                        <div>
                                            <label for="location_longitude" class="block text-xs font-medium text-gray-600 mb-1">Longitude</label>
                                            <input type="number" id="location_longitude" name="location_longitude" step="0.000001" readonly class="w-full px-3 py-2 text-xs bg-gray-100 border border-gray-300 rounded-md" value="<?php echo htmlspecialchars($event['location_longitude'] ?? ''); ?>" placeholder="Click map to set">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Geofence Radius -->
                                <div>
                                    <label for="geofence_radius" class="block text-xs font-medium text-gray-600 mb-1">Geofence Radius <span class="text-primary font-bold text-red-500">*</span></label>
                                    <select id="geofence_radius" name="geofence_radius" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary mb-2">
                                        <option value="50" <?php echo ($event['geofence_radius'] == 50) ? 'selected' : ''; ?>>50m - Very strict</option>
                                        <option value="100" <?php echo ($event['geofence_radius'] == 100 || empty($event['geofence_radius'])) ? 'selected' : ''; ?>>100m - Recommended</option>
                                        <option value="200" <?php echo ($event['geofence_radius'] == 200) ? 'selected' : ''; ?>>200m - Flexible</option>
                                        <option value="500" <?php echo ($event['geofence_radius'] == 500) ? 'selected' : ''; ?>>500m - Very flexible</option>
                                        <option value="1000" <?php echo ($event['geofence_radius'] == 1000) ? 'selected' : ''; ?>>1km - Very flexible</option>
                                        <option value="custom" <?php echo (!in_array($event['geofence_radius'], [50, 100, 200, 500, 1000]) && !empty($event['geofence_radius'])) ? 'selected' : ''; ?>>Custom radius</option>
                                    </select>
                                    
                                    <!-- Custom Radius Input -->
                                    <div id="customRadiusContainer" class="<?php echo (!in_array($event['geofence_radius'], [50, 100, 200, 500, 1000]) && !empty($event['geofence_radius'])) ? '' : 'hidden'; ?>">
                                        <div class="flex items-center gap-2">
                                            <input type="number" id="custom_radius" name="custom_radius" min="1" max="10000" placeholder="Enter radius" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary" value="<?php echo (!in_array($event['geofence_radius'], [50, 100, 200, 500, 1000]) && !empty($event['geofence_radius'])) ? htmlspecialchars($event['geofence_radius']) : ''; ?>" />
                                            <span class="text-xs text-gray-500">meters</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Enter value between 1-10000m</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3 text-xs text-gray-500">
                                <p><strong>📍 Instructions:</strong> Click anywhere on the map to set the event location. The colored circle shows the geofence area where students can mark attendance.</p>
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
                
                <!-- Right Column -->
                <div>
                <div class="mb-6">
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department <span class="text-primary font-bold text-red-500">*</span></label>
                        <select id="department" name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo ($event['department'] === $dept['department']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bg-red-50 p-6 rounded-lg mb-6 border border-red-100">
                        <h3 class="text-lg font-semibold text-primary mb-4">Event Dates</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date <span class="text-primary font-bold text-red-500">*</span></label>
                                <input type="date" id="start_date" name="start_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" 
                                    value="<?php echo htmlspecialchars($event['start_date']); ?>" required>
                            </div>
                            
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date <span class="text-primary font-bold text-red-500">*</span></label>
                                <input type="date" id="end_date" name="end_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" 
                                    value="<?php echo htmlspecialchars($event['end_date']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-orange-50 p-6 rounded-lg mb-6 border border-orange-100">
                        <h3 class="text-lg font-semibold text-secondary mb-4">Morning Session</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="morning_time_in" class="block text-sm font-medium text-gray-700 mb-2">Time In <span class="text-primary font-bold text-red-500">*</span></label>
                                <input type="time" id="morning_time_in" name="morning_time_in" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary" 
                                    value="<?php echo htmlspecialchars($event['morning_time_in']); ?>" required>
                            </div>
                            
                            <div>
                                <label for="morning_time_out" class="block text-sm font-medium text-gray-700 mb-2">Time Out <span class="text-primary font-bold text-red-500">*</span></label>
                                <input type="time" id="morning_time_out" name="morning_time_out" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary" 
                                    value="<?php echo htmlspecialchars($event['morning_time_out']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 p-6 rounded-lg mb-6 border border-yellow-100">
                        <h3 class="text-lg font-semibold text-yellow-600 mb-4">Afternoon Session</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="afternoon_time_in" class="block text-sm font-medium text-gray-700 mb-2">Time In <span class="text-primary font-bold text-red-500">*</span></label>
                                <input type="time" id="afternoon_time_in" name="afternoon_time_in" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" 
                                    value="<?php echo htmlspecialchars($event['afternoon_time_in']); ?>" required>  
                            </div>
                            
                            <div>
                                <label for="afternoon_time_out" class="block text-sm font-medium text-gray-700 mb-2">Time Out <span class="text-primary font-bold text-red-500">*</span></label>
                                <input type="time" id="afternoon_time_out" name="afternoon_time_out" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" 
                                    value="<?php echo htmlspecialchars($event['afternoon_time_out']); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 border-t border-gray-200 pt-6 flex justify-end">
                <a href="view.php?id=<?php echo $event_id; ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-6 rounded-lg mr-4 transition duration-300">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:opacity-90 font-medium py-2 px-6 rounded-lg text-white transition duration-300 flex items-center">

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Save Changes
                </button>
            </div>
    </form>
</div>

</main>
<script src="../../assets/js/main.js"></script>

<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    /* Custom map styles */
    .map-container {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #e5e7eb;
        background: white;
    }
    
    #locationMap {
        height: 400px;
        width: 100%;
        z-index: 1;
    }
     /* Custom marker styles */
     .custom-marker-icon {
            position: relative;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
            width: 36px !important;
            height: 36px !important;
            margin-left: -18px !important;
            margin-top: -18px !important;
            cursor: move;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-marker-icon::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 6px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.2);
        }
        
        /* Pulse animation for marker */
        @keyframes markerPulse {
            0% {
                border-radius: 50%;
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7);
                transform: scale(1);
            }
            50% {
                border-radius: 50%;
                box-shadow: 0 0 0 15px rgba(37, 99, 235, 0);
                transform: scale(1.1);
            }
            100% {
                border-radius: 50%;
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
                transform: scale(1);
            }
        }
        
        /* Custom geofence styles */
        .custom-geofence {
            stroke-dasharray: 10, 10;
            animation: geofencePulse 2s infinite;
            transition: all 0.3s ease;
        }
        
        @keyframes geofencePulse {
            0% {
                stroke-opacity: 0.4;
            }
            50% {
                stroke-opacity: 0.7;
            }
            100% {
                stroke-opacity: 0.4;
            }
        }
        
    /* Enhanced controls */
    .map-controls {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .map-control-btn {
        padding: 8px 12px;
        border-radius: 6px;
        border: none;
        color: white;
        font-size: 12px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .map-control-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .map-status {
        position: absolute;
        bottom: 20px;
        left: 20px;
        z-index: 1000;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease;
    }
    
    /* Tooltip styles */
    .custom-tooltip {
        background: rgba(0, 0, 0, 0.8);
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 12px;
    }
    
    /* Fix for Leaflet controls */
    .leaflet-control-zoom {
        border: none !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }
    
    .leaflet-control-zoom a {
        background: white !important;
        color: #374151 !important;
        border: 1px solid #e5e7eb !important;
        width: 30px !important;
        height: 30px !important;
        line-height: 30px !important;
        font-size: 16px !important;
    }
    
    .leaflet-control-zoom a:hover {
        background: #f3f4f6 !important;
        color: #111827 !important;
    }
    
    .leaflet-control-attribution {
        background: rgba(255, 255, 255, 0.8) !important;
        padding: 4px 8px !important;
        font-size: 10px !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize form elements first
        const radiusSelect = document.getElementById('geofence_radius');
        const customRadiusContainer = document.getElementById('customRadiusContainer');
        const customRadiusInput = document.getElementById('custom_radius');
        const latInput = document.getElementById('location_latitude');
        const lngInput = document.getElementById('location_longitude');
        const statusDiv = document.getElementById('mapStatus');
        
        // Initialize map with default center (Philippines)
        const map = L.map('locationMap', {
            center: [10.3157, 123.8854],
            zoom: 13,
            zoomControl: false
        });
        
        // Add zoom control to top-left
        L.control.zoom({
            position: 'topleft'
        }).addTo(map);
        
        // Custom tile layer with enhanced styling
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
            className: 'map-tiles'
        }).addTo(map);
        
        let marker = null;
        let circle = null;
        
        // Custom marker icon
        const customIcon = L.divIcon({
            className: 'custom-marker-icon',
            html: '<div style="width: 100%; height: 100%; animation: markerPulse 2s infinite;"></div>',
            iconSize: [36, 36],
            iconAnchor: [18, 36]
        });
        
        // Update coordinates helper function
        function updateCoordinates(lat, lng) {
            if (lat && lng) {
                latInput.value = parseFloat(lat).toFixed(6);
                lngInput.value = parseFloat(lng).toFixed(6);
                statusDiv.textContent = `Location set: ${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}`;
            } else {
                latInput.value = '';
                lngInput.value = '';
                statusDiv.textContent = 'Click on the map to set event location';
            }
        }

        function updateCustomOption(value) {
            const customOption = radiusSelect.querySelector('option[value="custom"]');
            if (value && !isNaN(value) && value >= 1 && value <= 10000) {
                customOption.textContent = `${value}m - Custom`;
            } else {
                customOption.textContent = 'Custom radius';
            }
        }

        // Initialize custom option text if there's a custom value
        if (customRadiusInput.value) {
            updateCustomOption(customRadiusInput.value);
        }
        
        // Add marker and circle if coordinates exist in form
        if (latInput.value && lngInput.value) {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            
            if (!isNaN(lat) && !isNaN(lng)) {
                marker = L.marker([lat, lng], {
                    icon: customIcon,
                    draggable: true
                }).addTo(map);
                
                marker.bindTooltip('Drag to adjust location', {
                    className: 'custom-tooltip',
                    offset: [0, -45],
                    direction: 'top'
                });
                
                marker.on('drag', function(e) {
                    const pos = e.target.getLatLng();
                    if (circle) {
                        circle.setLatLng(pos);
                    }
                    updateCoordinates(pos.lat, pos.lng);
                });

                // Get initial radius value from the database value
                let initialRadius = <?php echo !empty($event['geofence_radius']) ? $event['geofence_radius'] : 100; ?>;
                console.log('Initial radius:', initialRadius);
                
                // Update the select and custom input if it's a custom value
                if (!radiusSelect.querySelector(`option[value="${initialRadius}"]`)) {
                    radiusSelect.value = 'custom';
                    customRadiusInput.value = initialRadius;
                    customRadiusContainer.classList.remove('hidden');
                    updateCustomOption(initialRadius);
                } else {
                    radiusSelect.value = initialRadius.toString();
                }
                
                // Create the circle with the initial radius
                if (circle) {
                    map.removeLayer(circle);
                }
                
                circle = L.circle([lat, lng], {
                    radius: Number(initialRadius),
                    color: '#2563eb',
                    weight: 2,
                    fillColor: '#3b82f6',
                    fillOpacity: 0.1,
                    className: 'custom-geofence'
                }).addTo(map);
                
                console.log('Circle created with radius:', circle.getRadius());
                
                map.setView([lat, lng], 15);
                updateCoordinates(lat, lng);
            }
        }

        // Handle map clicks
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {
                    icon: customIcon,
                    draggable: true
                }).addTo(map);
                
                marker.bindTooltip('Drag to adjust location', {
                    className: 'custom-tooltip',
                    offset: [0, -45],
                    direction: 'top'
                });
                
                marker.on('drag', function(e) {
                    const pos = e.target.getLatLng();
                    if (circle) {
                        circle.setLatLng(pos);
                    }
                    updateCoordinates(pos.lat, pos.lng);
                });
            }
            
            let radius = radiusSelect.value === 'custom' ? 
                Number(customRadiusInput.value) : 
                Number(radiusSelect.value);
            
            if (circle) {
                circle.setLatLng(e.latlng);
                circle.setRadius(radius);
            } else {
                circle = L.circle(e.latlng, {
                    radius: radius,
                    color: '#2563eb',
                    weight: 2,
                    fillColor: '#3b82f6',
                    fillOpacity: 0.1,
                    className: 'custom-geofence'
                }).addTo(map);
            }
            
            updateCoordinates(lat, lng);
        });
        
        // Get current location
        document.getElementById('getCurrentLocationMap').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    map.setView([lat, lng], 15);
                    
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], {
                            icon: customIcon,
                            draggable: true
                        }).addTo(map);
                        
                        marker.bindTooltip('Drag to adjust location', {
                            className: 'custom-tooltip',
                            offset: [0, -45],
                            direction: 'top'
                        });
                        
                        marker.on('drag', function(e) {
                            const pos = e.target.getLatLng();
                            if (circle) {
                                circle.setLatLng(pos);
                            }
                            updateCoordinates(pos.lat, pos.lng);
                        });
                    }
                    
                    let radius = radiusSelect.value === 'custom' ? 
                        Number(customRadiusInput.value) : 
                        Number(radiusSelect.value);
                    
                    if (circle) {
                        circle.setLatLng([lat, lng]);
                        circle.setRadius(radius);
                    } else {
                        circle = L.circle([lat, lng], {
                            radius: radius,
                            color: '#2563eb',
                            weight: 2,
                            fillColor: '#3b82f6',
                            fillOpacity: 0.1,
                            className: 'custom-geofence'
                        }).addTo(map);
                    }
                    
                    updateCoordinates(lat, lng);
                });
            }
        });
        
        // Clear location
        document.getElementById('clearLocationMap').addEventListener('click', function() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            if (circle) {
                map.removeLayer(circle);
                circle = null;
            }
            
            updateCoordinates(null, null);
        });
        
        // Handle geofence radius changes
        radiusSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customRadiusContainer.classList.remove('hidden');
                if (customRadiusInput.value) {
                    updateCircleRadius(customRadiusInput.value);
                    updateCustomOption(customRadiusInput.value);
                }
            } else {
                customRadiusContainer.classList.add('hidden');
                updateCircleRadius(this.value);
            }
        });

        customRadiusInput.addEventListener('input', function() {
            const value = this.value;
            if (!isNaN(value) && value >= 1 && value <= 10000) {
                updateCircleRadius(value);
                updateCustomOption(value);
            }
        });

        // Form submission handler
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            if (radiusSelect.value === 'custom') {
                const customValue = customRadiusInput.value;
                if (!customValue || isNaN(customValue) || customValue < 1 || customValue > 10000) {
                    e.preventDefault();
                    alert('Please enter a valid radius between 1 and 10000 meters');
                    return;
                }
                // Create a hidden input to store the custom radius value
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'geofence_radius';
                hiddenInput.value = customValue;
                form.appendChild(hiddenInput);
                
                updateCustomOption(customValue);
            }
        });
    });
</script>

<?php
$page_content = ob_get_clean();

// Include the admin layout
require_once '../../includes/admin_layout.php';
?> 