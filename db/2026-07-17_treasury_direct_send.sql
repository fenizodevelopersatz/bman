-- ============================================================================
-- Treasury Direct Send
-- ----------------------------------------------------------------------------
-- A SEPARATE feature from wallet_internal_transfer settlement. That feature
-- moves EXISTING internal-ledger balance and settles it on-chain. This one is
-- for NEW money — an admin manually sending BMAN straight from the Treasury
-- wallet to a chosen member's on-chain address (reward / airdrop / one-off
-- adjustment). Nothing is debited from any internal ledger wallet anywhere,
-- and the recipient's platform dashboard balances are intentionally
-- untouched — only their actual on-chain BMAN balance changes. Its own audit
-- trail (treasury_direct_send), separate from wallet_internal_transfer so the
-- two very different kinds of movement are never confused in reporting.
--
-- SAFETY DEFAULTS (do not weaken without an explicit admin decision):
--   enabled = 0  — the tool refuses to send at all.
--   dry_run = 1  — even once enabled, no real broadcast until turned off.
-- Kept as its own settings row (not reusing wallet_transfer_settlement_settings
-- or token_settings.swap_*) for the same reason every other on-chain feature
-- in this codebase gets its own toggle: flipping one feature live must never
-- accidentally flip another.
--
-- Idempotent: safe to re-run. Additive only — drops nothing.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `treasury_direct_send` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref`           VARCHAR(40) NOT NULL,
  `admin_id`      INT NOT NULL,
  `user_id`       INT NOT NULL,
  `to_address`    VARCHAR(100) NOT NULL,
  `amount`        DECIMAL(30,8) NOT NULL,
  `reason`        VARCHAR(255) NULL DEFAULT NULL,
  `status`        ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `dry_run`       TINYINT(1) NOT NULL DEFAULT 1,
  `tx_hash`       VARCHAR(120) NULL DEFAULT NULL,
  `network`       VARCHAR(40) NULL DEFAULT NULL,
  `error_message` VARCHAR(255) NULL DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at`  DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref` (`ref`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `treasury_direct_send_settings` (
  `id` TINYINT UNSIGNED NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'master switch — 0 = the tool refuses to send',
  `dry_run` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = record a DRYRUN- hash, never broadcast',
  `min_treasury_reserve` DECIMAL(30,8) NOT NULL DEFAULT 0 COMMENT 'refuses to send if it would drop the Treasury on-chain BMAN balance below this',
  `updated_by` INT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `treasury_direct_send_settings` (`id`, `enabled`, `dry_run`, `min_treasury_reserve`)
VALUES (1, 0, 1, 0);
