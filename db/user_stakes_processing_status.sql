-- ============================================================================
-- Add 'processing' to user_stakes.status — for immediate purchase visibility.
-- A stake is created PROCESSING at purchase time and flips to ACTIVE once the
-- (optional) blockchain leg confirms. Purely additive — existing 'active' rows
-- and the 'active' default are preserved. Idempotent: safe to re-run.
-- ============================================================================

ALTER TABLE `user_stakes`
  MODIFY COLUMN `status`
  ENUM('processing','active','matured','withdrawn','cancelled')
  NOT NULL DEFAULT 'active';
