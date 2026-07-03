-- ============================================================================
-- Internal Wallet Transfer Module — Migration
-- Route: /user/transfer_wallet
-- Principle: Exchange ↔ Earning ↔ Staking ↔ Bonus (USDT excluded — blockchain only)
-- Idempotent: safe to re-run on any environment.
-- ============================================================================

-- --------------------------------------------------------------------------
-- 1. Add transfer_password to users (for PIN authentication before transfer)
-- --------------------------------------------------------------------------
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

CALL _add_col('users','transfer_password','`transfer_password` VARCHAR(255) NULL DEFAULT NULL COMMENT "Hashed transfer PIN (separate from login password)"');
CALL _add_col('users','transfer_password_set_at','`transfer_password_set_at` DATETIME NULL DEFAULT NULL');
DROP PROCEDURE IF EXISTS _add_col;

-- --------------------------------------------------------------------------
-- 2. Internal wallet transfer log
--    Records every user-initiated internal wallet-to-wallet transfer.
--    The actual balance movement stays in wallet_ledger (double-entry).
--    This table is the "audit header" — the two ledger rows are children.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_internal_transfer` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref`           VARCHAR(32)     NOT NULL COMMENT 'WTF-YYYYMMDD-XXXXXXXX unique reference',
  `user_id`       INT             NOT NULL,
  `from_wallet`   ENUM('exchange','earning','staking','bonus') NOT NULL,
  `to_wallet`     ENUM('exchange','earning','staking','bonus') NOT NULL,
  `amount`        DECIMAL(30,8)   NOT NULL DEFAULT 0,
  `fee`           DECIMAL(30,8)   NOT NULL DEFAULT 0,
  `net_amount`    DECIMAL(30,8)   NOT NULL DEFAULT 0 COMMENT 'amount - fee',
  `status`        ENUM('completed','failed','reversed') NOT NULL DEFAULT 'completed',
  `description`   VARCHAR(255)    NULL DEFAULT NULL,
  `debit_ledger_id`  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'FK → wallet_ledger.id (debit row)',
  `credit_ledger_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'FK → wallet_ledger.id (credit row)',
  `ip_address`    VARCHAR(45)     NULL DEFAULT NULL,
  `user_agent`    VARCHAR(255)    NULL DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref` (`ref`),
  KEY `idx_user`    (`user_id`),
  KEY `idx_status`  (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Internal wallet-to-wallet transfer audit log (USDT excluded)';

-- --------------------------------------------------------------------------
-- 3. Member-to-member + admin tracking columns (added after initial release)
--    * to_user_id  — recipient when this is a user→user transfer (NULL = self)
--    * txn_uid     — global 8-digit tracking id, shown on both grids
--    * from/to_before/after — balance snapshots for full audit trail
--    * via         — 'user' (member self-service) or 'admin' (panel, no gates)
--    Idempotent: safe to re-run.
-- --------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _wit_add_col;
DELIMITER //
CREATE PROCEDURE _wit_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wallet_internal_transfer' AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `wallet_internal_transfer` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

CALL _wit_add_col('to_user_id', '`to_user_id` INT NULL DEFAULT NULL AFTER `user_id`');
CALL _wit_add_col('txn_uid',    '`txn_uid` VARCHAR(8) NULL DEFAULT NULL AFTER `ref`');
CALL _wit_add_col('from_before','`from_before` DECIMAL(30,8) NULL DEFAULT NULL AFTER `net_amount`');
CALL _wit_add_col('from_after', '`from_after` DECIMAL(30,8) NULL DEFAULT NULL AFTER `from_before`');
CALL _wit_add_col('to_before',  '`to_before` DECIMAL(30,8) NULL DEFAULT NULL AFTER `from_after`');
CALL _wit_add_col('to_after',   '`to_after` DECIMAL(30,8) NULL DEFAULT NULL AFTER `to_before`');
CALL _wit_add_col('via',        "`via` VARCHAR(10) NOT NULL DEFAULT 'user' AFTER `status`");
-- txn_type makes the movement explicit: 'self' = between one user's own wallets,
-- 'member' = user→user. Combined with user_id / to_user_id / from_wallet /
-- to_wallet this fully records who sent, who received, and which wallet.
CALL _wit_add_col('txn_type',   "`txn_type` VARCHAR(10) NOT NULL DEFAULT 'self' AFTER `status`");
DROP PROCEDURE IF EXISTS _wit_add_col;

-- Backfill txn_type for any rows created before this column existed.
UPDATE `wallet_internal_transfer`
   SET `txn_type` = IF(`to_user_id` IS NULL OR `to_user_id` = 0, 'self', 'member')
 WHERE `txn_type` IS NULL OR `txn_type` = '';

-- Unique index on txn_uid + recipient index (guarded so re-runs don't error).
SET @has_uid := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wallet_internal_transfer' AND INDEX_NAME = 'uq_txn_uid');
SET @s := IF(@has_uid = 0, 'ALTER TABLE `wallet_internal_transfer` ADD UNIQUE KEY `uq_txn_uid` (`txn_uid`)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @has_to := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wallet_internal_transfer' AND INDEX_NAME = 'idx_to_user');
SET @s := IF(@has_to = 0, 'ALTER TABLE `wallet_internal_transfer` ADD KEY `idx_to_user` (`to_user_id`)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
