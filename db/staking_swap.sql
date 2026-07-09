-- ============================================================================
-- On-chain USDT <-> BMAN Swap (business model "A": everything on-chain)
--   Leg 1  USDT : user deposit address --> Admin (treasury) wallet
--   Leg 2  BMAN : Admin (treasury) wallet --> user deposit address
--   Leg 3  BMAN : optional 25% bonus, Admin --> user (if enabled)
--
-- SAFETY: swap_enabled defaults 0 (off) and swap_dry_run defaults 1. While
-- dry-run is on, the engine records exactly what it WOULD broadcast but sends
-- NOTHING on-chain. Flip both only after a live test with a funded signer.
-- Idempotent: safe to re-run.
-- ============================================================================

-- 1) swap config flags on token_settings
DROP PROCEDURE IF EXISTS _ts_add_col;
DELIMITER //
CREATE PROCEDURE _ts_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'token_settings' AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `token_settings` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;
CALL _ts_add_col('swap_enabled', "`swap_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'on-chain swap live?'");
CALL _ts_add_col('swap_dry_run', "`swap_dry_run` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'log-only, no broadcast'");
CALL _ts_add_col('swap_bonus_onchain', "`swap_bonus_onchain` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'send 25% bonus BMAN on-chain too'");
CALL _ts_add_col('swap_auto_gas', "`swap_auto_gas` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'auto BNB gas top-up to user deposit address before USDT send'");
DROP PROCEDURE IF EXISTS _ts_add_col;

-- 2) swap order state machine + on-chain tx audit
CREATE TABLE IF NOT EXISTS `staking_swap_orders` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref`            VARCHAR(32)  NOT NULL,
  `user_id`        INT NOT NULL,
  `package_id`     INT UNSIGNED NULL DEFAULT NULL,
  `user_address`   VARCHAR(120) NOT NULL COMMENT 'user deposit address',
  `admin_address`  VARCHAR(120) NOT NULL COMMENT 'treasury / admin receive address',
  `usdt_amount`    DECIMAL(30,8) NOT NULL,
  `bman_amount`    DECIMAL(30,8) NOT NULL,
  `bonus_bman`     DECIMAL(30,8) NOT NULL DEFAULT 0,
  `exchange_rate`  DECIMAL(24,8) NOT NULL,
  `gas_tx_hash`    VARCHAR(120) NULL DEFAULT NULL COMMENT 'BNB gas top-up to user address',
  `usdt_tx_hash`   VARCHAR(120) NULL DEFAULT NULL,
  `bman_tx_hash`   VARCHAR(120) NULL DEFAULT NULL,
  `bonus_tx_hash`  VARCHAR(120) NULL DEFAULT NULL,
  -- created → usdt_sent → bman_sent → completed  (or failed_* at any leg)
  `status`         VARCHAR(24) NOT NULL DEFAULT 'created',
  `dry_run`        TINYINT(1)  NOT NULL DEFAULT 1,
  `error`          VARCHAR(255) NULL DEFAULT NULL,
  `attempts`       INT NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref` (`ref`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='On-chain USDT<->BMAN swap orders (two-leg state machine + tx hashes)';

-- add missing columns to staking_swap_orders (idempotent)
DROP PROCEDURE IF EXISTS _sso_add_col;
DELIMITER //
CREATE PROCEDURE _sso_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_swap_orders'
        AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `staking_swap_orders` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;
CALL _sso_add_col('gas_tx_hash', "`gas_tx_hash` VARCHAR(120) NULL DEFAULT NULL COMMENT 'BNB gas payment tx'");
CALL _sso_add_col('plan_code', "`plan_code` VARCHAR(50) NULL DEFAULT NULL COMMENT 'fixed, variable, etc'");
CALL _sso_add_col('plan_id', "`plan_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'staking plan reference'");
CALL _sso_add_col('duration_years', "`duration_years` INT NULL DEFAULT NULL COMMENT 'staking duration in years'");
CALL _sso_add_col('coin_distribution_option_id', "`coin_distribution_option_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'which wallet receives BMAN (exchange/staking/earning/bonus)'");
CALL _sso_add_col('cron_status', "`cron_status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, processing, completed, skipped'");
DROP PROCEDURE IF EXISTS _sso_add_col;
