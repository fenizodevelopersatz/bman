-- ============================================================================
-- BMAN — Coin Distribution Master (proposal §3A)
-- Admin-managed wallet-allocation options for BMAN purchases. Every purchase
-- snapshots the selected option (id + percentages) into
-- coin_distribution_histories so later admin edits never affect old
-- purchases (proposal: "distribution option is locked after transaction
-- confirmation", "allocation history is permanently stored").
-- Idempotent: safe to re-run. No existing table is modified.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `coin_distribution_options` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `option_name` VARCHAR(80) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `exchange_percentage` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `earning_percentage` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `staking_percentage` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `bonus_percentage` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,        -- only one row may be 1
  `status` TINYINT(1) NOT NULL DEFAULT 1,            -- 1 active / 0 disabled
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_option_name` (`option_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `coin_distribution_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `purchase_id` BIGINT UNSIGNED DEFAULT NULL,        -- id in the purchase table (user_stakes / investment)
  `option_id` INT UNSIGNED NOT NULL,
  `exchange_percentage` DECIMAL(6,2) NOT NULL,       -- percentage snapshot (locked)
  `earning_percentage` DECIMAL(6,2) NOT NULL,
  `staking_percentage` DECIMAL(6,2) NOT NULL,
  `bonus_percentage` DECIMAL(6,2) NOT NULL,
  `exchange_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `earning_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `staking_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `bonus_amount` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(20,4) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_purchase` (`purchase_id`),
  KEY `idx_option` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit trail for option changes (create / edit / enable / disable / default
-- / percentage change) — mirrors the staking_roi_audit pattern.
CREATE TABLE IF NOT EXISTS `coin_distribution_audit` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `option_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(30) NOT NULL,                     -- create|edit|enable|disable|default_changed|percentage_changed|delete
  `old_value` TEXT DEFAULT NULL,                     -- JSON snapshot before
  `new_value` TEXT DEFAULT NULL,                     -- JSON snapshot after
  `changed_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_option` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Extend the existing wallet ledger so purchases can credit all four §3A
-- wallets. Adding enum values is backward compatible: existing rows and
-- queries ('earn' / 'bonus' / 'withdraw') are untouched.
-- ----------------------------------------------------------------------------
ALTER TABLE `wallet_transactions`
  MODIFY `tx_type` ENUM('earn','bonus','withdraw','exchange','earning','staking') NOT NULL DEFAULT 'earn';

-- ----------------------------------------------------------------------------
-- Seed: the 7 options from proposal §3A (each totals 100). Option 1 default.
-- ----------------------------------------------------------------------------
INSERT INTO `coin_distribution_options`
(`option_name`,`description`,`exchange_percentage`,`earning_percentage`,`staking_percentage`,`bonus_percentage`,`is_default`,`status`) VALUES
('Option 1', '100% Exchange Coin Balance',                                              100, 0,  0,  0,  1, 1),
('Option 2', '90% Exchange + 10% Bonus',                                                90,  0,  0,  10, 0, 1),
('Option 3', '80% Exchange + 10% Earning + 10% Bonus',                                  80,  10, 0,  10, 0, 1),
('Option 4', '80% Exchange + 10% Earning + 10% Staking',                                80,  10, 10, 0,  0, 1),
('Option 5', '90% Exchange + 10% Earning',                                              90,  10, 0,  0,  0, 1),
('Option 6', '90% Exchange + 10% Staking',                                              90,  0,  10, 0,  0, 1),
('Option 7', '70% Exchange + 10% Earning + 10% Staking + 10% Bonus',                    70,  10, 10, 10, 0, 1)
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);
