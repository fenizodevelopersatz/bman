-- 2026-08-12 — BmanWithdrawCollectCron: real on-chain refund on Reject.
-- See docs/2026-08-12_bman_withdraw_onchain_refund.md for the full design.
--
-- Bmanwithdraw_model::reject() previously only reversed the internal
-- bman_wallet_ledger row (lock or debit) — for an already-collected request
-- (collect_cron_status=1) that left the real BMAN sitting in the treasury
-- wallet, never actually sent back to the user's custodial wallet. This
-- column records the treasury -> user refund broadcast tx_hash, sent
-- synchronously as part of the Reject action (see
-- Bmanwithdraw_model::refund_bman_onchain()).
ALTER TABLE bman_withdraw_requests
  ADD COLUMN refund_tx_hash VARCHAR(255) DEFAULT NULL
    COMMENT 'treasury -> user on-chain BMAN refund tx, only set when rejected after collect_cron_status=1'
    AFTER refunded_at;
