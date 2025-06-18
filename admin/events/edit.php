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

// Get departments for dropdown
$query = "SELECT * FROM departments ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($result, MYSQLI_ASSOC);

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
$page_actions = '<a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
    </svg>
    Back to Events
</a>';

// Start output buffering
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <form method="POST" action="" class="space-y-6">
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div>
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Event Title <span class="text-primary">*</span></label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($event['description']); ?></textarea>
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
                                                <input type="number" id="location_latitude" name="location_latitude" step="0.000001" value="<?php echo $event['location_latitude']; ?>" class="w-full px-3 py-2 text-xs bg-gray-100 border border-gray-300 rounded-md" placeholder="Click map to set">
                                            </div>
                                            <div>
                                                <label for="location_longitude" class="block text-xs font-medium text-gray-600 mb-1">Longitude</label>
                                                <input type="number" id="location_longitude" name="location_longitude" step="0.000001" value="<?php echo $event['location_longitude']; ?>" class="w-full px-3 py-2 text-xs bg-gray-100 border border-gray-300 rounded-md" placeholder="Click map to set">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Geofence Radius -->
                                    <div>
                                        <label for="geofence_radius" class="block text-xs font-medium text-gray-600 mb-1">Geofence Radius</label>
                                        <select id="geofence_radius" name="geofence_radius" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                            <option value="50" <?php echo $event['geofence_radius'] == 50 ? 'selected' : ''; ?>>50m - Very strict</option>
                                            <option value="100" <?php echo $event['geofence_radius'] == 100 ? 'selected' : ''; ?>>100m - Recommended</option>
                                            <option value="200" <?php echo $event['geofence_radius'] == 200 ? 'selected' : ''; ?>>200m - Flexible</option>
                                            <option value="500" <?php echo $event['geofence_radius'] == 500 ? 'selected' : ''; ?>>500m - Very flexible</option>
                                            <option value="1000" <?php echo $event['geofence_radius'] == 1000 ? 'selected' : ''; ?>>1km - Very flexible</option>
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
                                <option value="<?php echo htmlspecialchars($dept['name']); ?>" <?php echo $event['department'] == $dept['name'] ? 'selected' : ''; ?>>
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
                                <input type="date" id="start_date" name="start_date" value="<?php echo $event['start_date']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                            </div>
                            
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date <span class="text-primary">*</span></label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $event['end_date']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-orange-50 p-6 rounded-lg mb-6 border border-orange-100">
                        <h3 class="text-lg font-semibold text-secondary mb-4">Morning Session</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="morning_time_in" class="block text-sm font-medium text-gray-700 mb-2">Time In <span class="text-primary">*</span></label>
                                <input type="time" id="morning_time_in" name="morning_time_in" value="<?php echo $event['morning_time_in']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary" required>
                            </div>
                            
                            <div>
                                <label for="morning_time_out" class="block text-sm font-medium text-gray-700 mb-2">Time Out <span class="text-primary">*</span></label>
                                <input type="time" id="morning_time_out" name="morning_time_out" value="<?php echo $event['morning_time_out']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-secondary" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 p-6 rounded-lg mb-6 border border-yellow-100">
                        <h3 class="text-lg font-semibold text-yellow-600 mb-4">Afternoon Session</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="afternoon_time_in" class="block text-sm font-medium text-gray-700 mb-2">Time In <span class="text-primary">*</span></label>
                                <input type="time" id="afternoon_time_in" name="afternoon_time_in" value="<?php echo $event['afternoon_time_in']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" required>
                            </div>
                            
                            <div>
                                <label for="afternoon_time_out" class="block text-sm font-medium text-gray-700 mb-2">Time Out <span class="text-primary">*</span></label>
                                <input type="time" id="afternoon_time_out" name="afternoon_time_out" value="<?php echo $event['afternoon_time_out']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" required>
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
                    Update Event
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Initialize map
    const map = L.map('locationMap').setView([
        <?php echo $event['location_latitude'] ?: '10.5387'; ?>, 
        <?php echo $event['location_longitude'] ?: '122.8307'; ?>
    ], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    let marker = null;
    let circle = null;
    
    // Add marker and circle if coordinates exist
    if (<?php echo $event['location_latitude'] ? 'true' : 'false'; ?>) {
        marker = L.marker([
            <?php echo $event['location_latitude']; ?>, 
            <?php echo $event['location_longitude']; ?>
        ]).addTo(map);
        
        circle = L.circle([
            <?php echo $event['location_latitude']; ?>, 
            <?php echo $event['location_longitude']; ?>
        ], {
            radius: <?php echo $event['geofence_radius'] ?: '100'; ?>,
            color: '#EF6161',
            fillColor: '#EF6161',
            fillOpacity: 0.2
        }).addTo(map);
        
        // Update status
        document.getElementById('mapStatus').textContent = `Location set: ${<?php echo $event['location_latitude']; ?>}, ${<?php echo $event['location_longitude']; ?>}`;
    }
    
    // Handle map clicks
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Update input fields
        document.getElementById('location_latitude').value = lat.toFixed(6);
        document.getElementById('location_longitude').value = lng.toFixed(6);
        
        // Update marker
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }
        
        // Update circle
        const radius = parseInt(document.getElementById('geofence_radius').value);
        if (circle) {
            circle.setLatLng(e.latlng);
            circle.setRadius(radius);
        } else {
            circle = L.circle(e.latlng, {
                radius: radius,
                color: '#EF6161',
                fillColor: '#EF6161',
                fillOpacity: 0.2
            }).addTo(map);
        }
        
        // Update status
        document.getElementById('mapStatus').textContent = `Location set: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    });
    
    // Handle geofence radius changes
    document.getElementById('geofence_radius').addEventListener('change', function() {
        if (marker && circle) {
            circle.setRadius(parseInt(this.value));
        }
    });
    
    // Get current location
    document.getElementById('getCurrentLocationMap').addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                map.setView([lat, lng], 15);
                
                // Update input fields
                document.getElementById('location_latitude').value = lat.toFixed(6);
                document.getElementById('location_longitude').value = lng.toFixed(6);
                
                // Update marker
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng]).addTo(map);
                }
                
                // Update circle
                const radius = parseInt(document.getElementById('geofence_radius').value);
                if (circle) {
                    circle.setLatLng([lat, lng]);
                    circle.setRadius(radius);
                } else {
                    circle = L.circle([lat, lng], {
                        radius: radius,
                        color: '#EF6161',
                        fillColor: '#EF6161',
                        fillOpacity: 0.2
                    }).addTo(map);
                }
                
                // Update status
                document.getElementById('mapStatus').textContent = `Location set: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
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
        
        document.getElementById('location_latitude').value = '';
        document.getElementById('location_longitude').value = '';
        document.getElementById('mapStatus').textContent = 'Click on the map to set event location';
    });
</script>

<?php
$page_content = ob_get_clean();

// Include admin layout
require_once '../../includes/admin_layout.php';
?> 