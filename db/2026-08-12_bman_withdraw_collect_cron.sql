-- 2026-08-12 — BmanWithdrawCollectCron: automated on-chain BMAN collection
-- for withdrawal requests, ahead of admin approval. See
-- docs/2026-08-12_bman_withdraw_collect_cron.md for the full design.
--
-- Ships DISABLED + dry-run by default (both new token_settings flags below)
-- — matches this codebase's own convention for exactly this risk category
-- (see WalletTransferSettlementCron, StakingPurchasecron's swap_enabled/
-- swap_dry_run). Nothing here can broadcast a real transaction until an
-- admin explicitly flips bman_withdraw_collect_enabled = 1 AND
-- bman_withdraw_collect_dry_run = 0 on the active token_settings row.

-- 'collecting' is deliberately a NEW, separate value from the pre-existing
-- 'processing' — 'processing' stays exclusively the legacy admin-manual
-- meaning (reachable only via the old mark_processing() click, approved ->
-- processing). Reusing it for the cron's own in-flight state would make the
-- cron's claim query (status IN ('pending','collecting')) also sweep up any
-- legacy 'processing' row an admin already started handling by hand under
-- the old process — a real collision, not a hypothetical one (confirmed
-- against a live 'processing' row from before this migration).
ALTER TABLE bman_withdraw_requests
  MODIFY COLUMN status ENUM('pending','approved','processing','collecting','awaiting_approval','completed','rejected','failed')
    NOT NULL DEFAULT 'pending',
  ADD COLUMN gas_cron_status TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'treasury -> user custodial wallet BNB funding leg, confirmed once = 1' AFTER status,
  ADD COLUMN gas_cron_status_message VARCHAR(500) DEFAULT NULL AFTER gas_cron_status,
  ADD COLUMN gas_tx_hash VARCHAR(255) DEFAULT NULL AFTER gas_cron_status_message,
  ADD COLUMN collect_cron_status TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'user custodial wallet -> treasury_wallet BMAN collection leg, confirmed once = 1' AFTER gas_tx_hash,
  ADD COLUMN collect_cron_status_message VARCHAR(500) DEFAULT NULL AFTER collect_cron_status,
  ADD COLUMN collect_tx_hash VARCHAR(255) DEFAULT NULL AFTER collect_cron_status_message,
  ADD COLUMN collected_at DATETIME DEFAULT NULL
    COMMENT 'when the BMAN collection leg confirmed on-chain' AFTER collect_tx_hash,
  ADD COLUMN refunded_at DATETIME DEFAULT NULL
    COMMENT 'when a rejected, already-collected request was credited back to the user'
    AFTER completed_at,
  ADD KEY idx_gas_collect_status (gas_cron_status, collect_cron_status, status);

ALTER TABLE token_settings
  ADD COLUMN bman_withdraw_collect_enabled TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'master switch for BmanWithdrawCollectCron — 0 = cron no-ops entirely' AFTER swap_auto_gas,
  ADD COLUMN bman_withdraw_collect_dry_run TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'simulate the BMAN collection leg (DRYRUN-* hashes, no real broadcast) while 1'
    AFTER bman_withdraw_collect_enabled;
