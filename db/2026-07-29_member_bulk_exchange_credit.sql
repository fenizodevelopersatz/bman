-- ============================================================================
-- Member Bulk Upload — credit the delivered BMAN to the member's EXCHANGE wallet
-- ----------------------------------------------------------------------------
-- Follow-up to db/2026-07-29_member_bulk_upload.sql.
--
-- The first cut sent BMAN from the Treasury (hot) wallet to the new member's
-- on-chain address and stopped there — the member's real chain balance moved
-- but their dashboard Exchange balance did not, so a freshly imported member
-- saw 0 BMAN in the panel. This migration adds the bookkeeping the cron needs
-- to ALSO post that delivery to the internal ledger, exactly the way
-- Depositlistener_model credits a detected on-chain deposit:
--
--     Walletledger_model::credit($user, 'exchange', $amount, 'admin_adjustment',
--                                ['tx_hash' => <the real send hash>, ...])
--
-- That single call locks the balance row, appends a `wallet_ledger` entry with
-- balance_after, updates `user_wallets`.`exchange_balance`, applies the normal
-- maturity rules for an exchange credit, and mirrors the movement into
-- `onchain_transactions`. The UNIQUE (tx_hash, wallet_type) index on
-- wallet_ledger makes a double-credit of the same send impossible, which is
-- what lets the cron safely retry a credit it did not manage to finish.
--
-- Two columns record the ledger side of each row so the cron can tell a row it
-- has already credited from one it still owes:
--   bman_ledger_id   -> wallet_ledger.id of the exchange credit
--   bman_credited_at -> when that credit was posted
--
-- A row that is bman_status='completed' with a REAL tx hash but a NULL
-- bman_ledger_id is a send that landed on-chain while the credit did not (a
-- crash between the two steps). The cron's second phase backfills exactly
-- those rows on its next pass — no admin action, no risk of double-credit.
--
-- Dry-run rows are deliberately NOT credited: fabricating a spendable Exchange
-- balance with no BMAN behind it is precisely what dry-run exists to avoid.
--
-- Idempotent: safe to re-run. Additive only — drops nothing.
-- Plain columns only (no STORED/generated columns), so a DB re-import from the
-- master dump cannot silently drop them.
-- ============================================================================

-- --- guarded column adds -----------------------------------------------------
-- No stored procedure and no DELIMITER here on purpose: DELIMITER is a directive
-- of the `mysql` command-line client, not a server statement, so a migration
-- that uses it cannot be applied programmatically (multi_query chokes on it).
-- The information_schema + PREPARE pattern below — the same one
-- db/custodial_wallets.sql uses — runs identically from the CLI, phpMyAdmin,
-- and application code.

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'member_bulk_upload_rows'
    AND COLUMN_NAME = 'bman_ledger_id');
SET @sql := IF(@has = 0,
  'ALTER TABLE `member_bulk_upload_rows` ADD COLUMN `bman_ledger_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT ''wallet_ledger.id of the Exchange credit''',
  'SELECT "bman_ledger_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'member_bulk_upload_rows'
    AND COLUMN_NAME = 'bman_credited_at');
SET @sql := IF(@has = 0,
  'ALTER TABLE `member_bulk_upload_rows` ADD COLUMN `bman_credited_at` DATETIME NULL DEFAULT NULL COMMENT ''when the Exchange credit was posted''',
  'SELECT "bman_credited_at already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Master switch for the internal credit. Off = deliver on-chain only, leaving
-- dashboard balances untouched (the original behaviour), for an operator who
-- reconciles the Exchange wallet by some other route.
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'member_bulk_upload_settings'
    AND COLUMN_NAME = 'credit_exchange_wallet');
SET @sql := IF(@has = 0,
  'ALTER TABLE `member_bulk_upload_settings` ADD COLUMN `credit_exchange_wallet` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''1 = also post the delivered BMAN to the member Exchange wallet''',
  'SELECT "credit_exchange_wallet already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --- index for the credit-backfill sweep -------------------------------------
-- The cron's phase 2 scans: bman_status='completed' AND bman_ledger_id IS NULL.
SET @has_ix := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'member_bulk_upload_rows'
    AND INDEX_NAME = 'idx_bman_credit_backfill'
);
SET @sql := IF(@has_ix = 0,
  'ALTER TABLE `member_bulk_upload_rows` ADD KEY `idx_bman_credit_backfill` (`bman_status`, `bman_ledger_id`)',
  'SELECT "idx_bman_credit_backfill already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
