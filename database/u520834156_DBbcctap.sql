-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2025 at 06:08 AM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u520834156_DBbcctap`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `qr_code_id` int(11) NOT NULL,
  `time_recorded` timestamp NOT NULL DEFAULT current_timestamp(),
  `session` enum('morning','afternoon') NOT NULL,
  `status` enum('time_in','time_out') NOT NULL,
  `attendance_status` enum('present','late','absent') NOT NULL DEFAULT 'present',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `event_id`, `qr_code_id`, `time_recorded`, `session`, `status`, `attendance_status`, `latitude`, `longitude`) VALUES
(2, 11, 3, 22, '2025-06-09 17:10:14', 'morning', 'time_in', 'present', 10.52806170, 122.84252660),
(3, 11, 4, 23, '2025-06-10 02:14:10', 'morning', 'time_in', 'present', 10.53051340, 122.84272800),
(4, 15, 5, 24, '2025-06-10 05:04:15', 'afternoon', 'time_in', 'present', 10.66180920, 122.94503390),
(5, 11, 5, 24, '2025-06-10 05:04:19', 'afternoon', 'time_in', 'present', 10.66179830, 122.94504060),
(6, 17, 5, 24, '2025-06-10 05:05:04', 'afternoon', 'time_in', 'present', 10.66181050, 122.94503280),
(7, 16, 5, 24, '2025-06-10 05:05:30', 'afternoon', 'time_in', 'present', 10.66179780, 122.94504180),
(8, 8, 5, 24, '2025-06-10 05:12:27', 'afternoon', 'time_in', 'present', 10.66180970, 122.94503460);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Information System', 'Bachelor of Science in Information System', '2025-05-28 05:21:11', '2025-05-28 05:41:29'),
(2, 'Arts', 'Bachelor of Arts', '2025-05-28 05:21:11', '2025-05-28 05:40:46'),
(3, 'Office Administration', 'Bachelor of Science in Office Administration', '2025-05-28 05:21:11', '2025-05-28 05:42:06'),
(4, 'Education', 'Bachelor of Education', '2025-05-28 05:21:11', '2025-05-28 05:42:37'),
(5, 'Criminology', 'Bachelor of Science in Criminology', '2025-05-28 05:21:11', '2025-05-28 05:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `morning_time_in` time NOT NULL,
  `morning_time_out` time NOT NULL,
  `afternoon_time_in` time NOT NULL,
  `afternoon_time_out` time NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `location_latitude` decimal(10,8) DEFAULT NULL,
  `location_longitude` decimal(11,8) DEFAULT NULL,
  `geofence_radius` int(11) DEFAULT 100 COMMENT 'Radius in meters',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `uuid`, `title`, `description`, `start_date`, `end_date`, `morning_time_in`, `morning_time_out`, `afternoon_time_in`, `afternoon_time_out`, `department`, `location`, `location_latitude`, `location_longitude`, `geofence_radius`, `created_by`, `created_at`) VALUES
(1, '740d4f07-a4c2-4dc6-a380-99ea554ae51a', 'bayle', 'may bayle sa bcc', '2025-06-10', '2025-06-11', '08:00:00', '12:00:00', '00:00:00', '05:00:00', '', NULL, 10.53734900, 122.83539300, 50, 1, '0000-00-00 00:00:00'),
(2, '782df9fa-81ad-45e1-8eb9-19159d9cc1ef', 'testin attendance on site', 'testing on site loca', '2025-06-10', '2025-06-11', '08:00:00', '12:00:00', '00:00:00', '05:00:00', '', NULL, 10.72015000, 122.56210600, 100, 1, '0000-00-00 00:00:00'),
(3, '93a67c4d-999c-4fd3-aad5-506a2407c7b9', 'testing 3', 'testing 3', '2025-06-10', '2025-06-11', '01:00:00', '12:00:00', '13:00:00', '17:00:00', '', NULL, 10.52846700, 122.84259600, 1000, 1, '0000-00-00 00:00:00'),
(4, '8e285dc0-d304-46d1-aed9-c972db9eee43', 'BCC IS', '', '2025-06-10', '2025-06-10', '08:00:00', '12:00:00', '13:00:00', '17:00:00', '', NULL, 10.53047700, 122.84255600, 50, 1, '0000-00-00 00:00:00'),
(5, '9a18a87c-280a-4a86-8c7f-8711f5f1a8e7', 'Nolitc', 'Nolitc', '2025-06-10', '2025-06-10', '08:00:00', '12:00:00', '13:00:00', '17:00:00', '', NULL, 10.66172500, 122.94504900, 50, 1, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `notification_verifications`
--

CREATE TABLE `notification_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `status` enum('sent','responded','expired','dismissed') DEFAULT 'sent',
  `tokens_sent` int(11) DEFAULT 0,
  `tokens_success` int(11) DEFAULT 0,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_data`)),
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_verifications`
--

INSERT INTO `notification_verifications` (`id`, `user_id`, `event_id`, `attempt_number`, `status`, `tokens_sent`, `tokens_success`, `response_data`, `responded_at`, `created_at`, `updated_at`) VALUES
(1, 2021116435, 999, 1, 'sent', 1, 0, '[{\"http_code\":404,\"response\":null,\"success\":false}]', NULL, '2025-06-10 06:22:00', '2025-06-10 06:22:00'),
(2, 2021116435, 999, 1, 'sent', 1, 0, '[{\"http_code\":404,\"response\":null,\"success\":false}]', NULL, '2025-06-10 06:24:07', '2025-06-10 06:24:07'),
(3, 2021116435, 999, 1, 'sent', 1, 0, '[{\"http_code\":404,\"response\":null,\"success\":false}]', NULL, '2025-06-10 06:26:39', '2025-06-10 06:26:39'),
(4, 2021116435, 999, 1, 'sent', 1, 0, '[{\"http_code\":404,\"response\":null,\"success\":false}]', NULL, '2025-06-10 06:26:56', '2025-06-10 06:26:57'),
(5, 2021116435, 999, 1, 'sent', 1, 0, '[{\"http_code\":404,\"response\":null,\"success\":false}]', NULL, '2025-06-10 06:27:07', '2025-06-10 06:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `session` enum('morning','afternoon') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `event_id`, `code`, `image_path`, `session`, `created_at`) VALUES
(19, 8, 'bd200e46-8f89-49d5-ac2d-dc81f7a8f8e4', 'uploads/qrcodes/event_8_qr_19.png', '', '2025-06-09 07:15:24'),
(20, 1, '740d4f07-a4c2-4dc6-a380-99ea554ae51a', 'uploads/qrcodes/event_1_qr_20.png', '', '2025-06-09 16:52:43'),
(21, 2, '782df9fa-81ad-45e1-8eb9-19159d9cc1ef', 'uploads/qrcodes/event_2_qr_21.png', '', '2025-06-09 16:57:55'),
(22, 3, '93a67c4d-999c-4fd3-aad5-506a2407c7b9', 'uploads/qrcodes/event_3_qr_22.png', '', '2025-06-09 17:01:19'),
(23, 4, '8e285dc0-d304-46d1-aed9-c972db9eee43', 'uploads/qrcodes/event_4_qr_23.png', '', '2025-06-10 02:13:28'),
(24, 5, '9a18a87c-280a-4a86-8c7f-8711f5f1a8e7', 'uploads/qrcodes/event_5_qr_24.png', '', '2025-06-10 05:03:01');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'enforce_device_restriction', '1', 'If set to 1, users can only access from devices they have previously used', '2025-05-30 17:54:28', '2025-06-09 06:03:06'),
(2, 'allow_self_registration', '1', 'If set to 1, students can register themselves', '2025-05-30 17:54:28', '2025-05-30 17:54:28'),
(3, 'require_admin_approval', '0', 'If set to 1, new accounts require admin approval', '2025-05-30 17:54:28', '2025-05-30 17:54:28'),
(4, 'system_name', 'BCCTAP', 'System name displayed in the interface and emails', '2025-05-30 17:54:28', '2025-05-30 17:54:28'),
(5, 'support_email', 'support@example.com', 'Email address for support inquiries', '2025-05-30 17:54:28', '2025-05-30 17:54:28'),
(6, 'late_interval_minutes', '15', 'Number of minutes after scheduled time to consider attendance as late', '2025-05-30 17:54:28', '2025-05-30 17:54:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `year_level` int(11) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `rfid` varchar(50) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL COMMENT 'Unique device identifier for student login',
  `first_device_date` datetime DEFAULT NULL COMMENT 'Date when first device was registered',
  `device_changed` tinyint(1) DEFAULT 0 COMMENT 'Flag for when user logs in from different device',
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `department`, `student_id`, `created_at`, `updated_at`, `year_level`, `section`, `address`, `gender`, `contact_number`, `rfid`, `last_login`, `device_id`, `first_device_date`, `device_changed`, `active`) VALUES
(1, 'admin', '$2y$10$.PXlDnSLJ.EyVsEd2nmj0O5Pu58Xym41XCqPZKRGRP/I/nCXKBO/e', 'admin', 'System Administrator', 'admin@bcctap.edu', NULL, NULL, '2025-05-28 05:21:10', '2025-05-28 05:33:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1),
(8, 'student_2021117070', '$2y$10$gBE3B2CJqL0YAA.q9XKBieTzOmeCUCMSgn0ml/UCB5YgU3obVWsOS', 'student', 'JON DANIEL ALVAREZ FORTUNO', 'JONDANIEL0405@GMAIL.COM', 'BACHELOR OF SCIENCE IN INFORMATION SYSTEMS', '2021117070', '2025-06-09 06:46:53', '2025-06-10 05:12:14', 4, 'A', 'LUMANGOB SIAN, NAPOLES, BAGO CITY', 'MALE', '09673148660', '0444175346', '2025-06-10 13:12:14', '8c741a8ed39cbb487a4c2405aa69c7a05997ee30703067b39877d0bf7b145915', '2025-06-09 06:46:53', 0, 1),
(10, 'student_2021116394', '$2y$10$JP2HMzT5GLkBMSMCyAIYHONah/fd6J/fKjhw7yv/oXqDC.NwHQTrK', 'student', 'JOHN RODEROS OGBAMIN', 'OGBAMIN.JOHN.21@GMAIL.COM', 'BACHELOR OF SCIENCE IN INFORMATION SYSTEMS', '2021116394', '2025-06-09 07:01:23', '2025-06-09 07:25:02', 4, 'A', 'PRK. MADINALAG-ON, BRGY. CABUG, BACOLOD CITY', 'MALE', '09505847501', '0438568946', '2025-06-09 07:25:02', 'de760a1ba6d6a4d958a21049a632a0ff8dd8fa87cacc2aa34d783aa8d26296ac', '2025-06-09 07:01:23', 0, 1),
(11, 'student_2021116435', '$2y$10$Vzz2aCHLRMW94tMf9myBiuaLXM3zvOLvE/c7Sk5/CpGeU.Nax.l8i', 'student', 'CHARLIE DERUEL PELLE', 'CHARLIEPELLE5@GMAIL.COM', 'BACHELOR OF SCIENCE IN INFORMATION SYSTEMS', '2021116435', '2025-06-09 07:18:09', '2025-06-10 06:37:53', 4, 'A', 'HDA D-64, BRGY. MA-AO, BAGO CITY', 'MALE', '09480691056', '0439794402', '2025-06-10 14:37:53', '50af300365b2c427943e1dd8b448772c6dfae21ca8ebe602029b51ef3b3e343c', '2025-06-09 07:18:09', 0, 1),
(12, 'student_2021116420', '$2y$10$IvwYAfT3WrftRI4Qfy7QIuFobWY3JK2.sHgMixCRD0Rq2phokQxOm', 'student', 'NILO SANCHEZ DIAZ', 'NILODIAZ04@GMAIL.COM', 'BACHELOR OF SCIENCE IN INFORMATION SYSTEMS', '2021116420', '2025-06-09 07:22:24', '2025-06-10 05:05:52', 4, 'A', 'RIZAL STREET, BRGY.MA-AO, BAGO CITY', 'MALE', '09955732509', '0456795138', '2025-06-10 13:05:52', 'de760a1ba6d6a4d958a21049a632a0ff8dd8fa87cacc2aa34d783aa8d26296ac', '2025-06-09 07:22:24', 0, 1),
(14, 'teacher_mitch', '$2y$10$XeSJj6nMK.yTc8c9Ch32aOiK9k8yHstBo1W78nBeywo0rlzWeeDjK', 'teacher', 'Mitch Barcenilla', 'charlie.sfvalley@gmail.com', 'BACHELOR OF SCIENCE IN INFORMATION SYSTEMS', NULL, '2025-06-09 08:27:05', '2025-06-09 08:27:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1),
(15, 'student_2021116763', '$2y$10$drI/zDASsULkil9EYJxAyuJnuvr3/WlLbFb7ggFw1D9uXf6ZI05iK', 'student', 'NIKOLAI ALVAREZ LAPATAN', 'NIKLAPATAN@GMAIL.COM', 'BACHELOR OF SCIENCE IN INFORMATION SYSTEMS', '2021116763', '2025-06-09 11:51:03', '2025-06-10 05:15:56', 4, 'A', 'PUROK DAISY, CALUMANGAN, BAGO CITY', 'MALE', '09054604916', '0372077052', '2025-06-10 13:15:56', 'd377616f5270c2b2db8b2f13c5995957c12f594ccc39780fcbb53a49c8642ede', '2025-06-09 11:51:03', 0, 1),
(16, 'student_2021116478', '$2y$10$ctRfO9LDAalEl3MHrdB.6O/qmix.fDPY2ggEEQM3qRF4ixv/R.vr6', 'student', 'IAN PARCON GODACA', 'IANPARCONGODACA@GMAIL.COM', 'BACHELOR OF SCIENCE IN INFORMATION SYSTEMS', '2021116478', '2025-06-10 05:04:38', '2025-06-10 05:04:38', 4, 'A', 'PUROK DAISY, ABUANAN, BAGO CITY', 'MALE', '09917583639', '0543836487', NULL, '01cc1aa58e9188397982698a2983ad53ed63d18d5601cd296f42feafca043d77', '2025-06-10 13:04:38', 0, 1),
(17, 'student_2021116439', '$2y$10$s22fc3.NL2rk5fEgdIirWeYokm5UlLAEFalYx3s3pmgBsaCVvUMFS', 'student', 'APRYLL JANE ANTONONG JAYONA', 'JAYONAAPRYLLJANE@GMAIL.COM', 'BACHELOR OF SCIENCE IN INFORMATION SYSTEMS', '2021116439', '2025-06-10 05:04:47', '2025-06-10 05:04:47', 4, 'A', 'RIZAL ST. PNB SUBDIVISION, BARANGAY 1, LA CARLOTA CITY', 'FEMALE', '09464509391', '0438121202', NULL, '5e2935884b12d4d5336208d3bc297462cfa4076dedcbb9e2c2eb7e3263d82e12', '2025-06-10 13:04:47', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Reference to users.id',
  `fingerprint` varchar(255) NOT NULL COMMENT 'Device fingerprint hash',
  `device_name` varchar(100) DEFAULT NULL COMMENT 'User-friendly device name',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unverified, 1=verified',
  `verification_date` datetime DEFAULT NULL COMMENT 'When the device was verified',
  `registration_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When the device was first registered',
  `last_seen` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Last time this device was used',
  `user_agent` text DEFAULT NULL COMMENT 'Last user agent string'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_devices`
--

INSERT INTO `user_devices` (`id`, `user_id`, `fingerprint`, `device_name`, `is_verified`, `verification_date`, `registration_date`, `last_seen`, `user_agent`) VALUES
(13, 11, '50af300365b2c427943e1dd8b448772c6dfae21ca8ebe602029b51ef3b3e343c', 'Chrome on Android', 1, '2025-06-09 07:18:09', '2025-06-09 07:18:09', '2025-06-10 14:36:01', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(14, 12, 'de760a1ba6d6a4d958a21049a632a0ff8dd8fa87cacc2aa34d783aa8d26296ac', 'Chrome on Android', 1, '2025-06-09 07:22:24', '2025-06-09 07:22:24', '2025-06-09 07:22:24', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36'),
(15, 10, 'de760a1ba6d6a4d958a21049a632a0ff8dd8fa87cacc2aa34d783aa8d26296ac', 'Chrome on Android', 1, '2025-06-09 07:25:02', '2025-06-09 07:25:02', '2025-06-09 07:25:02', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36'),
(26, 11, 'd999c07b751997a3ec92ec4afdf91df6a76815a4155abd16c1892492a27e97bf', 'Chrome on Android', 1, '2025-06-10 12:58:29', '2025-06-10 12:57:42', '2025-06-10 12:57:42', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(27, 15, '5a440c3350f50c7854acd46274dd2ddcf8d2d048462ba97bf86824828ada1091', 'Chrome on Android', 1, '2025-06-10 12:58:20', '2025-06-10 12:58:20', '2025-06-10 13:01:38', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(28, 16, '01cc1aa58e9188397982698a2983ad53ed63d18d5601cd296f42feafca043d77', 'Chrome on Android', 1, '2025-06-10 13:04:38', '2025-06-10 13:04:38', '2025-06-10 13:04:38', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(29, 17, '5e2935884b12d4d5336208d3bc297462cfa4076dedcbb9e2c2eb7e3263d82e12', 'Chrome on Linux', 1, '2025-06-10 13:04:47', '2025-06-10 13:04:47', '2025-06-10 13:04:47', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(30, 12, 'd375caf11e031af3a205b679e4d080238e1ed12d2f24e49914eeec5e283603f0', 'Chrome on Linux', 1, '2025-06-10 13:08:40', '2025-06-10 13:05:21', '2025-06-10 13:05:21', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'),
(33, 8, '01cc1aa58e9188397982698a2983ad53ed63d18d5601cd296f42feafca043d77', 'Chrome on Android', 1, '2025-06-10 13:08:55', '2025-06-10 13:08:47', '2025-06-10 13:12:14', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(35, 8, '523b8b9b2e296a19d7ea99578f8040c7797436931d0d5fd2f99a85af6976fc73', 'Chrome on Android', 0, NULL, '2025-06-10 13:10:52', '2025-06-10 13:10:52', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(36, 15, 'd377616f5270c2b2db8b2f13c5995957c12f594ccc39780fcbb53a49c8642ede', 'Chrome on Windows', 0, NULL, '2025-06-10 13:15:06', '2025-06-10 13:15:06', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(37, 15, 'af56214fbb2cbb5779e89b09fa3528eca4bd592b0c658513ad7de01c8f667f38', 'Chrome on Windows', 0, NULL, '2025-06-10 13:15:56', '2025-06-10 13:15:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(38, 11, 'd377616f5270c2b2db8b2f13c5995957c12f594ccc39780fcbb53a49c8642ede', 'Chrome on Windows', 0, NULL, '2025-06-10 14:36:56', '2025-06-10 14:36:56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `qr_code_id` (`qr_code_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notification_verifications`
--
ALTER TABLE `notification_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_event` (`user_id`,`event_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_fingerprint_idx` (`user_id`,`fingerprint`),
  ADD KEY `fingerprint_idx` (`fingerprint`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notification_verifications`
--
ALTER TABLE `notification_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`qr_code_id`) REFERENCES `qr_codes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `fk_user_devices_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
