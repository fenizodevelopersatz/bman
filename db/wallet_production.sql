-- ============================================================================
-- Production Wallet Architecture (deposit tracking + double-entry ledger)
-- Principle: balances are NEVER updated ad-hoc — every movement writes a
-- wallet_ledger row (credit/debit + balance_after), and every on-chain deposit
-- is tracked in wallet_deposits with a UNIQUE tx_hash (no double credit).
-- Reuses user_wallet (custodial address) and user_wallets (per-wallet balances).
-- Idempotent: safe to re-run.
-- ============================================================================

-- --------------------------------------------------------------------------
-- 1. Per-user internal balances (extend the existing user_wallets table).
--    usd_balance already exists (= USDT wallet). Add the four BMAN buckets
--    + lifetime totals. All nullable/defaulted → backward compatible.
-- --------------------------------------------------------------------------
SET @db := DATABASE();
-- helper to add a column only if missing
DROP PROCEDURE IF EXISTS _add_col;
DELIMITER //
CREATE PROCEDURE _add_col(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

CALL _add_col('user_wallets','exchange_balance','`exchange_balance` DECIMAL(30,8) NOT NULL DEFAULT 0');
CALL _add_col('user_wallets','earning_balance','`earning_balance` DECIMAL(30,8) NOT NULL DEFAULT 0');
CALL _add_col('user_wallets','staking_balance','`staking_balance` DECIMAL(30,8) NOT NULL DEFAULT 0');
CALL _add_col('user_wallets','bonus_balance','`bonus_balance` DECIMAL(30,8) NOT NULL DEFAULT 0');
CALL _add_col('user_wallets','total_deposit_usdt','`total_deposit_usdt` DECIMAL(30,8) NOT NULL DEFAULT 0');
CALL _add_col('user_wallets','total_withdraw_usdt','`total_withdraw_usdt` DECIMAL(30,8) NOT NULL DEFAULT 0');
DROP PROCEDURE IF EXISTS _add_col;

-- --------------------------------------------------------------------------
-- 2. Wallet ledger — the single source of truth for all balance movements.
--    Every credit/debit appends a row carrying the resulting balance_after.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `wallet_type` ENUM('usdt','exchange','earning','staking','bonus') NOT NULL,
  `credit` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `debit` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `balance_after` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `reference_type` VARCHAR(40) NOT NULL,   -- deposit|withdrawal|stake_purchase|roi|bonus|binary_commission|rank_reward|wallet_transfer|admin_adjustment
  `reference_id` VARCHAR(64) DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `tx_hash` VARCHAR(120) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,           -- admin id when applicable
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_wallet` (`user_id`,`wallet_type`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  -- One ledger row per (tx_hash, wallet_type): prevents double-credit of the
  -- same on-chain tx. NULL tx_hash rows (internal moves) are exempt (MySQL
  -- treats NULLs as distinct in a unique index).
  UNIQUE KEY `uq_tx_wallet` (`tx_hash`,`wallet_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 3. On-chain deposits — one row per detected USDT transfer to a user address.
--    UNIQUE tx_hash is the hard guard against crediting a deposit twice.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_deposits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `wallet_address` VARCHAR(100) NOT NULL,
  `tx_hash` VARCHAR(120) NOT NULL,
  `log_index` INT NOT NULL DEFAULT 0,
  `block_number` BIGINT UNSIGNED DEFAULT NULL,
  `token` VARCHAR(20) NOT NULL DEFAULT 'USDT',
  `amount_usdt` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `amount_bman` DECIMAL(30,8) NOT NULL DEFAULT 0,
  `confirmations` INT NOT NULL DEFAULT 0,
  `network` VARCHAR(20) NOT NULL DEFAULT 'mainnet',
  -- pending: seen, not enough confirmations · confirmed: enough confs ·
  -- credited: reflected into the ledger · failed/expired: terminal.
  `status` ENUM('pending','confirming','confirmed','credited','failed','expired') NOT NULL DEFAULT 'pending',
  `distribution_option_id` INT DEFAULT NULL,
  `credited_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tx` (`tx_hash`,`log_index`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_address` (`wallet_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 4. Listener state — last scanned block per network (so the poller resumes).
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_scan_state` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `network` VARCHAR(20) NOT NULL,
  `token` VARCHAR(20) NOT NULL DEFAULT 'USDT',
  `last_block` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_net_token` (`network`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
