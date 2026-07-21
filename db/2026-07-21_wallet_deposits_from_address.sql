-- ============================================================================
-- Capture the on-chain SENDER address for a detected deposit (wallet_address
-- already stores the recipient — the user's own custodial address). Needed
-- so the onchain_transactions mirror row (used by the Wallet History table)
-- can be backfilled with from_address/to_address for token types beyond USDT
-- (USDT gets this via a separate Etherscan "enrichment" pass; other tokens
-- don't have an equivalent, so this is captured directly at detection time).
-- Idempotent: guarded so re-running does not error if the column exists.
-- ============================================================================

SET @add_from_address := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wallet_deposits' AND COLUMN_NAME = 'from_address'
);
SET @sql := IF(@add_from_address = 0,
  'ALTER TABLE `wallet_deposits` ADD COLUMN `from_address` VARCHAR(120) NULL DEFAULT NULL AFTER `wallet_address`',
  'SELECT "wallet_deposits.from_address already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
