<?php
/**
 * View QR Codes for an Event
 */
require_once '../../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . 'admin/login.php');
}

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No event specified.";
    redirect(BASE_URL . 'admin/qrcodes/index.php');
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
    redirect(BASE_URL . 'admin/qrcodes/index.php');
}

$event = mysqli_fetch_assoc($result);

// Get QR code for this event (don't filter by session)
$query = "SELECT * FROM qr_codes WHERE event_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$qrCode = mysqli_fetch_assoc($result);

// Check if ANY QR codes exist for this event before generating new one
$query = "SELECT COUNT(*) as total FROM qr_codes WHERE event_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$qrCount = mysqli_fetch_assoc($result);

// If no QR codes exist at all for this event, automatically generate one
if ($qrCount['total'] == 0) {
    // Check if event UUID exists
    if (!isset($event['uuid']) || empty($event['uuid'])) {
        // Generate a UUID if it doesn't exist (for compatibility with older events)
        $event_uuid = generate_uuid();
        
        // Update the event with the new UUID
        $query = "UPDATE events SET uuid = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $event_uuid, $event_id);
        mysqli_stmt_execute($stmt);
    } else {
        $event_uuid = $event['uuid'];
    }
    
    // Generate new QR code
    $query = "INSERT INTO qr_codes (event_id, code, session) VALUES (?, ?, 'combined')";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $event_id, $event_uuid);
    
    if (mysqli_stmt_execute($stmt)) {
        $qr_code_id = mysqli_insert_id($conn);
        
        // Generate and save QR code image
        require_once '../../utils/QrCodeGenerator.php';
        
        try {
            // Create the scan URL using the configured BASE_URL
            $scan_url = BASE_URL . 'scan.php?code=' . urlencode($event_uuid);
            
            // Generate filename from event ID and QR code ID
            $filename = "event_{$event_id}_qr_{$qr_code_id}.png";
            
            // Generate the QR code image
            $qr_image_path = QrCodeGenerator::generate(
                $scan_url,
                $filename,
                '../../uploads/qrcodes',
                300,
                htmlspecialchars($event['title'])
            );
            
            // Update the QR code record with the image path
            $update_query = "UPDATE qr_codes SET image_path = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            $relative_path = 'uploads/qrcodes/' . basename($qr_image_path);
            mysqli_stmt_bind_param($stmt, "si", $relative_path, $qr_code_id);
            mysqli_stmt_execute($stmt);
            
            $_SESSION['success_message'] = "QR code generated successfully.";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error generating QR code image: " . $e->getMessage();
        }
        
        // Fetch the newly created QR code
        $query = "SELECT * FROM qr_codes WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $qr_code_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $qrCode = mysqli_fetch_assoc($result);
    }
}

// Function to generate a UUID v4 (same as in create.php)
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View QR Code - BCCTAP</title>
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
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
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-primary">QR Code for: <?php echo htmlspecialchars($event['title']); ?></h1>
                <button onclick="history.back()" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Back
                </button>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800"><?php echo $_SESSION['success_message']; ?></p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button type="button" class="close-alert inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800"><?php echo $_SESSION['error_message']; ?></p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button type="button" class="close-alert inline-flex bg-red-50 rounded-md p-1.5 text-red-500 hover:bg-red-100">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Event Details -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-primary mb-4">Event Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600"><span class="font-medium">Dates:</span> <?php echo date('M d, Y', strtotime($event['start_date'])); ?> - <?php echo date('M d, Y', strtotime($event['end_date'])); ?></p>
                        <p class="text-gray-600"><span class="font-medium">Department:</span> <?php echo !empty($event['department']) ? htmlspecialchars($event['department']) : 'All Departments'; ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600"><span class="font-medium">Morning:</span> <?php echo date('h:i A', strtotime($event['morning_time_in'])); ?> - <?php echo date('h:i A', strtotime($event['morning_time_out'])); ?></p>
                        <p class="text-gray-600"><span class="font-medium">Afternoon:</span> <?php echo date('h:i A', strtotime($event['afternoon_time_in'])); ?> - <?php echo date('h:i A', strtotime($event['afternoon_time_out'])); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- QR Code -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Attendance QR Code</h2>
                
                <?php if ($qrCode): ?>
                        <div class="flex flex-col items-center">
                        <div class="bg-gradient-primary text-white py-2 px-4 rounded-lg mb-4">
                            <p class="text-center font-medium">This QR code is for both morning and afternoon sessions</p>
                        </div>
                        
                        <div id="eventQrCode" class="mb-4 p-4 border-4 border-gray-100 rounded-lg">
                            <?php if (!empty($qrCode['image_path'])): ?>
                                <!-- Display the saved QR code image -->
                                <img src="<?php echo BASE_URL . $qrCode['image_path']; ?>" alt="QR Code" class="max-w-full" style="width: 300px; height: 300px;">
                    <?php else: ?>
                                <?php
                                // If QR code exists but image_path is empty, generate the image
                                if ($qrCode && empty($qrCode['image_path'])) {
                                    require_once '../../utils/QrCodeGenerator.php';
                                    
                                    try {
                                        // Create the scan URL using the configured BASE_URL
                                        $scan_url = BASE_URL . 'scan.php?code=' . urlencode($qrCode['code']);
                                        
                                        // Generate filename
                                        $filename = "event_{$event_id}_qr_{$qrCode['id']}.png";
                                        
                                        // Generate the QR code image
                                        $qr_image_path = QrCodeGenerator::generate(
                                            $scan_url,
                                            $filename,
                                            '../../uploads/qrcodes',
                                            300,
                                            htmlspecialchars($event['title'])
                                        );
                                        
                                        // Update the QR code record with the image path
                                        $update_query = "UPDATE qr_codes SET image_path = ? WHERE id = ?";
                                        $stmt = mysqli_prepare($conn, $update_query);
                                        $relative_path = 'uploads/qrcodes/' . basename($qr_image_path);
                                        mysqli_stmt_bind_param($stmt, "si", $relative_path, $qrCode['id']);
                                        mysqli_stmt_execute($stmt);
                                        
                                        // Show the generated image
                                        echo '<img src="' . BASE_URL . $relative_path . '" alt="QR Code" class="max-w-full" style="width: 300px; height: 300px;">';
                                    } catch (Exception $e) {
                                        error_log("Failed to generate QR code image: " . $e->getMessage());
                                        // Fall back to JS generation
                                        echo '<div id="jsQrCode" class="w-[300px] h-[300px] flex items-center justify-center">';
                                        echo '<span class="text-gray-500">Loading QR Code...</span>';
                                        echo '</div>';
                                    }
                                } else {
                                    // No image path and no QR code data, use JS fallback
                                    echo '<div id="jsQrCode" class="w-[300px] h-[300px] flex items-center justify-center">';
                                    echo '<span class="text-gray-500">Loading QR Code...</span>';
                                    echo '</div>';
                                }
                                
                                // Log that we're using fallback for debugging
                                error_log("QR Code image_path is empty for event_id: " . $event_id . ", using fallback");
                                ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500 mb-2">Code: <?php echo htmlspecialchars($qrCode['code']); ?></p>
                        <p class="text-xs text-gray-400 mb-4">Created: <?php echo date('M d, Y h:i A', strtotime($qrCode['created_at'])); ?></p>
                        
                        <div class="flex space-x-4">
                            <button class="bg-secondary hover:bg-orange-600 text-white px-4 py-2 rounded-lg flex items-center" onclick="printQRCode('eventQrCode', '<?php echo htmlspecialchars($event['title']); ?>')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0v3H7V4h6zm-3 11v-2h2v2H10z" clip-rule="evenodd" />
                                </svg>
                                Print
                            </button>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center" onclick="downloadQRCode('<?php echo BASE_URL . $qrCode['image_path']; ?>', '<?php echo htmlspecialchars($event['title']); ?>')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                Download
                            </button>
                            </div>
                        </div>
                    <?php else: ?>
                    <div class="text-center p-10 bg-gray-50 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No QR code found for this event</h3>
                        <p class="mt-1 text-sm text-gray-500">QR codes are automatically generated when an event is created</p>
                        </div>
                    <?php endif; ?>
            </div>
            
            <!-- Instructions -->
            <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Instructions</h2>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-6 w-6 rounded-full bg-red-100 flex items-center justify-center text-primary mr-3">
                            1
                        </div>
                        <p class="text-gray-600">Print the QR code and post it in a visible location at your event.</p>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-6 w-6 rounded-full bg-orange-100 flex items-center justify-center text-secondary mr-3">
                            2
                        </div>
                        <p class="text-gray-600">Students scan this QR code using their phone's camera app.</p>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-6 w-6 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-500 mr-3">
                            3
                        </div>
                        <p class="text-gray-600">Students will be redirected to login with their student account.</p>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-6 w-6 rounded-full bg-green-100 flex items-center justify-center text-green-500 mr-3">
                            4
                        </div>
                        <p class="text-gray-600">The system will automatically record their attendance based on the current time (morning or afternoon).</p>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-500 mr-3">
                            5
                        </div>
                        <p class="text-gray-600">Students can view their attendance records in their dashboard after logging in.</p>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include '../../includes/footer.php'; ?>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        // Generate QR code when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($qrCode): ?>
                <?php if (empty($qrCode['image_path'])): ?>
                // Only generate JS QR code if we don't have an image path
                const scanUrl = '<?php echo BASE_URL; ?>scan.php?code=<?php echo urlencode($qrCode['code']); ?>';
                generateQRCode('jsQrCode', scanUrl);
                <?php endif; ?>
            <?php endif; ?>
            
            // Close alert buttons
            document.querySelectorAll('.close-alert').forEach(button => {
                button.addEventListener('click', () => {
                    button.closest('.bg-green-50, .bg-red-50').remove();
                });
            });
        });
        
        // Function to generate a QR code
        function generateQRCode(elementId, data) {
            var qr = qrcode(0, 'M');
            qr.addData(data);
            qr.make();
            
            document.getElementById(elementId).innerHTML = qr.createImgTag(5);
        }
        
        // Function to print a QR code
        function printQRCode(elementId, title) {
            const printWindow = window.open('', '_blank');
            const qrCodeImg = document.getElementById(elementId).querySelector('img').src;
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Print QR Code - ${title}</title>
                    <style>
                        body {
                            font-family: 'Poppins', Arial, sans-serif;
                            text-align: center;
                            padding: 20px;
                        }
                        h1 {
                            font-size: 24px;
                            margin-bottom: 10px;
                            color: #EF6161;
                        }
                        .subtitle {
                            font-size: 16px;
                            margin-bottom: 30px;
                            color: #f3af3d;
                        }
                        .qr-container {
                            margin-bottom: 20px;
                            padding: 15px;
                            border: 5px solid #f1f1f1;
                            display: inline-block;
                            border-radius: 10px;
                        }
                        img {
                            max-width: 300px;
                            height: auto;
                        }
                        .instructions {
                            margin-top: 20px;
                            font-size: 14px;
                            color: #555;
                            text-align: left;
                            max-width: 400px;
                            margin-left: auto;
                            margin-right: auto;
                        }
                        .instruction-step {
                            margin-bottom: 8px;
                        }
                        .footer {
                            margin-top: 30px;
                            font-size: 14px;
                            color: #666;
                            border-top: 1px solid #eee;
                            padding-top: 15px;
                        }
                    </style>
                </head>
                <body>
                    <h1>${title}</h1>
                    <p class="subtitle">Attendance QR Code</p>
                    <div class="qr-container">
                        <img src="${qrCodeImg}" alt="QR Code">
                    </div>
                    <p>Scan this QR code to record your attendance</p>
                    
                    <div class="instructions">
                        <div class="instruction-step">1. Open your camera app and scan this QR code</div>
                        <div class="instruction-step">2. Login with your student credentials</div>
                        <div class="instruction-step">3. Your attendance will be recorded automatically</div>
                    </div>
                    
                    <div class="footer">
                        <p>Bago City College Time Attendance Platform</p>
                        <p>Event: ${title}</p>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            // Print after a short delay to ensure content is loaded
            setTimeout(function() {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
        
        // Function to download a QR code
        function downloadQRCode(qrCodeImageUrl, eventTitle) {
            const link = document.createElement('a');
            link.href = qrCodeImageUrl;
            link.download = `${eventTitle}_QR_Code.png`;
            link.click();
        }
    </script>
</body>
</html> 