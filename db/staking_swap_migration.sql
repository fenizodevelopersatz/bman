-- =============================================================================
-- Migration: Add plan details and cron status to staking_swap_orders
-- Version: 1.0
-- Date: 2026-07-09
-- Description: Adds plan tracking and ROI cron status management to staking flow
-- =============================================================================

-- Start transaction for safety
START TRANSACTION;

-- =============================================================================
-- SECTION 1: Add plan details columns to staking_swap_orders
-- =============================================================================

-- Add plan_code column
DROP PROCEDURE IF EXISTS _add_plan_code;
DELIMITER //
CREATE PROCEDURE _add_plan_code()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'plan_code';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `plan_code` VARCHAR(50) NULL DEFAULT NULL
      COMMENT 'Plan type: fixed, variable, etc.'
      AFTER `exchange_rate`;
    SELECT 'Added plan_code column' as status;
  ELSE
    SELECT 'plan_code column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_plan_code();
DROP PROCEDURE IF EXISTS _add_plan_code;

-- Add plan_id column
DROP PROCEDURE IF EXISTS _add_plan_id;
DELIMITER //
CREATE PROCEDURE _add_plan_id()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'plan_id';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `plan_id` INT UNSIGNED NULL DEFAULT NULL
      COMMENT 'Reference to staking plan'
      AFTER `plan_code`;
    SELECT 'Added plan_id column' as status;
  ELSE
    SELECT 'plan_id column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_plan_id();
DROP PROCEDURE IF EXISTS _add_plan_id;

-- Add duration_years column
DROP PROCEDURE IF EXISTS _add_duration_years;
DELIMITER //
CREATE PROCEDURE _add_duration_years()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'duration_years';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `duration_years` INT NULL DEFAULT 1
      COMMENT 'Staking duration in years (2, 3, 5, etc.)'
      AFTER `plan_id`;
    SELECT 'Added duration_years column' as status;
  ELSE
    SELECT 'duration_years column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_duration_years();
DROP PROCEDURE IF EXISTS _add_duration_years;

-- =============================================================================
-- SECTION 2: Add coin distribution and cron status columns
-- =============================================================================

-- Add coin_distribution_option_id column
DROP PROCEDURE IF EXISTS _add_coin_dist_id;
DELIMITER //
CREATE PROCEDURE _add_coin_dist_id()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'coin_distribution_option_id';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `coin_distribution_option_id` INT UNSIGNED NULL DEFAULT 1
      COMMENT 'ROI wallet: 1=exchange, 2=staking, 3=earning, 4=bonus'
      AFTER `duration_years`;
    SELECT 'Added coin_distribution_option_id column' as status;
  ELSE
    SELECT 'coin_distribution_option_id column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_coin_dist_id();
DROP PROCEDURE IF EXISTS _add_coin_dist_id;

-- Add cron_status column
DROP PROCEDURE IF EXISTS _add_cron_status;
DELIMITER //
CREATE PROCEDURE _add_cron_status()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'cron_status';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `cron_status` VARCHAR(50) NOT NULL DEFAULT 'pending'
      COMMENT 'ROI cron status: pending, processing, completed, skipped'
      AFTER `coin_distribution_option_id`;
    SELECT 'Added cron_status column' as status;
  ELSE
    SELECT 'cron_status column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_cron_status();
DROP PROCEDURE IF EXISTS _add_cron_status;

-- =============================================================================
-- SECTION 3: Update staking_roi_ledger table
-- =============================================================================

-- Add wallet_column to staking_roi_ledger
DROP PROCEDURE IF EXISTS _add_wallet_column;
DELIMITER //
CREATE PROCEDURE _add_wallet_column()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  DECLARE table_exists INT DEFAULT 0;

  -- First check if table exists
  SELECT COUNT(*) INTO table_exists FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_roi_ledger';

  IF table_exists = 1 THEN
    SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_roi_ledger'
      AND COLUMN_NAME = 'wallet_column';

    IF col_exists = 0 THEN
      ALTER TABLE `staking_roi_ledger`
        ADD COLUMN `wallet_column` VARCHAR(24) NOT NULL DEFAULT 'earning'
        COMMENT 'Wallet credited: exchange, staking, earning, bonus'
        AFTER `roi_type`;
      SELECT 'Added wallet_column to staking_roi_ledger' as status;
    ELSE
      SELECT 'wallet_column already exists in staking_roi_ledger' as status;
    END IF;
  ELSE
    SELECT 'Creating staking_roi_ledger table...' as status;
    CREATE TABLE IF NOT EXISTS `staking_roi_ledger` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `staking_id` INT UNSIGNED NOT NULL,
      `user_id` INT NOT NULL,
      `roi_amount` DECIMAL(30,8) NOT NULL,
      `roi_type` VARCHAR(24) NOT NULL DEFAULT 'hourly',
      `wallet_column` VARCHAR(24) NOT NULL DEFAULT 'earning' COMMENT 'exchange, staking, earning, bonus',
      `processed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_staking` (`staking_id`),
      KEY `idx_user` (`user_id`),
      KEY `idx_date` (`processed_at`),
      KEY `idx_wallet` (`wallet_column`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='ROI processing ledger with wallet distribution tracking';
  END IF;
END//
DELIMITER ;
CALL _add_wallet_column();
DROP PROCEDURE IF EXISTS _add_wallet_column;

-- =============================================================================
-- SECTION 4: Add indexes for performance
-- =============================================================================

-- Add composite index for cron status lookups
DROP PROCEDURE IF EXISTS _add_cron_index;
DELIMITER //
CREATE PROCEDURE _add_cron_index()
BEGIN
  DECLARE idx_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO idx_exists FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND INDEX_NAME = 'idx_cron_status';

  IF idx_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD KEY `idx_cron_status` (`cron_status`, `status`);
    SELECT 'Added idx_cron_status index' as status;
  ELSE
    SELECT 'idx_cron_status index already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_cron_index();
DROP PROCEDURE IF EXISTS _add_cron_index;

-- =============================================================================
-- SECTION 5: Verification and summary
-- =============================================================================

SELECT '✅ Migration completed successfully!' as message;

-- Verify staking_swap_orders structure
SELECT
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE,
  COLUMN_DEFAULT,
  COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'staking_swap_orders'
  AND COLUMN_NAME IN ('plan_code', 'plan_id', 'duration_years', 'coin_distribution_option_id', 'cron_status')
ORDER BY ORDINAL_POSITION;

-- Verify indexes
SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'staking_swap_orders'
  AND INDEX_NAME = 'idx_cron_status'
ORDER BY SEQ_IN_INDEX;

-- Commit transaction
COMMIT;

-- Final status
SELECT
  CONCAT('Migration v1.0 completed at ', NOW()) as completion_message,
  'All plan tracking and cron status columns added' as details;
