-- ============================================================================
-- User Profile fields (proposal §1) — fill the two gaps on the users table.
-- Existing columns already cover most of the profile:
--   name / first_name / last_name (Full Name) · email · contact (Mobile) ·
--   gender · dob · address (Address Line 1) · country · zipcode (Pin Code).
-- Missing: State and Address Line 2. Added as nullable — fully backward
-- compatible, no existing row or query is affected.
-- Idempotent-ish: guarded so re-running does not error if columns exist.
-- ============================================================================

SET @add_state := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'state'
);
SET @sql := IF(@add_state = 0,
  'ALTER TABLE `users` ADD COLUMN `state` VARCHAR(150) DEFAULT NULL AFTER `address`',
  'SELECT "users.state already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_line2 := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'address_line2'
);
SET @sql := IF(@add_line2 = 0,
  'ALTER TABLE `users` ADD COLUMN `address_line2` VARCHAR(255) DEFAULT NULL AFTER `address`',
  'SELECT "users.address_line2 already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
