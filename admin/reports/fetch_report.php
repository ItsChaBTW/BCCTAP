<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Get filter parameters from AJAX request
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');
$department = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$event = isset($_GET['event']) ? intval($_GET['event']) : 0;
$attendance_status = isset($_GET['attendance_status']) ? sanitize($_GET['attendance_status']) : '';

// Build the attendance query with filters
$query = "SELECT a.id, a.time_recorded, a.session, a.status, a.attendance_status,
                 u.id as user_id, u.full_name as student_name, u.student_id as student_id, u.department, 
                 e.id as event_id, e.title as event_title
          FROM attendance a
          INNER JOIN users u ON a.user_id = u.id
          INNER JOIN events e ON a.event_id = e.id
          WHERE a.time_recorded BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
$params = [$start_date, $end_date];
$types = "ss";

if (!empty($department)) {
    $query .= " AND u.department = ?";
    $params[] = $department;
    $types .= "s";
}

if ($event > 0) {
    $query .= " AND e.id = ?";
    $params[] = $event;
    $types .= "i";
}

if (!empty($attendance_status) && in_array($attendance_status, ['present','late','absent'])) {
    $query .= " AND a.attendance_status = ?";
    $params[] = $attendance_status;
    $types .= "s";
}

$query .= " ORDER BY a.time_recorded DESC LIMIT 1000";

$stmt = mysqli_prepare($conn, $query);
if ($stmt === false) {
    echo json_encode(['error' => 'Query prepare failed']);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Return as JSON
echo json_encode(['data' => $attendance_records]); 