-- ============================================================================
-- Self-service "Freeze Account" (Danger tab). Immediately restricts the
-- account (login is blocked) and the acting session is destroyed right away.
-- 0 = active (default), 1 = frozen — cleared by an admin to unfreeze.
-- Idempotent: guarded so re-running does not error if the columns exist.
-- ============================================================================

SET @add_account_frozen := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'account_frozen'
);
SET @sql := IF(@add_account_frozen = 0,
  'ALTER TABLE `users` ADD COLUMN `account_frozen` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT "users.account_frozen already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_account_frozen_at := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'account_frozen_at'
);
SET @sql := IF(@add_account_frozen_at = 0,
  'ALTER TABLE `users` ADD COLUMN `account_frozen_at` DATETIME NULL DEFAULT NULL',
  'SELECT "users.account_frozen_at already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
