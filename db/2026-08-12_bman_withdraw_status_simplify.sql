-- 2026-08-12 — BmanWithdrawCollectCron: simplify the cron-flow status names.
-- See docs/2026-08-12_bman_withdraw_status_simplify.md for the full design.
--
-- Old (this session's earlier migration): pending -> collecting -> awaiting_approval -> completed/rejected
-- New:                                    processing -> pending -> approved/rejected
--
-- 'collecting' and 'awaiting_approval' are retired (left in the ENUM,
-- unused going forward — no data currently sits in either, so no backfill
-- needed for those two). The legacy admin-manual values (pending, approved,
-- processing, completed, rejected, failed) are untouched and keep their
-- existing meaning for any pre-existing row that used them.

-- Column default now matches create_request()'s new initial status.
ALTER TABLE bman_withdraw_requests
  MODIFY COLUMN status ENUM('pending','approved','processing','collecting','awaiting_approval','completed','rejected','failed')
    NOT NULL DEFAULT 'processing';

-- Migrate the two live rows that were sitting in 'pending' un-claimed by the
-- cron (gas_cron_status=0 AND collect_cron_status=0, i.e. never touched) —
-- claim_for_collection() now looks for 'processing', so these would
-- otherwise be orphaned. Deliberately scoped tight: only rows the cron
-- never started on, so nothing mid-flight or already-collected is touched.
UPDATE bman_withdraw_requests
   SET status = 'processing'
 WHERE status = 'pending'
   AND gas_cron_status = 0
   AND collect_cron_status = 0
   AND approved_at IS NULL;
