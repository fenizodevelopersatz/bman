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
