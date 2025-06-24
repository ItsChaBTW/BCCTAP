<?php
/**
 * Excel Export for Department Head Attendance Records
 */
require_once '../config/config.php';

// Check if user is logged in and is a teacher (department head)
if (!isLoggedIn() || !isTeacher()) {
    redirect(BASE_URL . 'teacher/login.php');
}

// Get the department head's department
$query = "SELECT department FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$teacher = mysqli_fetch_assoc($result);
$department = $teacher['department'];

// Get filter values from URL parameters
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$session = isset($_GET['session']) ? sanitize($_GET['session']) : '';
$date = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$section_filter = isset($_GET['section']) ? sanitize($_GET['section']) : '';
$year_level_filter = isset($_GET['year_level']) ? intval($_GET['year_level']) : 0;

// Build the query
$query = "SELECT a.*, u.full_name as student_name, u.student_id, u.department as student_department, u.year_level, u.section,
                 e.title as event_title, e.department as event_department
          FROM attendance a 
          INNER JOIN users u ON a.user_id = u.id 
          INNER JOIN events e ON a.event_id = e.id 
          WHERE u.department = ?";

$params = [$department];
$types = "s";

if ($event_id > 0) {
    $query .= " AND a.event_id = ?";
    $params[] = $event_id;
    $types .= "i";
}

if (!empty($session)) {
    $query .= " AND a.session = ?";
    $params[] = $session;
    $types .= "s";
}

if (!empty($date)) {
    $query .= " AND DATE(a.time_recorded) = ?";
    $params[] = $date;
    $types .= "s";
}

if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($section_filter)) {
    $query .= " AND u.section = ?";
    $params[] = $section_filter;
    $types .= "s";
}

if ($year_level_filter > 0) {
    $query .= " AND u.year_level = ?";
    $params[] = $year_level_filter;
    $types .= "i";
}

$query .= " ORDER BY a.time_recorded DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Generate filename
$filename = $department . '_Attendance_Report';
if ($year_level_filter > 0) $filename .= '_Year' . $year_level_filter;
if (!empty($section_filter)) $filename .= '_Section' . $section_filter;
if (!empty($date)) $filename .= '_' . $date;
$filename .= '_' . date('Y-m-d_H-i-s') . '.csv';

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Write BOM for UTF-8 (helps with Excel compatibility)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header information
fputcsv($output, ['Bago City College Time Attendance Platform']);
fputcsv($output, [$department . ' Department Attendance Report']);
fputcsv($output, ['Generated on: ' . date('F d, Y h:i A')]);

// Add filter information
$filter_info = [];
if ($year_level_filter > 0) {
    $filter_info[] = 'Year Level: ' . $year_level_filter;
}
if (!empty($section_filter)) {
    $filter_info[] = 'Section: ' . $section_filter;
}
if (!empty($session)) {
    $filter_info[] = 'Session: ' . ucfirst($session);
}
if (!empty($status)) {
    $filter_info[] = 'Status: ' . str_replace('_', ' ', ucfirst($status));
}
if (!empty($date)) {
    $filter_info[] = 'Date: ' . date('F d, Y', strtotime($date));
}

if (!empty($filter_info)) {
    fputcsv($output, ['Filters Applied: ' . implode(', ', $filter_info)]);
}

// Add empty row
fputcsv($output, []);

// Calculate summary statistics
$total_records = count($attendance_records);
$present_count = count(array_filter($attendance_records, function($r) { return $r['status'] === 'present'; }));
$late_count = count(array_filter($attendance_records, function($r) { return $r['status'] === 'late'; }));
$excused_count = count(array_filter($attendance_records, function($r) { return $r['status'] === 'excused'; }));
$unique_students = count(array_unique(array_map(function($record) {
    return $record['student_id'];
}, $attendance_records)));

// Write summary
fputcsv($output, ['SUMMARY STATISTICS']);
fputcsv($output, ['Total Records:', $total_records]);
fputcsv($output, ['Present:', $present_count]);
fputcsv($output, ['Late:', $late_count]);
fputcsv($output, ['Excused:', $excused_count]);
fputcsv($output, ['Unique Students:', $unique_students]);
fputcsv($output, []);

// Write column headers
fputcsv($output, [
    'Student Name',
    'Student ID', 
    'Year Level',
    'Section',
    'Event',
    'Date',
    'Session',
    'Status',
    'Time Recorded',
    'Location (if available)'
]);

// Write data rows
foreach ($attendance_records as $record) {
    $location = '';
    if (!empty($record['latitude']) && !empty($record['longitude'])) {
        $location = $record['latitude'] . ', ' . $record['longitude'];
    } else {
        $location = 'No location data';
    }
    
    // Format Student ID as text to prevent Excel scientific notation
    $student_id = "'" . $record['student_id']; // Prefix with apostrophe to force text format
    
    // Clean and format data
    $student_name = !empty($record['student_name']) ? $record['student_name'] : 'Unknown Student';
    $year_level = !empty($record['year_level']) ? 'Year ' . $record['year_level'] : 'Year N/A';
    $section = !empty($record['section']) ? 'Section ' . $record['section'] : 'Section N/A';
    $event_title = !empty($record['event_title']) ? $record['event_title'] : 'Unknown Event';
    
    fputcsv($output, [
        $student_name,
        $student_id,
        $year_level,
        $section,
        $event_title,
        date('F d, Y', strtotime($record['time_recorded'])),
        ucfirst($record['session']),
        str_replace('_', ' ', ucfirst($record['status'])),
        date('h:i:s A', strtotime($record['time_recorded'])),
        $location
    ]);
}

fclose($output);
exit;
?> 