-- ============================================================================
-- BMAN Staking Module — Bonus Coin System (§7) & Binary Matching Bonus (§9)
-- Source: BMAN STAKING MASTER PROPOSAL DETAILS.pdf.
-- Single-row admin settings; per-package bonus % stays on staking_packages
-- (this row holds the global default + the reduction/transfer/matching rules).
-- Idempotent: safe to re-run. Companion to db/staking_module.sql.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `staking_bonus_settings` (
  `id` TINYINT UNSIGNED NOT NULL,

  -- §7 Staking Bonus: every stake purchase receives X% Bonus Coin
  `bonus_percent_default` DECIMAL(6,2) NOT NULL DEFAULT 25.00,

  -- §7 Bonus Coin Reduction: every N days, X% of Bonus Wallet is reduced
  `reduction_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `reduction_interval_days` INT NOT NULL DEFAULT 60,
  `reduction_percent` DECIMAL(6,2) NOT NULL DEFAULT 50.00,

  -- §7 Bonus Coin Transfer: only to direct left / right sponsored member
  `transfer_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `transfer_to_direct_left` TINYINT(1) NOT NULL DEFAULT 1,
  `transfer_to_direct_right` TINYINT(1) NOT NULL DEFAULT 1,
  `transfer_require_email_otp` TINYINT(1) NOT NULL DEFAULT 1,
  `transfer_require_transfer_password` TINYINT(1) NOT NULL DEFAULT 1,

  -- §9 Binary Matching Bonus: total %, split into Earning + Staking coin
  `matching_total_percent` DECIMAL(6,2) NOT NULL DEFAULT 10.00,
  `matching_earning_percent` DECIMAL(6,2) NOT NULL DEFAULT 8.00,
  `matching_staking_percent` DECIMAL(6,2) NOT NULL DEFAULT 2.00,

  `updated_by` INT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `staking_bonus_settings` (`id`) VALUES (1);
