-- ============================================================================
-- On-Chain Transaction Events — append-only immutable audit trail.
-- One row per lifecycle event of an onchain_transactions record: created,
-- status_change, confirmation_update, credited, failed, reverted, linked…
-- The onchain_transactions row holds CURRENT state; this table is the history
-- (never updated/deleted → immutable audit).
-- Idempotent: safe to re-run.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `onchain_tx_events` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tx_id`       BIGINT UNSIGNED NOT NULL,               -- → onchain_transactions.id
  `tx_hash`     VARCHAR(120) DEFAULT NULL,
  `event_type`  VARCHAR(40)  NOT NULL,                  -- created|status_change|confirmation|credited|failed|reverted|linked
  `old_status`  VARCHAR(20)  DEFAULT NULL,
  `new_status`  VARCHAR(20)  DEFAULT NULL,
  `confirmations` INT        DEFAULT NULL,
  `detail`      TEXT         DEFAULT NULL,              -- free JSON/text
  `actor_type`  VARCHAR(20)  NOT NULL DEFAULT 'system', -- system|admin|cron|user
  `actor_id`    INT          DEFAULT NULL,
  `ip_address`  VARCHAR(64)  DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tx`     (`tx_id`),
  KEY `idx_hash`   (`tx_hash`),
  KEY `idx_type`   (`event_type`),
  KEY `idx_created`(`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
