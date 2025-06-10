<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection settings for the BCCTAP system.
 */

define('DB_SERVER', 'localhost');    // Database server
define('DB_USERNAME', 'root');       // Database username
define('DB_PASSWORD', '');           // Database password
define('DB_NAME', 'bcctap_db');      // Database name

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn === false) {
    die("ERROR: Could not connect to the database. " . mysqli_connect_error());
}

// Set charset to ensure proper handling of special characters
mysqli_set_charset($conn, "utf8mb4");

// Set MySQL timezone to Asia/Manila
mysqli_query($conn, "SET time_zone = '+08:00'"); 