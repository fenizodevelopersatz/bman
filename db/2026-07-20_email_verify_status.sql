-- ============================================================================
-- Per-user "Email Verification" login toggle (mirrors the existing
-- users.twofa_status column/flow). 0 = OTP-at-login not required (default,
-- preserves current behaviour for existing users), 1 = required.
-- Idempotent: guarded so re-running does not error if the column exists.
-- ============================================================================

SET @add_email_verify_status := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verify_status'
);
SET @sql := IF(@add_email_verify_status = 0,
  'ALTER TABLE `users` ADD COLUMN `email_verify_status` INT(11) NOT NULL DEFAULT 0 AFTER `twofa_status`',
  'SELECT "users.email_verify_status already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
