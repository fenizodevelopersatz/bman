-- ============================================================================
-- Explicit execution_mode / gas_required on user_stakes.
--
-- Before this, "does this stake need the blockchain?" was only inferable by
-- checking whether swap_order_id was NULL — correct, but implicit, and every
-- reader had to already know that convention. This makes it a first-class,
-- queryable fact set once at insert time by the single authority for the
-- decision (application/models/staking/GasExecution_model.php):
--   execution_mode='onchain'  -> distribution option 1 (100% Exchange): a real
--                                 USDT->BMAN purchase, settled asynchronously
--                                 by StakingPurchasecron (gas->usdt->bonus->bman).
--   execution_mode='internal' -> distribution options 2-7: BMAN the user
--                                 already holds, re-staked synchronously in
--                                 one DB transaction, no chain, no gas, no cron.
--
-- gas_required mirrors execution_mode (1 for onchain, 0 for internal) as a
-- plain boolean so a query/report doesn't need to know the enum value to ask
-- "did this cost gas". gas_fee (already an existing column) is explicitly
-- written as 0 for internal stakes instead of left NULL, so NULL still means
-- "onchain but not yet known" and never gets confused with "genuinely free".
--
-- Idempotent: adds columns only if missing. Purely additive.
-- ============================================================================

DROP PROCEDURE IF EXISTS _us_add_execmode;
DELIMITER //
CREATE PROCEDURE _us_add_execmode()
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_stakes'
        AND COLUMN_NAME = 'execution_mode') = 0 THEN
    ALTER TABLE `user_stakes`
      ADD COLUMN `execution_mode` ENUM('onchain','internal') NOT NULL DEFAULT 'onchain' AFTER `chain_status`,
      ADD COLUMN `gas_required`   TINYINT(1) NOT NULL DEFAULT 1 AFTER `execution_mode`,
      ADD KEY `idx_us_execution_mode` (`execution_mode`);

    -- Backfill existing rows from the convention this column replaces.
    UPDATE `user_stakes`
       SET `execution_mode` = IF(`swap_order_id` IS NULL AND `distribution_option_id` BETWEEN 2 AND 7, 'internal', 'onchain'),
           `gas_required`   = IF(`swap_order_id` IS NULL AND `distribution_option_id` BETWEEN 2 AND 7, 0, 1);
    UPDATE `user_stakes`
       SET `gas_fee` = 0
     WHERE `execution_mode` = 'internal' AND `gas_fee` IS NULL;
  END IF;
END//
DELIMITER ;
CALL _us_add_execmode();
DROP PROCEDURE IF EXISTS _us_add_execmode;
