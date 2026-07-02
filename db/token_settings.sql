-- ============================================================================
-- BMAN — Token Settings Master (blockchain configuration, single source of
-- truth for BMAN / USDT tokens, exchange rate, wallets, contracts, network).
-- Multiple rows allowed (e.g. Mainnet + Testnet) but only ONE may be active.
-- The active row's exchange_rate is mirrored into the legacy
-- token_config.currency_value so every existing purchase flow keeps working
-- (purchases already snapshot the rate as user_investment.csq_price — old
-- transactions keep their original rate, new ones use the latest active).
-- Idempotent: safe to re-run. No existing table is modified.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `token_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Section 1: Network
  `network` ENUM('mainnet','testnet') NOT NULL DEFAULT 'mainnet',
  `blockchain` VARCHAR(80) NOT NULL DEFAULT 'Binance Smart Chain (BEP20)',
  `chain_id` INT NOT NULL DEFAULT 56,
  `rpc_url` VARCHAR(255) NOT NULL,
  `explorer_url` VARCHAR(255) NOT NULL,

  -- Section 2: BMAN token
  `bman_name` VARCHAR(80) NOT NULL DEFAULT 'BMAN Token',
  `bman_symbol` VARCHAR(20) NOT NULL DEFAULT 'BMAN',
  `bman_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 18,
  `bman_contract` VARCHAR(100) DEFAULT NULL,
  `bman_logo` VARCHAR(255) DEFAULT NULL,
  `bman_min_transfer` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `bman_max_transfer` DECIMAL(20,4) NOT NULL DEFAULT 0,   -- 0 = unlimited
  `bman_enabled` TINYINT(1) NOT NULL DEFAULT 1,

  -- Section 3: USDT token
  `usdt_name` VARCHAR(80) NOT NULL DEFAULT 'Tether USD',
  `usdt_symbol` VARCHAR(20) NOT NULL DEFAULT 'USDT',
  `usdt_decimals` TINYINT UNSIGNED NOT NULL DEFAULT 18,
  `usdt_contract` VARCHAR(100) DEFAULT NULL,
  `minimum_deposit` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `minimum_withdrawal` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `maximum_withdrawal` DECIMAL(20,4) NOT NULL DEFAULT 0,  -- 0 = unlimited
  `usdt_enabled` TINYINT(1) NOT NULL DEFAULT 1,

  -- Section 4: Exchange rate (only the active row's rate is live)
  `exchange_rate` DECIMAL(24,8) NOT NULL DEFAULT 1,
  `exchange_type` ENUM('usdt_to_bman','bman_to_usdt') NOT NULL DEFAULT 'usdt_to_bman',
  `rate_effective_from` DATE DEFAULT NULL,

  -- Section 5: Wallet addresses
  `treasury_wallet` VARCHAR(100) DEFAULT NULL,
  `deposit_wallet` VARCHAR(100) DEFAULT NULL,
  `gas_wallet` VARCHAR(100) DEFAULT NULL,
  `bonus_wallet` VARCHAR(100) DEFAULT NULL,
  `reserve_wallet` VARCHAR(100) DEFAULT NULL,
  `cold_wallet` VARCHAR(100) DEFAULT NULL,

  -- Section 6: Smart contracts (bman_contract above is the token itself)
  `staking_contract` VARCHAR(100) DEFAULT NULL,
  `bonus_contract` VARCHAR(100) DEFAULT NULL,
  `referral_contract` VARCHAR(100) DEFAULT NULL,
  `roi_contract` VARCHAR(100) DEFAULT NULL,

  -- Section 7: Blockchain parameters
  `minimum_confirmations` INT NOT NULL DEFAULT 15,
  `gas_limit` BIGINT NOT NULL DEFAULT 210000,
  `gas_price` VARCHAR(40) NOT NULL DEFAULT '5',           -- gwei
  `transaction_timeout` INT NOT NULL DEFAULT 300,         -- seconds
  `retry_count` INT NOT NULL DEFAULT 3,

  `status` TINYINT(1) NOT NULL DEFAULT 0,                 -- 1 = the single active config
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_network_chain` (`network`,`chain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit: every change logs old + new JSON, admin, date and IP (spec).
CREATE TABLE IF NOT EXISTS `token_settings_audit` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(30) NOT NULL,          -- create|edit|enable|disable|activate|rate_changed
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `changed_by` INT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_setting` (`setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Seed: one active BSC mainnet config. The exchange rate is taken from the
-- legacy token_config.currency_value so the live rate is unchanged.
-- ----------------------------------------------------------------------------
INSERT INTO `token_settings`
(`network`,`blockchain`,`chain_id`,`rpc_url`,`explorer_url`,
 `bman_name`,`bman_symbol`,`bman_decimals`,
 `usdt_name`,`usdt_symbol`,`usdt_decimals`,`usdt_contract`,
 `exchange_rate`,`exchange_type`,`rate_effective_from`,`status`)
SELECT 'mainnet','Binance Smart Chain (BEP20)',56,
       'https://bsc-dataseed.binance.org','https://bscscan.com',
       'BMAN Token','BMAN',18,
       'Tether USD','USDT',18,'0x55d398326f99059fF775485246999027B3197955',
       COALESCE((SELECT CAST(currency_value AS DECIMAL(24,8)) FROM token_config WHERE currency_status = 1 LIMIT 1), 1),
       'usdt_to_bman', CURDATE(), 1
WHERE NOT EXISTS (SELECT 1 FROM `token_settings`);
