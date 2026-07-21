-- ============================================================================
-- Generic audit trail for admin settings pages that don't have their own
-- dedicated audit table (unlike staking_roi_audit, coin_distribution_audit,
-- token_settings_audit, which already exist and are left as-is). Covers:
--   - Bonus & Matching Settings (module = 'bonus_settings')
--   - Withdraw Settings, both USD and Token tabs (module = 'withdraw_settings'
--     / 'token_withdraw_settings')
-- One row per changed field, so a single save that touches N fields writes N
-- rows — mirrors how the existing bespoke tables record one row per change.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `admin_settings_audit` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(60) NOT NULL,
  `field_name` VARCHAR(100) NOT NULL,
  `old_value` TEXT NULL DEFAULT NULL,
  `new_value` TEXT NULL DEFAULT NULL,
  `changed_by` INT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_module` (`module`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
