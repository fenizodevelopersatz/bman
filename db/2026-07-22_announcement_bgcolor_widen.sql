-- ============================================================================
-- bg_color was VARCHAR(20) (enough for a hex color like #6E56CF) but the new
-- gradient presets write full CSS gradient strings (e.g.
-- "linear-gradient(135deg,#6C4CF1,#4E2CF0)", 40+ chars) — widen to fit.
-- ============================================================================

ALTER TABLE `announcement`
  MODIFY COLUMN `bg_color` VARCHAR(120) NULL DEFAULT NULL;
