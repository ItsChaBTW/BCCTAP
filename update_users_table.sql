-- Update users table to add fields for TechnoPal API data

-- Check if columns already exist before adding
SELECT COUNT(*) INTO @year_level_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'year_level';

SELECT COUNT(*) INTO @section_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'section';

SELECT COUNT(*) INTO @address_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'address';

SELECT COUNT(*) INTO @gender_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'gender';

SELECT COUNT(*) INTO @contact_number_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'contact_number';

SELECT COUNT(*) INTO @rfid_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'rfid';

SELECT COUNT(*) INTO @last_login_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login';

-- Add columns if they don't exist
SET @query = CONCAT(
    CASE WHEN @year_level_exists = 0 THEN 'ALTER TABLE users ADD COLUMN year_level INT DEFAULT NULL;' ELSE '' END,
    CASE WHEN @section_exists = 0 THEN 'ALTER TABLE users ADD COLUMN section VARCHAR(20) DEFAULT NULL;' ELSE '' END,
    CASE WHEN @address_exists = 0 THEN 'ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL;' ELSE '' END,
    CASE WHEN @gender_exists = 0 THEN 'ALTER TABLE users ADD COLUMN gender VARCHAR(20) DEFAULT NULL;' ELSE '' END,
    CASE WHEN @contact_number_exists = 0 THEN 'ALTER TABLE users ADD COLUMN contact_number VARCHAR(20) DEFAULT NULL;' ELSE '' END,
    CASE WHEN @rfid_exists = 0 THEN 'ALTER TABLE users ADD COLUMN rfid VARCHAR(50) DEFAULT NULL;' ELSE '' END,
    CASE WHEN @last_login_exists = 0 THEN 'ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL;' ELSE '' END
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add device_id column to users table to track student devices
ALTER TABLE users ADD COLUMN device_id VARCHAR(255) NULL COMMENT 'Unique device identifier for student login';

-- Add first_device_date column to track when the device was first used
ALTER TABLE users ADD COLUMN first_device_date DATETIME NULL COMMENT 'Date when first device was registered';

-- Add a column to track if they've changed devices
ALTER TABLE users ADD COLUMN device_changed TINYINT(1) DEFAULT 0 COMMENT 'Flag for when user logs in from different device'; 