-- ============================================================================
-- Member Bulk Upload — Wallet Type Selection
-- 2026-07-30
-- ----------------------------------------------------------------------------
-- Adds a `wallet_type` column to three tables so the admin can choose which
-- internal wallet receives the BMAN amount from the Excel sheet:
--   exchange (default, backward-compatible) | earning | staking | bonus
--
-- member_bulk_upload_settings.wallet_type  — the site-wide default
-- member_bulk_upload_batches.wallet_type   — the default chosen at upload time
-- member_bulk_upload_rows.wallet_type      — the per-row effective wallet
--
-- Idempotent: every ALTER is guarded by a column-existence check.
-- ============================================================================

-- 1. Settings table — site-wide default wallet type
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `member_bulk_upload_settings`
       ADD COLUMN `wallet_type`
         ENUM(''exchange'',''earning'',''staking'',''bonus'')
         NOT NULL DEFAULT ''exchange''
         COMMENT ''Default wallet that receives the BMAN credit for all bulk-upload rows''
         AFTER `credit_exchange_wallet`',
    'SELECT 1 -- wallet_type already exists in member_bulk_upload_settings'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'member_bulk_upload_settings'
    AND COLUMN_NAME  = 'wallet_type'
);
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- Update existing settings row to the default so the panel shows a sane value
UPDATE `member_bulk_upload_settings`
   SET `wallet_type` = 'exchange'
 WHERE `id` = 1
   AND (`wallet_type` IS NULL OR `wallet_type` = '');

-- 2. Batches table — wallet type chosen at the time of upload
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `member_bulk_upload_batches`
       ADD COLUMN `wallet_type`
         ENUM(''exchange'',''earning'',''staking'',''bonus'')
         NOT NULL DEFAULT ''exchange''
         COMMENT ''Wallet type default at the time this batch was uploaded''
         AFTER `send_bman`',
    'SELECT 1 -- wallet_type already exists in member_bulk_upload_batches'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'member_bulk_upload_batches'
    AND COLUMN_NAME  = 'wallet_type'
);
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;

-- 3. Rows table — per-row effective wallet type
SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `member_bulk_upload_rows`
       ADD COLUMN `wallet_type`
         ENUM(''exchange'',''earning'',''staking'',''bonus'')
         NOT NULL DEFAULT ''exchange''
         COMMENT ''Effective wallet for this row (from sheet column, or batch default)''
         AFTER `bman_amount`',
    'SELECT 1 -- wallet_type already exists in member_bulk_upload_rows'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'member_bulk_upload_rows'
    AND COLUMN_NAME  = 'wallet_type'
);
PREPARE _stmt FROM @sql; EXECUTE _stmt; DEALLOCATE PREPARE _stmt;
