-- =============================================================================
-- Migration: Add granular per-transaction cron status tracking with failure messages
-- Version: 2.0
-- Date: 2026-07-09
-- Description: Track each TX step independently with status and failure message
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SESSION SQL_MODE = 'STRICT_TRANS_TABLES';

START TRANSACTION;

-- =============================================================================
-- SECTION 1: Add TX Hash Columns for Each Distribution Wallet
-- =============================================================================

-- Add bman_exchange_tx_hash
DROP PROCEDURE IF EXISTS _add_bman_exchange_tx_hash;
DELIMITER //
CREATE PROCEDURE _add_bman_exchange_tx_hash()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_exchange_tx_hash';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_exchange_tx_hash` VARCHAR(120) NULL DEFAULT NULL
      COMMENT 'BMAN transfer to exchange wallet'
      AFTER `bman_tx_hash`;
    SELECT 'Added bman_exchange_tx_hash' as status;
  ELSE
    SELECT 'bman_exchange_tx_hash already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_bman_exchange_tx_hash();
DROP PROCEDURE IF EXISTS _add_bman_exchange_tx_hash;

-- Add bman_earning_tx_hash
DROP PROCEDURE IF EXISTS _add_bman_earning_tx_hash;
DELIMITER //
CREATE PROCEDURE _add_bman_earning_tx_hash()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_earning_tx_hash';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_earning_tx_hash` VARCHAR(120) NULL DEFAULT NULL
      COMMENT 'BMAN transfer to earning wallet'
      AFTER `bman_exchange_tx_hash`;
    SELECT 'Added bman_earning_tx_hash' as status;
  ELSE
    SELECT 'bman_earning_tx_hash already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_bman_earning_tx_hash();
DROP PROCEDURE IF EXISTS _add_bman_earning_tx_hash;

-- Add bman_staking_tx_hash
DROP PROCEDURE IF EXISTS _add_bman_staking_tx_hash;
DELIMITER //
CREATE PROCEDURE _add_bman_staking_tx_hash()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_staking_tx_hash';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_staking_tx_hash` VARCHAR(120) NULL DEFAULT NULL
      COMMENT 'BMAN transfer to staking wallet'
      AFTER `bman_earning_tx_hash`;
    SELECT 'Added bman_staking_tx_hash' as status;
  ELSE
    SELECT 'bman_staking_tx_hash already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_bman_staking_tx_hash();
DROP PROCEDURE IF EXISTS _add_bman_staking_tx_hash;

-- Add bman_bonus_tx_hash
DROP PROCEDURE IF EXISTS _add_bman_bonus_tx_hash;
DELIMITER //
CREATE PROCEDURE _add_bman_bonus_tx_hash()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_bonus_tx_hash';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_bonus_tx_hash` VARCHAR(120) NULL DEFAULT NULL
      COMMENT 'BMAN transfer to bonus wallet'
      AFTER `bman_staking_tx_hash`;
    SELECT 'Added bman_bonus_tx_hash' as status;
  ELSE
    SELECT 'bman_bonus_tx_hash already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_bman_bonus_tx_hash();
DROP PROCEDURE IF EXISTS _add_bman_bonus_tx_hash;

-- Add bonus_tx_hash (for bonus BMAN)
DROP PROCEDURE IF EXISTS _add_bonus_tx_hash;
DELIMITER //
CREATE PROCEDURE _add_bonus_tx_hash()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bonus_tx_hash';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bonus_tx_hash` VARCHAR(120) NULL DEFAULT NULL
      COMMENT 'Bonus BMAN transfer TX hash'
      AFTER `bman_bonus_tx_hash`;
    SELECT 'Added bonus_tx_hash' as status;
  ELSE
    SELECT 'bonus_tx_hash already exists' as status;
  END IF;
END//
DELIMITER ;
CALL _add_bonus_tx_hash();
DROP PROCEDURE IF EXISTS _add_bonus_tx_hash;

-- =============================================================================
-- SECTION 2: Add Granular Cron Status Columns
-- =============================================================================

-- gas_cron_status
DROP PROCEDURE IF EXISTS _add_gas_cron_status;
DELIMITER //
CREATE PROCEDURE _add_gas_cron_status()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'gas_cron_status';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `gas_cron_status` TINYINT NOT NULL DEFAULT 0
      COMMENT 'Gas fee cron: 0=pending, 1=completed'
      AFTER `bonus_tx_hash`;
  END IF;
END//
DELIMITER ;
CALL _add_gas_cron_status();
DROP PROCEDURE IF EXISTS _add_gas_cron_status;

-- usdt_cron_status
DROP PROCEDURE IF EXISTS _add_usdt_cron_status;
DELIMITER //
CREATE PROCEDURE _add_usdt_cron_status()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'usdt_cron_status';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `usdt_cron_status` TINYINT NOT NULL DEFAULT 0
      COMMENT 'USDT payment cron: 0=pending, 1=completed'
      AFTER `gas_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_usdt_cron_status();
DROP PROCEDURE IF EXISTS _add_usdt_cron_status;

-- bonus_cron_status
DROP PROCEDURE IF EXISTS _add_bonus_cron_status;
DELIMITER //
CREATE PROCEDURE _add_bonus_cron_status()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bonus_cron_status';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bonus_cron_status` TINYINT NOT NULL DEFAULT 0
      COMMENT 'Bonus BMAN cron: 0=pending, 1=completed'
      AFTER `usdt_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bonus_cron_status();
DROP PROCEDURE IF EXISTS _add_bonus_cron_status;

-- bman_exchange_cron_status
DROP PROCEDURE IF EXISTS _add_bman_exchange_cron_status;
DELIMITER //
CREATE PROCEDURE _add_bman_exchange_cron_status()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_exchange_cron_status';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_exchange_cron_status` TINYINT NOT NULL DEFAULT 0
      COMMENT 'BMAN exchange distribution cron: 0=pending, 1=completed'
      AFTER `bonus_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bman_exchange_cron_status();
DROP PROCEDURE IF EXISTS _add_bman_exchange_cron_status;

-- bman_earning_cron_status
DROP PROCEDURE IF EXISTS _add_bman_earning_cron_status;
DELIMITER //
CREATE PROCEDURE _add_bman_earning_cron_status()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_earning_cron_status';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_earning_cron_status` TINYINT NOT NULL DEFAULT 0
      COMMENT 'BMAN earning distribution cron: 0=pending, 1=completed'
      AFTER `bman_exchange_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bman_earning_cron_status();
DROP PROCEDURE IF EXISTS _add_bman_earning_cron_status;

-- bman_staking_cron_status
DROP PROCEDURE IF EXISTS _add_bman_staking_cron_status;
DELIMITER //
CREATE PROCEDURE _add_bman_staking_cron_status()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_staking_cron_status';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_staking_cron_status` TINYINT NOT NULL DEFAULT 0
      COMMENT 'BMAN staking distribution cron: 0=pending, 1=completed'
      AFTER `bman_earning_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bman_staking_cron_status();
DROP PROCEDURE IF EXISTS _add_bman_staking_cron_status;

-- bman_bonus_cron_status
DROP PROCEDURE IF EXISTS _add_bman_bonus_cron_status;
DELIMITER //
CREATE PROCEDURE _add_bman_bonus_cron_status()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_bonus_cron_status';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_bonus_cron_status` TINYINT NOT NULL DEFAULT 0
      COMMENT 'BMAN bonus distribution cron: 0=pending, 1=completed'
      AFTER `bman_staking_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bman_bonus_cron_status();
DROP PROCEDURE IF EXISTS _add_bman_bonus_cron_status;

-- =============================================================================
-- SECTION 3: Add Failure Message Columns (NEW in v2.0)
-- =============================================================================

-- gas_cron_status_message
DROP PROCEDURE IF EXISTS _add_gas_cron_status_message;
DELIMITER //
CREATE PROCEDURE _add_gas_cron_status_message()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'gas_cron_status_message';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `gas_cron_status_message` TEXT NULL
      COMMENT 'Gas fee cron failure message/error'
      AFTER `gas_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_gas_cron_status_message();
DROP PROCEDURE IF EXISTS _add_gas_cron_status_message;

-- usdt_cron_status_message
DROP PROCEDURE IF EXISTS _add_usdt_cron_status_message;
DELIMITER //
CREATE PROCEDURE _add_usdt_cron_status_message()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'usdt_cron_status_message';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `usdt_cron_status_message` TEXT NULL
      COMMENT 'USDT payment cron failure message/error'
      AFTER `usdt_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_usdt_cron_status_message();
DROP PROCEDURE IF EXISTS _add_usdt_cron_status_message;

-- bonus_cron_status_message
DROP PROCEDURE IF EXISTS _add_bonus_cron_status_message;
DELIMITER //
CREATE PROCEDURE _add_bonus_cron_status_message()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bonus_cron_status_message';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bonus_cron_status_message` TEXT NULL
      COMMENT 'Bonus BMAN cron failure message/error'
      AFTER `bonus_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bonus_cron_status_message();
DROP PROCEDURE IF EXISTS _add_bonus_cron_status_message;

-- bman_exchange_cron_status_message
DROP PROCEDURE IF EXISTS _add_bman_exchange_cron_status_message;
DELIMITER //
CREATE PROCEDURE _add_bman_exchange_cron_status_message()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_exchange_cron_status_message';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_exchange_cron_status_message` TEXT NULL
      COMMENT 'BMAN exchange cron failure message/error'
      AFTER `bman_exchange_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bman_exchange_cron_status_message();
DROP PROCEDURE IF EXISTS _add_bman_exchange_cron_status_message;

-- bman_earning_cron_status_message
DROP PROCEDURE IF EXISTS _add_bman_earning_cron_status_message;
DELIMITER //
CREATE PROCEDURE _add_bman_earning_cron_status_message()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_earning_cron_status_message';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_earning_cron_status_message` TEXT NULL
      COMMENT 'BMAN earning cron failure message/error'
      AFTER `bman_earning_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bman_earning_cron_status_message();
DROP PROCEDURE IF EXISTS _add_bman_earning_cron_status_message;

-- bman_staking_cron_status_message
DROP PROCEDURE IF EXISTS _add_bman_staking_cron_status_message;
DELIMITER //
CREATE PROCEDURE _add_bman_staking_cron_status_message()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_staking_cron_status_message';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_staking_cron_status_message` TEXT NULL
      COMMENT 'BMAN staking cron failure message/error'
      AFTER `bman_staking_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bman_staking_cron_status_message();
DROP PROCEDURE IF EXISTS _add_bman_staking_cron_status_message;

-- bman_bonus_cron_status_message
DROP PROCEDURE IF EXISTS _add_bman_bonus_cron_status_message;
DELIMITER //
CREATE PROCEDURE _add_bman_bonus_cron_status_message()
BEGIN
  DECLARE col_exists INT DEFAULT 0;
  SELECT COUNT(*) INTO col_exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND COLUMN_NAME = 'bman_bonus_cron_status_message';
  IF col_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD COLUMN `bman_bonus_cron_status_message` TEXT NULL
      COMMENT 'BMAN bonus cron failure message/error'
      AFTER `bman_bonus_cron_status`;
  END IF;
END//
DELIMITER ;
CALL _add_bman_bonus_cron_status_message();
DROP PROCEDURE IF EXISTS _add_bman_bonus_cron_status_message;

-- =============================================================================
-- SECTION 4: Indexes for Performance
-- =============================================================================

DROP PROCEDURE IF EXISTS _add_cron_indexes;
DELIMITER //
CREATE PROCEDURE _add_cron_indexes()
BEGIN
  DECLARE idx_exists INT DEFAULT 0;

  -- Index for finding pending steps
  SELECT COUNT(*) INTO idx_exists FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
    AND INDEX_NAME = 'idx_cron_pending';

  IF idx_exists = 0 THEN
    ALTER TABLE `staking_swap_orders`
      ADD KEY `idx_cron_pending` (
        `gas_cron_status`, `usdt_cron_status`, `bonus_cron_status`,
        `bman_exchange_cron_status`, `bman_earning_cron_status`,
        `bman_staking_cron_status`, `bman_bonus_cron_status`, `status`
      );
  END IF;
END//
DELIMITER ;
CALL _add_cron_indexes();
DROP PROCEDURE IF EXISTS _add_cron_indexes;

-- =============================================================================
-- SECTION 5: Verification & Completion
-- =============================================================================

SELECT '✅ Granular cron status migration v2.0 completed!' as message;

-- Verify all status and message columns added
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'staking_swap_orders'
  AND (COLUMN_NAME LIKE '%cron_status%' OR COLUMN_NAME LIKE 'bman_%_tx_hash' OR COLUMN_NAME LIKE 'bonus_tx_hash' OR COLUMN_NAME LIKE '%_message')
ORDER BY ORDINAL_POSITION;

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

SELECT CONCAT('Migration v2.0 completed at ', NOW()) as completion_message,
       'Each TX step has independent status tracking + failure message logging for debugging' as details;

-- End of migration
