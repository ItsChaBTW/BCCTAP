<?php
/**
 * Main Configuration File
 * 
 * This file contains the main configuration settings for the BCCTAP system.
 */

// Start the session
session_start();

// Base URL of the application
// Determine the base URL dynamically based on the server variables
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$server_name = $_SERVER['SERVER_NAME'];

// Get the folder path from the script name
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_path = '';

// Handle subfolder installations properly
if ($script_path != '/' && $script_path != '\\') {
    // Remove "/config" from the path if present
    $base_path = str_replace('/config', '', $script_path);
    
    // Make sure path ends with a trailing slash
    if (substr($base_path, -1) != '/') {
        $base_path .= '/';
    }
}

// Set the appropriate base URL based on the environment
if ($host == 'localhost' || $host == '127.0.0.1') {
    // For local development
    $base_path = '/BCCTAP/';  // Add the project folder name
    define('BASE_URL', $protocol . '://' . $host . $base_path);
    define('SCAN_URL', $protocol . '://' . $host . $base_path);
} else if ($host == 'bcctap.bccbsis.com') {
    // For production environment
    define('BASE_URL', $protocol . '://' . $host . '/');
    define('SCAN_URL', $protocol . '://' . $host . '/');
} else {
    // For other environments (like IP-based access)
    define('BASE_URL', $protocol . '://' . $host . $base_path);
    define('SCAN_URL', $protocol . '://' . $host . $base_path);
}

// Define application paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Time zone setting
date_default_timezone_set('Asia/Manila');

// Include database configuration
require_once 'database.php';

// Include authentication functions
require_once __DIR__ . '/auth.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to redirect
function redirect($location) {
    header("Location: $location");
    exit;
}

// Function to sanitize user input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Function to check if admin
function isAdmin() {
    return hasRole('admin');
}

// Function to check if teacher
function isTeacher() {
    return hasRole('teacher');
}

// Function to check if student
function isStudent() {
    return hasRole('student');
}

// Function to get the absolute URL for QR codes that works across environments
function getAbsoluteUrl($path = '') {
    // Clean the path from any leading slashes
    $path = ltrim($path, '/');
    return SCAN_URL . $path;
}

// Function to generate a random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Function to set flash messages
function setFlashMessage($type, $message) {
    $_SESSION[$type . '_message'] = $message;
}

error_log("Config - BASE_URL defined as: " . BASE_URL);
error_log("Config - SCAN_URL defined as: " . SCAN_URL); 