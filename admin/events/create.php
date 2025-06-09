<?php
/**
 * Create Event Page for Admins
 */
require_once '../../config/config.php';

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
    
    // If no errors, create the event
    if (empty($errors)) {
        $query = "INSERT INTO events (uuid, title, description, start_date, end_date, morning_time_in, morning_time_out, 
                                       afternoon_time_in, afternoon_time_out, department, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssssssi", $event_uuid, $title, $description, $start_date, $end_date, $morning_time_in, 
                               $morning_time_out, $afternoon_time_in, $afternoon_time_out, $department, $_SESSION['user_id']);
        
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
</body>
</html> 