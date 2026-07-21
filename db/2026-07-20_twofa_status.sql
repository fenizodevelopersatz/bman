-- ============================================================================
-- Per-user "Two-Factor Authentication" login toggle.
-- 0 = OTP-at-login not required (default), 1 = TOTP required at login.
-- Idempotent: guarded so re-running does not error if the column exists.
-- ============================================================================

SET @add_twofa_status := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'twofa_status'
);
SET @sql := IF(@add_twofa_status = 0,
  'ALTER TABLE `users` ADD COLUMN `twofa_status` INT(11) NOT NULL DEFAULT 0',
  'SELECT "users.twofa_status already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
