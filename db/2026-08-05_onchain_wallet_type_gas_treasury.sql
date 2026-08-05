-- ============================================================================
-- onchain_transactions.wallet_type is ENUM('usdt','exchange','earning',
-- 'staking','bonus') — missing 'gas' and 'treasury'. Two call sites already
-- try to insert those exact values (StakingPurchasecron::_stepGas()'s
-- _recordOnchain() call, and Treasurysend_model's direct-send audit row);
-- this DB's sql_mode has no STRICT_TRANS_TABLES, so MySQL silently coerces
-- an out-of-enum value to '' instead of erroring — both call sites have been
-- writing blank wallet_type for their entire history without ever failing
-- loudly. Purely additive: widening an ENUM never affects existing rows or
-- queries against the current 5 values.
-- ============================================================================

ALTER TABLE `onchain_transactions`
  MODIFY COLUMN `wallet_type` ENUM('usdt','exchange','earning','staking','bonus','gas','treasury') NULL;

-- Backfill the rows already silently truncated to '' — reconstructable
-- exactly from tx_type, since each of these two tx_types only ever comes
-- from the one call site that meant one specific wallet_type.
UPDATE `onchain_transactions` SET `wallet_type` = 'gas' WHERE `wallet_type` = '' AND `tx_type` = 'gas_funding';
UPDATE `onchain_transactions` SET `wallet_type` = 'treasury' WHERE `wallet_type` = '' AND `tx_type` = 'treasury_direct_send';
