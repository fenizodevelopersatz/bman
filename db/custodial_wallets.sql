-- ============================================================================
-- Custodial Wallet Management (proposal §3 Wallet System)
-- Each user has ONE unique on-chain BEP-20 deposit address (reuses the
-- existing `user_wallet` table). The five wallet buckets (USDT / Exchange /
-- Earning / Staking / Bonus) are internal balances. This migration adds:
--   - on-chain deposit records detected by the monitor
--   - a wallet-monitor log (on-chain vs DB balance difference + reconcile)
-- No existing table is modified; `user_wallet` / `user_wallets` are reused.
-- Idempotent: safe to re-run.
-- ============================================================================

-- On-chain deposits detected for a user's custodial address. A deposit is
-- "credited" once its amount is reflected into the internal USDT balance.
CREATE TABLE IF NOT EXISTS `custodial_deposits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `address` VARCHAR(100) NOT NULL,
  `token` VARCHAR(20) NOT NULL DEFAULT 'USDT',      -- USDT | BMAN | BNB
  `amount` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `tx_hash` VARCHAR(120) DEFAULT NULL,
  `onchain_confirmed` TINYINT(1) NOT NULL DEFAULT 1,
  `credited` TINYINT(1) NOT NULL DEFAULT 0,         -- reflected into usd_balance
  `source` VARCHAR(30) NOT NULL DEFAULT 'monitor',  -- monitor | manual
  `note` VARCHAR(255) DEFAULT NULL,
  `detected_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `credited_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_address` (`address`),
  KEY `idx_credited` (`credited`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Every balance scan / reconcile is logged (on-chain vs our DB, difference).
CREATE TABLE IF NOT EXISTS `wallet_monitor_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `address` VARCHAR(100) NOT NULL,
  `token` VARCHAR(20) NOT NULL DEFAULT 'USDT',
  `onchain_balance` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `db_balance` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `difference` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `action` VARCHAR(20) NOT NULL DEFAULT 'scan',     -- scan | reconcile
  `changed_by` INT DEFAULT NULL,                    -- admin id, NULL = user/cron
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The custodial address stored in `user_wallet` needs to be unique per chain.
-- Add a unique index only if it is not present yet.
SET @has_uq := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_wallet' AND INDEX_NAME = 'uq_wallet_address'
);
SET @sql := IF(@has_uq = 0,
  'ALTER TABLE `user_wallet` ADD UNIQUE KEY `uq_wallet_address` (`wallet_address`)',
  'SELECT "uq_wallet_address already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
