-- ============================================================================
-- Staking Lifecycle Consolidation — Phase B (additive schema only, zero
-- behavior change). Confirmed against live schema before writing this file:
-- roi_staking_management.staking_swap_orders_id is NOT NULL with a real FK
-- (fk_roi_staking_swap_orders -> staking_swap_orders.id, ON DELETE CASCADE),
-- and is populated ONLY by the USDT-purchase path (Lendingcontroller::
-- swap_purchase() -> RoiStakingManagement_model::createROIRecord()). The other
-- two purchase paths (Staking_model::purchaseStake(), restakeFromWallets())
-- have no way to link into this table today, so their ROI schedules go into a
-- separate, effectively-dead table (staking_roi_payouts) instead. This
-- migration makes roi_staking_management linkable from ANY purchase path via
-- user_stakes_id, and adds the explicit backward-compat switch the maturity
-- cron needs to stop double-crediting principal for existing stakes.
-- ============================================================================

ALTER TABLE `roi_staking_management`
  MODIFY COLUMN `staking_swap_orders_id` BIGINT UNSIGNED NULL,
  ADD COLUMN `user_stakes_id` BIGINT UNSIGNED NULL AFTER `staking_swap_orders_id`,
  ADD KEY `idx_user_stakes_id` (`user_stakes_id`),
  ADD CONSTRAINT `fk_roi_user_stakes` FOREIGN KEY (`user_stakes_id`) REFERENCES `user_stakes` (`id`);

-- Explicit, self-documenting backward-compat switch on the stake itself (not
-- inferred from a NULL FK) — new stakes lock their principal to their own
-- maturity date and release via the shared service; existing stakes already
-- had their principal credited in full, untagged, at purchase time.
ALTER TABLE `user_stakes`
  ADD COLUMN `principal_release_mode`
    ENUM('credited_at_purchase_legacy','credited_at_maturity') NOT NULL DEFAULT 'credited_at_maturity'
    AFTER `status`;

-- Backfill: every existing user_stakes row was created via the USDT-purchase
-- path (swap_order_id set) and had its principal credited immediately.
UPDATE `user_stakes` SET `principal_release_mode` = 'credited_at_purchase_legacy' WHERE `swap_order_id` IS NOT NULL;
