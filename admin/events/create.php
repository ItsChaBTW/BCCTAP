<?php
/**
 * Create Event Page for Admins
 */
require_once '../../config/config.php';
require_once '../../utils/GeofenceHelper.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Get departments for dropdown
$query = "SELECT * FROM departments ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $morning_time_in = sanitize($_POST['morning_time_in']);
    $morning_time_out = sanitize($_POST['morning_time_out']);
    $afternoon_time_in = sanitize($_POST['afternoon_time_in']);
    $afternoon_time_out = sanitize($_POST['afternoon_time_out']);
    $department = isset($_POST['department']) ? sanitize($_POST['department']) : null;
    
    // Handle location data
    $location_latitude = !empty($_POST['location_latitude']) ? floatval($_POST['location_latitude']) : null;
    $location_longitude = !empty($_POST['location_longitude']) ? floatval($_POST['location_longitude']) : null;
    $geofence_radius = isset($_POST['geofence_radius']) ? intval($_POST['geofence_radius']) : 100;
    
    // Generate a globally unique ID for the event
    $event_uuid = generate_uuid();
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Event title is required";
    }
    
    if (empty($start_date) || empty($end_date)) {
        $errors[] = "Start and end dates are required";
    } elseif ($start_date > $end_date) {
        $errors[] = "End date must be after start date";
    }
    
    if (empty($morning_time_in) || empty($morning_time_out) || empty($afternoon_time_in) || empty($afternoon_time_out)) {
        $errors[] = "All time fields are required";
    }
    
    // Validate coordinates if provided
    if (($location_latitude !== null || $location_longitude !== null) && 
        (!GeofenceHelper::validateCoordinates($location_latitude, $location_longitude))) {
        $errors[] = "Invalid coordinates provided";
    }

    // If no errors, create the event
    if (empty($errors)) {
        $query = "INSERT INTO events (uuid, title, description, start_date, end_date, morning_time_in, morning_time_out, 
                                       afternoon_time_in, afternoon_time_out, department, location_latitude, location_longitude, 
                                       geofence_radius, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssssssddii", $event_uuid, $title, $description, $start_date, $end_date, $morning_time_in, 
                               $morning_time_out, $afternoon_time_in, $afternoon_time_out, $department, $location_latitude, 
                               $location_longitude, $geofence_radius, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $event_id = mysqli_insert_id($conn);
            
            // Generate a single QR code for the event that includes both morning and afternoon sessions
            $qr_code = generateQRCode($event_id, $event_uuid);
            
            $_SESSION['success_message'] = "Event created successfully! QR code has been generated.";
            redirect(BASE_URL . 'admin/events/index.php');
        } else {
            $errors[] = "Failed to create event: " . mysqli_error($conn);
        }
    }
}

// Function to generate a UUID v4
function generate_uuid() {
    // Generate 16 bytes (128 bits) of random data
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $data = openssl_random_pseudo_bytes(16);
    } else {
        // Fallback to less secure method
        $data = '';
        for ($i = 0; $i < 16; $i++) {
            $data .= chr(mt_rand(0, 255));
        }
    }
    
    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Function to generate QR code and save to database
function generateQRCode($event_id, $event_uuid) {
    global $conn;
    
    // Generate a unique code for the QR that includes the UUID
    $code = $event_uuid;
    
    // Get the event title
    $query = "SELECT title FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);
    $event_title = $event['title'];
    
    // Insert into database - using 'combined' instead of morning/afternoon to indicate single QR code
    $query = "INSERT INTO qr_codes (event_id, code, session) VALUES (?, ?, 'combined')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $event_id, $code);
    mysqli_stmt_execute($stmt);
    
    // Get the QR code ID
    $qr_code_id = mysqli_insert_id($conn);
    
    // Generate and save the QR code image
    require_once '../../utils/QrCodeGenerator.php';
    
    // The data to encode in the QR code - URL to the scan.php page with the code
    $scan_url = getAbsoluteUrl('scan.php?code=' . urlencode($code));
    
    // Log the generated URL for debugging
    error_log("QR Code URL generated: " . $scan_url);
    
    // Generate filename from event ID and QR code ID
    $filename = "event_{$event_id}_qr_{$qr_code_id}.png";
    
    try {
        // Generate the QR code and save it
        $qr_image_path = QrCodeGenerator::generate(
            $scan_url,
            $filename,
            '../../uploads/qrcodes',
            300,
            htmlspecialchars($event_title) // Use the event title as the QR code label
        );
        
        // Update the QR code record with the image path
        $update_query = "UPDATE qr_codes SET image_path = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        $relative_path = 'uploads/qrcodes/' . basename($qr_image_path);
        mysqli_stmt_bind_param($stmt, "si", $relative_path, $qr_code_id);
        mysqli_stmt_execute($stmt);
    } catch (Exception $e) {
        // Log error but continue
        error_log("Failed to generate QR code image: " . $e->getMessage());
    }
    
    return $code;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - BCCTAP</title>
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet 3D Plugin -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#EF6161',
                        secondary: '#f3af3d',
                    }
                }
            }
        }
    </script>
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #EF6161 0%, #f3af3d 100%);
        }
        .main-content {
            margin-left: 16rem; /* 256px - width of the sidebar */
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <?php include '../../includes/admin_sidebar.php'; ?>
        
        <main class="flex-grow main-content px-4 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-primary">Create New Event</h1>
                <a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Back to Events
                </a>
            </div>
            
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
                
                <div class="bg-indigo-50 p-4 mb-6 rounded-md border border-indigo-100">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-indigo-800">Each event will now have a single QR code that students can scan to record both morning and afternoon attendance.</p>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div>
                            <div class="mb-6">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Event Title <span class="text-primary">*</span></label>
                                <input type="text" id="title" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" 
                                    value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-6">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea id="description" name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <!-- Interactive Map Location Section -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Event Location & Geofence (Optional)</label>
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                    <!-- Map Container -->
                                    <div class="relative">
                                        <div id="locationMap" class="w-full h-96 bg-gray-100"></div>
                                        
                                        <!-- Map Controls Overlay -->
                                        <div class="absolute top-4 right-4 z-[1000] space-y-2">
                                            <button type="button" id="getCurrentLocationMap" class="block px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-md shadow-lg flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                                </svg>
                                                My Location
                                            </button>
                                            <button type="button" id="clearLocationMap" class="block px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded-md shadow-lg">
                                                Clear
                                            </button>
                                        </div>
                                        
                                        <!-- Map Status Overlay -->
                                        <div id="mapStatus" class="absolute bottom-4 left-4 z-[1000] bg-black bg-opacity-75 text-white px-3 py-2 rounded-md text-xs max-w-xs">
                                            Click on the map to set event location
                                        </div>
                                    </div>
                                    
                                    <!-- Map Controls Panel -->
                                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <!-- Coordinates Display -->
                                            <div class="md:col-span-2">
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label for="location_latitude" class="block text-xs font-medium text-gray-600 mb-1">Latitude</label>
                                                        <input type="number" id="location_latitude" name="location_latitude" step="0.000001" readonly class="w-full px-3 py-2 text-xs bg-gray-100 border border-gray-300 rounded-md" 
                                                            value="<?php echo isset($_POST['location_latitude']) ? htmlspecialchars($_POST['location_latitude']) : ''; ?>" placeholder="Click map to set">
                                                    </div>
                                                    <div>
                                                        <label for="location_longitude" class="block text-xs font-medium text-gray-600 mb-1">Longitude</label>
                                                        <input type="number" id="location_longitude" name="location_longitude" step="0.000001" readonly class="w-full px-3 py-2 text-xs bg-gray-100 border border-gray-300 rounded-md" 
                                                            value="<?php echo isset($_POST['location_longitude']) ? htmlspecialchars($_POST['location_longitude']) : ''; ?>" placeholder="Click map to set">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Geofence Radius -->
                                            <div>
                                                <label for="geofence_radius" class="block text-xs font-medium text-gray-600 mb-1">Geofence Radius</label>
                                                <select id="geofence_radius" name="geofence_radius" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                                    <option value="50" <?php echo (isset($_POST['geofence_radius']) && $_POST['geofence_radius'] == 50) ? 'selected' : ''; ?>>50m - Very strict</option>
                                                    <option value="100" <?php echo (!isset($_POST['geofence_radius']) || $_POST['geofence_radius'] == 100) ? 'selected' : ''; ?>>100m - Recommended</option>
                                                    <option value="200" <?php echo (isset($_POST['geofence_radius']) && $_POST['geofence_radius'] == 200) ? 'selected' : ''; ?>>200m - Flexible</option>
                                                    <option value="500" <?php echo (isset($_POST['geofence_radius']) && $_POST['geofence_radius'] == 500) ? 'selected' : ''; ?>>500m - Very flexible</option>
                                                    <option value="1000" <?php echo (isset($_POST['geofence_radius']) && $_POST['geofence_radius'] == 1000) ? 'selected' : ''; ?>>1km - Very flexible</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 text-xs text-gray-500">
                                            <p><strong>📍 Instructions:</strong> Click anywhere on the map to set the event location. The colored circle shows the geofence area where students can mark attendance.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department (Optional)</label>
                                <select id="department" name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['name']); ?>" <?php echo (isset($_POST['department']) && $_POST['department'] === $dept['name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div>
                            <div class="bg-red-50 p-6 rounded-lg mb-6 border border-red-100">
                                <h3 class="text-lg font-semibold text-primary mb-4">Event Dates</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date <span class="text-primary">*</span></label>
                                        <input type="date" id="start_date" name="start_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" 
                                            value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div>
                                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date <span class="text-primary">*</span></label>
                                        <input type="date" id="end_date" name="end_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" 
                                            value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-orange-50 p-6 rounded-lg mb-6 border border-orange-100">
                                <h3 class="text-lg font-semibold text-secondary mb-4">Morning Session</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="morning_time_in" class="block text-sm font-medium text-gray-700 mb-2">Time In <span class="text-primary">*</span></label>
                                        <input type="time" id="morning_time_in" name="morning_time_in" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary" 
                                            value="<?php echo isset($_POST['morning_time_in']) ? htmlspecialchars($_POST['morning_time_in']) : '08:00'; ?>" required>
                                    </div>
                                    
                                    <div>
                                        <label for="morning_time_out" class="block text-sm font-medium text-gray-700 mb-2">Time Out <span class="text-primary">*</span></label>
                                        <input type="time" id="morning_time_out" name="morning_time_out" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary" 
                                            value="<?php echo isset($_POST['morning_time_out']) ? htmlspecialchars($_POST['morning_time_out']) : '12:00'; ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-yellow-50 p-6 rounded-lg mb-6 border border-yellow-100">
                                <h3 class="text-lg font-semibold text-yellow-600 mb-4">Afternoon Session</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="afternoon_time_in" class="block text-sm font-medium text-gray-700 mb-2">Time In <span class="text-primary">*</span></label>
                                        <input type="time" id="afternoon_time_in" name="afternoon_time_in" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" 
                                            value="<?php echo isset($_POST['afternoon_time_in']) ? htmlspecialchars($_POST['afternoon_time_in']) : '13:00'; ?>" required>
                                    </div>
                                    
                                    <div>
                                        <label for="afternoon_time_out" class="block text-sm font-medium text-gray-700 mb-2">Time Out <span class="text-primary">*</span></label>
                                        <input type="time" id="afternoon_time_out" name="afternoon_time_out" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" 
                                            value="<?php echo isset($_POST['afternoon_time_out']) ? htmlspecialchars($_POST['afternoon_time_out']) : '17:00'; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 border-t border-gray-200 pt-6 flex justify-end">
                        <button type="reset" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-6 rounded-lg mr-4 transition duration-300">
                            Reset
                        </button>
                        <button type="submit" class="bg-gradient-primary hover:opacity-90 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Create Event
                        </button>
                    </div>
                </form>
            </div>
        </main>
        
        <?php include '../../includes/footer.php'; ?>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    
    <script>
        // Global variables for map functionality
        let eventLocationMap;
        let eventMarker;
        let geofenceCircle;
        let currentUserMarker;
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            setupMapEventListeners();
        });

        function initializeMap() {
            // Default location (Philippines - adjust as needed)
            const defaultLat = 10.3157;
            const defaultLon = 123.8854;
            
            // Initialize the map with enhanced tiles for 3D-like appearance
            eventLocationMap = L.map('locationMap', {
                center: [defaultLat, defaultLon],
                zoom: 13,
                zoomControl: true,
                attributionControl: true
            });

            // Add multiple tile layers for better visual appeal
            const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics',
                maxZoom: 19
            });

            const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            });

            const topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
                maxZoom: 17
            });

            // Default to street map
            streetLayer.addTo(eventLocationMap);

            // Add layer control
            const baseMaps = {
                "Street": streetLayer,
                "Satellite": satelliteLayer,
                "Topographic": topoLayer
            };

            L.control.layers(baseMaps).addTo(eventLocationMap);

            // Add custom scale with metric and imperial
            L.control.scale({
                metric: true,
                imperial: true,
                position: 'bottomleft'
            }).addTo(eventLocationMap);

            // Add custom coordinates display
            const coordsControl = L.control({position: 'bottomright'});
            coordsControl.onAdd = function(map) {
                this._div = L.DomUtil.create('div', 'coords-control');
                this._div.style.background = 'rgba(255,255,255,0.9)';
                this._div.style.padding = '5px';
                this._div.style.fontSize = '11px';
                this._div.style.borderRadius = '3px';
                this.update();
                return this._div;
            };
            coordsControl.update = function(lat, lng) {
                this._div.innerHTML = lat && lng ? 
                    `Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}` : 
                    'Move mouse on map';
            };
            coordsControl.addTo(eventLocationMap);

            // Update coordinates on mouse move
            eventLocationMap.on('mousemove', function(e) {
                coordsControl.update(e.latlng.lat, e.latlng.lng);
            });

            // Check if we have existing coordinates to display
            const existingLat = document.getElementById('location_latitude').value;
            const existingLon = document.getElementById('location_longitude').value;
            
            if (existingLat && existingLon) {
                setEventLocation(parseFloat(existingLat), parseFloat(existingLon));
                eventLocationMap.setView([existingLat, existingLon], 16);
            }

            // Map click handler for setting location
            eventLocationMap.on('click', function(e) {
                setEventLocation(e.latlng.lat, e.latlng.lng);
                updateMapStatus('Location set! Adjust radius if needed.', 'success');
            });
        }

        function setEventLocation(lat, lng) {
            // Update form inputs
            document.getElementById('location_latitude').value = lat.toFixed(6);
            document.getElementById('location_longitude').value = lng.toFixed(6);

            // Remove existing marker if any
            if (eventMarker) {
                eventLocationMap.removeLayer(eventMarker);
            }

            // Create enhanced marker with custom icon
            const eventIcon = L.divIcon({
                className: 'custom-event-marker',
                html: `
                    <div style="
                        background: linear-gradient(135deg, #EF6161 0%, #f3af3d 100%);
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        border: 3px solid white;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        animation: pulse 2s infinite;
                    ">
                        <div style="
                            width: 12px;
                            height: 12px;
                            background: white;
                            border-radius: 50%;
                        "></div>
                    </div>
                `,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            // Add marker
            eventMarker = L.marker([lat, lng], {
                icon: eventIcon,
                draggable: true
            }).addTo(eventLocationMap);

            // Make marker draggable
            eventMarker.on('dragend', function(e) {
                const newPos = e.target.getLatLng();
                setEventLocation(newPos.lat, newPos.lng);
                updateMapStatus('Location updated by dragging!', 'success');
            });

            // Add popup with location info
            eventMarker.bindPopup(`
                <div style="text-align: center;">
                    <strong>📍 Event Location</strong><br>
                    <small>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}</small><br>
                    <em>Drag to adjust position</em>
                </div>
            `).openPopup();

            // Update geofence circle
            updateGeofenceCircle(lat, lng);
        }

        function updateGeofenceCircle(lat, lng) {
            const radius = parseInt(document.getElementById('geofence_radius').value);

            // Remove existing circle
            if (geofenceCircle) {
                eventLocationMap.removeLayer(geofenceCircle);
            }

            // Create gradient circle with animation
            geofenceCircle = L.circle([lat, lng], {
                radius: radius,
                fillColor: '#3b82f6',
                fillOpacity: 0.2,
                color: '#1d4ed8',
                weight: 2,
                opacity: 0.8,
                dashArray: '5, 5',
                className: 'geofence-circle'
            }).addTo(eventLocationMap);

            // Add radius label
            const radiusPopup = L.popup({
                closeButton: false,
                autoClose: false,
                closeOnClick: false,
                className: 'radius-popup'
            })
            .setLatLng([lat, lng])
            .setContent(`
                <div style="text-align: center; font-size: 12px;">
                    <strong>Geofence Radius</strong><br>
                    ${radius}m (${(radius/1000).toFixed(2)}km)
                </div>
            `);

            // Show radius popup temporarily
            setTimeout(() => {
                radiusPopup.openOn(eventLocationMap);
                setTimeout(() => {
                    eventLocationMap.closePopup(radiusPopup);
                }, 3000);
            }, 500);
        }

        function setupMapEventListeners() {
            // Get current location button
            document.getElementById('getCurrentLocationMap').addEventListener('click', function() {
                const button = this;
                const originalContent = button.innerHTML;
                
                button.innerHTML = `
                    <svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Getting...
                `;
                button.disabled = true;

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            
                            setEventLocation(lat, lng);
                            eventLocationMap.setView([lat, lng], 16);
                            
                            // Add user location marker
                            if (currentUserMarker) {
                                eventLocationMap.removeLayer(currentUserMarker);
                            }
                            
                            currentUserMarker = L.marker([lat, lng], {
                                icon: L.divIcon({
                                    className: 'user-location-marker',
                                    html: `
                                        <div style="
                                            background: #10b981;
                                            width: 20px;
                                            height: 20px;
                                            border-radius: 50%;
                                            border: 2px solid white;
                                            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                                        "></div>
                                    `,
                                    iconSize: [20, 20],
                                    iconAnchor: [10, 10]
                                })
                            }).addTo(eventLocationMap);
                            
                            currentUserMarker.bindPopup('📱 Your Current Location').openPopup();
                            
                            button.innerHTML = originalContent;
                            button.disabled = false;
                            updateMapStatus('Current location set successfully!', 'success');
                        },
                        function(error) {
                            button.innerHTML = originalContent;
                            button.disabled = false;
                            updateMapStatus('Failed to get location: ' + error.message, 'error');
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    updateMapStatus('Geolocation not supported by browser', 'error');
                }
            });

            // Clear location button
            document.getElementById('clearLocationMap').addEventListener('click', function() {
                clearEventLocation();
                updateMapStatus('Location cleared', 'info');
            });

            // Radius change handler
            document.getElementById('geofence_radius').addEventListener('change', function() {
                const lat = document.getElementById('location_latitude').value;
                const lng = document.getElementById('location_longitude').value;
                
                if (lat && lng) {
                    updateGeofenceCircle(parseFloat(lat), parseFloat(lng));
                    updateMapStatus(`Geofence updated to ${this.value}m radius`, 'info');
                }
            });
        }

        function clearEventLocation() {
            // Clear form inputs
            document.getElementById('location_latitude').value = '';
            document.getElementById('location_longitude').value = '';

            // Remove markers and circles
            if (eventMarker) {
                eventLocationMap.removeLayer(eventMarker);
                eventMarker = null;
            }
            if (geofenceCircle) {
                eventLocationMap.removeLayer(geofenceCircle);
                geofenceCircle = null;
            }
            if (currentUserMarker) {
                eventLocationMap.removeLayer(currentUserMarker);
                currentUserMarker = null;
            }
        }

        function updateMapStatus(message, type = 'info') {
            const statusDiv = document.getElementById('mapStatus');
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600'
            };
            
            statusDiv.className = `absolute bottom-4 left-4 z-[1000] ${colors[type]} text-white px-3 py-2 rounded-md text-xs max-w-xs`;
            statusDiv.textContent = message;
            statusDiv.style.display = 'block';
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 3000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { box-shadow: 0 0 0 0 rgba(239, 97, 97, 0.7); }
                70% { box-shadow: 0 0 0 10px rgba(239, 97, 97, 0); }
                100% { box-shadow: 0 0 0 0 rgba(239, 97, 97, 0); }
            }
            
            .geofence-circle {
                animation: fadeIn 0.5s ease-in-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .leaflet-popup-content-wrapper {
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .radius-popup .leaflet-popup-content-wrapper {
                background: linear-gradient(135deg, #1d4ed8, #3b82f6);
                color: white;
            }
            
            .radius-popup .leaflet-popup-tip {
                background: #1d4ed8;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html> 