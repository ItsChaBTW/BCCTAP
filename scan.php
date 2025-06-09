<?php
/**
 * QR Code Scanner Handler
 * This file processes QR code scans and records attendance
 */
require_once 'config/config.php';

// Check if QR code is provided
if (!isset($_GET['code']) || empty($_GET['code'])) {
    $_SESSION['error_message'] = "Invalid QR code.";
    redirect(BASE_URL);
}

$code = sanitize($_GET['code']);

// Find QR code in database
$query = "SELECT qc.*, e.id as event_id, e.title, e.start_date, e.end_date, 
         e.morning_time_in, e.morning_time_out, e.afternoon_time_in, e.afternoon_time_out,
         e.department
         FROM qr_codes qc 
         JOIN events e ON qc.event_id = e.id 
         WHERE qc.code = ?";

// Log the query for debugging
error_log("QR Scan - Searching for QR code: " . $code);

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// If QR code not found
if (mysqli_num_rows($result) === 0) {
    error_log("QR Scan - Invalid QR code: " . $code);
    $_SESSION['error_message'] = "Invalid QR code. This QR code does not exist or has been regenerated.";
    redirect(BASE_URL);
}

$qrCode = mysqli_fetch_assoc($result);
$event_id = $qrCode['event_id'];
$event_title = $qrCode['title'];
$event_department = $qrCode['department'];

// Log found event
error_log("QR Scan - Found event: ID=" . $event_id . ", Title=" . $event_title);

// Save QR code data in session for after login
$_SESSION['qr_scan'] = [
    'code' => $code,
    'event_id' => $event_id,
    'event_title' => $event_title,
    'qr_code_id' => $qrCode['id']
];

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("QR Scan - User not logged in, redirecting to login page");
    // Save return URL in session using getAbsoluteUrl for consistent path handling
    $_SESSION['redirect_after_login'] = getAbsoluteUrl('record_attendance.php');
    // Redirect to student login
    redirect(BASE_URL . 'student/login.php');
    exit;
}

// User is logged in, redirect to record attendance
error_log("QR Scan - User logged in (ID: " . $_SESSION['user_id'] . "), redirecting to record_attendance.php");
redirect(BASE_URL . 'record_attendance.php');
?> 