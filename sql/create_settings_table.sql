-- Create settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`name`, `value`, `description`) VALUES
('enforce_device_restriction', '0', 'If set to 1, users can only access from devices they have previously used'),
('allow_self_registration', '1', 'If set to 1, students can register themselves'),
('require_admin_approval', '0', 'If set to 1, new accounts require admin approval'),
('system_name', 'BCCTAP', 'System name displayed in the interface and emails'),
('support_email', 'support@example.com', 'Email address for support inquiries'); 