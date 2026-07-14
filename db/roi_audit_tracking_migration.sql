-- =============================================================================
-- ROI Audit Tracking Migration
-- Tracks all ROI distributions, validations, and missed executions
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ROI Distribution Audit Table
DROP TABLE IF EXISTS `roi_distribution_audit`;
CREATE TABLE `roi_distribution_audit` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `stake_id` BIGINT UNSIGNED,
  `roi_type` ENUM('monthly', 'maturity', 'retry') NOT NULL COMMENT 'Type of ROI distribution',
  `plan_type` ENUM('fixed', 'regular', 'combo') NOT NULL COMMENT 'Staking plan type',
  `duration_years` INT NOT NULL,
  `principal_amount` DECIMAL(20,8) NOT NULL,
  `roi_rate_percent` DECIMAL(10,4) NOT NULL,
  `roi_amount` DECIMAL(20,8) NOT NULL COMMENT 'Actual ROI distributed',
  `payment_date` DATE NOT NULL COMMENT 'Date ROI was supposed to be paid',
  `actual_payment_date` DATETIME NOT NULL COMMENT 'When ROI was actually distributed',
  `execution_date` DATE NOT NULL COMMENT 'Cron execution date',
  `wallet_type` VARCHAR(50) NOT NULL COMMENT 'earning, staking, etc',
  `tx_hash` VARCHAR(255) NULL COMMENT 'Blockchain transaction hash',
  `status` ENUM('pending', 'processing', 'success', 'failed', 'retry') DEFAULT 'pending',
  `error_message` TEXT NULL,
  `retry_count` INT DEFAULT 0,
  `ledger_id` BIGINT UNSIGNED NULL COMMENT 'Reference to wallet_ledger',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`),
  KEY `idx_stake` (`stake_id`),
  KEY `idx_plan_type` (`plan_type`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_execution_date` (`execution_date`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks all ROI distributions with validation and retry logic';

-- ROI Missed Execution Tracking
DROP TABLE IF EXISTS `roi_cron_execution`;
CREATE TABLE `roi_cron_execution` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `execution_date` DATE NOT NULL,
  `execution_day` INT NOT NULL COMMENT '1-31 day of month',
  `cron_type` ENUM('monthly_payment', 'maturity_payout') NOT NULL,
  `status` ENUM('pending', 'running', 'success', 'failed') DEFAULT 'pending',
  `total_stakes_processed` INT DEFAULT 0,
  `total_stakes_failed` INT DEFAULT 0,
  `total_amount_distributed` DECIMAL(20,8) DEFAULT 0,
  `error_logs` LONGTEXT NULL,
  `execution_time_ms` INT DEFAULT 0,
  `retry_count` INT DEFAULT 0,
  `started_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_execution_date` (`execution_date`),
  KEY `idx_cron_type` (`cron_type`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `unique_execution` (`execution_date`, `cron_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks each cron execution with status and error handling';

-- ROI Maturity Schedule
DROP TABLE IF EXISTS `roi_maturity_schedule`;
CREATE TABLE `roi_maturity_schedule` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `stake_id` BIGINT UNSIGNED NOT NULL,
  `plan_type` ENUM('fixed', 'regular', 'combo') NOT NULL,
  `maturity_date` DATE NOT NULL,
  `principal_amount` DECIMAL(20,8) NOT NULL,
  `roi_rate_percent` DECIMAL(10,4) NOT NULL,
  `expected_roi_amount` DECIMAL(20,8) NOT NULL COMMENT 'Total ROI for the term',
  `fixed_roi_amount` DECIMAL(20,8) NULL COMMENT 'For fixed/combo - amount due at maturity',
  `regular_roi_amount` DECIMAL(20,8) NULL COMMENT 'For regular/combo - already distributed or awaiting',
  `distributed` TINYINT(1) DEFAULT 0 COMMENT '1 if maturity ROI has been paid',
  `distributed_at` DATETIME NULL,
  `tx_hash` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`),
  KEY `idx_stake` (`stake_id`),
  KEY `idx_maturity_date` (`maturity_date`),
  KEY `idx_distributed` (`distributed`),
  FOREIGN KEY (`user_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks maturity dates for each stake with expected ROI amounts';

-- ROI Monthly Payment Schedule (for validation)
DROP TABLE IF EXISTS `roi_monthly_schedule`;
CREATE TABLE `roi_monthly_schedule` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `stake_id` BIGINT UNSIGNED NOT NULL,
  `plan_type` ENUM('fixed', 'regular', 'combo') NOT NULL,
  `payment_month_year` DATE NOT NULL COMMENT 'First day of month (e.g., 2026-07-01)',
  `payment_days` VARCHAR(50) NOT NULL COMMENT 'Days of month (e.g., "5,15,25")',
  `monthly_roi_amount` DECIMAL(20,8) NOT NULL COMMENT 'Total monthly payment',
  `per_payment_amount` DECIMAL(20,8) NOT NULL COMMENT 'Per-day amount (monthly/3)',
  `payments_completed` INT DEFAULT 0 COMMENT '0-3 payments executed this month',
  `total_paid_month` DECIMAL(20,8) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`),
  KEY `idx_stake` (`stake_id`),
  KEY `idx_payment_month` (`payment_month_year`),
  FOREIGN KEY (`user_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks expected monthly ROI payments and completion status';

SET FOREIGN_KEY_CHECKS = 1;
