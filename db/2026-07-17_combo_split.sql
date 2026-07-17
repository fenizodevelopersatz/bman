-- ============================================================================
-- Combo plan — record the 50/50 principal split
-- ----------------------------------------------------------------------------
-- THE BUG
-- `staking_plans.combo_fixed_pct` / `combo_regular_pct` (seeded 50/50) were
-- validated to total 100% and shown on the admin page, then read by NOTHING.
-- Every layer applied BOTH rates to the FULL principal:
--
--   100,000 BMAN, 5y, 400% fixed + 3%/mo
--     monthly  100,000 × 3%   × 60 = 180,000   ← should be 50,000 × 3% × 60 = 90,000
--     fixed    100,000 × 400%      = 400,000   ← should be 50,000 × 400%   = 200,000
--     principal returned           = 100,000   ← should be 50,000 (regular half only)
--     TOTAL OUT                    = 680,000   ← should be 340,000
--
--   The platform promised exactly 2× on every combo stake.
--
-- THE MODEL (per spec)
--   Regular half: principal × regular_pct%, earns monthly%, principal RETURNED.
--   Fixed   half: principal × fixed_pct%,   pays principal × fixed%  as a GROSS
--                 multiple — 400% means "4× your money back, principal included",
--                 so the fixed half's principal is NOT returned separately.
--
--   100,000 → 90,000 (monthly) + 200,000 (fixed gross) + 50,000 (regular
--             principal back) = 340,000.
--
-- roi_staking_management had nowhere to record any of this — hence these columns.
--
-- Idempotent · additive only · drops nothing.
-- ============================================================================

DROP PROCEDURE IF EXISTS _rsm_add_col;
DELIMITER //
CREATE PROCEDURE _rsm_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roi_staking_management'
        AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `roi_staking_management` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

CALL _rsm_add_col('fixed_principal',
  "`fixed_principal` DECIMAL(20,8) NOT NULL DEFAULT 0 COMMENT 'principal earning the fixed rate (combo: principal x combo_fixed_pct)'");
CALL _rsm_add_col('regular_principal',
  "`regular_principal` DECIMAL(20,8) NOT NULL DEFAULT 0 COMMENT 'principal earning the monthly rate (combo: principal x combo_regular_pct)'");
CALL _rsm_add_col('principal_return_amount',
  "`principal_return_amount` DECIMAL(20,8) NOT NULL DEFAULT 0 COMMENT 'principal returned to the staking wallet at maturity. Combo: the REGULAR half only — the fixed half is absorbed into its gross payout.'");
CALL _rsm_add_col('combo_fixed_pct',
  "`combo_fixed_pct` DECIMAL(6,2) NULL DEFAULT NULL COMMENT 'split snapshotted at purchase, so later admin edits never re-price a live stake'");
CALL _rsm_add_col('combo_regular_pct',
  "`combo_regular_pct` DECIMAL(6,2) NULL DEFAULT NULL");

DROP PROCEDURE IF EXISTS _rsm_add_col;

-- ----------------------------------------------------------------------------
-- Backfill EXISTING rows.
--
-- Only rows that have paid nothing yet (total_paid_amount = 0) are re-priced —
-- a stake that has already paid out is left exactly as it is, because rewriting
-- its figures would not claw the money back and would only make the ledger
-- disagree with the record. Those need a manual decision.
-- ----------------------------------------------------------------------------

-- fixed / regular are unaffected by the split: the whole principal earns one
-- rate and is returned as before.
UPDATE `roi_staking_management`
   SET `fixed_principal`         = `principal_amount`,
       `regular_principal`       = 0,
       `principal_return_amount` = `principal_amount`
 WHERE `plan_type` = 'fixed' AND `principal_return_amount` = 0;

UPDATE `roi_staking_management`
   SET `fixed_principal`         = 0,
       `regular_principal`       = `principal_amount`,
       `principal_return_amount` = `principal_amount`
 WHERE `plan_type` = 'regular' AND `principal_return_amount` = 0;

-- COMBO — the actual repair. Re-price unpaid rows off the configured split.
-- Recomputes the per-month credit and the fixed lump from each HALF, and sets
-- the principal return to the regular half only.
UPDATE `roi_staking_management` r
  JOIN `staking_plans` p ON p.`code` = 'combo'
   SET r.`combo_fixed_pct`   = COALESCE(p.`combo_fixed_pct`, 50),
       r.`combo_regular_pct` = COALESCE(p.`combo_regular_pct`, 50),
       r.`fixed_principal`   = r.`principal_amount` * COALESCE(p.`combo_fixed_pct`, 50) / 100,
       r.`regular_principal` = r.`principal_amount` * COALESCE(p.`combo_regular_pct`, 50) / 100,
       -- per-month credit on the REGULAR half only
       r.`regular_payment_amount` = (r.`principal_amount` * COALESCE(p.`combo_regular_pct`, 50) / 100)
                                    * (r.`roi_rate_percent` / 100),
       -- fixed lump on the FIXED half only. Scale the stored lump rather than
       -- re-resolving the rate: fixed_payment_amount currently holds
       -- principal x fixed%, so x combo_fixed_pct% gives fixed_half x fixed%.
       r.`fixed_payment_amount` = r.`fixed_payment_amount` * COALESCE(p.`combo_fixed_pct`, 50) / 100,
       -- fixed half's principal is absorbed into its gross payout
       r.`principal_return_amount` = r.`principal_amount` * COALESCE(p.`combo_regular_pct`, 50) / 100
 WHERE r.`plan_type` = 'combo'
   AND r.`total_paid_amount` = 0;

-- Re-derive the combo totals from the corrected components.
UPDATE `roi_staking_management`
   SET `total_roi_amount` = (`regular_payment_amount` * `regular_payment_count`) + `fixed_payment_amount`,
       `remaining_to_pay` = (`regular_payment_amount` * `regular_payment_count`) + `fixed_payment_amount`
 WHERE `plan_type` = 'combo' AND `total_paid_amount` = 0;

-- ----------------------------------------------------------------------------
-- Anything left over: combo rows that have ALREADY paid. These keep their old
-- 2x figures and must be settled by hand — listing them here so they are not
-- silently forgotten.
--   SELECT id, user_id, ref, principal_amount, total_roi_amount, total_paid_amount
--     FROM roi_staking_management
--    WHERE plan_type = 'combo' AND total_paid_amount > 0;
-- ----------------------------------------------------------------------------
