-- ============================================================================
-- Gas fee tracking: policy table + real-payment audit ledger.
--
-- Problem: the existing /admin/finance/gas-fee-transactions and
-- /admin/all-transaction pages already read onchain_transactions.gas_used /
-- gas_price / gas_fee_total, but nothing on the staking-purchase write path
-- ever populates real, post-confirmation values there (StakingPurchasecron
-- inserts those rows as status='confirmed' at insert time, which structurally
-- skips the one mechanism, Chainsync_model::verifyTx(), that ever backfills
-- real chain data — see StakingPurchasecron.php/Chainsync_model.php changes
-- in this same feature). These two new tables are the policy + audit layer on
-- top of that fix:
--   - gas_fee_settings: gas POLICY per operation profile (never a hard-coded
--     literal in application code — GasFeeSettings_model::resolve() reads
--     this first, falling back to the existing token_settings.gas_limit/
--     gas_price only if no active row matches).
--   - gas_fee_ledger: one row per REAL gas payment (any StakingPurchasecron
--     broadcast leg), 'pending' at broadcast time, backfilled to 'confirmed'
--     with real gas_used/gas_price once Chainsync_model verifies the tx.
--     This is the complete "how much have we ever spent on gas" audit trail
--     that does not exist anywhere else (roi_gas_fees/roi_gas_budget are a
--     narrower, already-abandoned ROI-only attempt — left untouched).
--
-- No FOREIGN KEY constraints, matching this project's existing convention for
-- cross-table linkage columns (onchain_transactions.wallet_ledger_id /
-- linked_deposit_id / linked_withdrawal_id / parent_tx_id are all plain
-- indexed columns, not FKs) — soft reference + index only.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `gas_fee_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  -- Gas PROFILE, not a business step: 'gas_funding' = plain native BNB send
  -- (StakingPurchasecron's treasury→user gas-funding leg, Web3bman::sendBnb());
  -- 'token_transfer' = BEP-20 token send (the usdt/bonus/bman legs, all
  -- Web3bman::sendToken()). Two profiles because native transfers always cost
  -- a fixed 21000 gas while token transfers are configurable/heavier.
  `tx_type` VARCHAR(40) NOT NULL,
  `gas_limit` BIGINT NOT NULL,
  -- NULL = no fixed policy price; resolve() falls back to a live eth_gasPrice
  -- RPC read (same fallback Web3bman::buildSignSend() already uses).
  `gas_price_gwei` DECIMAL(20,9) NULL DEFAULT NULL,
  `buffer_multiplier` DECIMAL(6,3) NOT NULL DEFAULT 1.500,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by` INT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tx_type` (`tx_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed from whatever token_settings currently holds, so behavior is byte-for-
-- byte unchanged the moment this ships — nothing shifts until an admin edits
-- a policy on the new Gas Fee Settings page.
INSERT INTO `gas_fee_settings` (`tx_type`, `gas_limit`, `gas_price_gwei`, `buffer_multiplier`, `is_active`)
SELECT 'gas_funding', 21000, NULLIF(CAST(`gas_price` AS DECIMAL(20,9)), 0), 1.500, 1
FROM `token_settings` WHERE `status` = 1
ON DUPLICATE KEY UPDATE `tx_type` = `tx_type`;

INSERT INTO `gas_fee_settings` (`tx_type`, `gas_limit`, `gas_price_gwei`, `buffer_multiplier`, `is_active`)
SELECT 'token_transfer', COALESCE(NULLIF(`gas_limit`, 0), 210000), NULLIF(CAST(`gas_price` AS DECIMAL(20,9)), 0), 1.500, 1
FROM `token_settings` WHERE `status` = 1
ON DUPLICATE KEY UPDATE `tx_type` = `tx_type`;

-- Fallback seed in case no token_settings row has status=1 yet (fresh installs) —
-- keeps GasFeeSettings_model::resolve() always finding a row once this file runs.
INSERT INTO `gas_fee_settings` (`tx_type`, `gas_limit`, `gas_price_gwei`, `buffer_multiplier`, `is_active`)
SELECT * FROM (SELECT 'gas_funding' AS t, 21000 AS l, 5.0 AS p, 1.500 AS b, 1 AS a) x
WHERE NOT EXISTS (SELECT 1 FROM `gas_fee_settings` WHERE `tx_type` = 'gas_funding');

INSERT INTO `gas_fee_settings` (`tx_type`, `gas_limit`, `gas_price_gwei`, `buffer_multiplier`, `is_active`)
SELECT * FROM (SELECT 'token_transfer' AS t, 210000 AS l, 5.0 AS p, 1.500 AS b, 1 AS a) x
WHERE NOT EXISTS (SELECT 1 FROM `gas_fee_settings` WHERE `tx_type` = 'token_transfer');


CREATE TABLE IF NOT EXISTS `gas_fee_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  -- Business step, matching staking_swap_orders' {step}_tx_hash columns 1:1
  -- ('gas'|'usdt'|'bonus'|'bman') — NOT the same vocabulary as
  -- gas_fee_settings.tx_type (that's the coarser gas profile).
  `tx_type` VARCHAR(40) NOT NULL,
  `reference_type` VARCHAR(40) NULL DEFAULT NULL,
  `reference_id` VARCHAR(64) NULL DEFAULT NULL,
  `user_id` INT NULL DEFAULT NULL,
  `from_address` VARCHAR(120) NULL DEFAULT NULL,
  `to_address` VARCHAR(120) NULL DEFAULT NULL,
  `tx_hash` VARCHAR(120) NULL DEFAULT NULL,
  `onchain_transaction_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `status` ENUM('pending','confirmed','failed') NOT NULL DEFAULT 'pending',
  `gas_limit_used` BIGINT NULL DEFAULT NULL,
  `gas_price_wei` DECIMAL(38,0) NULL DEFAULT NULL,
  `gas_used` BIGINT NULL DEFAULT NULL,
  `native_fee_total` DECIMAL(38,18) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tx_hash` (`tx_hash`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_onchain_tx` (`onchain_transaction_id`),
  KEY `idx_status` (`status`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
