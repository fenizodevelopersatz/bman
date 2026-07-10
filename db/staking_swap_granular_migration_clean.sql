-- =============================================================================
-- Migration: Add granular per-transaction cron status tracking with failure messages
-- Version: 2.0-CLEAN
-- Date: 2026-07-09
-- Description: Track each TX step independently with status and failure message
-- Safe: No transactions, no FK conflicts
-- =============================================================================

-- =============================================================================
-- SECTION 1: Add TX Hash Columns for Each Distribution Wallet
-- =============================================================================

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_exchange_tx_hash` VARCHAR(120) NULL DEFAULT NULL
  COMMENT 'BMAN transfer to exchange wallet';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_earning_tx_hash` VARCHAR(120) NULL DEFAULT NULL
  COMMENT 'BMAN transfer to earning wallet';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_staking_tx_hash` VARCHAR(120) NULL DEFAULT NULL
  COMMENT 'BMAN transfer to staking wallet';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_bonus_tx_hash` VARCHAR(120) NULL DEFAULT NULL
  COMMENT 'BMAN transfer to bonus wallet';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bonus_tx_hash` VARCHAR(120) NULL DEFAULT NULL
  COMMENT 'Bonus BMAN transfer TX hash';

-- =============================================================================
-- SECTION 2: Add Granular Cron Status Columns (0=pending, 1=completed)
-- =============================================================================

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `gas_cron_status` TINYINT NOT NULL DEFAULT 0
  COMMENT 'Gas fee cron: 0=pending, 1=completed';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `usdt_cron_status` TINYINT NOT NULL DEFAULT 0
  COMMENT 'USDT payment cron: 0=pending, 1=completed';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bonus_cron_status` TINYINT NOT NULL DEFAULT 0
  COMMENT 'Bonus BMAN cron: 0=pending, 1=completed';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_exchange_cron_status` TINYINT NOT NULL DEFAULT 0
  COMMENT 'BMAN exchange distribution cron: 0=pending, 1=completed';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_earning_cron_status` TINYINT NOT NULL DEFAULT 0
  COMMENT 'BMAN earning distribution cron: 0=pending, 1=completed';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_staking_cron_status` TINYINT NOT NULL DEFAULT 0
  COMMENT 'BMAN staking distribution cron: 0=pending, 1=completed';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_bonus_cron_status` TINYINT NOT NULL DEFAULT 0
  COMMENT 'BMAN bonus distribution cron: 0=pending, 1=completed';

-- =============================================================================
-- SECTION 3: Add Failure Message Columns (NEW in v2.0)
-- =============================================================================

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `gas_cron_status_message` TEXT NULL
  COMMENT 'Gas fee cron failure message/error';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `usdt_cron_status_message` TEXT NULL
  COMMENT 'USDT payment cron failure message/error';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bonus_cron_status_message` TEXT NULL
  COMMENT 'Bonus BMAN cron failure message/error';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_exchange_cron_status_message` TEXT NULL
  COMMENT 'BMAN exchange cron failure message/error';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_earning_cron_status_message` TEXT NULL
  COMMENT 'BMAN earning cron failure message/error';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_staking_cron_status_message` TEXT NULL
  COMMENT 'BMAN staking cron failure message/error';

ALTER TABLE `staking_swap_orders` ADD COLUMN IF NOT EXISTS
  `bman_bonus_cron_status_message` TEXT NULL
  COMMENT 'BMAN bonus cron failure message/error';

-- =============================================================================
-- SECTION 4: Add Index for Performance (if not exists)
-- =============================================================================

ALTER TABLE `staking_swap_orders`
ADD KEY IF NOT EXISTS `idx_cron_pending` (
  `gas_cron_status`, `usdt_cron_status`, `bonus_cron_status`,
  `bman_exchange_cron_status`, `bman_earning_cron_status`,
  `bman_staking_cron_status`, `bman_bonus_cron_status`, `status`
);

-- =============================================================================
-- SECTION 5: Verification
-- =============================================================================

-- Show all newly added columns
SELECT 'Migration v2.0-CLEAN complete!' as message;

SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'staking_swap_orders'
  AND (COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE '%_tx_hash%' OR COLUMN_NAME LIKE '%_message')
ORDER BY ORDINAL_POSITION;

-- Count of new columns
SELECT COUNT(*) as total_new_columns
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'staking_swap_orders'
  AND (COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE '%_message')
  AND COLUMN_NAME NOT IN ('cron_status_gas', 'cron_status_usdt', 'cron_status_bman');

-- End of clean migration
