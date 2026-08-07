-- ============================================================================
-- Fix: openCycleDays() (shipped in db/roi_regular_payment_days.sql, same
-- session) re-read staking_plans.credit_days LIVE every time a new monthly
-- cycle opened — not just once at purchase. An admin changing "Monthly ROI
-- credit days" mid-term would silently reshape an already-active stake's
-- remaining cycles, contradicting the "snapshot at purchase, never reprice a
-- live stake" rule this codebase already enforces for combo split %, ROI
-- rate, and is_special.
--
-- credit_days_snapshot freezes the exact CSV that was in effect at purchase
-- time, on the record itself. Every cycle for that stake's whole life reads
-- THIS column, never staking_plans.credit_days again after creation.
--
-- Backfill: every existing credit_mode='per_day' row (this feature only
-- shipped minutes before this fix — there's no drift risk yet) gets its
-- snapshot set from its plan_type's CURRENT staking_plans.credit_days, which
-- is accurate because no admin edit has happened in between.
--
-- Idempotent: adds the column only if missing. Purely additive.
-- ============================================================================

DROP PROCEDURE IF EXISTS _rsm_add_credit_days_snapshot;
DELIMITER //
CREATE PROCEDURE _rsm_add_credit_days_snapshot()
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roi_staking_management'
        AND COLUMN_NAME = 'credit_days_snapshot') = 0 THEN
    ALTER TABLE `roi_staking_management`
      ADD COLUMN `credit_days_snapshot` VARCHAR(40) NULL DEFAULT NULL AFTER `credit_mode`;

    -- roi_staking_management.plan_type and staking_plans.code were created
    -- under different default collations (utf8mb4_unicode_ci vs
    -- utf8mb4_general_ci) — an explicit COLLATE on the join is required or
    -- MySQL refuses the comparison outright ("Illegal mix of collations").
    UPDATE `roi_staking_management` rsm
      JOIN `staking_plans` sp ON sp.code COLLATE utf8mb4_unicode_ci = rsm.plan_type
      SET rsm.credit_days_snapshot = sp.credit_days
      WHERE rsm.credit_mode = 'per_day';
  END IF;
END//
DELIMITER ;
CALL _rsm_add_credit_days_snapshot();
DROP PROCEDURE IF EXISTS _rsm_add_credit_days_snapshot;
