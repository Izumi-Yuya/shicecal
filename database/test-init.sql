-- Test Database Initialization Script for Shise-Cal
-- This script sets up the test database with proper configuration

-- Create test database if it doesn't exist
CREATE DATABASE IF NOT EXISTS shisecal_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create test user with appropriate permissions
CREATE USER IF NOT EXISTS 'shisecal_test_user'@'%' IDENTIFIED BY 'test_secure_password';
GRANT ALL PRIVILEGES ON shisecal_testing.* TO 'shisecal_test_user'@'%';

-- Grant additional permissions for testing
GRANT CREATE, DROP, ALTER, INDEX ON *.* TO 'shisecal_test_user'@'%';

-- Flush privileges to ensure changes take effect
FLUSH PRIVILEGES;

-- Use the test database
USE shisecal_testing;

-- Set appropriate SQL modes for testing
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- Create a test configuration table for environment verification
CREATE TABLE IF NOT EXISTS test_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(255) NOT NULL UNIQUE,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert test environment marker
INSERT INTO test_config (config_key, config_value) VALUES 
('environment', 'testing'),
('initialized_at', NOW()),
('version', '1.0.0')
ON DUPLICATE KEY UPDATE 
config_value = VALUES(config_value),
updated_at = NOW();