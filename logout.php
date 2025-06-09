<?php
/**
 * Logout Script
 * 
 * This script destroys the session and redirects to the homepage.
 */

// Include configuration file
require_once 'config/config.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the homepage
redirect(BASE_URL); 