-- ============================================================================
-- Per-user TOTP secret for Google Authenticator (2FA provisioning).
-- Stores the base32-encoded secret key that the user's authenticator app uses.
-- Idempotent: guarded so re-running does not error if the column exists.
-- ============================================================================

SET @add_twofa_secret := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'twofa_secret'
);
SET @sql := IF(@add_twofa_secret = 0,
  'ALTER TABLE `users` ADD COLUMN `twofa_secret` VARCHAR(255) DEFAULT NULL AFTER `email_verify_status`',
  'SELECT "users.twofa_secret already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
