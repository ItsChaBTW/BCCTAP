<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'utils/AttendanceStatus.php';

// Initialize the AttendanceStatus class
$attendanceStatus = new AttendanceStatus();

echo "Testing Attendance Status System\n";
echo "==============================\n\n";

// Test 1: Get current late interval
echo "Test 1: Current Late Interval\n";
echo "----------------------------\n";
$currentInterval = $attendanceStatus->getLateInterval();
echo "Current late interval: {$currentInterval} minutes\n\n";

// Test 2: Set new late interval
echo "Test 2: Setting New Late Interval\n";
echo "-------------------------------\n";
$newInterval = 20;
echo "Setting late interval to {$newInterval} minutes...\n";
if ($attendanceStatus->setLateInterval($newInterval)) {
    echo "Successfully updated late interval\n";
    echo "New interval: " . $attendanceStatus->getLateInterval() . " minutes\n";
} else {
    echo "Failed to update late interval\n";
}
echo "\n";

// Test 3: Test attendance status determination
echo "Test 3: Testing Attendance Status Determination\n";
echo "--------------------------------------------\n";

// Get an existing event from the database
$stmt = $conn->prepare("SELECT id, morning_time_in, afternoon_time_in FROM events LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if ($event) {
    echo "Testing with Event ID: {$event['id']}\n";
    echo "Morning time-in: {$event['morning_time_in']}\n";
    echo "Afternoon time-in: {$event['afternoon_time_in']}\n\n";

    // Test cases for morning session
    $testTimes = [
        'on_time' => date('Y-m-d ') . $event['morning_time_in'],
        'late' => date('Y-m-d ') . date('H:i:s', strtotime($event['morning_time_in'] . ' +10 minutes')),
        'very_late' => date('Y-m-d ') . date('H:i:s', strtotime($event['morning_time_in'] . ' +30 minutes'))
    ];

    foreach ($testTimes as $case => $time) {
        $status = $attendanceStatus->determineAttendanceStatus($event['id'], 'morning', $time);
        echo "Test case '{$case}' ({$time}): {$status}\n";
    }
} else {
    echo "No events found in the database\n";
}

// Test 4: Update existing attendance records
echo "\nTest 4: Updating Existing Attendance Records\n";
echo "------------------------------------------\n";

// Get an existing attendance record
$stmt = $conn->prepare("SELECT id FROM attendance LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();

if ($attendance) {
    echo "Updating attendance record ID: {$attendance['id']}\n";
    if ($attendanceStatus->updateAttendanceStatus($attendance['id'])) {
        echo "Successfully updated attendance status\n";
        
        // Verify the update
        $stmt = $conn->prepare("SELECT attendance_status FROM attendance WHERE id = ?");
        $stmt->bind_param("i", $attendance['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $updated = $result->fetch_assoc();
        echo "New status: {$updated['attendance_status']}\n";
    } else {
        echo "Failed to update attendance status\n";
    }
} else {
    echo "No attendance records found in the database\n";
}

// Reset late interval to original value
$attendanceStatus->setLateInterval($currentInterval);
echo "\nReset late interval back to {$currentInterval} minutes\n";

echo "\nTest completed!\n"; 