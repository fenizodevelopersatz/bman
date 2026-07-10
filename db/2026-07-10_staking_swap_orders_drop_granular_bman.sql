-- staking_swap_orders: drop the 12 granular per-wallet BMAN columns.
--
-- Superseded by the 4-step cron model: the BMAN principal is ONE on-chain transfer
-- (bman_tx_hash + bman_cron_status) and the split into exchange/earning/staking/bonus
-- is INTERNAL only — recorded exclusively in user_wallets (balances) + wallet_ledger
-- (per-wallet credited rows: wallet_type, credit, tx_hash, reference_id = order ref).
--
-- Usage audit (2026-07-10) before removal — confirmed NOT used by:
--   controllers/models/crons/APIs/admin reports. The only two consumers,
--   Lendingcontroller::getRecentStakingActivityForView() and swap_order_details()
--   (+ the lending_managment.php modal), were refactored to source distribution
--   from wallet_ledger. Apply this AFTER that code is deployed.

ALTER TABLE `staking_swap_orders`
  DROP COLUMN `bman_exchange_tx_hash`,
  DROP COLUMN `bman_earning_tx_hash`,
  DROP COLUMN `bman_staking_tx_hash`,
  DROP COLUMN `bman_bonus_tx_hash`,
  DROP COLUMN `bman_exchange_cron_status`,
  DROP COLUMN `bman_earning_cron_status`,
  DROP COLUMN `bman_staking_cron_status`,
  DROP COLUMN `bman_bonus_cron_status`,
  DROP COLUMN `bman_exchange_cron_status_message`,
  DROP COLUMN `bman_earning_cron_status_message`,
  DROP COLUMN `bman_staking_cron_status_message`,
  DROP COLUMN `bman_bonus_cron_status_message`;
