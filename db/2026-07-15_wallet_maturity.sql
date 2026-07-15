-- ============================================================================
-- Wallet maturity for withdrawal eligibility
-- Every ledger credit carries maturity_date + is_matured; withdrawals validate
-- against matured ledger balances, not user_wallets summary alone.
-- Idempotent: safe to re-run.
-- ============================================================================

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

CALL _add_col('wallet_ledger', 'maturity_date',
  '`maturity_date` DATETIME DEFAULT NULL COMMENT ''When this credit becomes withdrawable'' AFTER `created_at`');
CALL _add_col('wallet_ledger', 'is_matured',
  '`is_matured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = credit is withdrawable'' AFTER `maturity_date`');

DROP PROCEDURE IF EXISTS _add_col;

-- Backfill existing credits as already mature (preserves current withdrawable balances)
UPDATE `wallet_ledger`
SET `maturity_date` = `created_at`, `is_matured` = 1
WHERE `credit` > 0 AND (`maturity_date` IS NULL OR `is_matured` = 0);

-- Index for daily maturity cron
SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'wallet_ledger'
    AND INDEX_NAME = 'idx_wallet_maturity'
);
SET @sql := IF(@idx_exists = 0,
  'CREATE INDEX `idx_wallet_maturity` ON `wallet_ledger` (`is_matured`, `maturity_date`, `wallet_type`)',
  'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- Admin-configurable maturity rules (days per wallet type)
INSERT INTO `site_settings` (`settings_type`, `settings_name`, `settings_value`)
SELECT 'wallet_maturity_settings', 'maturity_enabled', '1'
FROM DUAL WHERE NOT EXISTS (
  SELECT 1 FROM `site_settings` WHERE `settings_type` = 'wallet_maturity_settings' AND `settings_name` = 'maturity_enabled'
);

INSERT INTO `site_settings` (`settings_type`, `settings_name`, `settings_value`)
SELECT 'wallet_maturity_settings', 'maturity_days_exchange', '0'
FROM DUAL WHERE NOT EXISTS (
  SELECT 1 FROM `site_settings` WHERE `settings_type` = 'wallet_maturity_settings' AND `settings_name` = 'maturity_days_exchange'
);

INSERT INTO `site_settings` (`settings_type`, `settings_name`, `settings_value`)
SELECT 'wallet_maturity_settings', 'maturity_days_earning', '30'
FROM DUAL WHERE NOT EXISTS (
  SELECT 1 FROM `site_settings` WHERE `settings_type` = 'wallet_maturity_settings' AND `settings_name` = 'maturity_days_earning'
);

INSERT INTO `site_settings` (`settings_type`, `settings_name`, `settings_value`)
SELECT 'wallet_maturity_settings', 'maturity_days_staking', '0'
FROM DUAL WHERE NOT EXISTS (
  SELECT 1 FROM `site_settings` WHERE `settings_type` = 'wallet_maturity_settings' AND `settings_name` = 'maturity_days_staking'
);

INSERT INTO `site_settings` (`settings_type`, `settings_name`, `settings_value`)
SELECT 'wallet_maturity_settings', 'maturity_days_bonus', '60'
FROM DUAL WHERE NOT EXISTS (
  SELECT 1 FROM `site_settings` WHERE `settings_type` = 'wallet_maturity_settings' AND `settings_name` = 'maturity_days_bonus'
);
