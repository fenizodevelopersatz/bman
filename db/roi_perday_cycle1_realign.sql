-- ============================================================================
-- Realign legacy per_day cycle-1 schedules to the same-month rule.
--
-- The first per-day shipment (commit 97e1f62) anchored EVERY cycle at
-- created_at + cycle_no months — cycle 1 always waited for the NEXT month,
-- even when the purchase happened on/before the plan's earliest credit day.
-- Hours later (commit 82b953b) cycleAnchorMonth() replaced that with the
-- intended rule: purchase day <= earliest configured day -> cycle 1 opens in
-- the PURCHASE month itself ("buy on the 7th and you get day 7's credit the
-- same day"). Records purchased in the in-between window kept schedules
-- seeded under the old rule, which the new rule then collides with: e.g.
-- bought 2026-08-07, days 7,8,9 — legacy cycle-1 rows sit in September, and
-- when they complete, cycleAnchorMonth() puts cycle 2 in September TOO,
-- crediting two full months of ROI in one calendar month.
--
-- Statement 1 pulls such a cycle 1 back into the purchase month. Guards:
--   - per_day records only, snapshot present, purchase day <= earliest
--     snapshot day (records bought after the earliest day are correct as-is:
--     both rules agree cycle 1 = next month);
--   - cycle 1 entirely unpaid and regular_payments_completed = 0 — a cycle
--     with ANY completed credit is money already moved and is never touched
--     (this also excludes the hand-backdated cron-test records);
--   - only rows sitting exactly one month after the purchase month (the old
--     rule's signature), which also makes the statement idempotent.
-- Day-of-month is re-clamped to the purchase month's real length (day 31 in
-- a 30-day month -> the 30th), matching dayInMonth(). Times are preserved.
--
-- Statement 2 re-points next_payment_date at the first day-row of the
-- currently-open cycle for every per_day record that has real rows — the
-- exact value createROIRecord()/the cron's cycle-completion path would have
-- written. This is a no-op for already-consistent records; it heals both
-- realigned records and records whose ROWS were seeded correctly but whose
-- next_payment_date still carries the old next-month value (bought after the
-- old-rule code wrote next_payment_date but before it wrote any rows).
-- next_payment_date is informational for per_day crediting (the cron gates on
-- roi_regular_payment_days.scheduled_date), but the admin "due in 7 days"
-- panel and member pages read it, so it must not lie.
--
-- Idempotent: both statements converge to zero affected rows. Purely data —
-- no schema changes.
-- ============================================================================

UPDATE roi_regular_payment_days d
JOIN roi_staking_management r ON r.id = d.roi_staking_management_id
JOIN (
    SELECT roi_staking_management_id AS rid
    FROM roi_regular_payment_days
    WHERE cycle_no = 1
    GROUP BY roi_staking_management_id
    HAVING SUM(status <> 'pending') = 0
) unpaid_c1 ON unpaid_c1.rid = d.roi_staking_management_id
SET d.scheduled_date = STR_TO_DATE(
        CONCAT(
            DATE_FORMAT(r.created_at, '%Y-%m-'),
            LPAD(LEAST(d.day_of_month, DAY(LAST_DAY(r.created_at))), 2, '0'),
            ' ', TIME(d.scheduled_date)
        ),
        '%Y-%m-%d %H:%i:%s')
WHERE d.cycle_no = 1
  AND r.credit_mode = 'per_day'
  AND r.regular_payments_completed = 0
  AND r.credit_days_snapshot IS NOT NULL
  AND r.credit_days_snapshot <> ''
  AND DAY(r.created_at) <= CAST(SUBSTRING_INDEX(r.credit_days_snapshot, ',', 1) AS UNSIGNED)
  AND PERIOD_DIFF(DATE_FORMAT(d.scheduled_date, '%Y%m'), DATE_FORMAT(r.created_at, '%Y%m')) = 1;

UPDATE roi_staking_management r
JOIN (
    SELECT roi_staking_management_id AS rid, cycle_no, MIN(scheduled_date) AS first_sched
    FROM roi_regular_payment_days
    GROUP BY roi_staking_management_id, cycle_no
) open_cycle ON open_cycle.rid = r.id
            AND open_cycle.cycle_no = r.regular_payments_completed + 1
SET r.next_payment_date = open_cycle.first_sched,
    r.updated_at = NOW()
WHERE r.credit_mode = 'per_day'
  AND r.regular_payments_completed < r.regular_payment_count
  AND r.overall_status IN ('active', 'in_progress')
  AND r.next_payment_date <> open_cycle.first_sched;
