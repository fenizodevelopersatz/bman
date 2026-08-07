-- ============================================================================
-- Adds "Option 2: 100% Exchange (Internal re-stake)" — a genuinely new
-- distribution option that did not exist before. Every existing option was
-- either the on-chain 100% Exchange purchase (id 1) or an internal re-stake
-- that always drew SOME percentage from Earning/Staking/Bonus (ids 2-7) —
-- there was no way to re-stake 100% of an existing Exchange balance
-- internally (no USDT, no blockchain). This adds exactly that.
--
-- Existing rows' PRIMARY KEY ids are never touched — user_stakes.
-- distribution_option_id and coin_distribution_histories.option_id are real
-- foreign references into purchase history, and renumbering an id would
-- silently reinterpret what an already-completed purchase meant. Only two
-- things change on the existing 7 rows:
--   - option_name display label (e.g. id=2 "Option 2" -> "Option 3"), so the
--     UI numbering reads 1..8 with the new option seated at position 2.
--   - execution_mode is backfilled explicitly (was previously only implied
--     by GasExecution_model's hardcoded id ranges).
--
-- New columns:
--   sort_order      -- UI/query ordering independent of id, since the new
--                       row's id (whatever the next auto-increment value is)
--                       does not match its intended *display* position.
--   execution_mode  -- 'onchain' | 'internal'. Moves the on-chain-vs-internal
--                       decision from GasExecution_model's hardcoded id-range
--                       constants onto the option row itself — the single
--                       place that should own it, and the only way a new
--                       option like this one can be added later without
--                       another code change.
--
-- Idempotent: the whole block is gated on execution_mode being absent, so it
-- can only ever run (and insert the new row) once.
-- ============================================================================

DROP PROCEDURE IF EXISTS _cdo_add_option2_internal;
DELIMITER //
CREATE PROCEDURE _cdo_add_option2_internal()
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'coin_distribution_options'
        AND COLUMN_NAME = 'execution_mode') = 0 THEN

    ALTER TABLE `coin_distribution_options`
      ADD COLUMN `sort_order`     SMALLINT NOT NULL DEFAULT 0 AFTER `bonus_percentage`,
      ADD COLUMN `execution_mode` ENUM('onchain','internal') NOT NULL DEFAULT 'internal' AFTER `sort_order`;

    -- Space the 7 existing rows 10 apart, gap at 20 reserved for the new
    -- option so it sorts second without touching anyone else's id.
    UPDATE `coin_distribution_options` SET `sort_order` = 10, `execution_mode` = 'onchain'  WHERE `id` = 1;
    UPDATE `coin_distribution_options` SET `sort_order` = 30, `execution_mode` = 'internal' WHERE `id` = 2;
    UPDATE `coin_distribution_options` SET `sort_order` = 40, `execution_mode` = 'internal' WHERE `id` = 3;
    UPDATE `coin_distribution_options` SET `sort_order` = 50, `execution_mode` = 'internal' WHERE `id` = 4;
    UPDATE `coin_distribution_options` SET `sort_order` = 60, `execution_mode` = 'internal' WHERE `id` = 5;
    UPDATE `coin_distribution_options` SET `sort_order` = 70, `execution_mode` = 'internal' WHERE `id` = 6;
    UPDATE `coin_distribution_options` SET `sort_order` = 80, `execution_mode` = 'internal' WHERE `id` = 7;

    -- Relabel display names 2->8 down to 3->8, highest id first so each
    -- target name is vacated before the next row claims it (option_name is
    -- UNIQUE) — no temp-name juggling needed.
    UPDATE `coin_distribution_options` SET `option_name` = 'Option 8' WHERE `id` = 7;
    UPDATE `coin_distribution_options` SET `option_name` = 'Option 7' WHERE `id` = 6;
    UPDATE `coin_distribution_options` SET `option_name` = 'Option 6' WHERE `id` = 5;
    UPDATE `coin_distribution_options` SET `option_name` = 'Option 5' WHERE `id` = 4;
    UPDATE `coin_distribution_options` SET `option_name` = 'Option 4' WHERE `id` = 3;
    UPDATE `coin_distribution_options` SET `option_name` = 'Option 3' WHERE `id` = 2;

    -- The new option itself.
    INSERT INTO `coin_distribution_options`
      (`option_name`, `description`, `exchange_percentage`, `earning_percentage`,
       `staking_percentage`, `bonus_percentage`, `sort_order`, `execution_mode`,
       `is_default`, `status`, `created_by`)
    VALUES
      ('Option 2', '100% Exchange Coin Balance (Internal re-stake — no blockchain, no USDT)',
       100.00, 0.00, 0.00, 0.00, 20, 'internal', 0, 1, NULL);

    -- Audit trail, consistent with Coindistribution_model's admin-driven
    -- writes (changed_by=0 marks a system/migration change, not an admin).
    INSERT INTO `coin_distribution_audit` (`option_id`, `action`, `old_value`, `new_value`, `changed_by`)
    SELECT `id`, 'create',
           NULL,
           JSON_OBJECT('option_name', `option_name`, 'exchange_percentage', `exchange_percentage`,
                       'earning_percentage', `earning_percentage`, 'staking_percentage', `staking_percentage`,
                       'bonus_percentage', `bonus_percentage`, 'execution_mode', `execution_mode`,
                       'note', 'Added by db/coin_distribution_option2_internal.sql'),
           0
      FROM `coin_distribution_options` WHERE `option_name` = 'Option 2' AND `exchange_percentage` = 100.00 AND `sort_order` = 20;

  END IF;
END//
DELIMITER ;
CALL _cdo_add_option2_internal();
DROP PROCEDURE IF EXISTS _cdo_add_option2_internal;
