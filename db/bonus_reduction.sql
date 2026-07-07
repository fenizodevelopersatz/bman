-- ============================================================================
-- Bonus Wallet 60-Day Reduction → Admin (on-chain) — schema
-- ----------------------------------------------------------------------------
-- Every reduction_interval_days, reduction_percent of a user's Bonus Wallet
-- (user_wallets.bonus_balance) is reduced and handed to the admin bonus wallet.
-- The reduction is applied through Walletledger_model (double-entry) and,
-- when reduction_onchain=1, mirrored on-chain by sending BMAN from the user's
-- custodial address to the admin bonus/treasury wallet (Web3bman::sendToken).
--
-- Testing: set staking_bonus_settings.reduction_interval_days = 1 and
-- reduction_dry_run first (preview), then reduction_dry_run=0 to apply.
-- Idempotent: safe to re-run.
-- ============================================================================

-- --- 1. Two new toggles on the existing single-row settings table ----------
-- Idempotent ADD COLUMN (MySQL has no "ADD COLUMN IF NOT EXISTS" on 5.x/MariaDB
-- pre-10.0, so guard via INFORMATION_SCHEMA + a prepared statement).

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'staking_bonus_settings'
               AND COLUMN_NAME  = 'reduction_dry_run');
SET @sql := IF(@col = 0,
  'ALTER TABLE `staking_bonus_settings` ADD COLUMN `reduction_dry_run` TINYINT(1) NOT NULL DEFAULT 1 AFTER `reduction_percent`',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'staking_bonus_settings'
               AND COLUMN_NAME  = 'reduction_onchain');
SET @sql := IF(@col = 0,
  'ALTER TABLE `staking_bonus_settings` ADD COLUMN `reduction_onchain` TINYINT(1) NOT NULL DEFAULT 0 AFTER `reduction_dry_run`',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- --- 2. Admin bonus wallet (singleton) — the pool of reclaimed bonus --------
-- NOTE: `admin_wallet` already exists (created by doc 10's schema) with columns
--   id, balance, lifetime_bonus_reduction_total, updated_at.
-- The reduction cron credits balance + lifetime_bonus_reduction_total on it.
-- This CREATE is only a safety net for a fresh DB that never ran doc 10.
CREATE TABLE IF NOT EXISTS `admin_wallet` (
  `id`                             TINYINT UNSIGNED NOT NULL,   -- always 1
  `balance`                        DECIMAL(30,8) NOT NULL DEFAULT 0,  -- running reclaimed total
  `lifetime_bonus_reduction_total` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `updated_at`                     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO `admin_wallet` (`id`) VALUES (1);

-- --- 3. One row per reduction event (audit + on-chain tx tracking) ----------
CREATE TABLE IF NOT EXISTS `bonus_reduction_log` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT NOT NULL,
  `cycle_no`       INT UNSIGNED NOT NULL DEFAULT 1,     -- 1st, 2nd, ... reduction for this user
  `anchor_date`    DATETIME DEFAULT NULL,               -- register_date or prior reduction
  `bonus_before`   DECIMAL(30,8) NOT NULL DEFAULT 0,
  `reduce_percent` DECIMAL(6,2)  NOT NULL DEFAULT 0,
  `amount`         DECIMAL(30,8) NOT NULL DEFAULT 0,     -- reclaimed BMAN
  `from_address`   VARCHAR(120) DEFAULT NULL,            -- user custodial address
  `to_address`     VARCHAR(120) DEFAULT NULL,            -- admin bonus/treasury wallet
  `wallet_ledger_id` BIGINT UNSIGNED DEFAULT NULL,       -- the debit row in wallet_ledger
  `tx_hash`        VARCHAR(120) DEFAULT NULL,            -- on-chain hash (when broadcast)
  -- internal = balance reduced, no broadcast; sent = on-chain broadcast ok;
  -- failed = on-chain broadcast failed (internal reduction still applied).
  `status`         ENUM('internal','sent','failed') NOT NULL DEFAULT 'internal',
  `note`           VARCHAR(255) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
