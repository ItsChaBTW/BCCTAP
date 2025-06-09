<?php
/**
 * Database Initialization Script
 * 
 * This script creates the database and tables needed for the BCCTAP system.
 */

// Database connection variables
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server without database selection
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to MySQL server successfully.<br>";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS bcctap_db";
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully or already exists.<br>";
    } else {
        die("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db('bcctap_db');
    
    // Read and execute SQL from database.sql
    $sql_file = file_get_contents('database.sql');
    
    // Split the SQL file into individual statements
    $statements = explode(';', $sql_file);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if ($conn->query($statement) === FALSE) {
                echo "Error executing statement: " . $conn->error . "<br>";
                echo "Statement: " . $statement . "<br>";
            }
        }
    }
    
    echo "Database schema imported successfully.<br>";
    
    // Check if admin user exists
    $result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
    
    if ($result->num_rows == 0) {
        // Admin user does not exist, create it
        $password_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, password, role, full_name, email) 
                VALUES ('admin', '$password_hash', 'admin', 'System Administrator', 'admin@bcctap.edu')";
        
        if ($conn->query($sql) === TRUE) {
            echo "Admin user created successfully.<br>";
        } else {
            echo "Error creating admin user: " . $conn->error . "<br>";
        }
    } else {
        echo "Admin user already exists.<br>";
    }
    
    echo "<br><strong>Initialization complete!</strong><br>";
    echo "You can now <a href='staff_login.php'>login as admin</a> with:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    
    $conn->close();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?> 