-- ============================================================================
-- STAKING PACKAGES — allow the same stake amount as both normal AND special
-- ----------------------------------------------------------------------------
-- staking_module.sql created the table with UNIQUE KEY `uq_amount`
-- (stake_amount), which made a stake amount globally unique. That blocks the
-- intended catalogue shape, e.g.:
--
--     2,000 BMAN            (normal)
--     2,000 BMAN  SPECIAL   (special)
--
-- Uniqueness now applies WITHIN a kind instead of across the whole table:
-- one normal and one special per amount is allowed; two normals or two
-- specials on the same amount are still rejected.
--
-- The matching application-side guard lives in
-- Staking_model::savePackage() — keep the two in step.
--
-- Depends on: 2026-07-24_special_offer.sql (adds staking_packages.is_special).
-- Safe to re-run: both steps are guarded and become no-ops once applied.
-- ============================================================================

-- 1. Drop the old single-column unique key, if it is still present.
SET @sql := (
  SELECT IF(COUNT(*) > 0,
    'ALTER TABLE `staking_packages` DROP INDEX `uq_amount`',
    'SELECT 1')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'staking_packages'
    AND INDEX_NAME   = 'uq_amount'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Add the composite unique key, if it is not already there.
SET @sql := (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `staking_packages` ADD UNIQUE KEY `uq_amount_special` (`stake_amount`, `is_special`)',
    'SELECT 1')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'staking_packages'
    AND INDEX_NAME   = 'uq_amount_special'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verify:
--   SHOW INDEX FROM `staking_packages` WHERE Key_name LIKE 'uq_amount%';
-- Expect one row set for `uq_amount_special` spanning (stake_amount, is_special)
-- and no `uq_amount`.
