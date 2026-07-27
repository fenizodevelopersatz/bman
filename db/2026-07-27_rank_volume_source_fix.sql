-- ============================================================================
-- BMAN — Rank Volume Source Fix (Achievement Rank only; Rank Power untouched)
-- ----------------------------------------------------------------------------
-- Companion to db/2026-07-17_rank_achievement_system.sql. The permanent
-- Achievement Rank's volume gate moves from "100% of the entire downline's
-- completed staking" (Rankcalculator_model::calculateGroupVolume) to "this
-- member's OWN lifetime Binary Matching Bonus":
--   SUM(staking_matching_payouts.earning_amount) + SUM(staking_matching_payouts.staking_amount)
--   for that user_id, no date window.
-- See application/models/staking/Rankcalculator_model.php::calculateBonusVolume().
--
-- Rank Power (application/models/staking/Rankpower_model.php) is DELIBERATELY
-- untouched — it keeps calling calculateGroupVolume() (downline purchase
-- volume, current-cycle windowed). This migration does not alter it, and does
-- not alter staking_matching_payouts's existing columns, binary_carry, or
-- staking_group_volume.
--
-- What this migration adds (additive only — nothing dropped or renamed):
--   1. user_rank_history + earning_volume, staking_volume (nullable)
--      The existing left_volume/right_volume columns are LEFT AS-IS so every
--      historical row keeps its original, correct team-volume meaning. NULL
--      on the new columns unambiguously means "promoted before this fix" —
--      distinct from a genuine 0 (member had zero matching bonus at
--      promotion time, which is a real and different fact).
--   2. staking_matching_payouts + a composite index to keep a per-user
--      lifetime-sum (or future windowed) query an index range scan.
--   3. user_ranks — NO schema change. It only ever stored a single cached
--      total (group_volume), never a left/right split, so it needs nothing
--      new — it will simply hold a different (correct) number going forward.
--
-- Idempotent: safe to re-run.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. user_rank_history — new nullable earning_volume / staking_volume columns
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _urh_add_col;
DELIMITER //
CREATE PROCEDURE _urh_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_rank_history'
        AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `user_rank_history` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

CALL _urh_add_col('earning_volume', "`earning_volume` DECIMAL(30,8) NULL DEFAULT NULL COMMENT 'lifetime binary matching bonus credited to Earning wallet, as of this promotion (NULL = recorded before the rank-volume-source fix)' AFTER `right_volume`");
CALL _urh_add_col('staking_volume', "`staking_volume` DECIMAL(30,8) NULL DEFAULT NULL COMMENT 'lifetime binary matching bonus credited to Staking wallet, as of this promotion (NULL = recorded before the rank-volume-source fix)' AFTER `earning_volume`");
DROP PROCEDURE IF EXISTS _urh_add_col;

-- ----------------------------------------------------------------------------
-- 2. staking_matching_payouts — composite index for the new per-user lookup.
--    Already has KEY idx_user (user_id) and KEY idx_run (run_ref) from
--    db/staking_matching.sql; add (user_id, created_at) so a future windowed
--    validation/reporting query stays a range scan.
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _smp_add_idx;
DELIMITER //
CREATE PROCEDURE _smp_add_idx(IN idx VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_matching_payouts'
        AND INDEX_NAME = idx) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `staking_matching_payouts` ADD ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;
CALL _smp_add_idx('idx_user_created', "INDEX `idx_user_created` (`user_id`,`created_at`)");
DROP PROCEDURE IF EXISTS _smp_add_idx;

-- ============================================================================
-- Threshold rescaling (staking_ranks.required_group_volume) is DELIBERATELY
-- NOT part of this auto-run migration — the new metric (10% conversion,
-- min(left,right) leg-balance requirement, per-user ceiling cap) is
-- structurally much smaller than the old one (100% of raw downline
-- purchases), so the existing seed thresholds need rescaling, but the right
-- numbers should be validated against real data first. Run this to see the
-- real distribution before changing anything:
--
--   SELECT user_id, SUM(earning_amount) + SUM(staking_amount) AS total_rank_volume
--   FROM staking_matching_payouts GROUP BY user_id ORDER BY total_rank_volume DESC;
--
-- Then apply final numbers through the existing admin Rank Definitions page
-- (admin/staking/ranks) — already has a required_group_volume field per rank,
-- already audited on save. See the plan doc for a proposed ÷20 interim table.
-- ============================================================================
