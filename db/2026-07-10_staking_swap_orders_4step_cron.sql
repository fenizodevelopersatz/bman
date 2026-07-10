-- Staking swap orders: move to the 4-step cron model + drop per-order dry_run.
--
-- Model: the ONLY on-chain legs are USDT (user->admin) and BMAN (admin->user).
-- The split of BMAN into exchange/earning/staking/bonus is INTERNAL ledger only,
-- so ONE bman_cron_status replaces the 4 granular bman_*_cron_status fields.
-- dry_run is now global (token_settings.swap_dry_run), not per order.
--
-- Apply order: this migration is safe to run after the code changes are deployed
-- (StakingPurchasecron rewrite + Swapengine_model / StakingSwap_model edits).

ALTER TABLE `staking_swap_orders`
  ADD COLUMN `bman_cron_status` tinyint(4) NOT NULL DEFAULT 0
      COMMENT 'BMAN distribution cron: 0=pending,1=completed' AFTER `bonus_cron_status`,
  ADD COLUMN `bman_cron_status_message` text DEFAULT NULL
      COMMENT 'BMAN distribution cron message' AFTER `bonus_cron_status_message`,
  DROP COLUMN `dry_run`;

-- OPTIONAL follow-up cleanup (only after confirming no other code reads them) —
-- these granular columns are superseded by bman_cron_status:
-- ALTER TABLE `staking_swap_orders`
--   DROP COLUMN `bman_exchange_cron_status`,  DROP COLUMN `bman_earning_cron_status`,
--   DROP COLUMN `bman_staking_cron_status`,   DROP COLUMN `bman_bonus_cron_status`,
--   DROP COLUMN `bman_exchange_cron_status_message`, DROP COLUMN `bman_earning_cron_status_message`,
--   DROP COLUMN `bman_staking_cron_status_message`,  DROP COLUMN `bman_bonus_cron_status_message`,
--   DROP COLUMN `bman_exchange_tx_hash`, DROP COLUMN `bman_earning_tx_hash`,
--   DROP COLUMN `bman_staking_tx_hash`,  DROP COLUMN `bman_bonus_tx_hash`,
--   DROP COLUMN `coin_distribution_option_id`; -- duplicate of coin_distribution_option
