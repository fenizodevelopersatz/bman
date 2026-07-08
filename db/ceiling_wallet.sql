-- ============================================================================
-- Ceiling Wallet — system-only (backend) ledger for capped binary/group income.
-- Idempotent: safe to re-run.
--
-- The Ceiling Wallet is NOT a user wallet: it lives in its own tables (never in
-- user_wallets / wallet_ledger) and is never shown to, transferred by, or
-- withdrawn by members. When a user's binary matching bonus would exceed the
-- earning ceiling defined by their active staking package(s) (staking_packages.
-- group_ceiling — unchanged), the eligible part is paid to the user and the
-- EXCESS is diverted here as a CEILING_HOLD. Admins can later CEILING_RELEASE or
-- CEILING_ADJUSTMENT held amounts. Every movement is ledger-based & audited.
--
-- Companion to db/staking_matching.sql. Does NOT touch existing wallet tables.
-- ============================================================================

-- 1) Per-user rolled-up ceiling balances (fast admin reads; ledger is truth).
CREATE TABLE IF NOT EXISTS `ceiling_wallet` (
  `user_id`        INT NOT NULL,
  `held_balance`   DECIMAL(20,4) NOT NULL DEFAULT 0 COMMENT 'currently held (not yet released)',
  `total_held`     DECIMAL(20,4) NOT NULL DEFAULT 0 COMMENT 'lifetime moved into ceiling',
  `total_released` DECIMAL(20,4) NOT NULL DEFAULT 0 COMMENT 'lifetime released back out',
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='System Ceiling Wallet — per-user held balance (backend only)';

-- 2) Append-only ceiling ledger — every HOLD / RELEASE / ADJUSTMENT.
CREATE TABLE IF NOT EXISTS `ceiling_wallet_ledger` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT NOT NULL,
  `tx_type`        ENUM('CEILING_HOLD','CEILING_RELEASE','CEILING_ADJUSTMENT') NOT NULL,
  -- signed amount: HOLD is positive (into ceiling), RELEASE negative (out of
  -- ceiling), ADJUSTMENT either way. `held_after` is the per-user running balance.
  `amount`         DECIMAL(20,4) NOT NULL,
  `held_after`     DECIMAL(20,4) NOT NULL DEFAULT 0,
  -- where the excess would otherwise have gone (earning / staking) — audit only.
  `source_wallet`  VARCHAR(16) NULL DEFAULT NULL,
  `matched_volume` DECIMAL(20,4) NULL DEFAULT NULL COMMENT 'matching context, if from matching',
  `reference_type` VARCHAR(40) NULL DEFAULT NULL COMMENT 'binary_matching / admin_release / admin_adjust',
  `reference_id`   VARCHAR(64) NULL DEFAULT NULL COMMENT 'run_ref / matching payout id / note',
  `description`    VARCHAR(255) NULL DEFAULT NULL,
  `created_by`     INT NULL DEFAULT NULL COMMENT 'admin id for release/adjustment',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`    (`user_id`),
  KEY `idx_type`    (`tx_type`),
  KEY `idx_ref`     (`reference_type`,`reference_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='System Ceiling Wallet ledger (HOLD/RELEASE/ADJUSTMENT) — backend only';
