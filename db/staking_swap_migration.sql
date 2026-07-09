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

-- Add coin_distribution_option column (1-7 wallet distribution)
DROP PROCEDURE IF EXISTS _add_coin_dist_option;
DELIMITER //
CREATE PROCEDURE _add_coin_dist_option()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'coin_distribution_option';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `coin_distribution_option` INT UNSIGNED NULL DEFAULT 1
      COMMENT 'BMAN distribution: 1-7 (see wallet allocation percentages)'
      AFTER `duration_years`;
    SELECT 'Added coin_distribution_option column' as status;
  ELSE
    SELECT 'coin_distribution_option column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_coin_dist_option();
DROP PROCEDURE IF EXISTS _add_coin_dist_option;

-- Add cron_status_gas column
DROP PROCEDURE IF EXISTS _add_cron_status_gas;
DELIMITER //
CREATE PROCEDURE _add_cron_status_gas()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'cron_status_gas';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `cron_status_gas` TINYINT NOT NULL DEFAULT 0
      COMMENT 'Gas fee cron: 0=need to execute, 1=completed'
      AFTER `coin_distribution_option`;
    SELECT 'Added cron_status_gas column' as status;
  ELSE
    SELECT 'cron_status_gas column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_cron_status_gas();
DROP PROCEDURE IF EXISTS _add_cron_status_gas;

-- Add cron_status_usdt column
DROP PROCEDURE IF EXISTS _add_cron_status_usdt;
DELIMITER //
CREATE PROCEDURE _add_cron_status_usdt()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'cron_status_usdt';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `cron_status_usdt` TINYINT NOT NULL DEFAULT 0
      COMMENT 'USDT payment cron: 0=need to execute, 1=completed'
      AFTER `cron_status_gas`;
    SELECT 'Added cron_status_usdt column' as status;
  ELSE
    SELECT 'cron_status_usdt column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_cron_status_usdt();
DROP PROCEDURE IF EXISTS _add_cron_status_usdt;

-- Add cron_status_bman column
DROP PROCEDURE IF EXISTS _add_cron_status_bman;
DELIMITER //
CREATE PROCEDURE _add_cron_status_bman()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'cron_status_bman';

  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `cron_status_bman` TINYINT NOT NULL DEFAULT 0
      COMMENT 'BMAN distribution cron: 0=need to execute, 1=completed'
      AFTER `cron_status_usdt`;
    SELECT 'Added cron_status_bman column' as status;
  ELSE
    SELECT 'cron_status_bman column already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_cron_status_bman();
DROP PROCEDURE IF EXISTS _add_cron_status_bman;

-- =============================================================================
-- SECTION 3: No ROI ledger tracking (handled by separate ROI system)
-- =============================================================================

-- All purchase status is tracked in onchain_transactions
-- No separate ROI ledger needed for purchase flow

-- =============================================================================
-- SECTION 4: Add indexes for performance
-- =============================================================================

-- Add index for cron status lookups (find orders needing processing)
DROP PROCEDURE IF EXISTS _add_cron_index;
DELIMITER //
CREATE PROCEDURE _add_cron_index()
BEGIN
  DECLARE idx_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO idx_exists FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND INDEX_NAME = 'idx_cron_processing';

  IF idx_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD KEY `idx_cron_processing` (`cron_status_gas`, `cron_status_usdt`, `cron_status_bman`, `status`);
    SELECT 'Added idx_cron_processing index' as status;
  ELSE
    SELECT 'idx_cron_processing index already exists' as status;
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
  AND COLUMN_NAME IN (
    'plan_code', 'plan_id', 'duration_years',
    'coin_distribution_option',
    'cron_status_gas', 'cron_status_usdt', 'cron_status_bman'
  )
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
