# BCCTAP - Bago City College Time Attendance Platform

A web-based attendance tracking system that automates and digitizes student attendance processes using QR codes and mobile phones.

## Features

### Student Features
- Scan QR codes to record attendance
- Geolocation tracking at the time of attendance
- View personal attendance records

### Teacher Features
- View filtered attendance records by department, event, date, and session
- Print attendance sheets
- Generate attendance reports

### Admin Features
- Create and manage events with time slots
- Generate QR codes for events
- Manage student and teacher accounts
- Monitor system-wide attendance

## Technology Stack

- **Backend**: PHP
- **Frontend**: Tailwind CSS
- **Database**: MySQL
- **QR Code Scanning**: jsQR (JavaScript QR Code scanner)
- **Geolocation**: HTML5 Geolocation API
- **Maps**: MapBox API

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

### Setup Instructions

1. Clone the repository to your web server directory:
   ```
   git clone https://github.com/yourusername/bcctap.git
   ```

2. Create a MySQL database:
   ```sql
   CREATE DATABASE bcctap_db;
   ```

3. Import the database schema:
   ```
   mysql -u username -p bcctap_db < database.sql
   ```

4. Update the database configuration in `config/database.php`:
   ```php
   define('DB_SERVER', 'localhost');  // Your database server
   define('DB_USERNAME', 'root');     // Your database username
   define('DB_PASSWORD', '');         // Your database password
   define('DB_NAME', 'bcctap_db');    // Your database name
   ```

5. Update the base URL in `config/config.php`:
   ```php
   define('BASE_URL', 'http://your-domain.com/bcctap/');
   ```

6. Set appropriate permissions for file uploads and logs:
   ```
   chmod -R 755 uploads/
   chmod -R 755 logs/
   ```

7. Access the application:
   - Navigate to `http://your-domain.com/bcctap/` in your web browser
   - Default admin login: username `admin`, password `admin123`

## Usage

### Admin
1. Log in as admin
2. Create events and specify time slots
3. Generate QR codes for events
4. Print QR codes and distribute them at event locations
5. Create teacher and student accounts as needed

### Teacher
1. Log in as teacher
2. View attendance records for specific events
3. Filter records by date, session, etc.
4. Print attendance reports

### Student
1. Log in as student
2. Navigate to the "Scan QR" section
3. Grant location and camera permissions
4. Scan the QR code at the event
5. View attendance history

## Folder Structure

- `admin/` - Admin portal files
- `teacher/` - Teacher portal files
- `student/` - Student portal files
- `assets/` - CSS, JavaScript, and image files
- `config/` - Configuration files
- `includes/` - Reusable PHP components
- `uploads/` - Uploaded files (profile pictures, etc.)

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- [Tailwind CSS](https://tailwindcss.com/)
- [jsQR](https://github.com/cozmo/jsQR)
- [MapBox](https://www.mapbox.com/)