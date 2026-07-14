-- =============================================================================
-- Complete ROI Management & Gas Fee Tracking Migration
-- Fixed version with proper constraints and gas fee handling
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SESSION SQL_MODE = 'STRICT_TRANS_TABLES';

-- =============================================================================
-- ROI Distribution Audit Table
-- =============================================================================
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
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks all ROI distributions with validation and retry logic';

-- =============================================================================
-- Gas Fee Management Table
-- =============================================================================
DROP TABLE IF EXISTS `roi_gas_fees`;
CREATE TABLE `roi_gas_fees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `roi_audit_id` BIGINT UNSIGNED NOT NULL,
  `transaction_type` VARCHAR(50) NOT NULL COMMENT 'monthly, maturity, retry',
  `gas_fee_usdt` DECIMAL(18,8) NOT NULL COMMENT 'Gas fee in USDT',
  `gas_fee_bman` DECIMAL(20,8) NULL COMMENT 'If paid in BMAN',
  `gas_price_gwei` DECIMAL(18,8) NULL,
  `gas_limit` BIGINT NULL,
  `block_number` BIGINT NULL,
  `tx_hash` VARCHAR(255) NULL COMMENT 'Gas transaction hash',
  `status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  `payment_date` DATE NOT NULL,
  `paid_at` DATETIME NULL,
  `admin_note` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`),
  KEY `idx_roi_audit` (`roi_audit_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_date` (`payment_date`),
  FOREIGN KEY (`roi_audit_id`) REFERENCES `roi_distribution_audit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Track gas fees for each ROI distribution';

-- =============================================================================
-- Failed Transaction Retry Queue
-- =============================================================================
DROP TABLE IF EXISTS `roi_failed_transactions`;
CREATE TABLE `roi_failed_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `roi_audit_id` BIGINT UNSIGNED NOT NULL,
  `roi_amount` DECIMAL(20,8) NOT NULL,
  `failure_reason` TEXT NOT NULL,
  `failure_code` VARCHAR(50),
  `gas_fee_issue` TINYINT(1) DEFAULT 0 COMMENT '1 if failure was due to insufficient gas',
  `retry_count` INT DEFAULT 0,
  `max_retries` INT DEFAULT 3,
  `next_retry_at` DATETIME,
  `last_retry_at` DATETIME NULL,
  `resolved_at` DATETIME NULL,
  `resolution_notes` TEXT,
  `status` ENUM('failed', 'pending_retry', 'resolved') DEFAULT 'failed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_retry_at` (`next_retry_at`),
  FOREIGN KEY (`roi_audit_id`) REFERENCES `roi_distribution_audit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Queue for failed ROI transactions requiring retry';

-- =============================================================================
-- ROI Cron Execution Log
-- =============================================================================
DROP TABLE IF EXISTS `roi_cron_execution`;
CREATE TABLE `roi_cron_execution` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `execution_date` DATE NOT NULL,
  `execution_day` INT NOT NULL COMMENT '1-31 day of month',
  `cron_type` ENUM('monthly_payment', 'maturity_payout', 'retry_failed') NOT NULL,
  `status` ENUM('pending', 'running', 'success', 'failed') DEFAULT 'pending',
  `total_stakes_processed` INT DEFAULT 0,
  `total_stakes_failed` INT DEFAULT 0,
  `total_amount_distributed` DECIMAL(20,8) DEFAULT 0,
  `total_gas_fees_charged` DECIMAL(20,8) DEFAULT 0,
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
COMMENT='Tracks each cron execution with status and performance metrics';

-- =============================================================================
-- ROI Maturity Schedule
-- =============================================================================
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
  `regular_roi_amount` DECIMAL(20,8) NULL COMMENT 'For regular/combo - already distributed',
  `distributed` TINYINT(1) DEFAULT 0 COMMENT '1 if maturity ROI has been paid',
  `distributed_at` DATETIME NULL,
  `tx_hash` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`),
  KEY `idx_stake` (`stake_id`),
  KEY `idx_maturity_date` (`maturity_date`),
  KEY `idx_distributed` (`distributed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks maturity dates for each stake with expected ROI amounts';

-- =============================================================================
-- ROI Monthly Payment Schedule
-- =============================================================================
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
  `total_gas_fees_month` DECIMAL(20,8) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`),
  KEY `idx_stake` (`stake_id`),
  KEY `idx_payment_month` (`payment_month_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks expected monthly ROI payments and completion status';

-- =============================================================================
-- Gas Fee Configuration & Budget
-- =============================================================================
DROP TABLE IF EXISTS `roi_gas_budget`;
CREATE TABLE `roi_gas_budget` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `budget_type` ENUM('monthly', 'daily') DEFAULT 'monthly',
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `total_budget_usdt` DECIMAL(20,8) NOT NULL,
  `total_spent_usdt` DECIMAL(20,8) DEFAULT 0,
  `remaining_usdt` DECIMAL(20,8) DEFAULT 0,
  `transaction_count` INT DEFAULT 0,
  `admin_notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_period` (`period_start`, `period_end`),
  UNIQUE KEY `unique_period` (`budget_type`, `period_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Track gas fee budget and spending limits';

-- =============================================================================
-- Create indexes for better query performance
-- =============================================================================
ALTER TABLE `roi_distribution_audit`
  ADD INDEX `idx_user_date` (`user_id`, `actual_payment_date`),
  ADD INDEX `idx_status_retry` (`status`, `retry_count`);

ALTER TABLE `roi_gas_fees`
  ADD INDEX `idx_user_date` (`user_id`, `payment_date`),
  ADD INDEX `idx_status_date` (`status`, `paid_at`);

ALTER TABLE `roi_failed_transactions`
  ADD INDEX `idx_retry_queue` (`status`, `next_retry_at`),
  ADD INDEX `idx_user_status` (`user_id`, `status`);

SET FOREIGN_KEY_CHECKS = 1;
