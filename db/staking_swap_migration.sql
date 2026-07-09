-- =============================================================================
-- Migration: Add plan details and cron status to staking_swap_orders
-- Date: 2026-07-09
-- =============================================================================

-- Safely add missing columns to staking_swap_orders table
DROP PROCEDURE IF EXISTS _migrate_sso;
DELIMITER //
CREATE PROCEDURE _migrate_sso()
BEGIN
  DECLARE col_exists INT;

  -- Check and add plan_code
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'plan_code';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders` ADD COLUMN `plan_code` VARCHAR(50) NULL DEFAULT NULL
      COMMENT 'fixed, variable, etc' AFTER `exchange_rate`;
  END IF;

  -- Check and add plan_id
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'plan_id';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders` ADD COLUMN `plan_id` INT UNSIGNED NULL DEFAULT NULL
      COMMENT 'staking plan reference' AFTER `plan_code`;
  END IF;

  -- Check and add duration_years
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'duration_years';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders` ADD COLUMN `duration_years` INT NULL DEFAULT NULL
      COMMENT 'staking duration in years' AFTER `plan_id`;
  END IF;

  -- Check and add coin_distribution_option_id
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'coin_distribution_option_id';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders` ADD COLUMN `coin_distribution_option_id` INT UNSIGNED NULL DEFAULT NULL
      COMMENT 'which wallet receives BMAN (exchange=1, staking=2, earning=3, bonus=4)' AFTER `duration_years`;
  END IF;

  -- Check and add cron_status
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'cron_status';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders` ADD COLUMN `cron_status` VARCHAR(50) DEFAULT 'pending'
      COMMENT 'pending, processing, completed, skipped' AFTER `coin_distribution_option_id`;
  END IF;

END//
DELIMITER ;
CALL _migrate_sso();
DROP PROCEDURE IF EXISTS _migrate_sso;

-- Add wallet_column to staking_roi_ledger if it doesn't exist
ALTER TABLE `staking_roi_ledger` ADD COLUMN `wallet_column` VARCHAR(24) DEFAULT 'earning'
  COMMENT 'exchange, staking, earning, or bonus' AFTER `roi_type`;

-- Add index for faster cron lookups
ALTER TABLE `staking_swap_orders` ADD KEY `idx_cron_status` (`cron_status`, `status`);

-- Done
SELECT 'Migration completed successfully' as status;
