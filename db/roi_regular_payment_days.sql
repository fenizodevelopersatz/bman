-- ============================================================================
-- Dynamic day-of-month ROI crediting for Regular/Combo plans.
--
-- Previously "Monthly ROI credit days" (staking_plans.credit_days, e.g.
-- "7,8,9") was purely decorative: RoiMonthlyDistribution_cron credited the
-- FULL monthly amount ONCE, on a rolling +1-calendar-month schedule anchored
-- to the purchase date, never reading credit_days at all. This adds the
-- missing piece: the monthly rate splits evenly across however many days the
-- admin configures (not hardcoded to any fixed count — there's already a
-- dead, hardcoded-to-exactly-3 attempt at this in payment_day_5/15/25_*
-- columns + RoiStakingManagement_model's getPendingMonthlyPayments()/
-- updatePaymentStatus()/calculateNextPayment(), never called by any cron —
-- left in place, untouched, superseded by this).
--
-- Existing/already-active regular & combo records are UNAFFECTED — this only
-- activates for records created after this ships. `roi_staking_management.
-- credit_mode` defaults to 'flat' for every existing row (and stays 'flat'
-- forever for them); only newly-created records opt into 'per_day', and only
-- when their plan has at least one configured credit day.
--
-- The configured days are SNAPSHOTTED per cycle onto roi_regular_payment_days
-- at cycle-open time (same "snapshot at creation, never let an admin edit
-- reprice something live" principle already used throughout this codebase
-- for combo split %, ROI rate, is_special, etc.) — an admin changing
-- staking_plans.credit_days mid-term never reshapes an in-flight cycle.
--
-- Idempotent: adds the table/column only if missing. Purely additive.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `roi_regular_payment_days` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roi_staking_management_id` BIGINT UNSIGNED NOT NULL,
  `cycle_no` INT UNSIGNED NOT NULL,           -- 1-based month number within the term
  `day_of_month` TINYINT UNSIGNED NOT NULL,   -- 1-31, snapshotted from staking_plans.credit_days at cycle-open time
  `scheduled_date` DATETIME NOT NULL,
  `amount` DECIMAL(30,8) NOT NULL,
  `status` ENUM('pending','completed') NOT NULL DEFAULT 'pending',
  `paid_date` DATETIME NULL,
  `tx_hash` VARCHAR(120) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rrpd_cycle_day` (`roi_staking_management_id`, `cycle_no`, `day_of_month`),
  KEY `idx_rrpd_due` (`status`, `scheduled_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS _rsm_add_credit_mode;
DELIMITER //
CREATE PROCEDURE _rsm_add_credit_mode()
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roi_staking_management'
        AND COLUMN_NAME = 'credit_mode') = 0 THEN
    ALTER TABLE `roi_staking_management`
      ADD COLUMN `credit_mode` ENUM('flat','per_day') NOT NULL DEFAULT 'flat' AFTER `plan_type`;
  END IF;
END//
DELIMITER ;
CALL _rsm_add_credit_mode();
DROP PROCEDURE IF EXISTS _rsm_add_credit_mode;
