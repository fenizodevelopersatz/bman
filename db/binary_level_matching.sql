-- ============================================================================
-- Binary Matching — level-wise distribution engine.
--
-- Replaces the carry-forward matcher's payout leg with a level-by-level model:
-- for each sponsor, level N pays on the CUMULATIVE eligible Lock Wallet volume
-- of levels 1..N per leg, capped by the ceiling of the sponsor's HIGHEST
-- eligible package (fresh cap every level), with any excess — and the whole
-- bonus when the sponsor holds no eligible stake — going to the Admin wallet.
--
-- Three changes, all additive and idempotent:
--
--  1. staking_matching_payouts becomes the level-completion record as well as
--     the audit row. One row per (user_id, level), enforced by a UNIQUE key —
--     the engine INSERTs it BEFORE crediting any wallet, so a duplicated cron
--     run collides with the key and aborts the level before money moves.
--     Pre-existing rows written by the old carry engine keep level = NULL;
--     MySQL/MariaDB allow many NULLs in a UNIQUE index, so they neither block
--     new rows nor get blocked.
--
--  2. admin_wallet gets its singleton row. It is currently EMPTY, which means
--     Bonusreduction_model's "UPDATE admin_wallet ... WHERE id = 1" matches
--     zero rows and silently credits nothing. Admin overflow must never
--     evaporate that way, so the row is seeded here (and the engine upserts,
--     never assuming it exists). ON DUPLICATE ... id = id so re-running can
--     never clobber a real balance.
--
--  3. Duplicate ACTIVE ceiling packages are deactivated. Ids 5/6/7 already
--     carry the correct Group Incentive Ceiling mapping (50k->30k, 100k->30k,
--     200k->50k); ids 45/46/47 are later duplicates whose ceiling equals the
--     stake amount, so "the ceiling for a 50,000 package" had two answers.
--     Guarded by a NOT EXISTS on user_stakes — a package someone actually
--     bought is never deactivated, even if that means the ambiguity survives
--     and must be resolved by hand.
--
-- Purely additive. No row is deleted, no balance is altered.
-- ============================================================================

DROP PROCEDURE IF EXISTS _bm_level_schema;
DELIMITER //
CREATE PROCEDURE _bm_level_schema()
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_matching_payouts'
         AND COLUMN_NAME = 'level') = 0 THEN
    ALTER TABLE `staking_matching_payouts`
      ADD COLUMN `level`              SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT '1-based binary level; NULL = legacy carry-engine row' AFTER `user_id`,
      ADD COLUMN `raw_bonus`          DECIMAL(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'matched_volume x total_percent, BEFORE the ceiling cap' AFTER `total_percent`,
      ADD COLUMN `ceiling_applied`    DECIMAL(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'the per-level ceiling in force for this payout' AFTER `staking_amount`,
      ADD COLUMN `admin_overflow`     DECIMAL(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'raw_bonus - user payout; credited to admin_wallet' AFTER `ceiling_applied`,
      ADD COLUMN `highest_package_id` INT NULL DEFAULT NULL COMMENT 'package whose ceiling was used (MAX eligible stake)' AFTER `admin_overflow`,
      ADD COLUMN `sponsor_eligible`   TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = no eligible stake, whole bonus went to admin' AFTER `highest_package_id`;
  END IF;

  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staking_matching_payouts'
         AND INDEX_NAME = 'uq_user_level') = 0 THEN
    ALTER TABLE `staking_matching_payouts`
      ADD UNIQUE KEY `uq_user_level` (`user_id`, `level`);
  END IF;
END//
DELIMITER ;
CALL _bm_level_schema();
DROP PROCEDURE IF EXISTS _bm_level_schema;

-- Admin wallet singleton — seed only, never overwrite a live balance.
INSERT INTO `admin_wallet` (`id`, `balance`) VALUES (1, 0)
  ON DUPLICATE KEY UPDATE `id` = `id`;

-- Resolve the ambiguous ceiling mapping (only for packages nobody bought).
UPDATE `staking_packages` sp
   SET sp.is_active = 0
 WHERE sp.id IN (45, 46, 47)
   AND sp.is_active = 1
   AND NOT EXISTS (SELECT 1 FROM `user_stakes` us WHERE us.package_id = sp.id);
