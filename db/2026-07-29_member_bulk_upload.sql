-- ============================================================================
-- Member Bulk Upload (Admin ▸ Members Management ▸ Bulk Upload)
-- ----------------------------------------------------------------------------
-- Imports many members at once from an Excel/CSV sheet. One sheet row becomes:
--   username + email + password  -> `users` (password stored HASHED, never plain)
--   reference_id (+ optional leg)-> sponsor lookup + `binary_placement` row
--   (dynamic)                    -> a fresh on-chain address in `user_wallet`
--   bman                         -> an on-chain BMAN send, QUEUED for the cron
--
-- Two-phase by design: STAGE (parse + validate the whole file, nothing written
-- to `users`) then IMPORT (create the accounts). The admin sees exactly what
-- will happen before a single account exists.
--
-- The BMAN column is NOT sent during import. Import is a synchronous web
-- request; broadcasting N on-chain transfers inside one is how you get a
-- half-finished batch on a timeout. Instead each row lands in
-- `member_bulk_upload_rows` with bman_status='pending' and the
-- member-bulk-bman-cron sweeps that queue from the Treasury wallet — the same
-- shape as wallet_internal_transfer + WalletTransferSettlementCron.
--
-- SAFETY DEFAULTS (do not weaken without an explicit admin decision):
--   enabled = 0  — the BMAN cron refuses to send at all; the queue just grows.
--   dry_run = 1  — even once enabled, a synthetic DRYRUN- hash is recorded and
--                  nothing is broadcast until an admin turns BOTH switches off.
-- Member creation itself is never gated by these — only the money movement is.
--
-- A failed BMAN send is TERMINAL (not auto-retried by the next sweep) for the
-- same reason as the settlement cron: a money-moving queue must surface its
-- failures rather than silently hammer a broken RPC. An admin re-queues by
-- hand from the batch detail page after investigating.
--
-- Idempotent: safe to re-run. Additive only — drops nothing.
-- NOTE: plain columns only (no STORED/generated columns) so a DB re-import
-- from the master dump cannot silently drop them.
-- ============================================================================

-- One row per uploaded file.
CREATE TABLE IF NOT EXISTS `member_bulk_upload_batches` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref`             VARCHAR(40) NOT NULL COMMENT 'MBU-YYYYMMDD-XXXXXXXX, shown to the admin',
  `admin_id`        INT NOT NULL,
  `original_name`   VARCHAR(255) NOT NULL COMMENT 'file name as uploaded; the file itself is never stored',
  `status`          ENUM('staged','importing','completed','failed','cancelled') NOT NULL DEFAULT 'staged',
  `total_rows`      INT UNSIGNED NOT NULL DEFAULT 0,
  `valid_rows`      INT UNSIGNED NOT NULL DEFAULT 0,
  `invalid_rows`    INT UNSIGNED NOT NULL DEFAULT 0,
  `imported_rows`   INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_rows`     INT UNSIGNED NOT NULL DEFAULT 0,
  `bman_queued`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'rows queued for the on-chain BMAN cron',
  `bman_total`      DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'sum of the queued BMAN amounts',
  `default_leg`     ENUM('left','right','auto') NOT NULL DEFAULT 'auto' COMMENT 'used when a row has no leg of its own',
  `send_bman`       TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = import the members but ignore the bman column entirely',
  `error_message`   VARCHAR(255) NULL DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `imported_at`     DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref` (`ref`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per sheet row. Survives the import as the batch's audit trail AND
-- doubles as the pending queue the BMAN cron drains (bman_status='pending').
--
-- `password_hash` holds the bcrypt hash ONLY. The sheet's plaintext password is
-- hashed the moment the file is parsed and is never written to disk, to the
-- session, or to this table — the uploaded file is read straight from PHP's
-- temp path and discarded when the request ends. Hashing at stage time (rather
-- than at import) is also what makes a staged batch importable later, from any
-- browser, without the plaintext still being around.
CREATE TABLE IF NOT EXISTS `member_bulk_upload_rows` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id`       BIGINT UNSIGNED NOT NULL,
  `row_number`     INT UNSIGNED NOT NULL COMMENT '1-based row number in the sheet, header excluded',
  `username`       VARCHAR(255) NULL DEFAULT NULL,
  `email`          VARCHAR(255) NULL DEFAULT NULL,
  `password_hash`  VARCHAR(255) NULL DEFAULT NULL COMMENT 'bcrypt hash of the sheet/default password — never plaintext',
  `reference_id`   VARCHAR(250) NULL DEFAULT NULL COMMENT 'sponsor referral code exactly as typed in the sheet',
  `sponsor_id`     INT NULL DEFAULT NULL COMMENT 'resolved users.id of that referral code',
  `leg`            ENUM('left','right','auto') NOT NULL DEFAULT 'auto',
  `bman_amount`    DECIMAL(30,8) NOT NULL DEFAULT 0,
  `status`         ENUM('valid','invalid','imported','failed','skipped') NOT NULL DEFAULT 'valid',
  `error_message`  VARCHAR(255) NULL DEFAULT NULL COMMENT 'why this row is invalid / failed to import',
  `user_id`        INT NULL DEFAULT NULL COMMENT 'users.id once created',
  `referral_id`    VARCHAR(250) NULL DEFAULT NULL COMMENT 'the NEW member own referral code',
  `wallet_address` VARCHAR(250) NULL DEFAULT NULL COMMENT 'the address generated for this member',
  `bman_status`    ENUM('none','pending','processing','completed','failed') NOT NULL DEFAULT 'none',
  `bman_attempts`  INT UNSIGNED NOT NULL DEFAULT 0,
  `bman_tx_hash`   VARCHAR(120) NULL DEFAULT NULL,
  `bman_network`   VARCHAR(40) NULL DEFAULT NULL,
  `bman_error`     VARCHAR(255) NULL DEFAULT NULL,
  `bman_sent_at`   DATETIME NULL DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_status` (`status`),
  -- the cron's claim query: WHERE bman_status='pending' ORDER BY id
  KEY `idx_bman_status` (`bman_status`, `id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Its own toggle row, not a reuse of treasury_direct_send_settings or
-- wallet_transfer_settlement_settings: flipping one on-chain feature live must
-- never accidentally flip another.
CREATE TABLE IF NOT EXISTS `member_bulk_upload_settings` (
  `id` TINYINT UNSIGNED NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'master switch — 0 = the BMAN cron refuses to send',
  `dry_run` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = record a DRYRUN- hash, never broadcast',
  `min_treasury_reserve` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'refuses to send if it would drop the Treasury on-chain BMAN balance below this',
  `max_batch_size` INT UNSIGNED NOT NULL DEFAULT 20 COMMENT 'rows claimed per cron pass',
  `max_rows_per_file` INT UNSIGNED NOT NULL DEFAULT 1000 COMMENT 'guard against a runaway sheet',
  `updated_by` INT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `member_bulk_upload_settings`
  (`id`, `enabled`, `dry_run`, `min_treasury_reserve`, `max_batch_size`, `max_rows_per_file`)
VALUES (1, 0, 1, 0, 20, 1000);

-- The run-lock lives in the shared cron-state table (it is keyed by `job`,
-- which is exactly why it was built that way). Created here too so this
-- migration is standalone if the settlement migration has not been run.
CREATE TABLE IF NOT EXISTS `wallet_settlement_cron_state` (
  `job`            VARCHAR(40) NOT NULL,
  `running`        TINYINT(1) NOT NULL DEFAULT 0,
  `heartbeat`      DATETIME NULL DEFAULT NULL,
  `last_run_at`    DATETIME NULL DEFAULT NULL,
  `last_result`    VARCHAR(255) NULL DEFAULT NULL,
  `total_settled`  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`job`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `wallet_settlement_cron_state` (`job`) VALUES ('member_bulk_bman');
