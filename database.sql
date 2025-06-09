-- Create the database
CREATE DATABASE IF NOT EXISTS bcctap_db;
USE bcctap_db;

-- Users table (for all users: admin, teachers, students)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(20) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    year_level INT DEFAULT NULL,
    section VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    rfid VARCHAR(50) DEFAULT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    created_at DATETIME NOT NULL,
    last_login DATETIME DEFAULT NULL
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    morning_time_in TIME NOT NULL,
    morning_time_out TIME NOT NULL,
    afternoon_time_in TIME NOT NULL,
    afternoon_time_out TIME NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    location VARCHAR(100),
    created_by INT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- QR Codes table
CREATE TABLE IF NOT EXISTS qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    code VARCHAR(255) NOT NULL,
    session ENUM('morning', 'afternoon') NOT NULL,
    expiration DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    qr_code_id INT NOT NULL,
    session ENUM('morning', 'afternoon') NOT NULL,
    timestamp DATETIME NOT NULL,
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    status ENUM('present', 'late', 'excused') DEFAULT 'present',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role, created_at)
VALUES ('admin', 'admin@bcctap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', NOW())
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some default departments
INSERT INTO departments (name, description) VALUES 
('Computer Science', 'Department of Computer Science and Information Technology'),
('Engineering', 'Department of Engineering'),
('Business Administration', 'Department of Business Administration'),
('Education', 'Department of Education'),
('Arts and Sciences', 'Department of Arts and Sciences'); 