-- ============================================================================
-- Wallet Transfer — on-chain settlement
-- ----------------------------------------------------------------------------
-- Today `wallet_internal_transfer` is a pure internal ledger move (Wallet
-- Ledger debit + credit, no blockchain involved) even though its own
-- detailEnriched() view already anticipates a `blockchain` block keyed on
-- tx_hash/block_number/confirmations/gas_used/gas_fee/network — those fields
-- did not exist yet. This migration adds them, plus a settlement lifecycle:
--
--   pending -> processing -> completed | failed | skipped
--
-- and a single-row settings table (same shape as staking_rank_power_settings)
-- controlling whether settlement runs at all, in dry-run or live, and for
-- which transfer types.
--
-- WHY EVERY TRANSFER, INCLUDING SELF-MOVES, GETS A SETTLEMENT ROW
-- A member has exactly ONE on-chain address (user_wallet.wallet_address).
-- Earning/Staking/Bonus balances were credited purely as ledger rows and were
-- never delivered on-chain — moving them between a member's own 4 internal
-- buckets is the first "real" event for that value, so it settles by sending
-- real BMAN from Treasury to the member's own address. Member-to-member
-- transfers settle to the RECIPIENT's address. Either way the source of every
-- settlement is the Treasury wallet (single custodial signer — see
-- Web3bman.php; individual members do not sign their own on-chain transfers).
--
-- SAFETY DEFAULTS (do not weaken without an explicit admin decision):
--   enabled  = 0  — settlement is OFF. Transfers behave exactly as today.
--   dry_run  = 1  — even once enabled, no real broadcast until turned off.
-- Matches the existing swap_enabled/swap_dry_run convention (token_settings),
-- kept as a SEPARATE toggle so going live on staking purchases can never
-- accidentally flip wallet-transfer settlement live too.
--
-- Idempotent: safe to re-run. Additive only — drops nothing.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. wallet_internal_transfer — settlement lifecycle columns
-- ----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _wits_add_col;
DELIMITER //
CREATE PROCEDURE _wits_add_col(IN col VARCHAR(64), IN ddl VARCHAR(255))
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wallet_internal_transfer'
        AND COLUMN_NAME = col) = 0 THEN
    SET @s := CONCAT('ALTER TABLE `wallet_internal_transfer` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END//
DELIMITER ;

CALL _wits_add_col('settlement_status',
  "`settlement_status` ENUM('pending','processing','completed','failed','skipped') NOT NULL DEFAULT 'pending' COMMENT 'on-chain settlement lifecycle — independent of `status` (the internal ledger completion)'");
CALL _wits_add_col('settlement_address',
  "`settlement_address` VARCHAR(100) NULL DEFAULT NULL COMMENT 'resolved 0x destination — self: own wallet_address, member: recipient wallet_address'");
CALL _wits_add_col('tx_hash',       "`tx_hash` VARCHAR(120) NULL DEFAULT NULL");
CALL _wits_add_col('block_number',  "`block_number` BIGINT UNSIGNED NULL DEFAULT NULL");
CALL _wits_add_col('confirmations', "`confirmations` INT NULL DEFAULT NULL");
CALL _wits_add_col('gas_used',      "`gas_used` DECIMAL(30,8) NULL DEFAULT NULL");
CALL _wits_add_col('gas_fee',       "`gas_fee` DECIMAL(30,8) NULL DEFAULT NULL COMMENT 'BNB spent broadcasting this settlement'");
CALL _wits_add_col('network',       "`network` VARCHAR(40) NULL DEFAULT NULL");
CALL _wits_add_col('settlement_attempts', "`settlement_attempts` INT NOT NULL DEFAULT 0");
CALL _wits_add_col('settlement_error',    "`settlement_error` VARCHAR(255) NULL DEFAULT NULL");
CALL _wits_add_col('settled_at',    "`settled_at` DATETIME NULL DEFAULT NULL");
-- Links back to the CREDIT wallet_ledger row (already carries an
-- auto-captured onchain_transactions row from Walletledger_model — the
-- settlement cron attaches the real tx_hash there via updateByLedgerId(),
-- rather than inserting a second, confusing on-chain row).
CALL _wits_add_col('credit_onchain_ledger_id',
  "`credit_onchain_ledger_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'alias of credit_ledger_id, named for clarity at the settlement call site'");

DROP PROCEDURE IF EXISTS _wits_add_col;

-- No backfill needed: the new column's DEFAULT is 'pending', so every
-- pre-existing transfer already lands in the settlement queue for free. That
-- is deliberate — the first cron run settles the platform's transfer BACKLOG
-- too, consistent with "every transfer gets settled" being the point of this
-- feature. If a backlog sweep is not wanted, bulk-mark old rows 'skipped'
-- before enabling settlement:
--   UPDATE wallet_internal_transfer SET settlement_status='skipped'
--    WHERE settlement_status='pending' AND created_at < '<cutover date>';

-- Supporting index for the cron's queue scan.
SET @has_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wallet_internal_transfer' AND INDEX_NAME = 'idx_settlement_status');
SET @s := IF(@has_idx = 0,
  'ALTER TABLE `wallet_internal_transfer` ADD INDEX `idx_settlement_status` (`settlement_status`,`id`)',
  'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ----------------------------------------------------------------------------
-- 2. Settlement settings — single row, mirrors staking_rank_power_settings.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_transfer_settlement_settings` (
  `id` TINYINT UNSIGNED NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'master switch — 0 = behave exactly as before this migration',
  `dry_run` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = record a DRYRUN- hash, never broadcast (mirrors token_settings.swap_dry_run, kept separate on purpose)',
  `settle_self_transfers` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'a member moving between their OWN 4 wallets',
  `settle_member_transfers` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'user A -> user B',
  `min_treasury_reserve` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'cron refuses to settle if it would drop the Treasury on-chain BMAN balance below this',
  `max_batch_size` INT NOT NULL DEFAULT 20 COMMENT 'settlements processed per cron invocation',
  `updated_by` INT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `wallet_transfer_settlement_settings`
  (`id`, `enabled`, `dry_run`, `settle_self_transfers`, `settle_member_transfers`, `min_treasury_reserve`, `max_batch_size`)
VALUES (1, 0, 1, 1, 1, 0, 20);

-- ----------------------------------------------------------------------------
-- 3. Run-lock + cursor, matching rank_cron_state's exact shape (reserved word
--    lesson learned there: `cursor_pos`, never `cursor`).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_settlement_cron_state` (
  `job`            VARCHAR(40) NOT NULL,
  `running`        TINYINT(1) NOT NULL DEFAULT 0,
  `heartbeat`      DATETIME NULL DEFAULT NULL,
  `last_run_at`    DATETIME NULL DEFAULT NULL,
  `last_result`    VARCHAR(255) NULL DEFAULT NULL,
  `total_settled`  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`job`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `wallet_settlement_cron_state` (`job`) VALUES ('wallet_transfer_settlement');
