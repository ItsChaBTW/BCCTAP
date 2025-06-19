<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class AttendanceStatus {
    private $db;
    private $lateInterval;

    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->loadLateInterval();
    }

    private function loadLateInterval() {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE name = 'late_interval_minutes'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $this->lateInterval = $row ? (int)$row['value'] : 15; // Default to 15 minutes if not set
    }

    public function determineAttendanceStatus($eventId, $session, $timeRecorded) {
        // Get event schedule
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN ? = 'morning' THEN morning_time_in
                    ELSE afternoon_time_in
                END as scheduled_time
            FROM events 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $session, $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();

        if (!$event) {
            return 'absent'; // Event not found
        }

        $scheduledTime = strtotime($event['scheduled_time']);
        $recordedTime = strtotime($timeRecorded);
        
        // Calculate time difference in minutes
        $timeDiff = ($recordedTime - $scheduledTime) / 60;

        if ($timeDiff <= 0) {
            return 'present'; // On time
        } elseif ($timeDiff <= $this->lateInterval) {
            return 'late'; // Within late interval
        } else {
            return 'absent'; // Beyond late interval
        }
    }

    public function updateAttendanceStatus($attendanceId) {
        // Get attendance record
        $stmt = $this->db->prepare("
            SELECT a.*, e.id as event_id, e.morning_time_in, e.afternoon_time_in
            FROM attendance a
            JOIN events e ON a.event_id = e.id
            WHERE a.id = ?
        ");
        $stmt->bind_param("i", $attendanceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $attendance = $result->fetch_assoc();

        if (!$attendance) {
            return false;
        }

        // Determine new status
        $newStatus = $this->determineAttendanceStatus(
            $attendance['event_id'],
            $attendance['session'],
            $attendance['time_recorded']
        );

        // Update status
        $updateStmt = $this->db->prepare("
            UPDATE attendance 
            SET attendance_status = ? 
            WHERE id = ?
        ");
        $updateStmt->bind_param("si", $newStatus, $attendanceId);
        return $updateStmt->execute();
    }

    public function setLateInterval($minutes) {
        if ($minutes < 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE settings 
            SET value = ? 
            WHERE name = 'late_interval_minutes'
        ");
        $stmt->bind_param("i", $minutes);
        $result = $stmt->execute();
        
        if ($result) {
            $this->lateInterval = $minutes;
        }
        
        return $result;
    }

    public function getLateInterval() {
        return $this->lateInterval;
    }
} 