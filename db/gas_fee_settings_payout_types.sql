-- ============================================================================
-- Gas fee policy rows for the remaining on-chain payout types.
--
-- gas_fee_settings already exists with the right shape (tx_type, gas_limit,
-- gas_price_gwei, buffer_multiplier, is_active) and a UNIQUE key on tx_type.
-- Only two profiles were ever seeded — 'gas_funding' and 'token_transfer' —
-- so every other payout path fell through resolve()'s token_settings fallback,
-- and the binary matching path bypassed the resolver entirely with its own
-- hardcoded 210000 / 5 gwei / 1.5.
--
-- These four rows make each payout type independently configurable, which is
-- what the admin gas page always intended. Values are seeded from the existing
-- 'token_transfer' profile — the policy that was effectively in force for BEP-20
-- sends — so behaviour is unchanged on day one and the admin can diverge them
-- afterwards. Nothing here invents a new number.
--
-- INSERT IGNORE on the UNIQUE tx_type key: re-running changes nothing, and an
-- already-tuned row is never overwritten.
-- ============================================================================

INSERT IGNORE INTO `gas_fee_settings` (tx_type, gas_limit, gas_price_gwei, buffer_multiplier, is_active)
SELECT 'binary_matching', gas_limit, gas_price_gwei, buffer_multiplier, 1
  FROM `gas_fee_settings` WHERE tx_type = 'token_transfer';

INSERT IGNORE INTO `gas_fee_settings` (tx_type, gas_limit, gas_price_gwei, buffer_multiplier, is_active)
SELECT 'roi_distribution', gas_limit, gas_price_gwei, buffer_multiplier, 1
  FROM `gas_fee_settings` WHERE tx_type = 'token_transfer';

INSERT IGNORE INTO `gas_fee_settings` (tx_type, gas_limit, gas_price_gwei, buffer_multiplier, is_active)
SELECT 'manual_payout', gas_limit, gas_price_gwei, buffer_multiplier, 1
  FROM `gas_fee_settings` WHERE tx_type = 'token_transfer';

INSERT IGNORE INTO `gas_fee_settings` (tx_type, gas_limit, gas_price_gwei, buffer_multiplier, is_active)
SELECT 'staking_swap', gas_limit, gas_price_gwei, buffer_multiplier, 1
  FROM `gas_fee_settings` WHERE tx_type = 'token_transfer';
