-- ================================================
-- Database Schema for Roblox Cookie Refresher
-- Version: 1.0.0
-- ================================================

-- Drop existing tables (in correct order due to foreign keys)
DROP TABLE IF EXISTS `queue_jobs`;
DROP TABLE IF EXISTS `refresh_history`;
DROP TABLE IF EXISTS `rate_limits`;
DROP TABLE IF EXISTS `analytics`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;

-- ================================================
-- Users Table
-- Stores Discord user information and preferences
-- ================================================
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL PRIMARY KEY COMMENT 'Discord user ID',
    `username` VARCHAR(100) NOT NULL,
    `discriminator` VARCHAR(10) DEFAULT '0',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `total_refreshes` INT UNSIGNED NOT NULL DEFAULT 0,
    `successful_refreshes` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_refreshes` INT UNSIGNED NOT NULL DEFAULT 0,
    `preferences` JSON DEFAULT NULL COMMENT 'User preferences and settings',
    INDEX `idx_username` (`username`),
    INDEX `idx_last_login` (`last_login`),
    INDEX `idx_total_refreshes` (`total_refreshes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Refresh History Table
-- Tracks all cookie refresh attempts with detailed info
-- ================================================
CREATE TABLE `refresh_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `cookie_hash` VARCHAR(64) NOT NULL COMMENT 'SHA256 hash of cookie for tracking',
    `status` ENUM('success', 'failed') NOT NULL,
    `error_type` VARCHAR(50) DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `response_time` INT UNSIGNED DEFAULT NULL COMMENT 'Response time in milliseconds',
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `roblox_username` VARCHAR(100) DEFAULT NULL,
    `roblox_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `robux_balance` INT UNSIGNED DEFAULT NULL,
    `premium_status` BOOLEAN DEFAULT FALSE,
    `account_age` INT UNSIGNED DEFAULT NULL COMMENT 'Account age in days',
    `friends_count` INT UNSIGNED DEFAULT NULL,
    `user_data_snapshot` JSON DEFAULT NULL COMMENT 'Full user data at time of refresh',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_cookie_hash` (`cookie_hash`),
    INDEX `idx_error_type` (`error_type`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Rate Limits Table
-- Persistent rate limiting replacing JSON files
-- ================================================
CREATE TABLE `rate_limits` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `request_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `first_request` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_request` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_banned` BOOLEAN NOT NULL DEFAULT FALSE,
    `ban_expiry` TIMESTAMP NULL DEFAULT NULL,
    `ban_reason` VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY `idx_ip_address` (`ip_address`),
    INDEX `idx_is_banned` (`is_banned`),
    INDEX `idx_first_request` (`first_request`),
    INDEX `idx_ban_expiry` (`ban_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Analytics Table
-- Aggregated statistics for dashboard
-- ================================================
CREATE TABLE `analytics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `metric_type` VARCHAR(50) NOT NULL COMMENT 'Type: hourly, daily, total',
    `metric_key` VARCHAR(100) NOT NULL COMMENT 'Specific metric identifier',
    `metric_value` BIGINT NOT NULL DEFAULT 0,
    `metric_data` JSON DEFAULT NULL COMMENT 'Additional metric data',
    `period_start` TIMESTAMP NOT NULL,
    `period_end` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_metric` (`metric_type`, `metric_key`, `period_start`),
    INDEX `idx_metric_type` (`metric_type`),
    INDEX `idx_period_start` (`period_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Queue Jobs Table
-- Background job processing for async refresh operations
-- ================================================
CREATE TABLE `queue_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `cookie_hash` VARCHAR(64) NOT NULL,
    `cookie_encrypted` TEXT NOT NULL COMMENT 'Encrypted cookie data',
    `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `priority` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1=highest, 10=lowest',
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `result_data` JSON DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_status_priority` (`status`, `priority`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Sessions Table (Optional)
-- Store session data in database for better scalability
-- ================================================
CREATE TABLE `sessions` (
    `id` VARCHAR(128) NOT NULL PRIMARY KEY,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `payload` TEXT NOT NULL,
    `last_activity` INT UNSIGNED NOT NULL,
    `remember_token` VARCHAR(100) DEFAULT NULL,
    `expires_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_activity` (`last_activity`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Initial Data / Defaults
-- ================================================

-- Insert default analytics metrics
INSERT INTO `analytics` (`metric_type`, `metric_key`, `metric_value`, `period_start`) VALUES
('total', 'refreshes', 0, NOW()),
('total', 'successful_refreshes', 0, NOW()),
('total', 'failed_refreshes', 0, NOW()),
('total', 'unique_users', 0, NOW()),
('total', 'avg_response_time', 0, NOW());

-- ================================================
-- Useful Views (Optional)
-- ================================================

-- User statistics view
CREATE OR REPLACE VIEW `user_stats` AS
SELECT 
    u.id,
    u.username,
    u.total_refreshes,
    u.successful_refreshes,
    u.failed_refreshes,
    ROUND((u.successful_refreshes / NULLIF(u.total_refreshes, 0)) * 100, 2) AS success_rate,
    u.last_login,
    u.created_at
FROM users u;

-- Recent refresh activity view
CREATE OR REPLACE VIEW `recent_activity` AS
SELECT 
    rh.id,
    rh.user_id,
    u.username,
    rh.ip_address,
    rh.status,
    rh.error_type,
    rh.response_time,
    rh.roblox_username,
    rh.robux_balance,
    rh.created_at
FROM refresh_history rh
LEFT JOIN users u ON rh.user_id = u.id
ORDER BY rh.created_at DESC
LIMIT 100;

-- Hourly statistics view
CREATE OR REPLACE VIEW `hourly_stats` AS
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') AS hour,
    COUNT(*) AS total_requests,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successful,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
    AVG(response_time) AS avg_response_time
FROM refresh_history
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY hour
ORDER BY hour DESC;

-- ================================================
-- Stored Procedures (Optional but useful)
-- NOTE: If running via PDO or non-CLI client, execute these
-- procedures separately after creating tables above.
-- For MySQL CLI: This script can be run as-is.
-- ================================================

DELIMITER //

-- Procedure to clean old rate limits
CREATE PROCEDURE `cleanup_rate_limits`()
BEGIN
    DELETE FROM rate_limits 
    WHERE first_request < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    AND is_banned = FALSE;
    
    UPDATE rate_limits 
    SET is_banned = FALSE, ban_expiry = NULL 
    WHERE is_banned = TRUE 
    AND ban_expiry < NOW();
END //

-- Procedure to update analytics
CREATE PROCEDURE `update_analytics`()
BEGIN
    -- Update total refreshes
    INSERT INTO analytics (metric_type, metric_key, metric_value, period_start)
    VALUES ('total', 'refreshes', (SELECT COUNT(*) FROM refresh_history), NOW())
    ON DUPLICATE KEY UPDATE 
        metric_value = (SELECT COUNT(*) FROM refresh_history),
        updated_at = NOW();
    
    -- Update successful refreshes
    INSERT INTO analytics (metric_type, metric_key, metric_value, period_start)
    VALUES ('total', 'successful_refreshes', (SELECT COUNT(*) FROM refresh_history WHERE status = 'success'), NOW())
    ON DUPLICATE KEY UPDATE 
        metric_value = (SELECT COUNT(*) FROM refresh_history WHERE status = 'success'),
        updated_at = NOW();
    
    -- Update failed refreshes
    INSERT INTO analytics (metric_type, metric_key, metric_value, period_start)
    VALUES ('total', 'failed_refreshes', (SELECT COUNT(*) FROM refresh_history WHERE status = 'failed'), NOW())
    ON DUPLICATE KEY UPDATE 
        metric_value = (SELECT COUNT(*) FROM refresh_history WHERE status = 'failed'),
        updated_at = NOW();
    
    -- Update unique users
    INSERT INTO analytics (metric_type, metric_key, metric_value, period_start)
    VALUES ('total', 'unique_users', (SELECT COUNT(DISTINCT user_id) FROM refresh_history WHERE user_id IS NOT NULL), NOW())
    ON DUPLICATE KEY UPDATE 
        metric_value = (SELECT COUNT(DISTINCT user_id) FROM refresh_history WHERE user_id IS NOT NULL),
        updated_at = NOW();
END //

DELIMITER ;

-- ================================================
-- Optimization: Add events for automatic cleanup
-- ================================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Event to clean old rate limits every hour
CREATE EVENT IF NOT EXISTS `event_cleanup_rate_limits`
ON SCHEDULE EVERY 1 HOUR
DO CALL cleanup_rate_limits();

-- Event to update analytics every 5 minutes
CREATE EVENT IF NOT EXISTS `event_update_analytics`
ON SCHEDULE EVERY 5 MINUTE
DO CALL update_analytics();

-- ================================================
-- Grant Permissions (Adjust as needed)
-- ================================================
-- GRANT SELECT, INSERT, UPDATE, DELETE ON refresh_tool.* TO 'app_user'@'localhost';
-- FLUSH PRIVILEGES;
